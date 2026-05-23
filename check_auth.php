<?php
// เปิดใช้งานระบบ Session ของ PHP เพื่อจำสิทธิ์การล็อกอิน
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * ฟังก์ชันเช็กสิทธิ์ผู้ใช้งานประจำหน้าเว็บต่าง ๆ
 * @param array $allowed_roles รายชื่อสิทธิ์ที่อนุญาตให้เข้าหน้าเว็บนั้นได้ เช่น ['admin', 'scorekeeper']
 */
function checkRole($allowed_roles = []) {
    // 1. ถ้าหน้านั้นเปิดเป็นสาธารณะ (บุคคลทั่วไปดูได้) ไม่ต้องเช็กสิทธิ์
    if (empty($allowed_roles)) {
        return true; 
    }

    // 2. ถ้าเป็นหน้าเฉพาะเจ้าหน้าที่ แต่ผู้ใช้ยังไม่ได้ล็อกอินเลย ให้ดีดกลับไปหน้าหลัก
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php?auth_error=login_required");
        exit();
    }

    // 3. ล็อกอินแล้ว แต่สิทธิ์ (Role) ไม่ตรงกับที่กำหนดไว้ ให้ดีดกลับหน้าหลักพร้อมแจ้งเตือน
    if (!in_array($_SESSION['user_role'], $allowed_roles)) {
        header("Location: index.php?auth_error=denied");
        exit();
    }
}
?>