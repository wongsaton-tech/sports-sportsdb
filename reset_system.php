<?php
require_once 'db.php';
require_once 'check_auth.php';

checkRole(['admin']);

try {
    $pdo->beginTransaction();

    // ล้างข้อมูลตามลำดับที่ถูกต้อง (จากตารางลูกไปตารางแม่)
    $pdo->exec("DELETE FROM match_results");
    $pdo->exec("DELETE FROM athletes");           // เพิ่มตารางนี้
    $pdo->exec("DELETE FROM matches");
    $pdo->exec("DELETE FROM sport_categories");
    $pdo->exec("DELETE FROM teams");

    // รีเซ็ต AUTO_INCREMENT
    $pdo->exec("ALTER TABLE match_results AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE athletes AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE matches AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE sport_categories AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE teams AUTO_INCREMENT = 1");

    $pdo->commit();

    // รีเฟรช session
    session_regenerate_id(true);

    header("Location: index.php?success=reset");
    exit();

} catch (\PDOException $e) {
    // ป้องกัน error rollBack เมื่อไม่มี transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Reset System Error: " . $e->getMessage());
    die("❌ เกิดข้อผิดพลาดในการรีเซ็ตระบบ: " . $e->getMessage());
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("❌ เกิดข้อผิดพลาด: " . $e->getMessage());
}
?>
