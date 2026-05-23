<?php
require_once 'db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ถ้ามีการล็อกอินค้างไว้แล้ว ให้เด้งไปหน้าแรกทันที
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']); // ในระบบจริงควรใช้ password_hash และ password_verify

    if (!empty($username) && !empty($password)) {
        try {
            // ค้นหาผู้ใช้จากฐานข้อมูล
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // ตรวจสอบรหัสผ่าน (เปรียบเทียบตรงตัวตามข้อมูลตัวอย่างที่คีย์เข้า DBeaver)
            if ($user && $user['password'] === $password) {
                // ฝังค่าสิทธิ์และข้อมูลลงใน Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                // ล็อกอินผ่าน! ส่งไปหน้าแรก
                header("Location: index.php");
                exit();
            } else {
                $error_msg = "❌ ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
            }
        } catch (\PDOException $e) {
            $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    } else {
        $error_msg = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - SportsDay Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f2f2f7; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: #ffffff; border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06); width: 100%; max-width: 400px; padding: 30px; }
        .form-control { border-radius: 12px; padding: 12px; border: 1px solid #d1d1d6; background-color: #f2f2f7; }
        .form-control:focus { background-color: #ffffff; border-color: #007aff; box-shadow: none; }
        .btn-login { border-radius: 12px; padding: 12px; font-weight: 600; background-color: #007aff; border: none; }
        .btn-login:hover { background-color: #0062cc; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <div class="display-5 text-warning mb-2">🏆</div>
        <h4 class="fw-bold m-0">SportsDay Center</h4>
        <p class="text-muted small">ระบบจัดการการแข่งขันและบันทึกคะแนน</p>
    </div>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger border-0 rounded-3 small p-2 text-center mb-3"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="mb-3">
            <label class="form-label small text-muted fw-bold">ชื่อผู้ใช้งาน (Username)</label>
            <input type="text" class="form-control" name="username" placeholder="เช่น admin01" required autocomplete="off">
        </div>
        <div class="mb-4">
            <label class="form-label small text-muted fw-bold">รหัสผ่าน (Password)</label>
            <input type="password" class="form-control" name="password" placeholder="••••••" required>
        </div>
        <button type="submit" class="btn btn-primary btn-login w-100 text-white mb-2">
            <i class="fa-solid fa-right-to-bracket me-1"></i> เข้าสู่ระบบ
        </button>
        <a href="index.php" class="btn btn-light w-100 py-2 small border-0" style="border-radius: 12px;">
            <i class="fa-solid fa-eye me-1"></i> เข้าดูหน้าแดชบอร์ดทั่วไป
        </a>
    </form>
</div>

</body>
</html>