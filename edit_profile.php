<?php
require_once 'db.php';
require_once 'check_auth.php';
// 🔒 หน้าเฉพาะเจ้าหน้าที่ที่ล็อกอินแล้วเท่านั้น เข้ามาแก้ไขได้
checkRole(['admin', 'scorekeeper']);

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// 1. ดึงข้อมูลปัจจุบันของผู้ใช้มาแสดงในฟอร์ม
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (\PDOException $e) {
    die("ข้อผิดพลาดระบบ: " . $e->getMessage());
}

// 2. ประมวลผลเมื่อกดปุ่มบันทึกการแก้ไข
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $password = trim($_POST['password']);

    if (!empty($name) && !empty($password)) {
        try {
            // อัปเดตเฉพาะ Name และ Password เท่านั้น (ไม่แตะต้องคอลัมน์ role)
            $update = $pdo->prepare("UPDATE users SET name = ?, password = ? WHERE id = ?");
            $update->execute([$name, $password, $user_id]);
            
            // อัปเดตชื่อใน Session ปัจจุบันให้เปลี่ยนตามด้วยทันที
            $_SESSION['user_name'] = $name;
            $success_msg = "🎉 อัปเดตข้อมูลส่วนตัวและรหัสผ่านเรียบร้อยแล้ว!";
            
            // ดึงข้อมูลใหม่มาโชว์ในฟอร์ม
            $user['name'] = $name;
            $user['password'] = $password;
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
    <title>แก้ไขข้อมูลส่วนตัว - SportsDay Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f2f2f7; color: #1c1c1e; }
        .navbar { background-color: rgba(28, 28, 30, 0.92) !important; backdrop-filter: blur(20px); }
        .ios-card { background: #ffffff; border-radius: 16px !important; border: none !important; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04) !important; width: 100%; max-width: 500px; margin: 0 auto; }
        .form-control { border-radius: 10px; border: 1px solid #d1d1d6; padding: 10px; }
        .btn { border-radius: 10px; padding: 12px; font-weight: 600; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="index.php">🏆 SportsDay Center</a>
    </nav>

<div class="container mt-5">
    <div class="card ios-card p-3">
        <div class="card-body">
            <div class="text-center mb-4">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 d-inline-block mb-2">
                    <i class="fa-solid fa-user-gear fa-2x"></i>
                </div>
                <h4 class="fw-bold m-0">ตั้งค่าบัญชีผู้ใช้งาน</h4>
                <p class="text-muted small">แก้ไขชื่อและรหัสผ่านเพื่อความปลอดภัย</p>
            </div>

            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success border-0 rounded-3 text-center small mb-3"><i class="fa-solid fa-circle-check me-1"></i> <?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-danger border-0 rounded-3 text-center small mb-3"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form action="edit_profile.php" method="POST">
                <div class="mb-3">
                    <label class="form-label small text-muted fw-bold">ชื่อผู้ใช้ระบบ (Username)</label>
                    <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    <span class="text-muted" style="font-size: 0.75rem;">* ไม่อนุญาตให้เปลี่ยนชื่อล็อกอินหลัก</span>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted fw-bold">ชื่อ-นามสกุล ของคุณครู</label>
                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted fw-bold">รหัสผ่านใหม่ (Password)</label>
                    <input type="text" class="form-control" name="password" value="<?php echo htmlspecialchars($user['password']); ?>" required placeholder="กำหนดรหัสผ่านใหม่">
                </div>
                <div class="mb-4">
                    <label class="form-label small text-muted fw-bold">ระดับสิทธิ์ปัจจุบัน</label>
                    <input type="text" class="form-control bg-light fw-bold text-primary" value="<?php echo strtoupper($user['role']); ?>" disabled>
                    <span class="text-muted" style="font-size: 0.75rem;">* ล็อกสิทธิ์อัตโนมัติ ไม่สามารถเปลี่ยนแปลงเองได้</span>
                </div>

                <button type="submit" name="update_profile" class="btn btn-primary w-100 text-white mb-2">
                    <i class="fa-solid fa-floppy-disk me-1"></i> บันทึกการเปลี่ยนแปลง
                </button>
                <a href="index.php" class="btn btn-light w-100 text-muted">กลับหน้าหลัก</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>