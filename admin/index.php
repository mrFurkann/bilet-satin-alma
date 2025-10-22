<?php
ob_start();

require '../includes/config.php';
require '../includes/auth.php';
if (!isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo '
    <!DOCTYPE html>
        <html>
            <head>
                <meta charset="UTF-8">
                <title>403 - Erişim Engellendi</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    .error-container {
                        min-height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background-color: #f8f9fa; /* Açık gri arka plan */
                    }
                    .error-box {
                        background: #fff;
                        padding: 50px;
                        border-radius: 10px;
                        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                    }
                </style>
            </head>
            <body>
                <div class="error-container">
                    <div class="error-box text-center">
                        <h1 class="display-1 fw-bold text-danger">403</h1>
                        <h2 class="mb-3">Erişim Engellendi</h2>
                        <p class="lead mb-4">Üzgünüz, bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</p>
                        <p class="text-muted">Lütfen yönetici ile iletişime geçin veya ana sayfaya geri dönün.</p>
                        
                        <a href="../index.php" class="btn btn-primary btn-lg mt-3">
                            <i class="bi bi-house-door-fill"></i> Ana Sayfaya Dön
                        </a>
                        
                        </div>
                </div>
            </body>
        </html>';
    exit; 
}

// Sekme seçimi
$tab = $_GET['tab'] ?? 'firmalar';

// Aktif sekme için sınıf tanımlama fonksiyonu
function setActive($current_tab, $tab_name) {
    return $current_tab === $tab_name ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Paneli | Otobüs Bilet Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body { background-color: #f4f7f6; }
        .sidebar { background-color: #343a40; min-height: 100vh; }
        .sidebar .nav-link { color: #f8f9fa; border-left: 3px solid transparent; }
        .sidebar .nav-link:hover { color: #ffffff; background-color: #495057; }
        .sidebar .nav-link.active { background-color: #0d6efd; color: #ffffff; border-left-color: #ffffff; font-weight: bold; }
        .page-header { border-bottom: 1px solid #dee2e6; padding-bottom: 1rem; margin-bottom: 1.5rem; }
    </style>
</head>
<body>

<div class="d-flex">
    
    <div class="sidebar text-white p-3 pt-4">
        <h4 class="text-center mb-4 text-warning">Admin Panel</h4>
        <hr class="border-secondary">
        
        <nav class="nav nav-pills flex-column">
            <a class="nav-link<?= setActive($tab, 'firmalar') ?>" href="?tab=firmalar">
                <i class="bi bi-building"></i> Firmalar
            </a>
            <a class="nav-link<?= setActive($tab, 'firma_admin') ?>" href="?tab=firma_admin">
                <i class="bi bi-person-gear"></i> Firma Adminleri
            </a>
            <a class="nav-link<?= setActive($tab, 'kuponlar') ?>" href="?tab=kuponlar">
                <i class="bi bi-ticket-perforated"></i> Kuponlar
            </a>
        </nav>

        <div class="mt-auto pt-5">
            <hr class="border-secondary">
            <a href="../logout.php" class="btn btn-outline-light w-100">
                <i class="bi bi-box-arrow-right"></i> Çıkış Yap
            </a>
        </div>
    </div>
    <div class="flex-grow-1 p-4">
        
        <div class="page-header d-flex justify-content-between align-items-center">
             <h1 class="h3 text-primary"><i class="bi bi-speedometer2"></i> Yönetici Paneli</h1>
             <span class="text-muted small">Hoş geldiniz, Admin!</span>
        </div>
        
        <div class="card shadow-sm p-4">
            
            <?php
            switch($tab) {
                case 'firmalar':
                    echo '<h4 class="mb-4 text-dark"><i class="bi bi-building"></i> Otobüs Firmaları Yönetimi</h4>';
                    include 'partials/firmalar.php';
                    break;
                case 'firma_admin':
                    echo '<h4 class="mb-4 text-dark"><i class="bi bi-person-gear"></i> Firma Yöneticileri</h4>';
                    include 'partials/firma_admin.php';
                    break;
                case 'kuponlar':
                    echo '<h4 class="mb-4 text-dark"><i class="bi bi-ticket-perforated"></i> Kupon Kodları</h4>';
                    include 'partials/kuponlar.php';
                    break;
                default:
                    echo "<div class='alert alert-danger'>Geçersiz sekme!</div>";
            }
            ?>
        </div>
        <footer class="mt-4 pt-3 border-top text-center text-muted small">
            &copy; <?php echo date("Y"); ?> Otobüs Bilet Sistemi Yönetimi
        </footer>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>