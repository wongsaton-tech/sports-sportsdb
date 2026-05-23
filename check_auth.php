<?php
// 1. เปิด Session (ตรวจสอบว่าเปิดหรือยัง)
if (!isset($_SESSION)) {
    session_start();
}

/**
 * ฟังก์ชันเช็กสิทธิ์ผู้ใช้งาน
 * @param array $allowed_roles รายชื่อสิทธิ์ที่อนุญาต เช่น ['admin']
 */
function checkRole($allowed_roles = []) {
    // ถ้าไม่ได้ล็อกอิน ให้ดีดกลับไปหน้า index (หรือหน้า login)
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php?auth_error=login_required");
        exit();
    }

    // ถ้ามีกำหนดสิทธิ์ แต่สิทธิ์ของผู้ใช้ไม่อยู่ในรายชื่อที่อนุญาต ให้ดีดกลับ
    if (!empty($allowed_roles) && !in_array($_SESSION['user_role'], $allowed_roles)) {
        header("Location: index.php?auth_error=denied");
        exit();
    }
}
?>
