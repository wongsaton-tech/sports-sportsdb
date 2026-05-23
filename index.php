<?php
require_once 'db.php';
require_once 'check_auth.php';

date_default_timezone_set('Asia/Bangkok');
$current_time = date('Y-m-d H:i:s');

$success_msg = "";
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'reset') {
        $success_msg = "✅ รีเซ็ตระบบเรียบร้อยแล้ว! ข้อมูลทั้งหมดถูกล้างเรียบร้อย";
    }
}

// ==================== ตรวจสอบสถานะข้อมูลสำหรับเงื่อนไขแสดงเมนู ====================
$has_teams = false;
$has_categories = false;

try {
    // นับจำนวนทีมสี
    $has_teams = $pdo->query("SELECT COUNT(*) FROM teams")->fetchColumn() > 0;
    
    // นับจำนวนประเภทกีฬา
    $has_categories = $pdo->query("SELECT COUNT(*) FROM sport_categories")->fetchColumn() > 0;

    // =========================
    // สรุปคะแนนทีมสี
    // =========================
    $sql_scores = "
        SELECT 
            t.id, t.team_name, t.team_color,
            SUM(CASE WHEN r.medal = 'ทอง' THEN 1 ELSE 0 END) as gold_count,
            SUM(CASE WHEN r.medal = 'เงิน' THEN 1 ELSE 0 END) as silver_count,
            SUM(CASE WHEN r.medal = 'ทองแดง' THEN 1 ELSE 0 END) as bronze_count,
            COALESCE(SUM(r.points), 0) as total_points
        FROM teams t
        LEFT JOIN match_results r ON t.id = r.team_id
        GROUP BY t.id, t.team_name, t.team_color
        ORDER BY total_points DESC, gold_count DESC, t.id ASC
    ";

    $teams_dashboard = $pdo->query($sql_scores)->fetchAll();

    // =========================
    // ปฏิทินการแข่งขัน
    // =========================
    $sql_matches = "
        SELECT m.*, c.category_name, COUNT(r.id) as is_finished
        FROM matches m
        LEFT JOIN sport_categories c ON m.category_id = c.id
        LEFT JOIN match_results r ON m.id = r.match_id
        GROUP BY m.id
        ORDER BY m.match_datetime ASC
    ";

    $all_matches = $pdo->query($sql_matches)->fetchAll();

    $upcoming_list = [];
    $finished_list = [];

    foreach ($all_matches as $match) {
        $match_time = $match['match_datetime'];
        $status_label = 'ยังไม่ถึงวันแข่งขัน';
        $status_class = 'bg-secondary bg-opacity-10 text-secondary';

        if ($match['is_finished'] > 0) {
            $status_label = 'จบการแข่งขัน';
            $status_class = 'bg-dark bg-opacity-10 text-dark';
        } else if (!empty($match_time)) {
            $time_diff = strtotime($match_time) - strtotime($current_time);
            if ($time_diff < 0) {
                if (abs($time_diff) <= 7200) {
                    $status_label = 'กำลังแข่งขัน';
                    $status_class = 'bg-danger text-white animate-pulse';
                } else {
                    $status_label = 'รอการบันทึกผล';
                    $status_class = 'bg-warning bg-opacity-20 text-warning-emphasis';
                }
            } else if ($time_diff <= 86400) {
                $status_label = 'ใกล้ถึงวันแข่งขัน';
                $status_class = 'bg-info bg-opacity-10 text-info';
            }
        }

        $match['status_label'] = $status_label;
        $match['status_class'] = $status_class;

        if ($status_label == 'จบการแข่งขัน') {
            $finished_list[] = $match;
        } else {
            $upcoming_list[] = $match;
        }
    }

    usort($upcoming_list, function($a, $b) {
        if (empty($a['match_datetime'])) return 1;
        if (empty($b['match_datetime'])) return -1;
        return strtotime($a['match_datetime']) - strtotime($b['match_datetime']);
    });

    $calendar_timeline = array_merge($upcoming_list, $finished_list);

} catch (\PDOException $e) {
    $teams_dashboard = [];
    $calendar_timeline = [];
}

$user_role = $_SESSION['user_role'] ?? 'guest';
$user_name = $_SESSION['user_name'] ?? 'บุคคลทั่วไป';
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
        .navbar { background-color: rgba(28, 28, 30, 0.92) !important; backdrop-filter: blur(20px); }
        .ios-card { background: #ffffff; border-radius: 18px !important; border: none !important; box-shadow: 0 4px 12px rgba(0,0,0,0.04) !important; }
        .menu-card { transition: all 0.25s ease-in-out; cursor: pointer; overflow: hidden; }
        .menu-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,0.08) !important; }
        .menu-card .card-body { min-height: 88px; }
        .rank-badge { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: 600; font-size: 0.9rem; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }
        .animate-pulse { animation: pulse 1.5s infinite; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="index.php">🏆 SportsDay Center</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> แดชบอร์ด</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link text-danger fw-bold" href="logout.php" onclick="return confirm('คุณต้องการออกจากระบบใช่หรือไม่?');">
                            <i class="fa-solid fa-right-from-bracket me-1"></i> ออกจากระบบ
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link text-success fw-bold" href="login.php">
                            <i class="fa-solid fa-right-to-bracket me-1"></i> เจ้าหน้าที่ล็อกอิน
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">

    <?php if(!empty($success_msg)): ?>
        <div class="alert alert-success border-0 rounded-3 mb-4 shadow-sm">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <div class="p-4 bg-white rounded-4 shadow-sm mb-4 border-0">
        <h1 class="fw-bold text-dark mb-1">SportsDay Dashboard</h1>
        <p class="text-muted mb-0">
            ยินดีต้อนรับ: 
            <span class="badge bg-dark"><?php echo htmlspecialchars($user_name); ?></span>
            (สิทธิ์: <span class="text-warning fw-bold"><?php echo strtoupper($user_role); ?></span>)
        </p>
    </div>

    <div class="row g-4 mb-4">

        <!-- LEFT: บอร์ดคะแนน -->
        <div class="col-lg-7">
            <div class="card ios-card p-2">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-award me-2 text-primary"></i> บอร์ดสรุปเหรียญรางวัล</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-muted small">
                                    <th class="text-center">อันดับ</th>
                                    <th>ทีมสี</th>
                                    <th class="text-center">🥇</th>
                                    <th class="text-center">🥈</th>
                                    <th class="text-center">🥉</th>
                                    <th class="text-center">คะแนน</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teams_dashboard as $index => $team): ?>
                                <tr>
                                    <td class="text-center">
                                        <?php $bg = ($index == 0) ? 'bg-warning text-dark' : 'bg-secondary bg-opacity-10 text-dark'; ?>
                                        <div class="rank-badge mx-auto <?php echo $bg; ?>"><?php echo $index + 1; ?></div>
                                    </td>
                                    <td class="fw-bold">
                                        <span class="badge me-2" style="background-color: <?php echo $team['team_color']; ?>; width:12px;height:12px;border-radius:50%;"></span>
                                        <?php echo htmlspecialchars($team['team_name']); ?>
                                    </td>
                                    <td class="text-center text-warning fw-bold"><?php echo $team['gold_count']; ?></td>
                                    <td class="text-center text-secondary fw-bold"><?php echo $team['silver_count']; ?></td>
                                    <td class="text-center text-danger fw-bold"><?php echo $team['bronze_count']; ?></td>
                                    <td class="text-center text-primary fw-bold"><?php echo $team['total_points']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: เมนูการจัดการ (เรียงตามที่ต้องการ) -->
        <div class="col-lg-5">
            <div class="d-flex flex-column gap-3">

                <?php if ($user_role == 'admin'): ?>
                    <!-- 1. จัดการผู้ใช้งาน -->
                    <div class="card ios-card menu-card border-0" onclick="location.href='manage_users.php'">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="bg-danger bg-opacity-10 text-danger rounded-4 p-3 me-3">
                                <i class="fa-solid fa-users-gear fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">จัดการผู้ใช้งานระบบ</h6>
                                <p class="mb-0 text-muted small">เพิ่ม / ลบ / แก้ไขบัญชีเจ้าหน้าที่</p>
                            </div>
                            <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                        </div>
                    </div>

                    <!-- 2. จัดการกลุ่มทีมสี -->
                    <div class="card ios-card menu-card border-0" onclick="location.href='manage_teams.php'">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-4 p-3 me-3">
                                <i class="fa-solid fa-palette fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">จัดการกลุ่มทีมสี</h6>
                                <p class="mb-0 text-muted small">เพิ่ม / แก้ไขทีมสี</p>
                            </div>
                            <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($user_role == 'admin' && $has_teams): ?>
                    <!-- 3. จัดการประเภทกีฬา (แสดงเมื่อมีทีมสีแล้ว) -->
                    <div class="card ios-card menu-card border-0" onclick="location.href='manage_categories.php'">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="bg-info bg-opacity-10 text-info rounded-4 p-3 me-3">
                                <i class="fa-solid fa-folder-open fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">จัดการประเภทกีฬา</h6>
                                <p class="mb-0 text-muted small">เพิ่ม / แก้ไขกลุ่มประเภทกีฬา</p>
                            </div>
                            <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($user_role == 'admin' && $has_categories): ?>
                    <!-- 4. จัดการรายการแข่งขัน (แสดงเมื่อมีประเภทกีฬา) -->
                    <div class="card ios-card menu-card border-0" onclick="location.href='manage_matches.php'">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="bg-success bg-opacity-10 text-success rounded-4 p-3 me-3">
                                <i class="fa-solid fa-person-running fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">จัดการรายการแข่งขัน</h6>
                                <p class="mb-0 text-muted small">สร้างและจัดการรายการแข่งขัน</p>
                            </div>
                            <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($user_role == 'admin' && $has_teams): ?>
                    <!-- 5. ล้างข้อมูลทั้งระบบ (แสดงเมื่อมีทีมสี) -->
                    <div class="card ios-card menu-card border-0 bg-danger bg-opacity-10" 
                         onclick="if(confirm('⚠️ คุณแน่ใจหรือไม่ที่จะล้างข้อมูลทั้งหมด?\n\nการกระทำนี้ไม่สามารถกู้คืนได้!')) location.href='reset_system.php';">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="bg-danger bg-opacity-20 text-danger rounded-4 p-3 me-3">
                                <i class="fa-solid fa-trash-can fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold text-danger">ล้างข้อมูลทั้งระบบ</h6>
                                <p class="mb-0 text-muted small">รีเซ็ตฐานข้อมูลทั้งหมด</p>
                            </div>
                            <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($user_role == 'admin' || $user_role == 'scorekeeper'): ?>
                    <!-- บันทึกคะแนน (แสดงทุกคนที่มีสิทธิ์) -->
                    <div class="card ios-card menu-card border-0" onclick="location.href='manage_scores.php'">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="bg-warning bg-opacity-10 text-warning rounded-4 p-3 me-3">
                                <i class="fa-solid fa-star fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">บันทึกคะแนนผลการแข่งขัน</h6>
                                <p class="mb-0 text-muted small">คีย์คะแนนและเหรียญรางวัล</p>
                            </div>
                            <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                        </div>
                    </div>

                    <!-- แก้ไขข้อมูลส่วนตัว -->
                    <div class="card ios-card menu-card border-0" onclick="location.href='edit_profile.php'">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="bg-info bg-opacity-10 text-info rounded-4 p-3 me-3">
                                <i class="fa-solid fa-user-pen fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">แก้ไขข้อมูลส่วนตัว</h6>
                                <p class="mb-0 text-muted small">เปลี่ยนชื่อและรหัสผ่าน</p>
                            </div>
                            <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- ปฏิทินการแข่งขัน -->
    <div class="row">
        <div class="col-12">
            <div class="card ios-card p-2 mb-5">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold m-0"><i class="fa-solid fa-calendar-days text-success me-2"></i> ปฏิทินการแข่งขัน</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-muted small">
                                    <th>วันเวลาแข่งขัน</th>
                                    <th>รายการแข่งขัน</th>
                                    <th>ประเภท</th>
                                    <th class="text-center">จำนวน</th>
                                    <th class="text-center">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($calendar_timeline as $match): ?>
                                <tr>
                                    <td><?php if (!empty($match['match_datetime'])) echo date('d/m/Y H:i', strtotime($match['match_datetime'])) . ' น.'; ?></td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($match['sport_name']); ?></div>
                                        <small class="text-muted"><?php echo $match['tournament_type']; ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($match['category_name'] ?? 'ทั่วไป'); ?></span></td>
                                    <td class="text-center"><?php echo $match['max_players_per_team']; ?></td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill px-3 py-2 <?php echo $match['status_class']; ?>">
                                            <?php echo $match['status_label']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
