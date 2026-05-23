<?php
require_once 'db.php';
require('fpdf/fpdf.php'); // ต้องมีไฟล์ไลบรารี FPDF อยู่ในโฟลเดอร์

// รับค่า ID ของแมตช์ที่ต้องการออกเกียรติบัตร
$match_id = $_GET['match_id'] ?? 0;

// ดึงข้อมูลรายการแข่งขันและรายชื่อนักกีฬาจากฐานข้อมูล
$sql = "SELECT m.match_name, r.athlete_name, r.medal 
        FROM match_results r
        JOIN matches m ON r.match_id = m.id
        WHERE m.id = :match_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['match_id' => $match_id]);
$results = $stmt->fetchAll();

if (!$results) { die("ไม่พบข้อมูลนักกีฬาในรายการนี้"); }

// เริ่มสร้าง PDF ขนาด A4 แนวนอน
$pdf = new FPDF('L', 'mm', 'A4');

foreach ($results as $row) {
    $pdf->AddPage();
    
    // 1. ใส่พื้นหลังเกียรติบัตร (แนะนำให้เซฟรูปเกียรติบัตรเป็น .jpg แล้ววางทับ)
    // $pdf->Image('certificate_template.jpg', 0, 0, 297, 210);

    // 2. ตั้งค่าฟอนต์ (สำหรับภาษาไทย ต้องใช้ไลบรารีที่รองรับฟอนต์ไทย เช่น TCPDF หรือ FPDF ตัวเสริม)
    $pdf->SetFont('Arial', 'B', 30);
    $pdf->Cell(0, 80, iconv('UTF-8', 'TIS-620', 'เกียรติบัตรผู้ชนะการแข่งขัน'), 0, 1, 'C');
    
    $pdf->SetFont('Arial', '', 20);
    $pdf->Cell(0, 20, iconv('UTF-8', 'TIS-620', 'มอบให้แก่: ' . $row['athlete_name']), 0, 1, 'C');
    
    $pdf->SetFont('Arial', 'I', 20);
    $pdf->Cell(0, 20, iconv('UTF-8', 'TIS-620', 'ในรายการ: ' . $row['match_name']), 0, 1, 'C');
    
    $pdf->SetFont('Arial', 'B', 25);
    $pdf->Cell(0, 30, iconv('UTF-8', 'TIS-620', 'รางวัล: ' . $row['medal']), 0, 1, 'C');
}

// สั่งให้เบราว์เซอร์ดาวน์โหลดไฟล์ PDF
$pdf->Output('D', 'Certificate_List.pdf');
?>