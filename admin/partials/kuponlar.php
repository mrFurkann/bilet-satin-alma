<?php

// SİLME

if(isset($_GET['delete_coupon'])){
    $id = $_GET['delete_coupon'];
    $stmt= $db->prepare("DELETE FROM Coupons WHERE id = ?");
    $stmt->execute([$id]);
}


// Güncelleme Ve Ekle
$id = $_GET['edit_coupon'] ?? null;
$code = $discount = $limit = $expire = "";
$current_company_name = "Tümü (Global)"; 

if ($id) {
    $stmt= $db->prepare("SELECT Coupons.*, Bus_Company.name AS company_name FROM Coupons LEFT JOIN Bus_Company ON Coupons.company_id = Bus_Company.id WHERE Coupons.id=? AND Coupons.company_id IS NULL");
    $stmt->execute([$id]);
    $global_coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$global_coupon){
        header("Location: index.php?tab=kuponlar");
        exit();
    }

    $code = $global_coupon['code'];
    $discount= $global_coupon['discount'];
    $limit = $global_coupon['usage_limit'];
    $expire=$global_coupon['expire_date'];
    $current_company_name = $global_coupon['company_name'] ?? "Tümü (Global)";
}

//Ekle

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['coupon_submit'])) {
    
    $code= trim($_POST['code']);
    $discount = $_POST['discount'];
    $limit = $_POST['limit'];
    $expire = $_POST['expire'];


    if($_POST['id']){
        $stmt=$db->prepare("UPDATE Coupons SET code=?, discount=?, usage_limit=?, expire_date=? WHERE id=? AND company_id IS NULL");
        $stmt->execute([$code,$discount,$limit,$expire,$_POST['id']]);
        
    }else{
        $new_uuid= generate_uuid_v4();

        $stmt=$db->prepare("INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, company_id) VALUES (?, ?, ?, ?, ?, NULL)");
        $stmt->execute([$new_uuid,$code,$discount,$limit,$expire]);

        header("Location: index.php?tab=kuponlar");
        exit();
    }

}

//Listeleme

$coupons_query = $db->query("
    SELECT Coupons.*,
        CASE
            WHEN Coupons.company_id IS NULL THEN 'Tümü(Global)'
            ELSE Bus_Company.name
        END AS company_name
    FROM Coupons
    LEFT JOIN Bus_Company ON Coupons.company_id = Bus_Company.id ORDER BY Coupons.created_at DESC

");
$coupons = $coupons_query->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-ticket-perforated"></i> Kupon Kodu <?= $id ? "Düzenle" : "Oluştur" ?>
        <span class="float-end badge bg-info text-dark">Geçerlilik: <?= $current_company_name ?></span>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <input type="hidden" name="id" value="<?= $id ?>">
            
            <div class="col-md-3">
                <label for="inputCode" class="form-label">Kupon Kodu</label>
                <input type="text" name="code" class="form-control" id="inputCode" placeholder="YAZINDIRIM" value="<?= htmlspecialchars($code) ?>" required>
            </div>
            
            <div class="col-md-3">
                <label for="inputDiscount" class="form-label">İndirim Oranı (%)</label>
                <input type="number" name="discount" class="form-control" id="inputDiscount" placeholder="Örn: 10" min="1" max="100" value="<?= htmlspecialchars($discount) ?>" required>
            </div>
            
            <div class="col-md-3">
                <label for="inputLimit" class="form-label">Kullanım Limiti</label>
                <input type="number" name="limit" class="form-control" id="inputLimit" placeholder="Örn: 100" min="1" value="<?= htmlspecialchars($limit) ?>" required>
            </div>
            
            <div class="col-md-3">
                <label for="inputExpire" class="form-label">Son Kullanma Tarihi</label>
                <input type="date" name="expire" class="form-control" id="inputExpire" value="<?= htmlspecialchars($expire) ?>" required>
            </div>
            
            <div class="col-12 mt-4">
                <button type="submit" name="coupon_submit" class="btn btn-<?= $id ? "success" : "primary" ?> w-100">
                    <i class="bi bi-save"></i> <?= $id ? "Kuponu Güncelle" : "Yeni Global Kupon Ekle" ?>
                </button>
            </div>
        </form>
    </div>
</div>

<h4 class="mb-3 mt-5"><i class="bi bi-list-columns"></i> Tanımlı Kuponlar (Tümü)</h4>

<?php if (empty($coupons)): ?>
    <div class="alert alert-info">Tanımlanmış herhangi bir kupon kodu bulunmamaktadır.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th style="width: 5%">ID</th>
                    <th style="width: 15%">Kod</th>
                    <th style="width: 15%">Firma</th> <th style="width: 15%">İndirim (%)</th>
                    <th style="width: 10%">Kullanım Limiti</th>
                    <th style="width: 20%">Son Kullanma Tarihi</th>
                    <th style="width: 20%">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($coupons as $c): ?>
                <tr>
                    <td><?= substr($c['id'], 0, 4) ?>..</td>
                    <td><span class="badge bg-primary fs-6"><?= htmlspecialchars($c['code']) ?></span></td>
                    <td>
                        <?php 
                            $badge_class = ($c['company_id'] === NULL || $c['company_id'] === '') ? 'bg-warning text-dark' : 'bg-secondary';
                        ?>
                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($c['company_name']) ?></span>
                    </td>
                    <td><span class="badge bg-success"><?= $c['discount'] ?>%</span></td>
                    <td><?= $c['usage_limit'] ?></td>
                    <td><?= date('d.m.Y', strtotime($c['expire_date'])) ?></td>
                    <td>
                        <?php if ($c['company_id'] === NULL || $c['company_id'] === ''): ?>
                            <a href="index.php?tab=kuponlar&edit_coupon=<?= $c['id'] ?>" class="btn btn-sm btn-info text-white me-2" title="Düzenle">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <a href="index.php?tab=kuponlar&delete_coupon=<?= $c['id'] ?>" 
                               onclick="return confirm('Bu GLOBAL kupon kodunu silmek istediğinizden emin misiniz?')" 
                               class="btn btn-sm btn-danger" title="Sil">
                               <i class="bi bi-trash"></i>
                            </a>
                        <?php else: ?>
                            <span class="badge bg-light text-muted border">Firma Özel</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>