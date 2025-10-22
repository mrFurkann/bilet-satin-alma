<?php 

date_default_timezone_set('Europe/Istanbul');
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '');

}
try{
    $db = new PDO('sqlite:'  . __DIR__ . '/../database/otobus-bilet-sistemi.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //hata fırlatırsa görmek için kullanıldı
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC); // bu bize sutun adları ile erişmemeizi sağlar $row['isim] gibi 
    $db->exec('PRAGMA foreign_keys = ON;'); // Foreign keyleri aktif ettik

}catch(PDOException $e){
    die("Veritabanı hatası: " .$e->getMessage());
}

if (!function_exists('generate_uuid_v4')) {  // Buradai ! ile fonksiyon daha önce tanımlıysa tekrardan çalışmaz. Ve bize hata vermez
    function generate_uuid_v4() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4 bit
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant bit
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}




?>