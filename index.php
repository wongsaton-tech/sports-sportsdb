<?php
if (!isset($_SESSION)) { session_start(); }
require_once 'db.php';

// ดึงข้อมูลการแข่งขันแบบปลอดภัย
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
    <title>SportsDay Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f2f2f7; font-family: sans-serif; }
        .ios-card { background: #ffffff; border-radius: 16px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .navbar { background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🏆 SportsDay Center</a>
        <div class="navbar-nav ms-auto">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="nav-link text-dark me-3">สวัสดี, <?php echo htmlspecialchars($user_name); ?></span>
                <a href="logout.php" class="nav-link text-danger"><i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ</a>
            <?php else: ?>
                <a href="login.php" class="nav-link text-primary"><i class="fa-solid fa-right-to-bracket"></i> ล็อคอิน</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="mb-4">
        <div class="card ios-card p-3 d-inline-block">
            สถานะ: <span class="badge bg-dark"><?php echo strtoupper($user_role); ?></span>
        </div>
    </div>

    <div class="card ios-card p-4 mb-4">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-calendar-days text-success me-2"></i> ตารางการแข่งขัน</h5>
        <div class="table-responsive">
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
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card ios-card p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-medal text-warning me-2"></i> สรุปคะแนนทีมสี</h5>
                <table class="table">
                    <thead><tr><th>ทีมสี</th><th>คะแนน</th></tr></thead>
                    <tbody>
                        <?php foreach ($teams as $t): ?>
                        <tr>
                            <td><span class="badge" style="background-color:<?php echo htmlspecialchars($t['color'] ?? '#8e8e93'); ?>;">&nbsp;</span> <?php echo htmlspecialchars($t['team_name'] ?? $t['name'] ?? '-'); ?></td>
                            <td>0</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card ios-card p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-bars text-dark me-2"></i> เมนูจัดการ</h5>
                <div class="d-grid gap-2">
                    <?php if ($user_role == 'admin'): ?>
                        <a href="manage_matches.php" class="btn btn-outline-dark">จัดการการแข่งขัน</a>
                        <a href="manage_teams.php" class="btn btn-outline-dark">จัดการทีมสี</a>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-primary">หน้าหลัก</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
