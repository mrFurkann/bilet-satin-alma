<?php

require '../includes/config.php';
require '../includes/auth.php';
require '../includes/fpdf/fpdf.php';


$current_user = getUser();
$ticket_id =$_GET['ticket_id'] ?? null;

if (!$current_user || !$ticket_id) {
    die("Yetkisiz Erişim veya Bilet Bilgisi Eksik.");

}

$user_id = $current_user['id'];

// Bilet Detayı Çekicez

$stmt= $db->prepare("
    SELECT
        t.total_price, t.created_at, t.status, tr.departure_city, tr.destination_city, tr.departure_time, tr.arrival_time, bs.seat_number, bc.name AS company_name
    FROM
        Tickets t
    JOIN
        Trips tr ON t.trip_id = tr.id
    JOIN
        Bus_Company bc ON tr.company_id = bc.id
    JOIN
        Booked_Seats bs ON bs.ticket_id = t.id
    WHERE
        t.id = ? AND t.user_id = ?
"); 

$stmt->execute([$ticket_id,$user_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);


if(!$ticket){
    die("Bilet bulunamadı");
}


//PDF Oluşturma
function turkce_karakter_cevir($metin) {
    
    $aranan = array(
        'ç', 'Ç', 
        'ğ', 'Ğ', 
        'ı', 'I', 
        'İ', 'i', 
        'ö', 'Ö', 
        'ş', 'Ş', 
        'ü', 'Ü'
    );
    
   
    $degistir = array(
        'c', 'C', 
        'g', 'G', 
        'i', 'I', 
        'I', 'i',
        'o', 'O', 
        's', 'S', 
        'u', 'U'
    );
    
    // str_replace ile tüm çevrimi tek seferde yapıyoruz
    return str_replace($aranan, $degistir, $metin);
}




$pdf = new FPDF('P', 'mm', 'A4');

$pdf->SetDisplayMode('fullpage');
$pdf->SetTitle("Otobus Biletiniz");
$pdf->SetAuthor("Yavuzlar Bilet Sistemi");
$pdf->SetCreator("FPDF ile oluşturulmuştur");


$pdf->AddFont('DejaVu', '', 'DejaVuSans.php');


$pdf->AddPage();


$pdf->SetFont('DejaVu', '', 16); 
$pdf->SetFillColor(200, 220, 255); 
$pdf->SetTextColor(0, 0, 0); 

// --- Başlık ---

$pdf->Cell(0, 10, 'Online Otobus Biletiniz - Fatura/Bilet', 1, 1, 'C', true);
$pdf->Ln(5);

// --- Bilet Detayları Başlığı ---
$pdf->SetFont('DejaVu', '', 14); 
$pdf->Cell(0, 8, 'Seyahat Bilgileri', 0, 1, 'L');
$pdf->SetDrawColor(100, 100, 100);
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(5);

// --- Bilet Bilgileri ---
$pdf->SetFont('DejaVu', '', 12);
$pdf->SetTextColor(50, 50, 50);

// İlk Sütun (Başlıklar)
$column1_width = 50;
$column2_width = 140;

$pdf->Cell($column1_width, 8, 'Bilet Numarasi:', 0, 0);
$pdf->Cell($column2_width, 8, $ticket_id, 0, 1);

$pdf->Cell($column1_width, 8, 'Firma:', 0, 0);
$pdf->Cell($column2_width, 8, $ticket['company_name'], 0, 1);

$pdf->Cell($column1_width, 8, 'Kalkis Sehri:', 0, 0);
$pdf->Cell($column2_width, 8, turkce_karakter_cevir($ticket['departure_city']), 0, 1);

$pdf->Cell($column1_width, 8, 'Varis Sehri:', 0, 0);
$pdf->Cell($column2_width, 8, turkce_karakter_cevir($ticket['destination_city']), 0, 1);

$pdf->Cell($column1_width, 8, 'Kalkis Zamani:', 0, 0);
$pdf->Cell($column2_width, 8, date('d.m.Y H:i', strtotime($ticket['departure_time'])), 0, 1);

$pdf->Cell($column1_width, 8, 'Tahmini Varis:', 0, 0);
$pdf->Cell($column2_width, 8, date('d.m.Y H:i', strtotime($ticket['arrival_time'])), 0, 1);

$pdf->Cell($column1_width, 8, 'Koltuk No:', 0, 0);
$pdf->SetFont('DejaVu', '', 14); 
$pdf->SetTextColor(200, 50, 50);
$pdf->Cell($column2_width, 8, $ticket['seat_number'], 0, 1);

$pdf->Ln(10);

// --- Fiyat ve Ödeme Bilgileri ---
$pdf->SetFont('DejaVu', '', 14); 
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 8, 'Odeme Ozeti', 0, 1, 'L');
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(5);

$pdf->SetFont('DejaVu', '', 12);

$pdf->Cell($column1_width, 8, 'Toplam Ucret:', 0, 0);
$pdf->SetFont('DejaVu', '', 12); 
$pdf->SetTextColor(34, 139, 34); 
$pdf->Cell($column2_width, 8, number_format($ticket['total_price'], 2) . ' TL', 0, 1);

$pdf->SetFont('DejaVu', '', 12);
$pdf->SetTextColor(50, 50, 50);

$pdf->Cell($column1_width, 8, 'Odeme Tarihi:', 0, 0);
$pdf->Cell($column2_width, 8, date('d.m.Y H:i', strtotime($ticket['created_at'])), 0, 1);

$pdf->Cell($column1_width, 8, 'Durum:', 0, 0);
$pdf->Cell($column2_width, 8, $ticket['status'], 0, 1);

$pdf->Ln(15);

// --- Yolcu Bilgisi ---
$pdf->SetFont('DejaVu', '', 14); 
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 8, 'Yolcu Bilgisi', 0, 1, 'L');
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(5);

$pdf->SetFont('DejaVu', '', 12);
$pdf->SetTextColor(50, 50, 50);

$pdf->Cell($column1_width, 8, 'Ad Soyad:', 0, 0);
$pdf->Cell($column2_width, 8, turkce_karakter_cevir($current_user['full_name']) ?? 'Bilinmiyor', 0, 1);


$pdf->Ln(10);
$pdf->SetFont('DejaVu', '', 10);
$pdf->Cell(0, 5, '* Bu bilet, fatura yerine gecmektedir. iyi yolculuklar dileriz!', 0, 1, 'C');


$pdf->Output();

?>

