<?php
require_once 'db.php';
require_once 'check_auth.php';

date_default_timezone_set('Asia/Bangkok');
$current_time = date('Y-m-d H:i:s');

// ตัวแปรเริ่มต้น
$teams_dashboard = [];
$calendar_timeline = [];
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'guest';
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'บุคคลทั่วไป';

// ดึงข้อมูลทีมสี (ใช้ try-catch ครอบเพื่อป้องกัน Error)
try {
    $sql_teams = "SELECT id, team_name, color FROM teams ORDER BY id ASC";
    $teams_dashboard = $pdo->query($sql_teams)->fetchAll();
} catch (Exception $e) {
    $teams_dashboard = [];
}

// ดึงข้อมูลแมตช์ (ใช้ try-catch ครอบ)
try {
    $sql_matches = "SELECT * FROM matches ORDER BY date_time ASC";
    $matches = $pdo->query($sql_matches)->fetchAll();
    
    foreach ($matches as $match) {
        $match['computed_time'] = $match['date_time'] ?? '';
        $match['computed_name'] = $match['match_name'] ?? 'รายการแข่งขัน';
        
        $match['status_label'] = 'เปิดลงทะเบียน';
        $match['status_class'] = 'bg-success bg-opacity-10 text-success';
        
        $calendar_timeline[] = $match;
    }
} catch (Exception $e) {
    $calendar_timeline = [];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>SportsDay Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark p-3">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">🏆 SportsDay Center</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="card p-4">
        <h3>Dashboard</h3>
        <p>ยินดีต้อนรับ: <?php echo htmlspecialchars($user_name); ?></p>
        
        <hr>
        
        <h5>รายการแข่งขัน</h5>
        <table class="table">
            <thead>
                <tr><th>รายการ</th><th>วันเวลา</th><th>สถานะ</th></tr>
            </thead>
            <tbody>
                <?php if (!empty($calendar_timeline)): foreach ($calendar_timeline as $m): ?>
                <tr>
                    <td><?php echo htmlspecialchars($m['computed_name']); ?></td>
                    <td><?php echo htmlspecialchars($m['computed_time']); ?></td>
                    <td><span class="badge <?php echo $m['status_class']; ?>"><?php echo $m['status_label']; ?></span></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="3">ยังไม่มีข้อมูลรายการแข่งขัน</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
