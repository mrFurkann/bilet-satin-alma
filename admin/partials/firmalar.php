<?php
require '../includes/config.php';

    //Silme İşlemi
   if (isset($_GET['sil'])) {
   
    $id = $_GET['sil'];

        try {
            $db->beginTransaction(); // güvenli transaction Bun diyerek aşağıdai işlemleri hemen veritabnına geçirme demek istiyoruz aslında veri bütünlüğünü sağlıyor

            $db->prepare("DELETE FROM Trips WHERE company_id = ?")->execute([$id]);

            $db->prepare("DELETE FROM Coupons WHERE company_id = ?")->execute([$id]);

            $db->prepare("DELETE FROM User WHERE company_id = ?")->execute([$id]);

            $db->prepare("DELETE FROM Bus_Company WHERE id = ?")->execute([$id]);

            $db->commit(); // commit() ile yaptığımız işlemleri artık gönder diyoruz 

            header("Location: index.php?tab=firmalar");
            exit;
        } catch (Exception $e) {
            
            $db->rollBack(); // Yukarıdaki işlemlerde sorun olursa Tüm işlemler geri alınır
            echo "Hata oluştu: " . $e->getMessage();
        }
    
    
    
    
    }


    // UPDATE ve Ekleme İşlemi 
    $name = ""; 
    $logo_path = ""; 
    $id=$_GET['duzenle'] ?? null;

    if ($id) {
        $stmt=$db->prepare("SELECT * FROM Bus_Company WHERE id = ?");
        $stmt->execute([$id]);
        $duzenlenecek_firma = $stmt->fetch();

       
            $name = $duzenlenecek_firma['name'];
            $logo_path = $duzenlenecek_firma['logo_path'];
        
    }




    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ekle'])) {
        $name = $_POST['name'];
        $logo = $_POST['logo'];
        if ($_POST['id']) {
            $stmt = $db->prepare("UPDATE Bus_Company SET name=?, logo_path=? WHERE id = ?");
            $stmt-> execute([$name,$logo_path,$_POST['id']]);
        }else {
            $name = $_POST['name'];
            $logo_path = $_POST["logo"];
            $uuid = generate_uuid_v4();
            $stmt=$db->prepare("INSERT INTO Bus_Company (id,name,logo_path) VALUES (?,?,?)");
            $stmt->execute([$uuid,$name,$logo_path]);   
        }
        header("Location: index.php?tab=firmalar");
        exit;


    }



// ✅ Firma Listesi
$firmalar = $db->query("SELECT * FROM Bus_Company ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>


<div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-bus-front"></i> Otobüs Firması Ekle
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
        <input type="hidden" name="id" value="<?= $id ?>">  <!--Burada GET den aldığımız id yi guncelleme için kullanmak için post dan alınacak şekilde  bir input yaptık bunu $_POST['id'] şekilde çekip kullanıcaz-->

            <div class="col-md-5">
                <label class="form-label">Firma Adı</label>
                <input type="text" name="name" class="form-control" placeholder="Metro Turizm, Kamil Koç vb." value="<?= htmlspecialchars($name) ?>" required>
            </div>
            
            <div class="col-md-5">
                <label class="form-label">Logo URL</label>
                <input type="text" name="logo" class="form-control" value="<?= htmlspecialchars($logo_path) ?>" placeholder="https://ornek.com/logo.png">
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
               <button type="submit" name="ekle" class="btn btn-<?= $id ? "success" : "primary" ?> w-100">  <!--id varsa succes yoksa primary-->
                    <i class="bi bi-save"></i> <?= $id ? "Güncelle" : "Ekle" ?> <!--id varsa Güncelle yoksa Ekle-->
                </button>
            </div>
        </form>
    </div>
</div>

<h4 class="mb-3 mt-5"><i class="bi bi-list-columns"></i> Kayıtlı Firmalar</h4>

<?php if (empty($firmalar)): ?>
    <div class="alert alert-info">Henüz firma eklenmemiş.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th style="width:5%">ID</th>
                    <th style="width:30%">Ad</th>
                    <th style="width:45%">Logo</th>
                    <th style="width:20%">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($firmalar as $f): ?>
                    <tr>
                        <td><?= htmlspecialchars($f['id']) ?></td>
                        <td><?= htmlspecialchars($f['name']) ?></td>
                        <td>
                            <?php if ($f['logo_path']): ?>
                                <img src="<?= htmlspecialchars($f['logo_path']) ?>" alt="Logo" style="max-height:50px; border:1px solid #ddd; border-radius:10px;">
                            <?php else: ?>
                                <span class="text-muted fst-italic">Logo Yok</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="index.php?tab=firmalar&sil=<?= $f['id'] ?>"
                               onclick="return confirm('Bu firmayı silmek istiyor musunuz?')"
                               class="btn btn-sm btn-danger">
                               <i class="bi bi-trash"></i> Sil
                            </a>
                            <a href="index.php?tab=firmalar&duzenle=<?= $f['id'] ?>"
                                class="btn btn-sm btn-info text-white me-2" title="Düzenle">
                                <i class="bi bi-pencil-square"></i> Düzenle
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>