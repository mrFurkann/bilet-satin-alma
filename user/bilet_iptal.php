<?php
require '../includes/config.php';
require '../includes/auth.php';

$current_user = getUser();
if (!$current_user) {
    header("Location: ../login.php");
    exit;
}

$user_id = $current_user['id'];
$ticket_id = $_POST['ticket_id'] ?? null;

if (!$ticket_id) {
    header("Location: biletler.php?msg=" . urlencode("Hatalı bilet ID.") . "&type=danger");
    exit;
}

try {
    $db->beginTransaction();

    // Bilet aktif mi ve bilgileri al
    $stmt = $db->prepare("SELECT total_price, status, departure_time FROM Tickets t JOIN Trips tr ON t.trip_id = tr.id WHERE t.id = ? AND t.user_id = ?");
    $stmt->execute([$ticket_id, $user_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket || $ticket['status'] !== 'Active') {
        throw new Exception("Bu bilet iptal edilemez.");
    }

  
    $departure_time = new DateTime($ticket['departure_time'], new DateTimeZone('Europe/Istanbul'));
    $current_time   = new DateTime('now', new DateTimeZone('Europe/Istanbul'));
    $interval       = $departure_time->getTimestamp() - $current_time->getTimestamp();
    $cancellation_deadline = 3600; // 1 saat öncesi

    if ($interval < $cancellation_deadline) {
        throw new Exception("Kalkışa son 1 saat kala bilet iptali yapılamaz.");
    }

    //Bileti iptal et
    $stmt = $db->prepare("UPDATE Tickets SET status = 'Cancelled' WHERE id = ?");
    $stmt->execute([$ticket_id]);

    //Kullanıcıya para iadesi yap
    $stmt = $db->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$ticket['total_price'], $user_id]);

    // Oturum bakiyesini güncelle
    $_SESSION['user']['balance'] += $ticket['total_price'];

    $db->commit();

    header("Location: biletler.php?msg=" . urlencode("Bilet başarıyla iptal edildi ve ücret iade edildi.") . "&type=success");
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    header("Location: biletler.php?msg=" . urlencode("Hata: " . $e->getMessage()) . "&type=danger");
    exit;
}
?>
