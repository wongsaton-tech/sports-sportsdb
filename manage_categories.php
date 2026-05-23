<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    if (!empty($category_name)) {
        try {
            $pdo->prepare("INSERT INTO sport_categories (category_name) VALUES (?)")->execute([$category_name]);
            header("Location: manage_categories.php?success=1");
            exit();
        } catch (\PDOException $e) { $error_msg = $e->getMessage(); }
    }
}

if (isset($_GET['delete_id'])) {
    try {
        $pdo->prepare("DELETE FROM sport_categories WHERE id = ?")->execute([$_GET['delete_id']]);
        header("Location: manage_categories.php?success=2");
        exit();
    } catch (\PDOException $e) { $error_msg = "⚠️ ไม่สามารถลบกลุ่มประเภทนี้ได้"; }
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
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f2f2f7; color: #1c1c1e; }
        .navbar { background-color: rgba(28, 28, 30, 0.92) !important; backdrop-filter: blur(20px); }
        .ios-card { background: #ffffff; border-radius: 16px !important; border: none !important; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04) !important; }
        .form-control { border-radius: 10px; border: 1px solid #d1d1d6; padding: 10px; }
        .btn { border-radius: 10px; padding: 10px; font-weight: 600; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="index.php">🏆 SportsDay Center</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> แดชบอร์ด</a></li>
                <li class="nav-item"><a class="nav-link active" href="manage_categories.php"><i class="fa-solid fa-folder-open me-1"></i> ประเภทกีฬา</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="fw-bold mb-4">🗂️ จัดการกลุ่มประเภทกีฬา</h3>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card ios-card p-2">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">เพิ่มกลุ่มประเภท</h5>
                    <form action="manage_categories.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small text-muted">ชื่อกลุ่มประเภท</label>
                            <input type="text" class="form-control" name="category_name" placeholder="เช่น กรีฑาประเภทลู่" required>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-primary w-100">เพิ่มรายการ</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card ios-card p-2">
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr class="text-muted small"><th>#</th><th>ชื่อกลุ่มประเภทกีฬา</th><th class="text-center">จัดการ</th></tr></thead>
                        <tbody>
                            <?php foreach ($categories as $i => $cat): ?>
                            <tr>
                                <td><?php echo $i+1; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($cat['category_name']); ?></td>
                                <td class="text-center"><a href="manage_categories.php?delete_id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ลบ?');"><i class="fa-solid fa-trash"></i></a></td>
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
