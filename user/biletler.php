<?php
require '../includes/config.php';
require '../includes/auth.php';

$msg = $_GET['msg'] ?? '';
$type = $_GET['type'] ?? 'success';

$current_user = getUser();
if (!$current_user) {
    header("Location: ../login.php");
    exit;
}

$user_id = $current_user['id'];

$stmt = $db->prepare("
    SELECT 
        t.id AS ticket_id, 
        t.status, 
        t.total_price, 
        t.created_at,
        tr.departure_city, 
        tr.destination_city, 
        tr.departure_time,
        bc.name AS company_name,
        GROUP_CONCAT(bs.seat_number, ', ') AS seats
    FROM Tickets t
    JOIN Trips tr ON t.trip_id = tr.id
    JOIN Bus_Company bc ON tr.company_id = bc.id
    JOIN Booked_Seats bs ON bs.ticket_id = t.id
    WHERE t.user_id = ?
    GROUP BY t.id
    ORDER BY tr.departure_time DESC
");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);


$aktifBiletler = [];
$gecmisBiletler = [];
$iptalBiletler = [];

foreach ($tickets as $t) {
    $isPast = strtotime($t['departure_time']) < time();

    if ($t['status'] === 'Cancelled') {
        $iptalBiletler[] = $t;
    } elseif ($isPast) {
        $gecmisBiletler[] = $t;
    } else {
        $aktifBiletler[] = $t;
    }
}


?>

<?php include('../includes/header.php'); ?>

<div class="container mt-5">


    <?php if ($msg): ?>
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1055">
            <div id="liveToast" class="toast align-items-center text-bg-<?= htmlspecialchars($type) ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?= htmlspecialchars(urldecode($msg)) ?>
                    </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
            </div>
        </div>
        <script>
            var toastEl = document.getElementById('liveToast');
            var toast = new bootstrap.Toast(toastEl, { delay: 5000 });
            toast.show();
        </script>
    <?php endif; ?>



    <h2 class="text-center text-primary mb-5">
        <i class="bi bi-ticket-detailed"></i> Biletlerim
    </h2>

    <!-- Aktif Biletler -->
    <h4 class="section-title text-success"><i class="bi bi-clock-history"></i> Aktif Biletler</h4>
    <?php if (count($aktifBiletler) > 0): ?>
        <div class="ticket-grid">
            <?php foreach ($aktifBiletler as $t): ?>
                <div class="ticket-card border-success shadow-sm">
                    <div class="ticket-header bg-success text-white">
                        <strong><?= htmlspecialchars($t['company_name']) ?></strong>
                        <span class="badge bg-light text-success">Aktif</span>
                    </div>
                    <div class="ticket-body">
                        <p><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($t['departure_city']) ?> → <?= htmlspecialchars($t['destination_city']) ?></p>
                        <p><i class="bi bi-calendar-event"></i> <?= date('d.m.Y H:i', strtotime($t['departure_time'])) ?></p>
                        <p><i class="bi bi-chair"></i> Koltuk(lar): <?= htmlspecialchars($t['seats']) ?></p>
                        <p><i class="bi bi-cash"></i> Fiyat: <strong><?= number_format($t['total_price'], 2) ?> ₺</strong></p>
                    </div>
                    <div class="ticket-footer">
                        <div class="d-flex gap-2">
                            <form method="POST" action="../user/bilet_iptal.php" onsubmit="return confirm('Bu bileti iptal etmek istediğine emin misin?');" class="flex-fill">
                                <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($t['ticket_id']) ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                    <i class="bi bi-x-circle"></i> İptal Et
                                </button>   
                            </form>

                            <form method="GET" action="bilet_pdf.php" class="flex-fill">
                                <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($t['ticket_id']) ?>">
                                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-file-earmark-pdf"></i> PDF
                                </button>
                            </form>
                        </div> 
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-light text-center border mt-3">Hiç aktif bilet bulunamadı.</div>
    <?php endif; ?>

    <!-- Geçmiş Biletler -->
    <h4 class="section-title text-secondary mt-5"><i class="bi bi-calendar-check"></i> Geçmiş Biletler</h4>
    <?php if (count($gecmisBiletler) > 0): ?>
        <div class="ticket-grid">
            <?php foreach ($gecmisBiletler as $t): ?>
                <div class="ticket-card border-secondary shadow-sm">
                    <div class="ticket-header bg-secondary text-white">
                        <strong><?= htmlspecialchars($t['company_name']) ?></strong>
                        <span class="badge bg-light text-secondary">Geçmiş</span>
                    </div>
                    <div class="ticket-body">
                        <p><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($t['departure_city']) ?> → <?= htmlspecialchars($t['destination_city']) ?></p>
                        <p><i class="bi bi-calendar-event"></i> <?= date('d.m.Y H:i', strtotime($t['departure_time'])) ?></p>
                        <p><i class="bi bi-chair"></i> Koltuk(lar): <?= htmlspecialchars($t['seats']) ?></p>
                        <p><i class="bi bi-cash"></i> Fiyat: <strong><?= number_format($t['total_price'], 2) ?> ₺</strong></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-light text-center border mt-3">Hiç geçmiş bilet bulunamadı.</div>
    <?php endif; ?>

    <!-- İptal Biletler -->
    <h4 class="section-title text-danger mt-5"><i class="bi bi-x-octagon"></i> İptal Edilen Biletler</h4>
    <?php if (count($iptalBiletler) > 0): ?>
        <div class="ticket-grid">
            <?php foreach ($iptalBiletler as $t): ?>
                <div class="ticket-card border-danger shadow-sm">
                    <div class="ticket-header bg-danger text-white">
                        <strong><?= htmlspecialchars($t['company_name']) ?></strong>
                        <span class="badge bg-light text-danger">İptal</span>
                    </div>
                    <div class="ticket-body">
                        <p><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($t['departure_city']) ?> → <?= htmlspecialchars($t['destination_city']) ?></p>
                        <p><i class="bi bi-calendar-event"></i> <?= date('d.m.Y H:i', strtotime($t['departure_time'])) ?></p>
                        <p><i class="bi bi-chair"></i> Koltuk(lar): <?= htmlspecialchars($t['seats']) ?></p>
                        <p><i class="bi bi-cash"></i> Fiyat: <strong><?= number_format($t['total_price'], 2) ?> ₺</strong></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-light text-center border mt-3">Hiç iptal edilmiş bilet bulunamadı.</div>
    <?php endif; ?>

    <div class="text-center mt-5">
      
    </div>
</div>

<style>
.section-title {
    font-weight: 600;
    margin-bottom: 1rem;
}
.ticket-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}
.ticket-card {
    border: 2px solid #ccc;
    border-radius: 12px;
    background: #fff;
    transition: 0.3s;
    overflow: hidden;
}
.ticket-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.ticket-header {
    padding: 10px 15px;
    font-size: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.ticket-body {
    padding: 15px;
}
.ticket-body p {
    margin: 0.3rem 0;
    font-size: 0.95rem;
}
.ticket-body i {
    margin-right: 6px;
}
.ticket-footer {
    border-top: 1px solid #eee;
    padding: 10px 15px;
    background-color: #fafafa;
}
</style>



