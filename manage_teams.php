<?php
// 1. ดึงไฟล์เชื่อมต่อฐานข้อมูลมาใช้งาน
require_once 'db.php';

// 2. ระบบบันทึกข้อมูลเมื่อมีการกดปุ่ม "บันทึกทีมสี" (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_team'])) {
    $team_name = trim($_POST['team_name']);
    $team_color = $_POST['team_color'];

    if (!empty($team_name) && !empty($team_color)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO teams (team_name, team_color) VALUES (?, ?)");
            $stmt->execute([$team_name, $team_color]);
            header("Location: manage_teams.php?success=1");
            exit();
        } catch (\PDOException $e) {
            $error_msg = "เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage();
        }
    }
}

// 3. ระบบลบข้อมูลทีมสี
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
        $stmt->execute([$_GET['delete_id']]);
        header("Location: manage_teams.php?success=2");
        exit();
    } catch (\PDOException $e) {
        $error_msg = "ไม่สามารถลบทีมนี้ได้ เนื่องจากมีข้อมูลนักกีฬาหรือผลการแข่งเชื่อมโยงอยู่";
    }
}

// 4. ดึงข้อมูลทีมทั้งหมดมาแสดงผลในตาราง
try {
    $stmt = $pdo->query("SELECT * FROM teams ORDER BY id DESC");
    $teams = $stmt->fetchAll();
} catch (\PDOException $e) {
    $error_msg = "ไม่สามารถดึงข้อมูลได้: " . $e->getMessage();
    $teams = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการทีมกีฬาสี</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="text-center mb-4">🏆 ระบบจัดการทีมกีฬาสี</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
                if($_GET['success'] == 1) echo "เพิ่มข้อมูลทีมสีสำเร็จ!";
                if($_GET['success'] == 2) echo "ลบข้อมูลทีมสีเรียบร้อยแล้ว!";
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger" role="alert"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">🎨 เพิ่มทีมสีใหม่</h5>
                </div>
                <div class="card-body">
                    <form action="manage_teams.php" method="POST">
                        <div class="mb-3">
                            <label for="team_name" class="form-label">ชื่อทีมสี</label>
                            <input type="text" class="form-control" id="team_name" name="team_name" placeholder="เช่น สีแดง, สีน้ำเงิน" required>
                        </div>
                        <div class="mb-3">
                            <label for="team_color" class="form-label">เลือกสีประจำทีม</label>
                            <input type="color" class="form-control form-control-color w-100" id="team_color" name="team_color" value="#007bff" title="เลือกสี" required>
                        </div>
                        <button type="submit" name="add_team" class="btn btn-success w-100">💾 บันทึกทีมสี</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="card-title mb-0">📋 รายชื่อทีมสีในปัจจุบัน</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>ชื่อทีมสี</th>
                                    <th>สีประจำทีม</th>
                                    <th class="text-center">การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($teams) > 0): ?>
                                    <?php foreach ($teams as $index => $team): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><strong><?php echo htmlspecialchars($team['team_name']); ?></strong></td>
                                            <td>
                                                <span class="badge" style="background-color: <?php echo $team['team_color']; ?>; color: #fff; padding: 8px 15px;">
                                                    <?php echo $team['team_color']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="manage_teams.php?delete_id=<?php echo $team['id']; ?>" 
                                                   class="btn btn-danger btn-sm" 
                                                   onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบทีมสีนี้?');">
                                                   🗑️ ลบ
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">ยังไม่มีข้อมูลทีมสีในระบบ</td>
                                    </tr>
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