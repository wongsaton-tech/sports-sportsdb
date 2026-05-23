<?php
require_once 'db.php';
// 2. ตรวจสอบและโหลดไฟล์ check_auth.php อย่างเข้มงวด
$auth_file = __DIR__ . '/check_auth.php'; // __DIR__ หมายถึงโฟลเดอร์ที่ไฟล์นี้อยู่
if (file_exists($auth_file)) {
    require_once $auth_file;
} else {
    die("Error: ไม่พบไฟล์ check_auth.php ในตำแหน่งที่กำหนด (ตรวจสอบว่าไฟล์ชื่อถูกต้องและอยู่ในโฟลเดอร์เดียวกัน)");
}

// 🔒 ล็อกความปลอดภัยสูงสุด: เฉพาะระดับ admin เท่านั้นที่มีสิทธิ์เข้าหน้านี้ได้
checkRole(['admin']);

$success_msg = "";
$error_msg = "";

// 1. ระบบเพิ่มผู้ใช้ใหม่
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $name = trim($_POST['name']);
    $role = $_POST['role'];

    if (!empty($username) && !empty($password) && !empty($name)) {
        try {
            // 🛑 ตรวจสอบ Username ซ้ำในฐานข้อมูล
            $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $chk->execute([$username]);
            if ($chk->fetchColumn() > 0) {
                $error_msg = "❌ ไม่สามารถเพิ่มได้: ชื่อบัญชี '$username' นี้มีผู้อื่นใช้งานแล้ว";
            } else {
                // ผ่านการตรวจสอบ -> เพิ่มข้อมูลลงตาราง users
                $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $password, $name, $role]);
                $success_msg = "🎉 เพิ่มบัญชีผู้ใช้งานระบบสำเร็จเรียบร้อย!";
            }
        } catch (\PDOException $e) { $error_msg = $e->getMessage(); }
    }
}

// 2. ระบบลบผู้ใช้งาน (ห้ามลบไอดีของตัวเองเด็ดขาด)
if (isset($_GET['delete_user_id'])) {
    $delete_id = intval($_GET['delete_user_id']);
    if ($delete_id === $_SESSION['user_id']) {
        $error_msg = "❌ ไม่สามารถลบบัญชีที่คุณกำลังใช้งานล็อกอินอยู่ได้ครับ";
    } else {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$delete_id]);
        header("Location: manage_users.php?success=1");
        exit();
    }
}

// ดึงรายชื่อผู้ใช้ทั้งหมดมาแสดงในตาราง
$all_users = $pdo->query("SELECT * FROM users ORDER BY role ASC, id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสมาชิกผู้ใช้งานระบบ - SportsDay Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f2f2f7; color: #1c1c1e; }
        .navbar { background-color: rgba(28, 28, 30, 0.92) !important; backdrop-filter: blur(20px); }
        .ios-card { background: #ffffff; border-radius: 16px !important; border: none !important; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04) !important; }
        .form-control, .form-select { border-radius: 10px; border: 1px solid #d1d1d6; padding: 10px; }
        .btn { border-radius: 10px; padding: 10px; font-weight: 600; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="index.php">🏆 SportsDay Center</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">กลับสู่หน้าแดชบอร์ด</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="fw-bold mb-4">👥 จัดการสมาชิกเจ้าหน้าที่ระบบ</h3>

    <?php if(!empty($success_msg) || isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 rounded-3 shadow-sm mb-3"><i class="fa-solid fa-circle-check me-1"></i> ทำรายการสำเร็จเรียบร้อย!</div>
    <?php endif; ?>
    <?php if(!empty($error_msg)): ?>
        <div class="alert alert-danger border-0 rounded-3 shadow-sm mb-3"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card ios-card p-2">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">➕ เพิ่มเจ้าหน้าที่ใหม่</h5>
                    <form action="manage_users.php" method="POST">
                        <div class="mb-2"><label class="small text-muted">ชื่อผู้ใช้ล็อกอิน (Username)</label>
                            <input type="text" class="form-control" name="username" placeholder="เช่น score02" required autocomplete="off">
                        </div>
                        <div class="mb-2"><label class="small text-muted">รหัสผ่านเริ่มต้น (Password)</label>
                            <input type="text" class="form-control" name="password" placeholder="กำหนดรหัสผ่าน" required>
                        </div>
                        <div class="mb-2"><label class="small text-muted">ชื่อ-นามสกุล ของคุณครู</label>
                            <input type="text" class="form-control" name="name" placeholder="ระบุชื่อจริงเจ้าหน้าที่" required>
                        </div>
                        <div class="mb-3"><label class="small text-muted">กำหนดระดับสิทธิ์การทำงาน</label>
                            <select class="form-select" name="role" required>
                                <option value="scorekeeper">SCOREKEEPER (บันทึกคะแนน+ดูแดชบอร์ด)</option>
                                <option value="admin">ADMIN (ควบคุมจัดการทั้งหมดสูงสุด)</option>
                            </select>
                        </div>
                        <button type="submit" name="add_user" class="btn btn-primary w-100">💾 บันทึกและสร้างบัญชี</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card ios-card p-2">
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-muted small"><th>ชื่อล็อกอิน</th><th>ชื่อ-นามสกุลเจ้าหน้าที่</th><th class="text-center">ระดับสิทธิ์</th><th class="text-center">จัดการ</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $u): ?>
                            <tr>
                                <td class="font-monospace fw-bold"><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['name']); ?></td>
                                <td class="text-center">
                                    <span class="badge px-3 py-2 rounded-pill <?php echo ($u['role'] == 'admin') ? 'bg-danger bg-opacity-10 text-danger' : 'bg-warning bg-opacity-10 text-warning-emphasis'; ?>">
                                        <?php echo strtoupper($u['role']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if($u['id'] !== $_SESSION['user_id']): ?>
                                        <a href="manage_users.php?delete_user_id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบบัญชีผู้ใช้งานรายนี้ออกไปจากระบบ?');"><i class="fa-solid fa-trash"></i></a>
                                    <?php else: ?>
                                        <span class="text-muted small">คุณกำลังใช้งาน</span>
                                    <?php endif; ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
