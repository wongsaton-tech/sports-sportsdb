<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// ล้างข้อมูลเซสชันทั้งหมด
$_SESSION = array();
session_destroy();

// ส่งกลับไปหน้าหลัก
header("Location: index.php");
exit();