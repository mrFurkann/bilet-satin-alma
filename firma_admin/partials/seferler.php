<?php
require '../includes/config.php'; 

// Silme
if (isset($_GET['sil'])) {
    $id = $_GET['sil'];

    try {
            $db->beginTransaction(); // güvenli transaction Bun diyerek aşağıdai işlemleri hemen veritabnına geçirme demek istiyoruz aslında veri bütünlüğünü sağlıyor
            $stmt = $db->prepare("DELETE FROM Booked_Seats WHERE ticket_id IN (SELECT id FROM Tickets WHERE trip_id = ?)");
            $stmt->execute([$id]);

            $stmt = $db->prepare("DELETE FROM Tickets WHERE trip_id = ?");
            $stmt->execute([$id]);

            // Şimdi seferi sil
            $stmt = $db->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $user_company_id]);

            $db->commit(); // commit() ile yaptığımız işlemleri artık gönder diyoruz 

            header("Location: index.php?tab=seferler");
            exit;
        } catch (Exception $e) {
            
            $db->rollBack(); // Yukarıdaki işlemlerde sorun olursa Tüm işlemler geri alınır
            echo "Hata oluştu: " . $e->getMessage();
        }
}

//Düzenle Sil 
$id = $_GET['duzenle'] ?? null;
$trip = null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM Trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$id, $user_company_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        echo "<div class='alert alert-danger'>Bu sefer size ait değil veya bulunamadı.</div>";
        exit;
    }
}

// Formda kullanılacak değişkenler
$departure_city = $trip['departure_city'] ?? '';
$destination_city = $trip['destination_city'] ?? '';
$trip_date = $trip ? substr($trip['departure_time'], 0, 10) : '';
$departure_time = $trip ? substr($trip['departure_time'], 11, 5) : '';
$arrival_time = $trip ? substr($trip['arrival_time'], 11, 5) : '';
$price = $trip['price'] ?? '';
$capacity = $trip['capacity'] ?? '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kaydet'])) {
    $departure_city =Transliterator::create('tr-lower')->transliterate($_POST['departure_city']); //mb_strtolower kullandığımda kullanıcı eğer BARTIN girerse bartin olarak kaydediyor ve buda istemediğim bir şey oluyo Transliterator ile turkçe karakterle beraber işlem başarılı oluyor
    $destination_city =Transliterator::create('tr-lower')->transliterate($_POST['destination_city']);
    $trip_date = $_POST['trip_date'];
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = $_POST['price'];
    $capacity = $_POST['capacity'];

    // Tarihleri birleştir
    $departure_datetime = $trip_date . ' ' . $departure_time . ':00';
    $arrival_datetime = $trip_date . ' ' . $arrival_time . ':00';

    if ($_POST['id']) {
        // Güncelleme
        $stmt = $db->prepare("UPDATE Trips SET departure_city=?, destination_city=?, departure_time=?, arrival_time=?, price=?, capacity=? WHERE id=? AND company_id=?");
        $stmt->execute([$departure_city, $destination_city, $departure_datetime, $arrival_datetime, $price, $capacity, $_POST['id'], $user_company_id]);
    } else {
        // Yeni ekleme
        $uuid = generate_uuid_v4();
        $stmt = $db->prepare("INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$uuid, $user_company_id, $departure_city, $destination_city, $departure_datetime, $arrival_datetime, $price, $capacity]);
    }

    header("Location: index.php?tab=seferler");
    exit;
}

//Listele
$seferler = $db->prepare("SELECT * FROM Trips WHERE company_id=? ORDER BY departure_time DESC");
$seferler->execute([$user_company_id]);
$liste = $seferler->fetchAll(PDO::FETCH_ASSOC);
?>


<div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-calendar-plus"></i> Sefer <?= $id ? "Düzenle" : "Ekle" ?>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="col-md-3">
                <label class="form-label">Kalkış Şehri</label>
                <input type="text" name="departure_city" class="form-control" value="<?= htmlspecialchars($departure_city) ?>" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Varış Şehri</label>
                <input type="text" name="destination_city" class="form-control" value="<?= htmlspecialchars($destination_city) ?>" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Tarih</label>
                <input type="date" name="trip_date" class="form-control" value="<?= htmlspecialchars($trip_date) ?>" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Kalkış</label>
                <input type="time" name="departure_time" class="form-control" value="<?= htmlspecialchars($departure_time) ?>" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Varış</label>
                <input type="time" name="arrival_time" class="form-control" value="<?= htmlspecialchars($arrival_time) ?>" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Fiyat (₺)</label>
                <input type="number" name="price" step="0.01" class="form-control" value="<?= htmlspecialchars($price) ?>" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Koltuk</label>
                <input type="number" name="capacity" class="form-control" value="<?= htmlspecialchars($capacity) ?>" min="10" max="60" required>
            </div>

            <div class="col-12 mt   -4">
                <button type="submit" name="kaydet" class="btn btn-<?= $id ? "success" : "primary" ?> w-100">
                    <i class="bi bi-save"></i> <?= $id ? "Güncelle" : "Ekle" ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!--Listele-->
<h4 class="mb-3 mt-5"><i class="bi bi-list-columns"></i> Tanımlı Seferler</h4>

<?php if (empty($liste)): ?>
    <div class="alert alert-info">Henüz sefer eklenmemiş.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th style="width:10%">ID</th>
                    <th style="width:25%">Güzergah</th>
                    <th style="width:15%">Tarih</th>
                    <th style="width:15%">Saatler</th>
                    <th style="width:10%">Fiyat</th>
                    <th style="width:10%">Koltuk</th>
                    <th style="width:15%">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($liste as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['id']) ?></td>
                        <td><?= Transliterator::create('tr-title')->transliterate(htmlspecialchars($s['departure_city'])) ?> → <?= Transliterator::create('tr-title')->transliterate(htmlspecialchars($s['destination_city'])) ?></td>
                        <td><?= date('d.m.Y', strtotime($s['departure_time'])) ?></td>
                        <td><?= date('H:i', strtotime($s['departure_time'])) ?> - <?= date('H:i', strtotime($s['arrival_time'])) ?></td>
                        <td><?= number_format($s['price'], 2, ',', '.') ?> ₺</td>
                        <td><?= htmlspecialchars($s['capacity']) ?></td>
                        <td>
                            <a href="index.php?tab=seferler&duzenle=<?= $s['id'] ?>" class="btn btn-sm btn-info text-white me-1">
                                <i class="bi bi-pencil-square"></i> Düzenle
                            </a>
                            <a href="index.php?tab=seferler&sil=<?= $s['id'] ?>" onclick="return confirm('Bu seferi silmek istiyor musunuz?')" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i> Sil
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
