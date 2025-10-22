<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_PATH ?>/index.php">
            <i class="bi bi-bus-front-fill me-2"></i> Yavuzlar Bilet
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_PATH ?>/index.php">
                        <i class="bi bi-house-door-fill"></i> Ana Sayfa
                    </a>
                </li>

                <?php if ($is_logged_in): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($current_user['full_name'] ?? 'Profilim') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li>
                                <a class="dropdown-item" href="<?= BASE_PATH ?>/user/hesap.php">
                                    <i class="bi bi-wallet me-2"></i> Hesabım / Bakiye (<?= number_format($current_user['balance'] ?? 0, 2, ',', '.') ?> ₺)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_PATH ?>/user/biletler.php">
                                    <i class="bi bi-ticket-detailed me-2"></i> Biletlerim
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= BASE_PATH ?>/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> Çıkış Yap
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-success text-white ms-2" href="login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Giriş Yap
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ms-2" href="register.php">
                            <i class="bi bi-person-plus"></i> Kayıt Ol
                        </a>
                    </li>
                <?php endif; ?>
                
                
            </ul>
        </div>
    </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>