<?php
// DOSYANIN EN BAŞINA OUTPUT BUFFERING EKLEYELİM (Önceki yönlendirme sorununu kesin çözmek için)
ob_start(); 

require '../includes/config.php';
require '../includes/auth.php';

// Kullanıcıyı al ve yetkilendirmeyi kontrol et
$current_user = getUser(); 

if (!isCompanyAdmin()) {
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


$user_company_id = $current_user['company_id'] ?? null;
$user_company_name = 'Bilinmiyor';


if (empty($user_company_id)) {
    header("Location: ../logout.php?msg=" . urlencode("Yönetici yetkiniz var ancak atanmış bir firmanız bulunmamaktadır."));
    exit;
}


try {
    $stmt = $db->prepare("SELECT name FROM Bus_Company WHERE id = ?");
    $stmt->execute([$user_company_id]);
    $company_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($company_data) {
        $user_company_name = $company_data['name'];
        
        if (!isset($current_user['company_name'])) {
            $current_user['company_name'] = $user_company_name;
        }
    }
} catch (Exception $e) {

}


// Sekme seçimi
$tab = $_GET['tab'] ?? 'seferler'; // Varsayılan sekme: seferler

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
    <title>Firma Yönetim Paneli | Otobüs Bilet Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body { background-color: #f4f7f6; }
        .sidebar { background-color: #343a40; min-height: 100vh; }
        .sidebar .nav-link { color: #f8f9fa; border-left: 3px solid transparent; }
        .sidebar .nav-link:hover { color: #ffffff; background-color: #495057; }
        .sidebar .nav-link.active { background-color: #198754; color: #ffffff; border-left-color: #ffc107; font-weight: bold; }
        .page-header { border-bottom: 1px solid #dee2e6; padding-bottom: 1rem; margin-bottom: 1.5rem; }
    </style>
</head>
<body>

<div class="d-flex">
    
    <div class="sidebar text-white p-3 pt-4">
        <h4 class="text-center mb-2 text-warning">Firma Panel</h4>
        <div class="text-center small mb-4 text-white-50"><?= htmlspecialchars($user_company_name) ?></div> <hr class="border-secondary">
        
        <nav class="nav nav-pills flex-column">
            <a class="nav-link<?= setActive($tab, 'seferler') ?>" href="?tab=seferler">
                <i class="bi bi-calendar-range"></i> Sefer Yönetimi
            </a>
            
            <a class="nav-link<?= setActive($tab, 'firma_kupon') ?>" href="?tab=firma_kupon">
                <i class="bi bi-ticket-perforated"></i> Kupon Yönetimi
            </a>
            </nav>

        <div class="mt-auto pt-5">
            <hr class="border-secondary">
            <div class="text-center small text-secondary mb-2 ">
                Hoş Geldiniz, <?= htmlspecialchars($current_user['full_name']) ?>
            </div>
            <a href="../logout.php" class="btn btn-outline-danger w-100">
                <i class="bi bi-box-arrow-right"></i> Çıkış Yap
            </a>
        </div>
    </div>
    
    <div class="flex-grow-1 p-4">
        
        <div class="page-header d-flex justify-content-between align-items-center">
             <h1 class="h3 text-primary"><i class="bi bi-bus-front"></i> Firma Yönetim Paneli</h1>
             <span class="text-muted small">
                 Firma: **<?= htmlspecialchars($user_company_name) ?>**
             </span>
        </div>
        
        <?php
        $message = $_GET['msg'] ?? '';
        $message_type = $_GET['type'] ?? 'success';
        if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card shadow-sm p-4">
            
            <?php
            // Sekme seçimine göre partials dosyasını dahil ediyoruz
            switch($tab) {
                case 'seferler':
                    include 'partials/seferler.php'; 
                    break;
                case 'firma_kupon':
                    include 'partials/firma_kupon.php';
                    break;
                default:
                    echo "<div class='alert alert-danger'>Geçersiz sekme!</div>";
            }
            ?>
        </div>
        
        <footer class="mt-4 pt-3 border-top text-center text-muted small">
            &copy; <?php echo date("Y"); ?> Otobüs Bilet Sistemi | Firma Yönetimi
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Output Buffering'i kapat ve içeriği gönder
ob_end_flush();
?>