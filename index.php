<?php
if (!isset($_SESSION)) { session_start(); }
require_once 'db.php';

// ดึงข้อมูลการแข่งขัน
$matches = [];
try {
    $stmt = $pdo->query("SELECT * FROM matches ORDER BY match_datetime ASC");
    $matches = $stmt->fetchAll();
} catch (Exception $e) {}

// ดึงข้อมูลทีม
$teams = [];
try {
    $stmt = $pdo->query("SELECT * FROM teams ORDER BY id ASC");
    $teams = $stmt->fetchAll();
} catch (Exception $e) {}

$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'guest';
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'บุคคลทั่วไป';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f2f2f7; font-family: sans-serif; }
        .ios-card { background: #ffffff; border-radius: 16px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        /* ป้องกัน Error เวลาเมาส์ชี้โดยใช้สีพื้นหลังปกติ */
        .table-hover tbody tr:hover { background-color: #f8f9fa !important; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="mb-4">
        <h2 class="fw-bold">Dashboard</h2>
        <div class="card ios-card p-3 d-inline-block">
            ผู้ใช้งาน: <b><?php echo htmlspecialchars($user_name); ?></b> | 
            สิทธิ์: <span class="badge bg-dark"><?php echo strtoupper($user_role); ?></span>
        </div>
    </div>

    <div class="card ios-card p-4 mb-4">
        <h5 class="fw-bold mb-3">ตารางการแข่งขัน</h5>
        <table class="table table-hover">
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

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card ios-card p-4">
                <h5 class="fw-bold mb-3">สรุปคะแนนทีมสี</h5>
                <table class="table">
                    <thead><tr><th>ทีมสี</th><th>คะแนน</th></tr></thead>
                    <tbody>
                        <?php foreach ($teams as $t): ?>
                        <tr>
                            <td><span class="badge" style="background-color:<?php echo htmlspecialchars($t['color'] ?? '#8e8e93'); ?>;">&nbsp;</span> <?php echo htmlspecialchars($t['team_name'] ?? $t['name'] ?? 'ไม่มีชื่อทีม'); ?></td>
                            <td>0</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card ios-card p-4">
                <h5 class="fw-bold mb-3">เมนู</h5>
                <div class="d-grid gap-2">
                    <?php if ($user_role == 'admin'): ?>
                        <a href="manage_matches.php" class="btn btn-outline-dark">จัดการการแข่งขัน</a>
                        <a href="manage_teams.php" class="btn btn-outline-dark">จัดการทีมสี</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-danger">ออกจากระบบ</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
