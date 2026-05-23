<?php
// 1. กำหนดค่าการเชื่อมต่อฐานข้อมูลจาก Aiven (ใช้ข้อมูลจริงของคุณ)
$host     = 'sportsdb-leaderboardpro.f.aivencloud.com';
$port     = '24312';
$dbname   = 'defaultdb';
$username = 'avnadmin';
$password = 'AVNS_xWqtJ4ATVSH-Z21LNpL'; 

// 2. ตั้งค่า Data Source Name (DSN) สำหรับ MySQL
$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

// 3. ตั้งค่าการเชื่อมต่อเพื่อบังคับใช้ SSL Mode ตามเงื่อนไขความปลอดภัยของ Aiven
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // เปิดการแจ้งเตือน Error แบบละเอียด
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // ดึงข้อมูลในรูปแบบ Array
];

try {
    // 4. เริ่มทำการเชื่อมต่อฐานข้อมูลผ่าน PDO
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // ถ้าการเชื่อมต่อมีปัญหา ให้แสดงข้อผิดพลาดออกมาทางหน้าจอ
    die("ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . $e->getMessage());
}
?>
