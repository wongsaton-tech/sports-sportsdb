<?php
require_once 'db.php';
require_once 'check_auth.php';

date_default_timezone_set('Asia/Bangkok');
$current_time = date('Y-m-d H:i:s');

$success_msg = "";
$login_error = "";

if (isset($_GET['success']) && $_GET['success'] === 'reset') {
    $success_msg = "✅ รีเซ็ตระบบเรียบร้อยแล้ว!";
}

// ==================== จัดการ Login จากหน้า Dashboard ====================
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
            $login_error = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    } else {
        $login_error = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
    }
}

// ตรวจสอบข้อมูลสำหรับควบคุมเมนู
$has_teams = $pdo->query("SELECT COUNT(*) FROM teams")->fetchColumn() > 0;
$has_categories = $pdo->query("SELECT COUNT(*) FROM sport_categories")->fetchColumn() > 0;
$has_matches = $pdo->query("SELECT COUNT(*) FROM matches")->fetchColumn() > 0;

try {
    // สรุปคะแนนทีมสี
    $sql_scores = "
        SELECT t.id, t.team_name, t.team_color,
               SUM(CASE WHEN r.medal = 'ทอง' THEN 1 ELSE 0 END) as gold_count,
               SUM(CASE WHEN r.medal = 'เงิน' THEN 1 ELSE 0 END) as silver_count,
               SUM(CASE WHEN r.medal = 'ทองแดง' THEN 1 ELSE 0 END) as bronze_count,
               COALESCE(SUM(r.points), 0) as total_points
        FROM teams t LEFT JOIN match_results r ON t.id = r.team_id
        GROUP BY t.id, t.team_name, t.team_color
        ORDER BY total_points DESC, gold_count DESC, t.id ASC
    ";
    $teams_dashboard = $pdo->query($sql_scores)->fetchAll();

    // ปฏิทินการแข่งขัน (โค้ดเดิม)
    $sql_matches = "
        SELECT m.*, c.category_name, COUNT(r.id) as is_finished
        FROM matches m
        LEFT JOIN sport_categories c ON m.category_id = c.id
        LEFT JOIN match_results r ON m.id = r.match_id
        GROUP BY m.id
        ORDER BY m.match_datetime ASC
    ";
    $all_matches = $pdo->query($sql_matches)->fetchAll();

    // ... (ส่วน logic ปฏิทินเหมือนเดิม - ย่อเพื่อความกระชับ)
    $upcoming_list = [];
    $finished_list = [];
    foreach ($all_matches as $match) {
        // logic เดิม...
        $match['status_label'] = 'ยังไม่ถึงวันแข่งขัน';
        $match['status_class'] = 'bg-secondary bg-opacity-10 text-secondary';
        // ... (สามารถคัดลอกส่วน logic ปฏิทินจากโค้ดเก่า)
    }
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
        <!-- Navbar เดิม -->
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
        <!-- LEFT: บอร์ดสรุปเหรียญ -->
        <div class="col-lg-7">
            <div class="card ios-card p-2 h-100">
                <!-- โค้ดบอร์ดสรุปเหรียญเหมือนเดิม -->
            </div>
        </div>

        <!-- RIGHT: เมนู / Login Card -->
        <div class="col-lg-5">
            <div class="d-flex flex-column gap-3">

                <?php if (!$is_logged_in): ?>
                    <!-- === CARD LOGIN === -->
                    <div class="card ios-card p-3">
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <i class="fa-solid fa-lock fa-3x text-warning mb-3"></i>
                                <h5 class="fw-bold">เข้าสู่ระบบสำหรับเจ้าหน้าที่</h5>
                                <p class="text-muted small">กรุณาล็อกอินเพื่อจัดการระบบ</p>
                            </div>

                            <?php if(!empty($login_error)): ?>
                                <div class="alert alert-danger border-0 rounded-3 small"><?php echo $login_error; ?></div>
                            <?php endif; ?>

                            <form method="POST" action="index.php">
                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-bold">ชื่อผู้ใช้งาน</label>
                                    <input type="text" class="form-control" name="username" placeholder="เช่น admin01" required autocomplete="off">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small text-muted fw-bold">รหัสผ่าน</label>
                                    <input type="password" class="form-control" name="password" placeholder="••••••" required>
                                </div>
                                <button type="submit" name="dashboard_login" class="btn btn-primary w-100">
                                    <i class="fa-solid fa-right-to-bracket me-2"></i> เข้าสู่ระบบ
                                </button>
                            </form>

                            <div class="text-center mt-3">
                                <small class="text-muted">หรือ <a href="login.php" class="text-decoration-none">ใช้หน้าเข้าสู่ระบบแบบเต็ม</a></small>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- เมนูสำหรับผู้ล็อกอิน (เหมือนโค้ดก่อนหน้า) -->
                    <!-- ... วางเมนูตามลำดับที่เรียงไว้ก่อนหน้านี้ ... -->
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- ปฏิทินการแข่งขัน (เหมือนเดิม) -->
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
