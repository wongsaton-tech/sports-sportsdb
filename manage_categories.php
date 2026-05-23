<?php
require_once 'db.php';

// บันทึกประเภทกีฬา
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    if (!empty($category_name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO sport_categories (category_name) VALUES (?)");
            $stmt->execute([$category_name]);
            header("Location: manage_categories.php?success=1");
            exit();
        } catch (\PDOException $e) { $error_msg = $e->getMessage(); }
    }
}

// ลบประเภทกีฬา
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM sport_categories WHERE id = ?");
        $stmt->execute([$_GET['delete_id']]);
        header("Location: manage_categories.php?success=2");
        exit();
    } catch (\PDOException $e) { $error_msg = "ไม่สามารถลบได้ เนื่องจากถูกนำไปใช้งานในรายการแข่งขันแล้ว"; }
}

$categories = $pdo->query("SELECT * FROM sport_categories ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการกลุ่มประเภทกีฬา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🗂️ จัดการกลุ่มประเภทกีฬา (เพื่อออกรายงาน)</h2>
        <a href="manage_teams.php" class="btn btn-outline-secondary">🎨 ไปหน้าจัดการทีมสี</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_GET['success'] == 1 ? "เพิ่มสำเร็จ!" : "ลบสำเร็จ!"; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white"><h5>เพิ่มกลุ่มประเภท</h5></div>
                <div class="card-body">
                    <form action="manage_categories.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">ชื่อกลุ่มประเภท</label>
                            <input type="text" class="form-control" name="category_name" placeholder="เช่น กรีฑาประเภทลู่, ขบวนพาเหรด" required>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-primary w-100">บันทึก</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-striped">
                        <thead><tr><th>#</th><th>ชื่อกลุ่มประเภทกีฬา</th><th>จัดการ</th></tr></thead>
                        <tbody>
                            <?php foreach ($categories as $i => $cat): ?>
                            <tr>
                                <td><?php echo $i+1; ?></td>
                                <td><?php echo htmlspecialchars($cat['category_name']); ?></td>
                                <td><a href="manage_categories.php?delete_id=<?php echo $cat['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('ยืนยันการลบ?');">ลบ</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>