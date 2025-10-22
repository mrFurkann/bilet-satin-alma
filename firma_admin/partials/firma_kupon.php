<?php

$firma_company_id= $current_user['company_id'];
$firma_company_name = $current_user['company_name'] ?? 'Bilinmiyor';

if (!$firma_company_id) {
    echo '<div class="alert alert-danger">Hata: Firma ID\'niz tanımlı değil. Kupon yönetimi yapılamaz.</div>';
    return;
}


// SİLME
if (isset($_GET['kupon_sil'])) {

    $sil_stmt=$db->prepare("DELETE FROM Coupons WHERE id=? AND company_id=?");
    $sil_stmt->execute([$_GET['kupon_sil'],$firma_company_id]);
    header("Location: index.php?tab=firma_kupon");
    exit;
}


//DÜZENLE  ve EKLE 
$kupon_id = $_GET['kupon_duzenle'] ?? null;
$kupon_kod= $kupon_indirim = $kupon_limit = $kupon_gecerlilik= "";   

if ($kupon_id) {
    $stmt=$db->prepare("SELECT * FROM Coupons WHERE id =? AND company_id=?");
    $stmt->execute([$kupon_id,$firma_company_id]);
    $kupon=$stmt->fetch(PDO::FETCH_ASSOC);
  
    if (!$kupon) {  //Kupon yoksa o firmaya ait değildir sql sorgusuna göre
        header("Location: index.php?tab=firma_kupon");
        exit;
    }

    $kupon_kod=$kupon['code'];
    $kupon_indirim = $kupon['discount'];
    $kupon_limit = $kupon['usage_limit'];
    $kupon_gecerlilik = substr($kupon['expire_date'],0,10);;

} // Kupon duzenle ye basınca o kupondaki bilgileri inputa yazdırdık.

// Şimdi Güncelleme Ve ekle İşlemine Devam edelim

if ($_SERVER['REQUEST_METHOD'] ==='POST' && isset($_POST['coupon_submit'])) {
    $code = trim($_POST['code']);
    $discount = $_POST['discount'];
    $usage_limit = $_POST['usage_limit'];
    $expire_date = $_POST['expire_date'];

    if ($_POST['kupon_duzenle_id']) {
        $stmt=$db->prepare("UPDATE Coupons SET code=?, discount=?, usage_limit=?, expire_date=? WHERE id=? AND company_id=?");
        $stmt->execute([$code,$discount,$usage_limit,$expire_date,$_POST['kupon_duzenle_id'],$firma_company_id]);
    }else {
        $new_uuid = generate_uuid_v4(); 

        $stmt= $db->prepare("INSERT INTO Coupons (id , code, discount, usage_limit, expire_date, company_id) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$new_uuid,$code,$discount,$usage_limit,$expire_date,$firma_company_id]);
    }
    header("Location: index.php?tab=firma_kupon");
    exit;

}

//LİSTELEME
$kupon_stmt= $db->prepare("SELECT * FROM Coupons WHERE company_id=?");
$kupon_stmt->execute([$firma_company_id]);
$kuponlar= $kupon_stmt->fetchAll(PDO::FETCH_ASSOC);
?> 


<div class="card mb-4 border-success">
        <div class="card-header bg-success text-white">
            <i class="bi bi-ticket-perforated"></i>Kupon Kodu <?= $kupon_id ? "Düzenle": "Oluştur"?>
            <span class="float-end badge bg-info text-dark">Geçerlilik: **<?= htmlspecialchars($firma_company_name) ?>**</span>
        </div>


        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="kupon_duzenle_id" value="<?= $kupon_id ?>">

                <div class="col md-3">
                    <label for="inputCode" class="form-label">Kupon Kodu</label>
                    <input type="text" name="code" id="inputCode" class="form-control" placeholder="FIRMA10" value="<?= htmlspecialchars($kupon_kod)?>" required>
                </div>

                <div class="col md-3">
                    <label for="inputDiscount" class="form-label">İndirim Oranı (%)</label>
                    <input type="number" name="discount" id="inputDiscount" class="form-control" placeholder="Örn: 10" min="1" max="100" value="<?= htmlspecialchars($kupon_indirim)?>" required>
                </div>

                <div class="col md-3">
                    <label for="inputLimit" class="form-label">Kullanım Limiti</label>
                    <input type="number" name="usage_limit" id="inputLimit" class="form-control" placeholder="Örn: 20" min="1" value="<?= htmlspecialchars($kupon_limit)?>" required>
                </div>

                <div class="col md-3">
                    <label for="inputExpire" class="form-label">Son Kullanma Tarihi</label>
                    <input type="date" name="expire_date" id="inputExpire" class="form-control" value="<?= htmlspecialchars($kupon_gecerlilik)?>" required>
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" name="coupon_submit" class="btn btn-<?= $kupon_id  ? "warning" : "success"?> w-100">
                        <i class="bi bi-save"></i> <?= $kupon_id  ? "Kuponu Güncelle" : "Kupon Ekle"?>
                    </button>
                </div>

            </form>
        </div>
</div>


<h4 class="mb-3 mt-5"><i class="bi bi-list-columns"></i> <?= htmlspecialchars($firma_company_name) ?> Kuponları</h4>


<?php if(empty($kuponlar)):?>
    <div class="alert alert-info">Firmanıza Özel Tanımlanmış Kupon Kodu Bulunmamaktadır.</div>
    
    <?php else:?>
        <div class="table-responsive">

            <table class="table table-striped table-hover table-bordered align-middle">

                <thead class="table-dark">
                    <tr>
                        <th style="width: 5%">ID</th>
                        <th style="width: 20%">Kod</th>
                        <th style="width: 20%">İndirim (%)</th>
                        <th style="width: 20%">Kullanım Limiti</th>
                        <th style="width: 25%">Son Kullanma Tarihi</th>
                        <th style="width: 10%">İşlemler</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach($kuponlar as $kupon):?>
                        <tr>
                            <td><?= substr($kupon['id'],0,8)."..."?></td>
                            <td><?= $kupon['code']?></td>
                            <td><?= $kupon['discount']?></td>
                            <td><?= $kupon['usage_limit']?></td>
                            <td><?= $kupon['expire_date']?></td>
                            <td>
                                <a href="index.php?tab=firma_kupon&kupon_duzenle=<?= $kupon['id']?>" class="btn btn-sm btn-info text-white me-2" title="Düzenle">
                                    <i  class="bi bi-pencil-square" ></i>
                                </a>
                                <a href="index.php?tab=firma_kupon&kupon_sil=<?= $kupon['id']?>" class="btn btn-sm btn-danger" title="Sil" onclick="return confirm('Kupon Kodunu Silmek İstediğinizden Emin Misiniz?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>

                    <?php endforeach;?>
                </tbody>


            </table>


        </div>














<?php endif;?>
    