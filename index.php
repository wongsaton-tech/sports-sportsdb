<?php
require_once 'db.php';
require_once 'check_auth.php';

date_default_timezone_set('Asia/Bangkok');
$current_time = date('Y-m-d H:i:s');

try {
    $sql_scores = "SELECT t.id, t.team_name, t.team_color,
            SUM(CASE WHEN r.medal = 'ทอง' THEN 1 ELSE 0 END) as gold_count,
            SUM(CASE WHEN r.medal = 'เงิน' THEN 1 ELSE 0 END) as silver_count,
            SUM(CASE WHEN r.medal = 'ทองแดง' THEN 1 ELSE 0 END) as bronze_count,
            COALESCE(SUM(r.points), 0) as total_points
            FROM teams t
            LEFT JOIN match_results r ON t.id = r.team_id
            GROUP BY t.id, t.team_name, t.team_color
            ORDER BY total_points DESC, gold_count DESC, t.id ASC";
            
    $stmt_scores = $pdo->query($sql_scores);
    $teams_dashboard = $stmt_scores->fetchAll();
    $has_scores = false;
    foreach ($teams_dashboard as $t) {
        if ($t['total_points'] > 0 || $t['gold_count'] > 0) { $has_scores = true; break; }
    }

    $sql_matches = "SELECT m.*, c.category_name, COUNT(r.id) as is_finished FROM matches m 
                    LEFT JOIN sport_categories c ON m.category_id = c.id 
                    LEFT JOIN match_results r ON m.id = r.match_id
                    GROUP BY m.id ORDER BY m.match_datetime ASC";
    $all_matches = $pdo->query($sql_matches)->fetchAll();
    $upcoming_list = []; $finished_list = [];

    foreach ($all_matches as $match) {
        $status_label = ($match['is_finished'] > 0) ? 'จบการแข่งขัน' : 'รอการบันทึกผล';
        $status_class = ($match['is_finished'] > 0) ? 'bg-dark bg-opacity-10 text-dark' : 'bg-warning bg-opacity-20 text-warning-emphasis';
        $match['status_label'] = $status_label;
        $match['status_class'] = $status_class;
        if ($status_label == 'จบการแข่งขัน') $finished_list[] = $match; else $upcoming_list[] = $match;
    }
    $calendar_timeline = array_merge($upcoming_list, $finished_list);
} catch (\PDOException $e) { $teams_dashboard = []; $calendar_timeline = []; $has_scores = false; }

$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'guest';
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'บุคคลทั่วไป';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportsDay Control Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f2f2f7; color: #1c1c1e; }
        .ios-card { background: #ffffff; border-radius: 16px !important; border: none !important; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04) !important; }
        .menu-card { transition: all 0.25s ease-in-out; cursor: pointer; }
        .menu-card:hover { transform: scale(1.02); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="index.php">🏆 SportsDay Center</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link active" href="index.php">แดชบอร์ด</a></li>
                <?php if ($user_role == 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="manage_users.php">จัดการผู้ใช้งาน</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_teams.php">ทีมสี</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="p-4 bg-white rounded-4 shadow-sm mb-4">
        <h1 class="fw-bold text-dark">Dashboard</h1>
        <p class="text-muted">ยินดีต้อนรับ: <?php echo $user_name; ?> (สิทธิ์: <?php echo strtoupper($user_role); ?>)</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card ios-card p-3">
                <h5><i class="fa-solid fa-award text-primary me-2"></i> บอร์ดสรุปเหรียญ</h5>
                <table class="table table-hover mt-3">
                    <thead><tr><th>ทีมสี</th><th>ทอง</th><th>เงิน</th><th>ทองแดง</th><th>คะแนน</th></tr></thead>
                    <tbody>
                        <?php foreach ($teams_dashboard as $t): ?>
                        <tr>
                            <td><span class="badge" style="background-color:<?php echo $t['team_color'];?>; width:10px; height:10px; border-radius:50%"></span> <?php echo $t['team_name'];?></td>
                            <td><?php echo $t['gold_count'];?></td><td><?php echo $t['silver_count'];?></td><td><?php echo $t['bronze_count'];?></td>
                            <td class="fw-bold text-primary"><?php echo $t['total_points'];?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-5">
            <?php if ($user_role == 'admin'): ?>
                <div class="card ios-card menu-card border-0 mb-3" onclick="location.href='manage_users.php'">
                    <div class="card-body d-flex align-items-center"><i class="fa-solid fa-users-gear text-info me-3"></i> <div><h6>จัดการผู้ใช้งาน</h6></div></div>
                </div>
                <div class="card ios-card menu-card border-0 mb-3" onclick="confirmReset()">
                    <div class="card-body d-flex align-items-center text-danger"><i class="fa-solid fa-trash-can me-3"></i> <div><h6>ล้างข้อมูลการแข่งขันทั้งหมด</h6></div></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmReset() {
    if (confirm('⚠️ คำเตือน: ข้อมูลการแข่งขันจะถูกล้างทั้งหมด! ยืนยันหรือไม่?')) {
        window.location.href = 'reset_system.php';
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
