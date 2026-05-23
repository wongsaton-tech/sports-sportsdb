<?php
require_once 'db.php';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการกลุ่มประเภทกีฬา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="index.php">🏆 SportsDay Center</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> แดชบอร์ด</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_teams.php"><i class="fa-solid fa-palette me-1"></i> จัดการทีมสี</a></li>
                <li class="nav-item"><a class="nav-link active" href="manage_categories.php"><i class="fa-solid fa-folder-open me-1"></i> ประเภทกีฬา</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_matches.php"><i class="fa-solid fa-person-running me-1"></i> รายการแข่ง & นักกีฬา</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="mb-4">🗂️ จัดการกลุ่มประเภทกีฬา</h2>

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
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white"><h5 class="mb-0">เพิ่มกลุ่มประเภท</h5></div>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
