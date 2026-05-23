<?php
require_once 'db.php';

try {
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
    <title>SportsDay Control Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f2f2f7; /* iOS Light Background */
            color: #1c1c1e;
        }
        .navbar {
            background-color: rgba(28, 28, 30, 0.92) !important;
            backdrop-filter: blur(20px); /* iOS Glassmorphism */
        }
        .ios-card {
            background: #ffffff;
            border-radius: 16px !important;
            border: none !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04) !important;
        }
        .menu-card {
            transition: all 0.25s ease-in-out;
            cursor: pointer;
        }
        .menu-card:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08) !important;
        }
        .rank-badge {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 600;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm py-3">
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
    <div class="p-4 bg-white rounded-4 shadow-sm mb-4 border-0 text-center text-md-start d-md-flex align-items-center justify-content-between">
        <div>
            <h1 class="fw-bold text-dark mb-1">SportsDay Dashboard</h1>
            <p class="text-muted mb-0">ระบบควบคุมและสรุปสถิติกีฬาสีสไตล์โมเดิร์น</p>
        </div>
        <div class="mt-3 mt-md-0">
            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill"><i class="fa-solid fa-cloud-check me-1"></i> Aiven Cloud Connected</span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card ios-card p-2 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold m-0"><i class="fa-solid fa-chart-line me-2 text-primary"></i> สรุปเหรียญรางวัล</h5>
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">Live Update</span>
                    </div>
                    
                    <?php if ($has_scores && !empty($teams_dashboard)): ?>
                        <div class="p-3 bg-warning bg-opacity-10 rounded-4 border-0 mb-4 text-center">
                            <h6 class="text-warning-emphasis fw-bold mb-1"><i class="fa-solid fa-crown me-1"></i> ผู้นำในขณะนี้</h6>
                            <h2 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($teams_dashboard[0]['team_name']); ?></h2>
                            <small class="text-muted">คะแนนสะสม: <?php echo $teams_dashboard[0]['total_points']; ?> คะแนน</small>
                        </div>
                    <?php else: ?>
                        <div class="p-3 bg-secondary bg-opacity-5 rounded-4 mb-4 text-center">
                            <h6 class="text-muted fw-bold mb-0"><i class="fa-solid fa-hourglass-start me-1"></i> อยู่ระหว่างรอผลการแข่งขันกีฬา</h6>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr class="text-muted small">
                                    <th class="text-center">อันดับ</th>
                                    <th>ทีมสี</th>
                                    <th class="text-center">🥇 ทอง</th>
                                    <th class="text-center">🥈 เงิน</th>
                                    <th class="text-center">🥉 ทองแดง</th>
                                    <th class="text-center">คะแนน</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($teams_dashboard) > 0): ?>
                                    <?php foreach ($teams_dashboard as $index => $team): ?>
                                        <tr>
                                            <td class="text-center">
                                                <?php 
                                                    $bg_badge = 'bg-secondary bg-opacity-10 text-dark';
                                                    if ($has_scores && $index == 0) $bg_badge = 'bg-warning text-dark';
                                                    if ($has_scores && $index == 1) $bg_badge = 'bg-light text-dark border';
                                                    if ($has_scores && $index == 2) $bg_badge = 'bg-danger bg-opacity-10 text-danger';
                                                ?>
                                                <div class="rank-badge mx-auto <?php echo $bg_badge; ?>">
                                                    <?php echo $index + 1; ?>
                                                </div>
                                            </td>
                                            <td class="fw-bold">
                                                <span class="badge me-2" style="background-color: <?php echo $team['team_color']; ?>; width: 12px; height: 12px; display: inline-block; border-radius: 50%;"> </span>
                                                <?php echo htmlspecialchars($team['team_name']); ?>
                                            </td>
                                            <td class="text-center fw-bold text-warning"><?php echo $team['gold_count'] ?? 0; ?></td>
                                            <td class="text-center fw-bold text-secondary"><?php echo $team['silver_count'] ?? 0; ?></td>
                                            <td class="text-center fw-bold text-danger"><?php echo $team['bronze_count'] ?? 0; ?></td>
                                            <td class="text-center fw-bold text-primary"><?php echo $team['total_points']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="d-flex flex-column h-100 gap-3">
                
                <div class="card ios-card menu-card border-0" onclick="location.href='manage_teams.php'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-4 p-3 me-3"><i class="fa-solid fa-palette fa-lg"></i></div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">จัดการกลุ่มทีมสี</h6>
                            <p class="mb-0 text-muted small">เพิ่ม ลด และตั้งค่ารหัสสีสโมสร</p>
                        </div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted small"></i>
                    </div>
                </div>

                <div class="card ios-card menu-card border-0" onclick="location.href='manage_categories.php'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="bg-dark bg-opacity-10 text-dark rounded-4 p-3 me-3"><i class="fa-solid fa-folder-open fa-lg"></i></div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">จัดการประเภทกีฬา</h6>
                            <p class="mb-0 text-muted small">แยกกลุ่มกรีฑา ลู่-ลาน หรือกองเชียร์</p>
                        </div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted small"></i>
                    </div>
                </div>

                <div class="card ios-card menu-card border-0" onclick="location.href='manage_matches.php'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="bg-success bg-opacity-10 text-success rounded-4 p-3 me-3"><i class="fa-solid fa-person-running fa-lg"></i></div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">สร้างแมตช์ & รายชื่อนักกีฬา</h6>
                            <p class="mb-0 text-muted small">กำหนดตารางการแข่งและคีย์ชื่อนักเรียน</p>
                        </div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted small"></i>
                    </div>
                </div>

                <div class="card ios-card menu-card border-0" onclick="location.href='manage_scores.php'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="bg-warning bg-opacity-10 text-warning rounded-4 p-3 me-3"><i class="fa-solid fa-star fa-lg"></i></div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">บันทึกคะแนนผลการแข่ง</h6>
                            <p class="mb-0 text-muted small">กรอกเหรียญรางวัลเพื่อคำนวณแดชบอร์ด</p>
                        </div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted small"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
