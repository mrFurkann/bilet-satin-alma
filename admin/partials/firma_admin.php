<?php

//Firma Seçtirmemiz için company Tablosunda veri çekmemiz lazım
$stmt = $db->prepare("SELECT * FROM Bus_Company");
$stmt->execute();
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Silme İşlemi

if (isset($_GET['delete_admin'])) {
    $id = $_GET['delete_admin'];
    
    $db->prepare("DELETE FROM User WHERE id=?")->execute([$id]);
}



//Ekleme İşlemi && Güncelleme İşlemi 
$full_name ="";
$email="";
$company_id= "";

$id = $_GET['edit_admin']?? null;

if ($id) {
    
    $stmt=$db->prepare("SELECT * FROM User WHERE id = ?");
    $stmt->execute([$id]);
    $admin_duzen=$stmt->fetch();

    if ($admin_duzen) {
        $full_name = $admin_duzen['full_name'];
        $email = $admin_duzen['email'];
        $company_id = $admin_duzen['company_id'];
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['admin_submit'])) {
    $post_name = $_POST['full_name'];
    $post_email = $_POST['email'];
    $post_compid= $_POST['company_id'];

    if ($id) {
        $stmt=$db->prepare("UPDATE User SET full_name=?, email=?, company_id=? WHERE id=?");
        $stmt->execute([$post_name,$post_email,$post_compid,$_POST['id']]);

    }else {
        
        $check_stmt = $db->prepare("SELECT email FROM User WHERE email = ?");
        $check_stmt->execute([$post_email]);

        if ($check_stmt->fetch()) {
            die("<div class='alert alert-danger'>HATA: **$post_email** adresi sistemde zaten kayıtlı (Yolcu veya Başka bir Yönetici olarak). Lütfen farklı bir e-posta kullanın.</div><a href='index.php?tab=firma_admin' class='btn btn-warning mt-3'>Geri Dön</a>");
        }

        $new_uuid = generate_uuid_v4();
        $password= password_hash($_POST['password'],PASSWORD_DEFAULT);

        $stmt=$db->prepare("INSERT INTO User (id,full_name,email,role,password,company_id) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$new_uuid,$post_name,$post_email,'company_admin',$password,$post_compid]);

        header("Location: index.php?tab=firma_admin");
        exit();
        }
}




//Listeleme
$astmt=$db->prepare("SELECT User.id,User.full_name,User.email,Bus_Company.name as company_name FROM User LEFT JOIN Bus_Company ON User.company_id=Bus_Company.id Where User.role=?");
$astmt->execute(['company_admin']);
$fadmins =$astmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-person-plus"></i> Firma Yöneticisi <?= $id ? "Düzenle" : "Ekle" ?>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <input type="hidden" name="id" value="<?= $id ?>">
            
            <div class="col-md-4">
                <label for="inputFullName" class="form-label">Ad Soyad</label>
                <input type="text" name="full_name" class="form-control" id="inputFullName" placeholder="Ad Soyad" value="<?= htmlspecialchars($full_name) ?>" required>
            </div>
            
            <div class="col-md-4">
                <label for="inputEmail" class="form-label">Email</label>
                <input type="email" name="email" class="form-control" id="inputEmail" placeholder="ornek@firma.com" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            
            <div class="col-md-4">
                <label for="inputCompany" class="form-label">Firma Seç</label>
                <select name="company_id" class="form-select" id="inputCompany" required>
                    <option value="" disabled <?= $company_id ? '' : 'selected' ?>>Firma Seçin...</option>
                    <?php foreach($companies as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $company_id==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if(!$id): ?>
                <div class="col-md-4">
                    <label for="inputPassword" class="form-label">Şifre</label>
                    <input type="password" name="password" class="form-control" id="inputPassword" placeholder="Şifre" required>
                </div>
            <?php endif; ?>
            
            <div class="col-12 mt-4">
                <button type="submit" name="admin_submit" class="btn btn-<?= $id ? "success" : "primary" ?> w-100">
                    <i class="bi bi-save"></i> <?= $id ? "Yönetici Bilgilerini Güncelle" : "Yeni Yönetici Ekle" ?>
                </button>
            </div>
        </form>
    </div>
</div>

<h4 class="mb-3 mt-5"><i class="bi bi-list-columns"></i> Kayıtlı Firma Yöneticileri</h4>

<?php if (empty($fadmins)): ?>
    <div class="alert alert-info">Sisteme kayıtlı firma yöneticisi bulunmamaktadır.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>Email</th>
                    <th>Firma</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($fadmins as $a): ?>
                <tr>
                    <td><?= $a['id'] ?></td>
                    <td><?= htmlspecialchars($a['full_name']) ?></td>
                    <td><?= htmlspecialchars($a['email']) ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($a['company_name']) ?></span></td>
                    <td>
                        <a href="index.php?tab=firma_admin&edit_admin=<?= $a['id'] ?>" class="btn btn-sm btn-info text-white me-2" title="Düzenle">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <a href="index.php?tab=firma_admin&delete_admin=<?= $a['id'] ?>" 
                           onclick="return confirm('Bu yöneticiyi silmek istediğinizden emin misiniz?')" 
                           class="btn btn-sm btn-danger" title="Sil">
                           <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>