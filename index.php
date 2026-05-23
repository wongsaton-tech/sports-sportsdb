<?php
require_once 'db.php';

try {
    // ใช้คำสั่ง SQL LEFT JOIN เพื่อผูกทีมสีเข้ากับตารางผลการแข่ง และคำนวณ SUM คะแนนดิบ พร้อม COUNT แยกเหรียญรางวัล
    $sql = "SELECT t.id, t.team_name, t.team_color,
            SUM(CASE WHEN r.medal = 'ทอง' THEN 1 ELSE 0 END) as gold_count,
            SUM(CASE WHEN r.medal = 'เงิน' THEN 1 ELSE 0 END) as silver_count,
            SUM(CASE WHEN r.medal = 'ทองแดง' THEN 1 ELSE 0 END) as bronze_count,
            COALESCE(SUM(r.points), 0) as total_points
            FROM teams t
            LEFT JOIN match_results r ON t.id = r.team_id
            GROUP BY t.id, t.team_name, t.team_color
            ORDER BY total_points DESC, gold_count DESC, t.id ASC";
            
    $stmt = $pdo->query($sql);
    $teams_dashboard = $stmt->fetchAll();

    // เช็กสถานะว่าตารางการแข่ง มีคะแนนบันทึกเข้ามาจริง ๆ หรือยัง
    $has_scores = false;
    foreach ($teams_dashboard as $t) {
        if ($t['total_points'] > 0 || $t['gold_count'] > 0) {
            $has_scores = true;
            break;
        }
    }
} catch (\PDOException $e) {
    $teams_dashboard = [];
    $has_scores = false;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการและแดชบอร์ดกีฬาสี</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .menu-card { transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; }
        .menu-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important; }
        .rank-badge { width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="index.php">🏆 SportsDay Center</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link active" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> แดชบอร์ด</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_teams.php"><i class="fa-solid fa-palette me-1"></i> จัดการทีมสี</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_categories.php"><i class="fa-solid fa-folder-open me-1"></i> ประเภทกีฬา</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_matches.php"><i class="fa-solid fa-person-running me-1"></i> รายการแข่ง & นักกีฬา</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_scores.php"><i class="fa-solid fa-star me-1"></i> บันทึกคะแนน</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="text-center p-4 bg-white rounded shadow-sm mb-4 border-top border-primary border-4">
        <h1 class="fw-bold text-primary mb-1">🏆 Sports Day Control & Dashboard</h1>
        <p class="text-muted mb-0">ระบบจัดการข้อมูลและสรุปผลคะแนนการแข่งขันรวมในหน้าเดียว</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa-solid fa-chart-line me-2 text-warning"></i> ตารางสรุปคะแนนรวม & เหรียญรางวัล</h5>
                    <span class="badge bg-success">Real-time</span>
                </div>
                <div class="card-body">
                    
                    <?php if ($has_scores && !empty($teams_dashboard)): ?>
                        <div class="p-3 bg-warning bg-opacity-10 rounded border border-warning mb-4 text-center">
                            <h6 class="text-warning-emphasis fw-bold mb-1"><i class="fa-solid fa-crown me-1"></i> ทีมสีที่มีคะแนนนำในขณะนี้</h6>
                            <h3 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($teams_dashboard[0]['team_name']); ?></h3>
                            <small class="text-muted">คะแนนรวมสะสมสูงสุด: <?php echo $teams_dashboard[0]['total_points']; ?> คะแนน</small>
                        </div>
                    <?php else: ?>
                        <div class="p-3 bg-secondary bg-opacity-10 rounded border border-secondary mb-4 text-center">
                            <h6 class="text-muted-emphasis fw-bold mb-0"><i class="fa-solid fa-hourglass-start me-1"></i> ยังไม่มีคะแนนบันทึกในระบบ (รอผลการแข่งขัน)</h6>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="10%" class="text-center">อันดับ</th>
                                    <th width="35%">ทีมสีประจำโรงเรียน</th>
                                    <th width="15%" class="text-center">🥇 ทอง</th>
                                    <th width="15%" class="text-center">🥈 เงิน</th>
                                    <th width="15%" class="text-center">🥉 ทองแดง</th>
                                    <th width="10%" class="text-center">คะแนน</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($teams_dashboard) > 0): ?>
                                    <?php foreach ($teams_dashboard as $index => $team): ?>
                                        <tr>
                                            <td class="text-center">
                                                <?php 
                                                    $bg_badge = 'bg-secondary text-white';
                                                    if ($has_scores && $index == 0) $bg_badge = 'bg-warning text-dark';
                                                    if ($has_scores && $index == 1) $bg_badge = 'bg-light text-dark border';
                                                    if ($has_scores && $index == 2) $bg_badge = 'bg-danger bg-opacity-25 text-danger';
                                                ?>
                                                <div class="rank-badge mx-auto <?php echo $bg_badge; ?>">
                                                    <?php echo $index + 1; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge me-2" style="background-color: <?php echo $team['team_color']; ?>; width: 15px; height: 15px; display: inline-block; vertical-align: middle;"> </span>
                                                <strong><?php echo htmlspecialchars($team['team_name']); ?></strong>
                                            </td>
                                            <td class="text-center fw-bold text-warning"><?php echo $team['gold_count'] ?? 0; ?></td>
                                            <td class="text-center fw-bold text-secondary"><?php echo $team['silver_count'] ?? 0; ?></td>
                                            <td class="text-center fw-bold text-muted"><?php echo $team['bronze_count'] ?? 0; ?></td>
                                            <td class="text-center fw-bold text-primary"><?php echo $team['total_points']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted p-4">ยังไม่มีข้อมูลทีมสีในระบบ</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="d-flex flex-column h-100">
                <div class="card shadow-sm menu-card mb-2 border-start border-primary border-4" onclick="location.href='manage_teams.php'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded p-3 me-3"><i class="fa-solid fa-palette fa-xl"></i></div>
                        <div><h5 class="mb-1 fw-bold text-dark">🎨 เมนูจัดการทีมสี</h5><p class="mb-0 text-muted small">เพิ่มกลุ่มทีมสีและกำหนดรหัสสีระบบ</p></div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                    </div>
                </div>

                <div class="card shadow-sm menu-card mb-2 border-start border-dark border-4" onclick="location.href='manage_categories.php'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="bg-dark bg-opacity-10 text-dark rounded p-3 me-3"><i class="fa-solid fa-folder-open fa-xl"></i></div>
                        <div><h5 class="mb-1 fw-bold text-dark">🗂️ เมนูจัดการประเภทกีฬา</h5><p class="mb-0 text-muted small">แบ่งกลุ่มประเภทกรีฑาและชนิดกีฬา</p></div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                    </div>
                </div>

                <div class="card shadow-sm menu-card mb-2 border-start border-success border-4" onclick="location.href='manage_matches.php'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="bg-success bg-opacity-10 text-success rounded p-3 me-3"><i class="fa-solid fa-person-running fa-xl"></i></div>
                        <div><h5 class="mb-1 fw-bold text-dark">🏃 ตารางแข่ง & รายชื่อนักกีฬา</h5><p class="mb-0 text-muted small">สร้างแมตช์กีฬาและบันทึกรายชื่อนักเรียน</p></div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                    </div>
                </div>

                <div class="card shadow-sm menu-card mb-3 border-start border-warning border-4" onclick="location.href='manage_scores.php'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="bg-warning bg-opacity-10 text-warning rounded p-3 me-3"><i class="fa-solid fa-star fa-xl"></i></div>
                        <div><h5 class="mb-1 fw-bold text-dark">🏅 บันทึกคะแนน & เหรียญรางวัล</h5><p class="mb-0 text-muted small">บันทึกอันดับการชนะแมตช์เพื่ออัปเดตแดชบอร์ด</p></div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                    </div>
                </div>

                <div class="bg-white p-3 rounded shadow-sm text-center border mt-auto">
                    <small class="text-muted"><i class="fa-solid fa-cloud text-success me-1"></i> Connected to Aiven Cloud Production</small>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
