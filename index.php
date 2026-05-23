<?php
require_once 'db.php';
require_once 'check_auth.php';

date_default_timezone_set('Asia/Bangkok');
$current_time = date('Y-m-d H:i:s');

$success_msg = "";
$login_error = "";

// ==================== Login จากหน้า Dashboard ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dashboard_login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && $user['password'] === $password) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                header("Location: index.php");
                exit();
            } else {
                $login_error = "❌ ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
            }
        } catch (\PDOException $e) {
            $login_error = "เกิดข้อผิดพลาดระบบ";
        }
    } else {
        $login_error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}

if (isset($_GET['success']) && $_GET['success'] === 'reset') {
    $success_msg = "✅ รีเซ็ตระบบเรียบร้อยแล้ว!";
}

// ตรวจสอบข้อมูลสำหรับควบคุมเมนู
$has_teams = $pdo->query("SELECT COUNT(*) FROM teams")->fetchColumn() > 0;
$has_categories = $pdo->query("SELECT COUNT(*) FROM sport_categories")->fetchColumn() > 0;
$has_matches = $pdo->query("SELECT COUNT(*) FROM matches")->fetchColumn() > 0;

try {
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
        } elseif (!empty($match_time)) {
            $time_diff = strtotime($match_time) - strtotime($current_time);
            if ($time_diff < 0) {
                if (abs($time_diff) <= 7200) {
                    $status_label = 'กำลังแข่งขัน';
                    $status_class = 'bg-danger text-white animate-pulse';
                } else {
                    $status_label = 'รอการบันทึกผล';
                    $status_class = 'bg-warning bg-opacity-20 text-warning-emphasis';
                }
            } elseif ($time_diff <= 86400) {
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
$is_logged_in = isset($_SESSION['user_id']);
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
        .rank-badge { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: 600; font-size: 0.9rem; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }
        .animate-pulse { animation: pulse 1.5s infinite; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="index.php">🏆 SportsDay Center</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link active" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> แดชบอร์ด</a></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if ($is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link text-danger fw-bold" href="logout.php" onclick="return confirm('คุณต้องการออกจากระบบใช่หรือไม่?');">
                            <i class="fa-solid fa-right-from-bracket me-1"></i> ออกจากระบบ
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link text-success fw-bold" href="login.php">
                            <i class="fa-solid fa-right-to-bracket me-1"></i> หน้าเข้าสู่ระบบ
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php if(!empty($success_msg)): ?>
        <div class="alert alert-success border-0 rounded-3 mb-4"><i class="fa-solid fa-circle-check me-2"></i> <?php echo $success_msg; ?></div>
    <?php endif; ?>

    <div class="p-4 bg-white rounded-4 shadow-sm mb-4">
        <h1 class="fw-bold text-dark mb-1">SportsDay Dashboard</h1>
        <p class="text-muted mb-0">
            ยินดีต้อนรับ: <span class="badge bg-dark"><?php echo htmlspecialchars($user_name); ?></span>
            (สิทธิ์: <span class="text-warning fw-bold"><?php echo strtoupper($user_role); ?></span>)
        </p>
    </div>

    <div class="row g-4 mb-4">
        <!-- LEFT: บอร์ดสรุปเหรียญรางวัล -->
        <div class="col-lg-7">
            <div class="card ios-card p-2 h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-award me-2 text-primary"></i> บอร์ดสรุปเหรียญรางวัล</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-muted small">
                                    <th class="text-center">อันดับ</th>
                                    <th>ชื่อทีม</th>
                                    <th class="text-center">🥇</th>
                                    <th class="text-center">🥈</th>
                                    <th class="text-center">🥉</th>
                                    <th class="text-center fw-bold">คะแนนรวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teams_dashboard as $index => $team): 
                                    $is_leader = $index === 0 && $team['total_points'] > 0;
                                ?>
                                <tr class="<?php echo $is_leader ? 'table-warning' : ''; ?>">
                                    <td class="text-center">
                                        <div class="rank-badge mx-auto <?php echo $is_leader ? 'bg-warning text-dark' : 'bg-secondary bg-opacity-10 text-dark'; ?>">
                                            <?php echo $index + 1; ?>
                                        </div>
                                    </td>
                                    <td class="fw-bold">
                                        <span class="d-inline-block me-2" style="background-color: <?php echo htmlspecialchars($team['team_color']); ?>; width:18px; height:18px; border-radius:50%; border:2px solid #fff;"></span>
                                        <?php echo htmlspecialchars($team['team_name']); ?>
                                        <?php if($is_leader): ?><i class="fa-solid fa-trophy text-warning ms-2"></i><?php endif; ?>
                                    </td>
                                    <td class="text-center text-warning fw-bold"><?php echo $team['gold_count']; ?></td>
                                    <td class="text-center text-secondary fw-bold"><?php echo $team['silver_count']; ?></td>
                                    <td class="text-center text-danger fw-bold"><?php echo $team['bronze_count']; ?></td>
                                    <td class="text-center fw-bold text-primary fs-5"><?php echo $team['total_points']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Login / เมนู -->
        <div class="col-lg-5">
            <div class="d-flex flex-column gap-3">
                <?php if (!$is_logged_in): ?>
                    <!-- Card Login -->
                    <div class="card ios-card p-4">
                        <div class="text-center mb-4">
                            <i class="fa-solid fa-lock fa-3x text-warning mb-3"></i>
                            <h5 class="fw-bold">เข้าสู่ระบบสำหรับเจ้าหน้าที่</h5>
                            <p class="text-muted small">กรุณาล็อกอินเพื่อจัดการระบบ</p>
                        </div>
                        <?php if(!empty($login_error)): ?>
                            <div class="alert alert-danger border-0"><?php echo $login_error; ?></div>
                        <?php endif; ?>
                        <form method="POST" action="index.php">
                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold">ชื่อผู้ใช้งาน</label>
                                <input type="text" class="form-control" name="username" placeholder="เช่น admin01" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small text-muted fw-bold">รหัสผ่าน</label>
                                <input type="password" class="form-control" name="password" placeholder="••••••" required>
                            </div>
                            <button type="submit" name="dashboard_login" class="btn btn-primary w-100">
                                <i class="fa-solid fa-right-to-bracket me-2"></i> เข้าสู่ระบบ
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- เมนูสำหรับผู้ล็อกอิน -->
                    <?php if ($user_role === 'admin'): ?>
                        <div class="card ios-card menu-card border-0" onclick="location.href='manage_users.php'">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-4 p-3 me-3"><i class="fa-solid fa-users-gear fa-lg"></i></div>
                                <div><h6 class="mb-0 fw-bold">จัดการผู้ใช้งานระบบ</h6><p class="text-muted small mb-0">เพิ่ม/แก้ไข/ลบบัญชี</p></div>
                                <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card ios-card menu-card border-0" onclick="location.href='manage_teams.php'">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-4 p-3 me-3"><i class="fa-solid fa-palette fa-lg"></i></div>
                            <div><h6 class="mb-0 fw-bold">จัดการกลุ่มทีมสี</h6><p class="text-muted small mb-0">เพิ่ม/แก้ไขทีมสี</p></div>
                            <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                        </div>
                    </div>

                    <?php if ($has_teams): ?>
                        <div class="card ios-card menu-card border-0" onclick="location.href='manage_categories.php'">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="bg-info bg-opacity-10 text-info rounded-4 p-3 me-3"><i class="fa-solid fa-folder-open fa-lg"></i></div>
                                <div><h6 class="mb-0 fw-bold">จัดการประเภทกีฬา</h6><p class="text-muted small mb-0">จัดการกลุ่มกีฬา</p></div>
                                <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                            </div>
                        </div>

                        <?php if ($has_categories): ?>
                            <div class="card ios-card menu-card border-0" onclick="location.href='manage_matches.php'">
                                <div class="card-body d-flex align-items-center p-3">
                                    <div class="bg-success bg-opacity-10 text-success rounded-4 p-3 me-3"><i class="fa-solid fa-person-running fa-lg"></i></div>
                                    <div><h6 class="mb-0 fw-bold">จัดการรายการแข่งขัน</h6><p class="text-muted small mb-0">สร้างรายการแข่ง</p></div>
                                    <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                                </div>
                            </div>

                            <?php if ($has_matches): ?>
                                <div class="card ios-card menu-card border-0" onclick="location.href='manage_scores.php'">
                                    <div class="card-body d-flex align-items-center p-3">
                                        <div class="bg-warning bg-opacity-10 text-warning rounded-4 p-3 me-3"><i class="fa-solid fa-star fa-lg"></i></div>
                                        <div><h6 class="mb-0 fw-bold">บันทึกผลการแข่งขัน</h6><p class="text-muted small mb-0">บันทึกคะแนน</p></div>
                                        <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($user_role === 'admin'): ?>
                            <div class="card ios-card menu-card border-0 bg-danger bg-opacity-10" onclick="if(confirm('⚠️ ยืนยันล้างข้อมูลทั้งระบบ?')) location.href='reset_system.php';">
                                <div class="card-body d-flex align-items-center p-3">
                                    <div class="bg-danger bg-opacity-20 text-danger rounded-4 p-3 me-3"><i class="fa-solid fa-trash-can fa-lg"></i></div>
                                    <div><h6 class="mb-0 fw-bold text-danger">ล้างข้อมูลทั้งระบบ</h6><p class="text-muted small mb-0">รีเซ็ตฐานข้อมูล</p></div>
                                    <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="card ios-card menu-card border-0" onclick="location.href='edit_profile.php'">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="bg-info bg-opacity-10 text-info rounded-4 p-3 me-3"><i class="fa-solid fa-user-pen fa-lg"></i></div>
                            <div><h6 class="mb-0 fw-bold">ปรับปรุงข้อมูลผู้ใช้</h6><p class="text-muted small mb-0">แก้ไขชื่อ-รหัสผ่าน</p></div>
                            <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- สรุปผลการแข่งขันตามประเภทกีฬา -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card ios-card p-2">
                <div class="card-body">
                    <h5 class="fw-bold mb-4">
                        <i class="fa-solid fa-trophy text-success me-2"></i>
                        สรุปผลการแข่งขันตามประเภทกีฬา
                    </h5>

                    <?php
                    $sql_category = "
                        SELECT 
                            c.category_name,
                            m.sport_name,
                            m.gender_type,
                            t.team_name,
                            t.team_color,
                            r.medal,
                            r.points
                        FROM match_results r
                        JOIN matches m ON r.match_id = m.id
                        JOIN sport_categories c ON m.category_id = c.id
                        JOIN teams t ON r.team_id = t.id
                        WHERE r.medal != 'ไม่มี'
                        ORDER BY c.category_name, m.match_datetime DESC, r.points DESC";
                    $results = $pdo->query($sql_category)->fetchAll();
                    $grouped = [];
                    foreach ($results as $row) {
                        $cat = $row['category_name'];
                        if (!isset($grouped[$cat])) $grouped[$cat] = [];
                        $grouped[$cat][] = $row;
                    }
                    ?>

                    <?php if (empty($grouped)): ?>
                        <div class="text-center text-muted py-5">
                            ยังไม่มีผลการแข่งขันที่บันทึก<br>
                            <small>เมื่อบันทึกผลตามประเภทกีฬา จะแสดงผลที่นี่</small>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($grouped as $cat_name => $items): ?>
                                <div class="col-lg-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-light py-3">
                                            <h6 class="mb-0 fw-bold">🏷️ <?php echo htmlspecialchars($cat_name); ?></h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <?php 
                                            $shown = [];
                                            foreach ($items as $item): 
                                                $key = $item['sport_name'] . ' (' . $item['gender_type'] . ')';
                                                if (in_array($key, $shown)) continue;
                                                $shown[] = $key;
                                            ?>
                                                <div class="mb-3 pb-2 border-bottom">
                                                    <div class="fw-bold small mb-2"><?php echo htmlspecialchars($key); ?></div>
                                                    <?php foreach ($items as $res): 
                                                        if ($res['sport_name'].' ('.$res['gender_type'].')' !== $key) continue;
                                                    ?>
                                                        <div class="d-flex justify-content-between align-items-center py-1">
                                                            <span class="badge px-3 py-1" style="background-color: <?php echo htmlspecialchars($res['team_color']); ?>; color:white;">
                                                                <?php echo htmlspecialchars($res['team_name']); ?>
                                                            </span>
                                                            <span>
                                                                <?php echo ($res['medal']=='ทอง')?'🥇':(($res['medal']=='เงิน')?'🥈':'🥉'); ?>
                                                                <small class="text-muted">(<?php echo $res['points']; ?> คะแนน)</small>
                                                            </span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ปฏิทินการแข่งขัน -->
    <div class="row">
        <div class="col-12">
            <div class="card ios-card p-2 mb-5">
                <div class="card-body">
                    <h5 class="fw-bold mb-4"><i class="fa-solid fa-calendar-days text-success me-2"></i> ปฏิทินการแข่งขัน</h5>
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
                                    <td><?php echo !empty($match['match_datetime']) ? date('d/m/Y H:i', strtotime($match['match_datetime'])) . ' น.' : '-'; ?></td>
                                    <td><div class="fw-bold"><?php echo htmlspecialchars($match['sport_name']); ?></div><small class="text-muted"><?php echo $match['tournament_type']; ?></small></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($match['category_name'] ?? 'ทั่วไป'); ?></span></td>
                                    <td class="text-center"><?php echo $match['max_players_per_team']; ?></td>
                                    <td class="text-center"><span class="badge rounded-pill px-3 py-2 <?php echo $match['status_class']; ?>"><?php echo $match['status_label']; ?></span></td>
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
