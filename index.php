<?php
require_once 'db.php';
require_once 'check_auth.php';

date_default_timezone_set('Asia/Bangkok');
$current_time = date('Y-m-d H:i:s');

// ดึงข้อมูลสรุปเหรียญ
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
    $teams_dashboard = $pdo->query($sql_scores)->fetchAll();
} catch (\PDOException $e) { $teams_dashboard = []; }

// ดึงข้อมูลรายการแข่งขัน (ส่วนที่หายไป)
$sql_matches = "SELECT m.*, c.category_name, COUNT(r.id) as is_finished FROM matches m 
                LEFT JOIN sport_categories c ON m.category_id = c.id 
                LEFT JOIN match_results r ON m.id = r.match_id
                GROUP BY m.id ORDER BY m.match_datetime ASC";
$all_matches = $pdo->query($sql_matches)->fetchAll();

foreach ($all_matches as &$m) {
    $m['status_label'] = ($m['is_finished'] > 0) ? 'จบการแข่งขัน' : 'รอการบันทึกผล';
    $m['status_class'] = ($m['is_finished'] > 0) ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning';
}

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
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f2f2f7; }
        .ios-card { background: #ffffff; border-radius: 16px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .menu-card { transition: transform 0.2s; cursor: pointer; }
        .menu-card:hover { transform: translateY(-2px); }
        /* ปรับระยะห่าง Card ฝั่งขวาให้ชัดเจน */
        .right-menu-container { display: flex; flex-direction: column; gap: 16px; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Dashboard</h2>
            <p class="text-muted mb-0">ยินดีต้อนรับคุณ <?php echo $user_name; ?></p>
        </div>
        <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card ios-card p-4">
                <h5 class="mb-3 fw-bold"><i class="fa-solid fa-medal text-warning me-2"></i> สรุปเหรียญรางวัล</h5>
                <table class="table align-middle">
                    <thead><tr><th>ทีมสี</th><th>ทอง</th><th>เงิน</th><th>ทองแดง</th><th class="text-primary">รวม</th></tr></thead>
                    <tbody>
                        <?php foreach ($teams_dashboard as $t): ?>
                        <tr>
                            <td><span class="badge rounded-circle p-2 me-2" style="background-color:<?php echo $t['team_color'];?>"></span> <?php echo $t['team_name'];?></td>
                            <td><?php echo $t['gold_count'];?></td><td><?php echo $t['silver_count'];?></td><td><?php echo $t['bronze_count'];?></td>
                            <td class="fw-bold text-primary"><?php echo $t['total_points'];?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="right-menu-container">
                <div class="card ios-card menu-card border-0" onclick="location.href='manage_scores.php'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="bg-warning bg-opacity-10 text-warning rounded-4 p-3 me-3"><i class="fa-solid fa-star fa-lg"></i></div>
                        <div><h6 class="mb-0 fw-bold">บันทึกคะแนน</h6><p class="mb-0 text-muted small">คีย์เหรียญและรางวัล</p></div>
                    </div>
                </div>

                <?php if ($user_role == 'admin'): ?>
                <div class="card ios-card menu-card border-0" onclick="location.href='manage_users.php'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="bg-info bg-opacity-10 text-info rounded-4 p-3 me-3"><i class="fa-solid fa-users-gear fa-lg"></i></div>
                        <div><h6 class="mb-0 fw-bold">จัดการผู้ใช้งาน</h6><p class="mb-0 text-muted small">เพิ่ม/ลบ สิทธิ์เจ้าหน้าที่</p></div>
                    </div>
                </div>

                <div class="card ios-card menu-card border-0" onclick="confirmReset()">
                    <div class="card-body d-flex align-items-center p-3 text-danger">
                        <div class="bg-danger bg-opacity-10 text-danger rounded-4 p-3 me-3"><i class="fa-solid fa-trash-can fa-lg"></i></div>
                        <div><h6 class="mb-0 fw-bold">ล้างข้อมูลการแข่งขัน</h6><p class="mb-0 text-danger small">รีเซ็ตคะแนนทั้งหมด</p></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-12">
            <div class="card ios-card p-4">
                <h5 class="mb-3 fw-bold"><i class="fa-solid fa-calendar-days text-primary me-2"></i> รายการแข่งขัน</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <tbody>
                            <?php foreach ($all_matches as $match): ?>
                            <tr>
                                <td><div class="fw-bold"><?php echo htmlspecialchars($match['sport_name']); ?></div><small class="text-muted"><?php echo $match['match_datetime']; ?></small></td>
                                <td><span class="badge bg-light text-secondary"><?php echo htmlspecialchars($match['category_name'] ?? 'ทั่วไป'); ?></span></td>
                                <td class="text-center"><span class="badge <?php echo $match['status_class']; ?> px-3 py-2 rounded-pill"><?php echo $match['status_label']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmReset() {
    if (confirm('⚠️ คำเตือน: ข้อมูลจะถูกล้างทั้งหมด! ยืนยันหรือไม่?')) {
        window.location.href = 'reset_system.php';
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
