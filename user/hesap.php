<?php
require '../includes/config.php';
require '../includes/auth.php';

$current_user = getUser();
if (!$current_user) {
    header("Location: ../login.php");
    exit;
}

$full_name = $current_user['full_name'];
$email = $current_user['email'];
$role= $current_user['role'];
$balance = $current_user['balance'];
switch ($role) {
    case 'user':
        $role='Yolcu';
        break;
    case 'admin':
        $role = 'Admin';
        break;
    case 'company_admin':
        $role = 'Firma Admin';
    default:
        break;
}
?>

<?php include("../includes/header.php");?>

<div class="container mt-5">
    <h2 class="mb-4">
        <i class="bi bi-person-circle m-2"></i>Hesap Bilgilerim
    </h2>
    <hr>

    <div class="row">


        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-wihte">
                <h4>Kullanıcı Detayları</h4>
            </div>
            <div class="card-body">
                <p class="fs-5">
                    <i class="bi bi-person me-2 text-primary"></i>Ad Soyad: <?= htmlspecialchars($full_name);?>
                </p>

                <p class="fs-5">
                    <i class="bi bi-envelope me-2 text-primary"></i>E-posta: <?= htmlspecialchars($email);?>
                </p>

                <p class="fs-5">
                    <i class="bi bi-person-badge me-2 text-primary"></i> Kullanıcı Türü: <?= htmlspecialchars($role);?>
                </p>
            </div>
            </div>
        </div> <!-- Kullanıcı Detayı -->
        

        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h4>Sanal Cüzdan Bakiyesi</h4>
                </div>

                <div class="card-body text-center">
                    
                    <p class="fs-1 text-success fw-bold my-3">
                        <?= htmlspecialchars(number_format($balance,2,",","."));?> ₺
                    </p>

                    <p class="text-muted">Bu bakiye ile bilet satın alma işlemi yapabilirsiniz</p>
                </div>
            </div>
        </div><!-- Kullanıcı Cüzdan Bakiye Detayı-->


        <div class="col-12">
            <a href="biletler.php" class="btn btn-info text-white me-2">
                <i class="bi bi-ticket-detailed"></i> Biletlerimi Yönet
            </a>
        </div>




    </div><!-- ROW -->
</div><!-- CONTAİNER -->