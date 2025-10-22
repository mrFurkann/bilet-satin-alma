<?php
session_start();

function getUser() {
    global $db;
    if (isset($_SESSION['user']['id'])) {
        $stmt = $db->prepare("SELECT * FROM User WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $_SESSION['user'] = $user; // session güncelle
            return $user;
        }
    }
    return null;
}


// Admin kontrol
function isAdmin() {
    $user = getUser();
    return $user && strtolower($user['role']) === 'admin'; // kullanıcı var mı ve rolu admin mi diye kontrol ediyoruz
}

function isCompanyAdmin(){
    $user = getUser();
    return $user && strtolower($user['role']) === 'company_admin';
}

$is_logged_in = isset($_SESSION['user']) && !empty($_SESSION['user']);
$current_user = $is_logged_in ? $_SESSION['user'] : null;
?>