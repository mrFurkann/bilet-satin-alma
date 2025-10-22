<?php

require 'includes/config.php';
session_start();

$flash_output='';
$error_message ='';

if (isset($_SESSION['flash_message'])) {
    $msg = $_SESSION['flash_message'];
    $flash_output = '<div class="alert alert-' . $msg['type'] . '" role="alert">' . $msg['text'] . '</div>';
    unset($_SESSION['flash_message']); 
}

    if ($_SERVER['REQUEST_METHOD'] === 'POST') 
    {
        $email=$_POST['email'];
        $password = $_POST['password'];
        $stmt=$db->prepare("SELECT * FROM User WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        
         if ($user && password_verify($password, $user['password'])) 
            {//$user diyerek user var mı yani true mu ve hashlenmiş şifre ile girilen şifre aynı mı
            
                unset($user['password']);  // Gönderdiğimiz Session içinde parolo bilgisi olmasın diye Yaptık
                $_SESSION['user'] = $user;

                $role = strtolower($user['role']);  // role unu aldık çunku giriş yapanın kim olduğunu bilirsek ona göre redirect yapıcaz
                
                
                $redirect_url = 'index.php';

                switch ($role) 
                    {
                        case 'admin':
                            $redirect_url = 'admin/index.php';
                            break;
                        case 'company_admin':
                            $redirect_url = 'firma_admin/index.php';
                            break;
                        default:
                            $redirect_url = 'index.php';
                            break;
                    }
                header('Location: ' . $redirect_url,true,303); //303 girilen istek başarıyla işlendi şimdi yönelndirilebilrisin ve tekrar yönlendirildiğinde değişmez en son doğru kayıtı alır. See other
                exit;
            }else 
                {
                    $error_message = "Girdiğiniz e-posta veya şifre yanlış. Lütfen tekrar deneyin.";
                }   
    }




?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<section class="vh-100 d-flex justify-content-center align-items-center" style="background-color: #f8f9fa;">

    <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
        <div class="card shadow-lg p-4" style="border-radius: 10px;">
            <div class="card-body">
                <h3 class="card-title text-center mb-4">Hesabınıza Giriş Yapın</h3>

                <?php
                
                    echo $flash_output;

                    if ($error_message) {
                        echo '<div class="alert alert-danger" role="alert">'.$error_message.'</div>';
                    }
                
                ?>


                <form method="post">
                    
                    <div class="mb-3">
                        <label for="inputEmail" class="form-label">E mail</label>
                        <input type="email" name="email" class="form-control" id="inputEmail" placeholder="ornek@mail.com" required>
                    </div>

                    <div class="mb-4">
                        <label for="inputPass" class="form-label">Şifre</label>
                        <input type="password" name="password" class="form-control" id="inputPass" placeholder="Şifreniz" required>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">Giriş Yap</button>
                    </div>
                </form>

                <p class="text-center text-muted mt-3 mb-0">
                    Hesabınız yok mu? <a href="register.php" class="text-decoration-none">Şimdi Kaydolun</a>
                </p>


            </div>
        </div>
    </div>
</section>