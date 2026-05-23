<?php
if (!isset($_SESSION)) { session_start(); }
require_once 'db.php';

// ดึงข้อมูลเบื้องต้น
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'guest';
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'บุคคลทั่วไป';

// 1. ดึงข้อมูลตารางการแข่งขัน
$matches = [];
try {
    $stmt = $pdo->query("SELECT * FROM matches ORDER BY match_datetime ASC");
    $matches = $stmt->fetchAll();
} catch (Exception $e) {}

// 2. ดึงข้อมูลทีมและคะแนน
$teams = [];
try {
    $stmt = $pdo->query("SELECT * FROM teams ORDER BY id ASC");
    $teams = $stmt->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>SportsDay Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    
    <div class="mb-4">
        <h1 class="fw-bold text-primary">SportsDay Management System</h1>
        <div class="alert alert-info">
            ยินดีต้อนรับ: <b><?php echo htmlspecialchars($user_name); ?></b> | 
            สิทธิ์การใช้งาน: <span class="badge bg-dark"><?php echo strtoupper($user_role); ?></span>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-success text-white fw-bold">ตารางการแข่งขัน</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>รายการ</th><th>วันเวลา</th></tr></thead>
                <tbody>
                    <?php foreach ($matches as $m): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($m['match_name'] ?? $m['sport_name'] ?? 'ไม่มีชื่อ'); ?></td>
                        <td><?php echo htmlspecialchars($m['match_datetime'] ?? $m['date_time'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning fw-bold">ตารางสรุปคะแนน</div>
                <table class="table mb-0">
                    <thead><tr><th>ทีมสี</th><th>คะแนน</th></tr></thead>
                    <tbody>
                        <?php foreach ($teams as $t): ?>
                        <tr>
                            <td><span class="badge" style="background-color:<?php echo $t['color']; ?>;">&nbsp;</span> <?php echo htmlspecialchars($t['team_name']); ?></td>
                            <td>0</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white fw-bold">เมนูจัดการ</div>
                <div class="list-group list-group-flush">
                    <?php if ($user_role == 'admin'): ?>
                        <a href="manage_matches.php" class="list-group-item list-group-item-action">จัดการการแข่งขัน</a>
                        <a href="manage_teams.php" class="list-group-item list-group-item-action">จัดการทีมสี</a>
                    <?php endif; ?>
                    <a href="logout.php" class="list-group-item list-group-item-action text-danger">ออกจากระบบ</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
