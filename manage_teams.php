<?php
require_once 'db.php';

$edit_mode = false;
$edit_id = '';
$edit_name = '';
$edit_color = '';

if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$edit_id]);
    $team_to_edit = $stmt->fetch();
    
    if ($team_to_edit) {
        $edit_mode = true;
        $edit_name = $team_to_edit['team_name'];
        $edit_color = $team_to_edit['team_color'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_team'])) {
    $team_name = trim($_POST['team_name']);
    $team_color = $_POST['team_color'];
    $is_edit = isset($_POST['is_edit']) && $_POST['is_edit'] == '1';
    $target_id = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;

    if (!empty($team_name) && !empty($team_color)) {
        try {
            if ($is_edit) {
                $chk = $pdo->prepare("SELECT COUNT(*) FROM teams WHERE team_name = ? AND id != ?");
                $chk->execute([$team_name, $target_id]);
                if ($chk->fetchColumn() > 0) {
                    $error_msg = "❌ มีชื่อทีมสี '" . htmlspecialchars($team_name) . "' นี้อยู่ในระบบแล้ว";
                    $edit_mode = true; $edit_id = $target_id; $edit_name = $team_name; $edit_color = $team_color;
                } else {
                    $stmt = $pdo->prepare("UPDATE teams SET team_name = ?, team_color = ? WHERE id = ?");
                    $stmt->execute([$team_name, $team_color, $target_id]);
                    header("Location: manage_teams.php?success=3");
                    exit();
                }
            } else {
                $chk = $pdo->prepare("SELECT COUNT(*) FROM teams WHERE team_name = ?");
                $chk->execute([$team_name]);
                if ($chk->fetchColumn() > 0) {
                    $error_msg = "❌ มีชื่อทีมสี '" . htmlspecialchars($team_name) . "' นี้อยู่ในระบบแล้ว";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO teams (team_name, team_color) VALUES (?, ?)");
                    $stmt->execute([$team_name, $team_color]);
                    header("Location: manage_teams.php?success=1");
                    exit();
                }
            }
        } catch (\PDOException $e) { $error_msg = $e->getMessage(); }
    }
}

if (isset($_GET['delete_id'])) {
    try {
        $pdo->prepare("DELETE FROM teams WHERE id = ?")->execute([$_GET['delete_id']]);
        header("Location: manage_teams.php?success=2");
        exit();
    } catch (\PDOException $e) { $error_msg = "⚠️ ไม่สามารถลบทีมได้ เนื่องจากมีข้อมูลส่วนอื่นผูกอยู่"; }
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
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> แดชบอร์ด</a></li>
                <li class="nav-item"><a class="nav-link active" href="manage_teams.php"><i class="fa-solid fa-palette me-1"></i> จัดการทีมสี</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="fw-bold mb-4">🎨 จัดการกลุ่มทีมสี</h3>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 rounded-3 shadow-sm mb-3">
            <i class="fa-solid fa-circle-check me-1"></i> ทำรายการสำเร็จเรียบร้อย!
        </div>
    <?php endif; ?>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger border-0 rounded-3 shadow-sm mb-3"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card ios-card p-2">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><?php echo $edit_mode ? 'แก้ไขข้อมูล' : 'เพิ่มทีมสีใหม่'; ?></h5>
                    <form action="manage_teams.php" method="POST">
                        <input type="hidden" name="is_edit" value="<?php echo $edit_mode ? '1' : '0'; ?>">
                        <input type="hidden" name="target_id" value="<?php echo $edit_id; ?>">

                        <div class="mb-3">
                            <label class="form-label small text-muted">ชื่อทีมสี</label>
                            <input type="text" class="form-control" name="team_name" value="<?php echo htmlspecialchars($edit_name); ?>" required placeholder="เช่น สีเหลือง">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">เลือกสีประจำทีม</label>
                            <input type="color" class="form-control form-control-color w-100 p-1" name="team_color" value="<?php echo !empty($edit_color) ? $edit_color : '#007bff'; ?>" required>
                        </div>
                        <button type="submit" name="save_team" class="btn <?php echo $edit_mode ? 'btn-warning' : 'btn-primary'; ?> w-100">
                            <i class="fa-solid fa-floppy-disk me-1"></i> บันทึกข้อมูล
                        </button>
                        <?php if($edit_mode): ?><a href="manage_teams.php" class="btn btn-light w-100 mt-2">ยกเลิก</a><?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card ios-card p-2">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-muted small"><th>#</th><th>ชื่อทีมสี</th><th>สีประจำทีม</th><th class="text-center">การจัดการ</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teams as $i => $t): ?>
                                <tr>
                                    <td><?php echo $i+1; ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($t['team_name']); ?></td>
                                    <td><span class="badge px-3 py-2" style="background-color: <?php echo $t['team_color']; ?>; border-radius: 8px;"><?php echo $t['team_color']; ?></span></td>
                                    <td class="text-center">
                                        <a href="manage_teams.php?edit_id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-warning me-1"><i class="fa-solid fa-pen-to-square"></i></a>
                                        <a href="manage_teams.php?delete_id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ยืนยันลบ?');"><i class="fa-solid fa-trash"></i></a>
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
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
