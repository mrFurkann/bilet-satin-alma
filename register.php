<?php

require 'includes/config.php';

session_start();

$message ='';
$message_type='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);


    // Eposta Kontrolu için oluştuduğumuz kısım
    $check_stmt =  $db -> prepare("SELECT email FROM User WHERE email = ?");
    $check_stmt ->execute([$email]);

    if ($check_stmt->fetch()) {
        $message = 'Bu e-posta adresi zaten kullanılıyor';
        $message_type='danger';
    }else{
        $new_uuid = generate_uuid_v4();

        $stmt = $db->prepare("INSERT INTO User (id,full_name,email,password) VALUES (?,?,?,?)");
        $stmt->execute([$new_uuid,$full_name,$email,$password]);


        $_SESSION['flash_message']=[
            'type' => 'success',
            'text' => 'Kayıt Başarılı Giriş Yapabilirsiniz'
        ];
        header("Location: login.php");
        exit;

    }

}
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<section class="vh-100 d-flex justify-content-center align-items-center" style="background-color: #f8f9fa;">

    <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
        <div class="card shadow-lg p-4" style="border-radius: 10px;">
            <div class="card-body">
                <h3 class="card-title text-center mb-4">Yeni Hesap Oluştur</h3>

                <?php
                    if ($message) {
                        echo '<div class="alert alert-' .$message_type. '" role="alert">'. $message . '</div>';
                    }
                
                
                ?>
                
                <form method="post">
                    <div class="mb-3">
                        <label for="inputName" class="form-label">Ad Soyad</label>
                        <input type="text" name="full_name" id="inputName" class="form-control" placeholder="Ad soyad" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="inputEmail" class="form-label">E mail</label>
                        <input type="email" name="email" class="form-control" id="inputEmail" placeholder="ornek@mail.com" required>
                    </div>

                    <div class="mb-4">
                        <label for="inputPass" class="form-label">Şifre</label>
                        <input type="password" name="password" class="form-control" id="inputPass" placeholder="Şifreniz" required>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Kayıt Ol</button>
                    </div>
                </form>

                <p class="text-center text-muted mt-3 mb-0">
                    Zaten hesabınız var mı? <a href="login.php" class="text-decoration-none">Giriş Yap</a>
                </p>


            </div>
        </div>
    </div>
</section>