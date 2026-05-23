<?php
require_once 'db.php';
require_once 'check_auth.php';

checkRole(['admin']);

try {
    $pdo->beginTransaction();

    // ล้างข้อมูลตามลำดับที่ถูกต้อง (จากลูกไปพ่อ)
    $pdo->exec("DELETE FROM match_results");
    $pdo->exec("DELETE FROM athletes");           // สำคัญ! เพิ่มตารางนี้
    $pdo->exec("DELETE FROM matches");
    $pdo->exec("DELETE FROM sport_categories");
    $pdo->exec("DELETE FROM teams");

    // รีเซ็ต AUTO_INCREMENT (optional แต่แนะนำ)
    $pdo->exec("ALTER TABLE match_results AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE athletes AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE matches AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE sport_categories AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE teams AUTO_INCREMENT = 1");

    $pdo->commit();

    // ล้าง cache/session ที่อาจค้าง
    session_regenerate_id(true);

    header("Location: index.php?success=reset");
    exit();
} catch (\PDOException $e) {
    $pdo->rollBack();
    error_log("Reset Error: " . $e->getMessage());
    die("เกิดข้อผิดพลาดในการรีเซ็ตระบบ: " . $e->getMessage());
}
?>
