<?php
require_once 'db.php';
require_once 'check_auth.php';

// ตรวจสอบว่าเป็น Admin เท่านั้น
if ($_SESSION['user_role'] !== 'admin') {
    die("ไม่มีสิทธิ์ใช้งาน");
}

try {
    $pdo->beginTransaction();

    // ล้างข้อมูลตามลำดับความสัมพันธ์ (ป้องกันติด Foreign Key)
    $pdo->exec("DELETE FROM match_results");
    $pdo->exec("DELETE FROM match_participants"); // หากมีตารางนี้
    $pdo->exec("DELETE FROM matches");
    $pdo->exec("DELETE FROM sport_categories");
    $pdo->exec("DELETE FROM teams");

    $pdo->commit();
    header("Location: index.php?success=reset");
} catch (Exception $e) {
    $pdo->rollBack();
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>