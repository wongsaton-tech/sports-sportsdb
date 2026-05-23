<?php
require_once 'db.php';
require_once 'check_auth.php'; // เรียกใช้ระบบเช็กสิทธิ์ Session

// ตั้งค่า Timezone ให้ตรงกับประเทศไทยเพื่อความแม่นยำในการคำนวณเวลาปฏิทิน
date_default_timezone_set('Asia/Bangkok');
$current_time = date('Y-m-d H:i:s');

try {
    // ------------------------------------------------------------------
    // ส่วนที่ 1: ดึงข้อมูลกลุ่มสีและคำนวณคะแนน (ปรับตามตารางจริงใน DBeaver)
    // ------------------------------------------------------------------
    $sql_scores = "SELECT t.id, t.team_name, t.color,
            SUM(CASE WHEN r.medal = 'ทอง' THEN 1 ELSE 0 END) as gold_count,
            SUM(CASE WHEN r.medal = 'เงิน' THEN 1 ELSE 0 END) as silver_count,
            SUM(CASE WHEN r.medal = 'ทองแดง' THEN 1 ELSE 0 END) as bronze_count,
            COALESCE(SUM(r.points), 0) as total_points
            FROM teams t
            LEFT JOIN match_results r ON t.id = r.team_id
            GROUP BY t.id, t.team_name, t.color
            ORDER BY total_points DESC, gold_count DESC, t.id ASC";
            
    $stmt_scores = $pdo->query($sql_scores);
    $teams_dashboard = $stmt_scores->fetchAll();

    $has_scores = false;
    foreach ($teams_dashboard as $t) {
        if ($t['total_points'] > 0 || $t['gold_count'] > 0) {
            $has_scores = true;
            break;
        }
    }

    // ------------------------------------------------------------------
    // ส่วนที่ 2: ดึงข้อมูลปฏิทินรายการแข่งขัน (ปรับใช้ match_name และ date_time ตามตารางจริง)
    // ------------------------------------------------------------------
    $sql_matches = "SELECT m.*, 
                    (SELECT COUNT(*) FROM match_results r WHERE r.match_id = m.id) as is_finished
                    FROM matches m 
                    ORDER BY m.date_time ASC";
    
    $all_matches = $pdo->query($sql_matches)->fetchAll();

    $upcoming_list = [];
    $finished_list = [];

    foreach ($all_matches as $match) {
        $match_time = isset($match['date_time']) ? $match['date_time'] : '';
        $status_label = 'ยังไม่ถึงวันแข่งขัน';
        $status_class = 'bg-secondary bg-opacity-10 text-secondary';
        
        if ($match['is_finished'] > 0) {
            $status_label = 'จบการแข่งขัน';
            $status_class = 'bg-dark bg-opacity-10 text-dark';
        } else if (!empty($match_time)) {
            $time_diff = strtotime($match_time) - strtotime($current_time);
            
            if ($time_diff < 0) {
                // เกินเวลาเริ่มมาแล้วแต่ยังไม่มีการบันทึกผล สมมติว่ากำลังแข่งขันอยู่ (ภายในช่วง 2 ชั่วโมง)
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

        $match['computed_time'] = $match_time;
        $match['status_label'] = $status_label;
        $match['status_class'] = $status_class;

        if ($status_label == 'จบการแข่งขัน') {
            $finished_list[] = $match;
        } else {
            $upcoming_list[] = $match;
        }
    }

    // เรียงกลุ่มที่ยังไม่แข่ง (Upcoming) ให้วันที่ใกล้จะถึงที่สุดอยู่บนสุด
    usort($upcoming_list, function($a, $b) {
        if (empty($a['computed_time'])) return 1;
        if (empty($b['computed_time'])) return -1;
        return strtotime($a['computed_time']) - strtotime($b['computed_time']);
    });

    // ยุบรวม 2 กลุ่มเข้าด้วยกัน (Upcoming อยู่บน เสมอ และ Finished ต่อท้ายลงไปข้างล่าง)
    $calendar_timeline = array_merge($upcoming_list, $finished_list);

} catch (\PDOException $e) {
    die("<div class='container mt-5 alert alert-danger text-center'>เกิดข้อผิดพลาดในการเชื่อมโยงระบบฐานข้อมูล: " . $e->getMessage() . "</div>");
}

// ตรวจสอบสถานะสิทธิ์จาก Session
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
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f2f2f7; /* iOS Light Background */
            color: #1c1c1e;
        }
        .navbar {
            background-color: rgba(28, 28, 30, 0.92) !important;
            backdrop-filter: blur(20px);
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
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
        }
        .animate-pulse {
            animation: pulse 1.5s infinite;
        }
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
                <li class="nav-item"><a class="nav-link active" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> แดชบอร์ด</a></li>
                
                <?php if ($user_role == 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="manage_teams.php"><i class="fa-solid fa-palette me-1"></i> จัดการทีมสี</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_categories.php"><i class="fa-solid fa-folder-open me-1"></i> ประเภทกีฬา</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_matches.php"><i class="fa-solid fa-person-running me-1"></i> รายการแข่ง & นักกีฬา</a></li>
                <?php endif; ?>
                
                <?php if ($user_role == 'admin' || $user_role == 'scorekeeper'): ?>
                    <li class="nav-item"><a class="nav-link" href="manage_scores.php"><i class="fa-solid fa-star me-1"></i> บันทึกคะแนน</a></li>
                <?php endif; ?>
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

    <?php if (isset($_GET['auth_error']) && $_GET['auth_error'] == 'denied'): ?>
        <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-3">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> <strong>ปฏิเสธการเข้าถึง!</strong> บัญชีของคุณไม่มีสิทธิ์เข้าใช้งานในส่วนที่เลือกครับ
        </div>
    <?php endif; ?>

    <div class="p-4 bg-white rounded-4 shadow-sm mb-4 border-0 text-center text-md-start d-md-flex align-items-center justify-content-between">
        <div>
            <h1 class="fw-bold text-dark mb-1">SportsDay Dashboard</h1>
            <p class="text-muted mb-0">
                ยินดีต้อนรับ: <span class="badge bg-dark"><?php echo htmlspecialchars($user_name); ?></span> 
                (สิทธิ์การใช้งาน: <span class="text-warning fw-bold"><?php echo strtoupper($user_role); ?></span>)
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="edit_profile.php" class="btn btn-sm btn-outline-secondary py-1 px-2 ms-2" style="font-size: 0.8rem; border-radius: 6px;">
                        <i class="fa-solid fa-user-pen me-1"></i> แก้ไขรหัสผ่าน
                    </a>
                <?php endif; ?>

                <?php if ($user_role == 'admin'): ?>
                    <a href="manage_users.php" class="btn btn-sm btn-outline-danger py-1 px-2 ms-1" style="font-size: 0.8rem; border-radius: 6px;">
                        <i class="fa-solid fa-users-gear me-1"></i> จัดการสมาชิกผู้ใช้
                    </a>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card ios-card p-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold m-0"><i class="fa-solid fa-award me-2 text-primary"></i> บอร์ดสรุปเหรียญรางวัลประจำโรงเรียน</h5>
                    </div>
                    
                    <?php if ($has_scores && !empty($teams_dashboard)): ?>
                        <div class="p-3 bg-warning bg-opacity-10 rounded-4 border-0 mb-3 text-center">
                            <h6 class="text-warning-emphasis fw-bold mb-1"><i class="fa-solid fa-crown me-1"></i> คะแนนรวมอันดับที่ 1 ในปัจจุบัน</h6>
                            <h2 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($teams_dashboard[0]['team_name']); ?></h2>
                            <small class="text-muted">ผลรวม: <?php echo $teams_dashboard[0]['total_points']; ?> คะแนน</small>
                        </div>
                    <?php else: ?>
                        <div class="p-3 bg-secondary bg-opacity-5 rounded-4 mb-3 text-center">
                            <h6 class="text-muted fw-bold mb-0"><i class="fa-solid fa-hourglass-start me-1"></i> ระบบเปิดสแตนด์บายเรียบร้อย (รออัปเดตผลการแข่ง)</h6>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-muted small">
                                    <th class="text-center" width="15%">อันดับ</th>
                                    <th width="35%">ทีมสี</th>
                                    <th class="text-center" width="12%">🥇 ทอง</th>
                                    <th class="text-center" width="12%">🥈 เงิน</th>
                                    <th class="text-center" width="12%">🥉 ทองแดง</th>
                                    <th class="text-center" width="14%">คะแนน</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($teams_dashboard) > 0): foreach ($teams_dashboard as $index => $team): ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php 
                                                $bg_badge = 'bg-secondary bg-opacity-10 text-dark';
                                                if ($has_scores && $index == 0) $bg_badge = 'bg-warning text-dark';
                                                if ($has_scores && $index == 1) $bg_badge = 'bg-light text-dark border';
                                                if ($has_scores && $index == 2) $bg_badge = 'bg-danger bg-opacity-10 text-danger';
                                            ?>
                                            <div class="rank-badge mx-auto <?php echo $bg_badge; ?>"><?php echo $index + 1; ?></div>
                                        </td>
                                        <td class="fw-bold">
                                            <span class="badge me-2" style="background-color: <?php echo $team['color']; ?>; width: 12px; height: 12px; display: inline-block; border-radius: 50%;"> </span>
                                            <?php echo htmlspecialchars($team['team_name']); ?>
                                        </td>
                                        <td class="text-center fw-bold text-warning"><?php echo $team['gold_count']; ?></td>
                                        <td class="text-center fw-bold text-secondary"><?php echo $team['silver_count']; ?></td>
                                        <td class="text-center fw-bold text-danger"><?php echo $team['bronze_count']; ?></td>
                                        <td class="text-center fw-bold text-primary"><?php echo $team['total_points']; ?></td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="6" class="text-center text-muted p-3">ยังไม่มีข้อมูลกลุ่มทีมสีในระบบ</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="d-flex flex-column gap-2 h-100 justify-content-start">
                
                <?php if ($user_role == 'admin'): ?>
                <div class="card ios-card menu-card border-0" onclick="location.href='manage_teams.php'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-4 p-3 me-3"><i class="fa-solid fa-palette fa-lg"></i></div>
                        <div><h6 class="mb-0 fw-bold text-dark">จัดการกลุ่มทีมสี</h6><p class="mb-0 text-muted small">เซ็ตอัพเพิ่มลดและตั้งค่าสโมสรสี</p></div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted small"></i>
                    </div>
                </div>
                <div class="card ios-card menu-card border-0" onclick="location.href='manage_categories.php'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="bg-dark bg-opacity-10 text-dark rounded-4 p-3 me-3"><i class="fa-solid fa-folder-open fa-lg"></i></div>
                        <div><h6 class="mb-0 fw-bold text-dark">จัดการประเภทกีฬา</h6><p class="mb-0 text-muted small">แบ่งกลุ่มกรีฑา ลู่-ลาน หรือพาเหรด</p></div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted small"></i>
                    </div>
                </div>
                <div class="card ios-card menu-card border-0" onclick="location.href='manage_matches.php'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="bg-success bg-opacity-10 text-success rounded-4 p-3 me-3"><i class="fa-solid fa-person-running fa-lg"></i></div>
                        <div><h6 class="mb-0 fw-bold text-dark">สร้างแมตช์ & รายชื่อนักกีฬา</h6><p class="mb-0 text-muted small">วางผังตารางแข่งและกรอกชื่อนักเรียน</p></div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted small"></i>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($user_role == 'admin' || $user_role == 'scorekeeper'): ?>
                <div class="card ios-card menu-card border-0" onclick="location.href='manage_scores.php'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="bg-warning bg-opacity-10 text-warning rounded-4 p-3 me-3"><i class="fa-solid fa-star fa-lg"></i></div>
                        <div><h6 class="mb-0 fw-bold text-dark">บันทึกคะแนนผลการแข่ง</h6><p class="mb-0 text-muted small">คีย์เหรียญและรางวัลเพื่อดันคะแนนขึ้นบอร์ด</p></div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted small"></i>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($user_role == 'guest'): ?>
                    <div class="p-4 rounded-4 border border-dashed text-center text-muted small bg-white shadow-sm h-100 d-flex flex-column align-items-center justify-content-center">
                        <i class="fa-solid fa-lock fa-2x mb-2 text-black-50"></i><br>
                        <span>คุณกำลังเข้าชมในฐานะบุคคลทั่วไป (โหมดรับชมบอร์ด)<br>หากเป็นกรรมการกรุณากด <strong>"เจ้าหน้าที่ล็อกอิน"</strong> ด้านบน</span>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card ios-card p-2 mb-5">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                        <h5 class="fw-bold m-0"><i class="fa-solid fa-calendar-days text-success me-2"></i> ปฏิทินไทม์ไลน์กำหนดการแข่งขันกีฬาสี</h5>
                        <span class="small text-muted"><i class="fa-regular fa-clock me-1"></i> ข้อมูลสรุปอัปเดตเรียลไทม์</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-muted small">
                                    <th width="20%">📅 วันเวลาแข่งขัน</th>
                                    <th width="35%">รายการแข่งขันกีฬา</th>
                                    <th width="20%">กลุ่มประเภท</th>
                                    <th width="10%" class="text-center">จำกัด/สี</th>
                                    <th width="15%" class="text-center">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($calendar_timeline) > 0): foreach ($calendar_timeline as $match): ?>
                                    <tr style="<?php echo ($match['status_label'] == 'จบการแข่งขัน') ? 'opacity: 0.55; background-color: #fafafa;' : ''; ?>">
                                        <td class="fw-bold">
                                            <?php if (!empty($match['computed_time'])): ?>
                                                <i class="fa-regular fa-calendar text-black-50 me-1"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($match['computed_time'])); ?> น.
                                            <?php else: ?>
                                                <span class="text-muted small">- ยังไม่ระบุเวลา -</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark">
                                                <?php echo htmlspecialchars($match['match_name'] ?? 'รายการแข่งขัน'); ?>
                                            </span>
                                            <?php if(isset($match['gender_type'])): ?>
                                                <span class="badge bg-light text-dark font-monospace mx-1"><?php echo $match['gender_type']; ?></span>
                                            <?php endif; ?>
                                            <small class="text-muted d-block mt-1"><?php echo htmlspecialchars($match['tournament_type'] ?? ''); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border px-2 py-1">
                                                ID ประเภท: <?php echo htmlspecialchars($match['category_id'] ?? '-'); ?>
                                            </span>
                                        </td>
                                        <td class="text-center font-monospace text-muted">
                                            <?php echo $match['max_players_per_team'] ?? '-'; ?> คน
                                        </td>
                                        <td class="text-center">
                                            <span class="badge px-3 py-2 rounded-pill font-weight-600 text-sm <?php echo $match['status_class']; ?>">
                                                <?php echo $match['status_label']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="5" class="text-center text-muted p-5">ยังไม่มีรายการสร้างแมตช์การแข่งขันในระบบ</td></tr>
                                <?php endif; ?>
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
