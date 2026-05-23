<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_team'])) {
    $team_name = trim($_POST['team_name']);
    $team_color = $_POST['team_color'];

    if (!empty($team_name) && !empty($team_color)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO teams (team_name, team_color) VALUES (?, ?)");
            $stmt->execute([$team_name, $team_color]);
            header("Location: manage_teams.php?success=1");
            exit();
        } catch (\PDOException $e) { $error_msg = "เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage(); }
    }
}

if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
        $stmt->execute([$_GET['delete_id']]);
        header("Location: manage_teams.php?success=2");
        exit();
    } catch (\PDOException $e) { $error_msg = "ไม่สามารถลบทีมนี้ได้ เนื่องจากมีข้อมูลนักกีฬาหรือผลการแข่งเชื่อมโยงอยู่"; }
}

$teams = $pdo->query("SELECT * FROM teams ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการทีมกีฬาสี</title>
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
                <li class="nav-item"><a class="nav-link active" href="manage_teams.php"><i class="fa-solid fa-palette me-1"></i> จัดการทีมสี</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_categories.php"><i class="fa-solid fa-folder-open me-1"></i> ประเภทกีฬา</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_matches.php"><i class="fa-solid fa-person-running me-1"></i> รายการแข่ง & นักกีฬา</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="mb-4">🎨 ระบบจัดการทีมกีฬาสี</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_GET['success'] == 1 ? "เพิ่มข้อมูลทีมสีสำเร็จ!" : "ลบข้อมูลทีมสีเรียบร้อยแล้ว!"; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger" role="alert"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white"><h5 class="mb-0">เพิ่มทีมสีใหม่</h5></div>
                <div class="card-body">
                    <form action="manage_teams.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">ชื่อทีมสี</label>
                            <input type="text" class="form-control" name="team_name" placeholder="เช่น สีแดง, สีน้ำเงิน" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">เลือกสีประจำทีม</label>
                            <input type="color" class="form-control form-control-color w-100" name="team_color" value="#007bff" required>
                        </div>
                        <button type="submit" name="add_team" class="btn btn-success w-100">💾 บันทึกทีมสี</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white"><h5 class="mb-0">รายชื่อทีมสีในปัจจุบัน</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr><th>#</th><th>ชื่อทีมสี</th><th>สีประจำทีม</th><th class="text-center">การจัดการ</th></tr>
                            </thead>
                            <tbody>
                                <?php if (count($teams) > 0): ?>
                                    <?php foreach ($teams as $index => $team): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><strong><?php echo htmlspecialchars($team['team_name']); ?></strong></td>
                                            <td><span class="badge" style="background-color: <?php echo $team['team_color']; ?>; padding: 8px 15px;"><?php echo $team['team_color']; ?></span></td>
                                            <td class="text-center">
                                                <a href="manage_teams.php?delete_id=<?php echo $team['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('ยืนยันการลบ?');">🗑️ ลบ</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted">ยังไม่มีข้อมูลทีมสี</td></tr>
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
