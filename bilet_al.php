<?php
ob_start();
require 'includes/config.php';
require 'includes/auth.php';

$current_user = getUser();
if (!$current_user) header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));

$user_id = $current_user['id'];
$trip_id = $_POST['trip_id'] ?? null;
$selected_seats = $_POST['seats'] ?? [];
$coupon_id = $_POST['coupon_id'] ?? null;

if (!$trip_id || empty($selected_seats)) {
    $msg = urlencode("Sefer veya koltuk seçimi eksik!");
    header("Location: index.php?msg={$msg}&type=danger");
    exit;
}

$db->exec("PRAGMA foreign_keys = ON;");
$db->beginTransaction();

try {
    $stmt_trip = $db->prepare("SELECT id, price, company_id FROM Trips WHERE id = ?");
    $stmt_trip->execute([$trip_id]);
    $trip = $stmt_trip->fetch(PDO::FETCH_ASSOC);
    if (!$trip) throw new Exception("Sefer bilgisi bulunamadı.");

    $unit_price = $trip['price'];
    $seat_count = count($selected_seats);
    $total_price = $unit_price * $seat_count;

    // Koltuk doluluk kontrolü
    $placeholders = implode(',', array_fill(0, $seat_count, '?'));
    $stmt_check_seats = $db->prepare("
        SELECT bs.seat_number
        FROM Booked_Seats bs
        JOIN Tickets t ON bs.ticket_id = t.id
        WHERE t.trip_id = ? AND t.status = 'Active' AND bs.seat_number IN ($placeholders)
    ");
    $stmt_check_seats->execute(array_merge([$trip_id], $selected_seats));
    $already_booked = $stmt_check_seats->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($already_booked)) throw new Exception("Bazı koltuklar dolu: " . implode(', ', $already_booked));

    // Kupon uygulama
    $applied_coupon_id = null;
    if ($coupon_id) {
        $stmt_coupon = $db->prepare("
            SELECT * FROM Coupons 
            WHERE id = ? 
              AND expire_date >= datetime('now', 'localtime') 
              AND usage_limit > 0 
              AND (company_id IS NULL OR company_id = ?)
        ");
        $stmt_coupon->execute([$coupon_id, $trip['company_id']]);
        $coupon = $stmt_coupon->fetch(PDO::FETCH_ASSOC);

        if ($coupon) {
            $stmt_check = $db->prepare("SELECT 1 FROM User_Coupons WHERE user_id = ? AND coupon_id = ?");
            $stmt_check->execute([$user_id, $coupon['id']]);
            if ($stmt_check->fetchColumn()) throw new Exception("Bu kuponu daha önce kullandınız.");

            $total_price = max(0, $total_price * (1 - $coupon['discount']/100));
            $applied_coupon_id = $coupon['id'];

            $stmt_update = $db->prepare("UPDATE Coupons SET usage_limit = usage_limit - 1 WHERE id = ?");
            $stmt_update->execute([$coupon['id']]);
        } else {
            throw new Exception("Geçersiz veya bu firmaya ait olmayan kupon.");
        }
    }

    // Bakiye düş
    $stmt_balance = $db->prepare("SELECT balance FROM User WHERE id = ?");
    $stmt_balance->execute([$user_id]);
    $current_balance = $stmt_balance->fetchColumn();
    if ($current_balance < $total_price) throw new Exception("Yetersiz bakiye. Toplam fiyat: " . number_format($total_price,2)." ₺");

    $stmt_deduct = $db->prepare("UPDATE User SET balance = balance - ? WHERE id = ?");
    $stmt_deduct->execute([$total_price, $user_id]);
    $new_balance = $current_balance - $total_price;
    if (isset($_SESSION['user'])) $_SESSION['user']['balance'] = $new_balance;

    // Bilet ve koltuk kaydı
    $ticket_id = generate_uuid_v4();
    $stmt_ticket = $db->prepare("INSERT INTO Tickets (id, trip_id, user_id, status, total_price) VALUES (?, ?, ?, 'Active', ?)");
    $stmt_ticket->execute([$ticket_id, $trip_id, $user_id, $total_price]);

    $stmt_seat = $db->prepare("INSERT INTO Booked_Seats (id, ticket_id, seat_number) VALUES (?, ?, ?)");
    foreach ($selected_seats as $s) $stmt_seat->execute([generate_uuid_v4(), $ticket_id, (int)$s]);

    if ($applied_coupon_id) {
        $stmt_uc = $db->prepare("INSERT INTO User_Coupons (id, coupon_id, user_id) VALUES (?, ?, ?)");
        $stmt_uc->execute([generate_uuid_v4(), $applied_coupon_id, $user_id]);
    }

    $db->commit();

    $msg = urlencode("Biletler başarıyla alındı! Koltuklar: ".implode(', ',$selected_seats).". Yeni bakiye: ".number_format($new_balance,2)." ₺");
    header("Location: user/biletler.php?msg={$msg}&type=success");
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    $msg = urlencode("Bilet satın alma başarısız: " . $e->getMessage());
    header("Location: index.php?trip_detail={$trip_id}&msg={$msg}&type=danger");
    exit;
}
?>
