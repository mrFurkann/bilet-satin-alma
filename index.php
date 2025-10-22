<?php
require 'includes/config.php';
require 'includes/auth.php';

$message = '';
$message_type = '';
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = htmlspecialchars(urldecode($_GET['msg']));
    $message_type = htmlspecialchars($_GET['type']);
}

$current_user = getUser();
$is_logged_in = (bool)$current_user;
$trips = [];
$searchPerformed = false;

$tripForBooking = null;
$bookedSeats = [];
$showSeatSelection = false;
$userCoupons = [];

//Sefer arama
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_trip'])) {
    $searchPerformed = true;
    $departure_city = trim($_POST['departure_city']);
    $destination_city = trim($_POST['destination_city']);
    $departure_time = trim($_POST['departure_time']);

    $stmt = $db->prepare("
        SELECT T.*, BC.name AS company_name 
        FROM Trips AS T
        JOIN Bus_Company AS BC ON T.company_id = BC.id
        WHERE T.departure_city = ? 
          AND T.destination_city = ? 
          AND DATE(T.departure_time) = ? 
        ORDER BY T.departure_time ASC
    ");
    $stmt->execute([$departure_city, $destination_city, $departure_time]);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//Koltuk seçimi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_seats'])) {
    $tripId = $_POST['trip_id'];
    
    $stmt = $db->prepare("
        SELECT T.*, BC.name AS company_name 
        FROM Trips AS T
        JOIN Bus_Company AS BC ON T.company_id = BC.id
        WHERE T.id = ?
    ");
    $stmt->execute([$tripId]);
    $tripForBooking = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tripForBooking) {
        $showSeatSelection = true;

        // Dolu koltukları al
        $stmt2 = $db->prepare("
            SELECT bs.seat_number 
            FROM Booked_Seats bs 
            JOIN Tickets t ON bs.ticket_id = t.id
            WHERE t.trip_id = ? AND t.status = 'Active'
        ");
        $stmt2->execute([$tripId]);
        $bookedSeatsRaw = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        $bookedSeats = array_map('strval', $bookedSeatsRaw);

        // Kullanıcının kullanabileceği kuponlar
        $stmt3 = $db->prepare("
            SELECT * FROM Coupons 
            WHERE (company_id IS NULL OR company_id = ?) 
              AND expire_date >= datetime('now', 'localtime') 
              AND usage_limit > 0
              AND id NOT IN (
                  SELECT coupon_id FROM User_Coupons WHERE user_id = ?
              )
        ");
        $stmt3->execute([$tripForBooking['company_id'], $current_user['id']]);
        $userCoupons = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<?php include('includes/header.php'); ?>
<div class="container mt-5">
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Sefer arama kartı -->
    <div class="search-card mb-5 p-4 bg-light rounded shadow-sm">
        <h2 class="h4 text-center text-primary mb-4">Otobüs Seferi Ara</h2>
        <form method="post" class="row g-3">
            <div class="col-md-4">
                <label for="inputDeparture" class="form-label">Kalkış Noktası</label>
                <input type="text" list="city-options" name="departure_city" class="form-control" id="inputDeparture" placeholder="Şehir Seçiniz" required>
            </div>
            <div class="col-md-4">
                <label for="inputDestination" class="form-label">Varış Noktası</label>
                <input type="text" list="city-options" name="destination_city" class="form-control" id="inputDestination" placeholder="Şehir Seçiniz" required>
            </div>
            <div class="col-md-3">
                <label for="Departure_time" class="form-label">Tarih</label>
                <input type="date" name="departure_time" class="form-control" id="Departure_time" required>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" name="search_trip" class="btn btn-primary w-100">Ara</button>
            </div>
        </form>
    </div>

    <!-- Uygun seferler -->
    <?php if ($searchPerformed && !$showSeatSelection): ?> 
        <h3 class="mt-4">Uygun Seferler</h3>
        <?php if (count($trips) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover mt-3">
                    <thead>
                        <tr>
                            <th>Firma Adı</th> <th>Kalkış Şehri</th>
                            <th>Varış Şehri</th>
                            <th>Kalkış Zamanı</th>
                            <th>Fiyat</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trips as $trip): ?>
                            <tr>
                                <td><?= htmlspecialchars($trip['company_name']) ?></td>
                                <td><?= htmlspecialchars($trip['departure_city']) ?></td>
                                <td><?= htmlspecialchars($trip['destination_city']) ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?></td>
                                <td><?= number_format($trip['price'], 2) ?> ₺</td>
                                <td>
                                    <?php if ($is_logged_in): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="trip_id" value="<?= $trip['id'] ?>">
                                            <button type="submit" name="book_seats" class="btn btn-success btn-sm">Koltuk Seç</button>
                                        </form>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-primary btn-sm">Giriş Yap</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center mt-3">Bu güzergahta ve tarihte sefer bulunamadı.</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Koltuk seçimi -->
    <?php if ($showSeatSelection && $tripForBooking): ?>
        <div class="mt-5 p-4 border rounded shadow-sm bg-white">
            <h3 class="text-center text-primary">Koltuk Seçimi - <?= htmlspecialchars($tripForBooking['company_name']) ?></h3>
            <p class="text-center"><?= date('d.m.Y H:i', strtotime($tripForBooking['departure_time'])) ?> | <?= htmlspecialchars($tripForBooking['departure_city']) ?> → <?= htmlspecialchars($tripForBooking['destination_city']) ?></p>
            <hr>

            <form method="post" action="bilet_al.php">
                <input type="hidden" name="trip_id" value="<?= $tripForBooking['id'] ?>">
                <input type="hidden" name="trip_price" id="trip-price-data" value="<?= $tripForBooking['price'] ?>">

                <div class="row">
                    <div class="col-md-6 d-flex justify-content-center">
                        <div class="bus-container">
                            <div class="driver">ŞOFÖR</div>
                            <?php
                            $seatCount = 1;
                            $capacity = $tripForBooking['capacity'];

                            while ($seatCount <= $capacity): ?>
                                <div class="bus-row">
                                    <!-- Sol tek koltuk -->
                                    <?php if ($seatCount <= $capacity):
                                        $isBooked = in_array(strval($seatCount), $bookedSeats);
                                        $classes = 'seat' . ($isBooked ? ' booked' : '');
                                    ?>
                                    <label class="<?= $classes ?>">
                                        <?php if (!$isBooked): ?>
                                            <input type="checkbox" name="seats[]" value="<?= $seatCount ?>" style="display:none;">
                                        <?php endif; ?>
                                        <?= $seatCount ?>
                                    </label>
                                    <?php $seatCount++; endif; ?>

                                    <!-- Koridor -->
                                    <div class="aisle"></div>

                                    <!-- Sağdaki iki koltuk -->
                                    <?php for ($c = 0; $c < 2; $c++):
                                        if ($seatCount > $capacity) break;
                                        $isBooked = in_array(strval($seatCount), $bookedSeats);
                                        $classes = 'seat' . ($isBooked ? ' booked' : '');
                                    ?>
                                    <label class="<?= $classes ?>">
                                        <?php if (!$isBooked): ?>
                                            <input type="checkbox" name="seats[]" value="<?= $seatCount ?>" style="display:none;">
                                        <?php endif; ?>
                                        <?= $seatCount ?>
                                    </label>
                                    <?php $seatCount++; endfor; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h4 class="mb-3">Ödeme Özeti</h4>
                        <div class="mb-3">
                            <label for="coupon_select" class="form-label">Kupon Seç</label>
                            <select name="coupon_id" id="coupon_select" class="form-control">
                                <option value="">Kupon Yok</option>
                                <?php foreach($userCoupons as $c): ?>
                                    <option value="<?= $c['id'] ?>" data-discount="<?= $c['discount'] ?>"><?= htmlspecialchars($c['code']) ?> - %<?= $c['discount'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="summary card p-3 bg-light">
                            <p>Birim Fiyat: <strong><?= number_format($tripForBooking['price'], 2) ?> ₺</strong></p>
                            <p>Seçilen Koltuk: <span id="selected-count">0</span> adet</p>
                            <hr>
                            <p class="h5">Toplam Tutar: <span id="total-price" class="text-success">0.00</span> ₺</p>
                        </div>

                        <button type="submit" class="btn btn-lg btn-block btn-success mt-4 w-100">Bilet Al</button>
                        <a href="index.php" class="btn btn-secondary btn-block mt-2 w-100">Geri Dön</a>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
<style>
.bus-container { width: 320px; margin: 20px auto; padding: 10px; background: #f8f9fa; border:1px solid #ccc; border-radius:8px; }
.driver { text-align:center; font-weight:bold; margin-bottom:10px; }
.bus-row { display: grid; grid-template-columns: 50px 30px 50px 50px; gap: 5px; margin-bottom:5px; align-items:center; }
.seat { width: 100%; height:40px; line-height:40px; text-align:center; border-radius:5px; background:#198754; color:white; font-weight:bold; cursor:pointer; user-select: none; }
.seat.selected { background:#0d6efd; }
.seat.booked { background:#dc3545; cursor:not-allowed; pointer-events:none; }
.aisle { width:30px; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const seatLabels = document.querySelectorAll('.seat:not(.booked)');
    const selectedCountEl = document.getElementById('selected-count');
    const totalPriceEl = document.getElementById('total-price');
    const pricePerSeatEl = document.getElementById('trip-price-data');
    const couponSelect = document.getElementById('coupon_select');

    const pricePerSeat = parseFloat(pricePerSeatEl.value);

    function updateTotal() {
        const selectedSeats = document.querySelectorAll('.seat input:checked').length;
        let total = selectedSeats * pricePerSeat;

        // Kupon indirimini uygula
        const selectedCoupon = couponSelect.selectedOptions[0];
        if (selectedCoupon && selectedCoupon.dataset.discount) {
            const discount = parseFloat(selectedCoupon.dataset.discount);
            total = total * (1 - discount / 100);
        }

        selectedCountEl.textContent = selectedSeats;
        totalPriceEl.textContent = total.toFixed(2);
    }

    seatLabels.forEach(label => {
        const checkbox = label.querySelector('input[type="checkbox"]');
        label.addEventListener('click', function() {
            if (!checkbox.disabled) {
                checkbox.checked = !checkbox.checked;
                label.classList.toggle('selected', checkbox.checked);
                updateTotal();
            }
        });
    });

    couponSelect.addEventListener('change', updateTotal);
    updateTotal(); // sayfa yüklendiğinde toplam fiyatı güncelle
});
</script>