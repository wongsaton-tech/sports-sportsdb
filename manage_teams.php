<?php
require_once 'db.php';

$edit_mode = false;
$edit_id = '';
$edit_name = '';
$edit_color = '';

// ================= สเต็บที่ 1: ดึงข้อมูลมาสแตนด์บายเมื่อกดปุ่ม "แก้ไข" (GET) =================
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

// ================= สเต็บที่ 2: ระบบบันทึกข้อมูล (ทั้งเพิ่มใหม่ และ อัปเดตแก้ไข) =================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_team'])) {
    $team_name = trim($_POST['team_name']);
    $team_color = $_POST['team_color'];
    $is_edit = isset($_POST['is_edit']) && $_POST['is_edit'] == '1';
    $target_id = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;

    if (!empty($team_name) && !empty($team_color)) {
        try {
            if ($is_edit) {
                // 🛑 เคสแก้ไข: เช็กว่าชื่อใหม่นี้ ไปซ้ำกับ "ทีมอื่น" หรือไม่ (หลีกเลี่ยง ID ของตัวเอง)
                $chk = $pdo->prepare("SELECT COUNT(*) FROM teams WHERE team_name = ? AND id != ?");
                $chk->execute([$team_name, $target_id]);
                $is_duplicate = $chk->fetchColumn() > 0;

                if ($is_duplicate) {
                    $error_msg = "❌ ไม่สามารถแก้ไขได้: มีชื่อ '" . htmlspecialchars($team_name) . "' อยู่ในระบบแล้ว!";
                    // ล็อคค่าฟอร์มไว้ให้แก้ไขต่อ
                    $edit_mode = true; $edit_id = $target_id; $edit_name = $team_name; $edit_color = $team_color;
                } else {
                    // ผ่านการตรวจสอบ -> ทำการอัปเดต
                    $stmt = $pdo->prepare("UPDATE teams SET team_name = ?, team_color = ? WHERE id = ?");
                    $stmt->execute([$team_name, $team_color, $target_id]);
                    header("Location: manage_teams.php?success=3");
                    exit();
                }
            } else {
                // 🛑 เคสเพิ่มใหม่: เช็กว่าชื่อซ้ำกับที่มีอยู่แล้วในระบบทั้งหมดหรือไม่
                $chk = $pdo->prepare("SELECT COUNT(*) FROM teams WHERE team_name = ?");
                $chk->execute([$team_name]);
                $is_duplicate = $chk->fetchColumn() > 0;

                if ($is_duplicate) {
                    $error_msg = "❌ ไม่สามารถเพิ่มได้: มีชื่อ '" . htmlspecialchars($team_name) . "' อยู่ในระบบแล้ว!";
                } else {
                    // ผ่านการตรวจสอบ -> ทำการเพิ่มใหม่
                    $stmt = $pdo->prepare("INSERT INTO teams (team_name, team_color) VALUES (?, ?)");
                    $stmt->execute([$team_name, $team_color]);
                    header("Location: manage_teams.php?success=1");
                    exit();
                }
            }
        } catch (\PDOException $e) {
            $error_msg = "เกิดข้อผิดพลาดในระบบ: " . $e->getMessage();
        }
    }
}

// ================= สเต็บที่ 3: ระบบลบข้อมูลทีมสี =================
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
        $stmt->execute([$_GET['delete_id']]);
        header("Location: manage_teams.php?success=2");
        exit();
    } catch (\PDOException $e) {
        $error_msg = "⚠️ ไม่สามารถลบทีมนี้ได้ เนื่องจากมีข้อมูลนักกีฬาหรือผลการแข่งเชื่อมโยงอยู่";
    }
}

// ดึงข้อมูลทีมทั้งหมดมาแสดงผลในตาราง
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
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> แดชบอร์ด</a></li>
                <li class="nav-item"><a class="nav-link active" href="manage_teams.php"><i class="fa-solid fa-palette me-1"></i> จัดการทีมสี</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_categories.php"><i class="fa-solid fa-folder-open me-1"></i> ประเภทกีฬา</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_matches.php"><i class="fa-solid fa-person-running me-1"></i> รายการแข่ง & นักกีฬา</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_scores.php"><i class="fa-solid fa-star me-1"></i> บันทึกคะแนน</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="mb-4">🎨 ระบบจัดการทีมกีฬาสี</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-1"></i>
            <?php 
                if($_GET['success'] == 1) echo "เพิ่มข้อมูลทีมสีสำเร็จ!";
                if($_GET['success'] == 2) echo "ลบข้อมูลทีมสีเรียบร้อยแล้ว!";
                if($_GET['success'] == 3) echo "อัปเดตแก้ไขข้อมูลทีมสีสำเร็จ!";
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-start border-4 <?php echo $edit_mode ? 'border-warning' : 'border-primary'; ?>">
                <div class="card-header <?php echo $edit_mode ? 'bg-warning text-dark' : 'bg-primary text-white'; ?> fw-bold">
                    <h5 class="mb-0"><?php echo $edit_mode ? '📝 แก้ไขข้อมูลทีมสี' : '🎨 เพิ่มทีมสีใหม่'; ?></h5>
                </div>
                <div class="card-body">
                    <form action="manage_teams.php" method="POST">
                        <input type="hidden" name="is_edit" value="<?php echo $edit_mode ? '1' : '0'; ?>">
                        <input type="hidden" name="target_id" value="<?php echo $edit_id; ?>">

                        <div class="mb-3">
                            <label class="form-label fw-bold">ชื่อทีมสี</label>
                            <input type="text" class="form-control" name="team_name" value="<?php echo htmlspecialchars($edit_name); ?>" placeholder="เช่น สีแดง, สีน้ำเงิน" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">เลือกสีประจำทีม</label>
                            <input type="color" class="form-control form-control-color w-100" name="team_color" value="<?php echo !empty($edit_color) ? $edit_color : '#007bff'; ?>" required>
                        </div>
                        
                        <div class="row g-2">
                            <div class="<?php echo $edit_mode ? 'col-6' : 'col-12'; ?>">
                                <button type="submit" name="save_team" class="btn <?php echo $edit_mode ? 'btn-warning fw-bold' : 'btn-success'; ?> w-100">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> <?php echo $edit_mode ? 'อัปเดตข้อมูล' : 'บันทึกทีมสี'; ?>
                                </button>
                            </div>
                            <?php if ($edit_mode): ?>
                                <div class="col-6">
                                    <a href="manage_teams.php" class="btn btn-secondary w-100">ยกเลิก</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white"><h5 class="mb-0">รายชื่อทีมสีในปัจจุบัน</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="10%">#</th>
                                    <th width="40%">ชื่อทีมสี</th>
                                    <th width="25%">สีประจำทีม</th>
                                    <th width="25%" class="text-center">การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($teams) > 0): ?>
                                    <?php foreach ($teams as $index => $team): ?>
                                        <tr <?php echo ($edit_id == $team['id']) ? 'class="table-warning"' : ''; ?>>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><strong><?php echo htmlspecialchars($team['team_name']); ?></strong></td>
                                            <td>
                                                <span class="badge" style="background-color: <?php echo $team['team_color']; ?>; padding: 8px 15px; color: #fff; text-shadow: 0px 1px 2px rgba(0,0,0,0.5);">
                                                    <?php echo $team['team_color']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="manage_teams.php?edit_id=<?php echo $team['id']; ?>" class="btn btn-sm btn-outline-warning me-1">
                                                    <i class="fa-solid fa-pen-to-square"></i> แก้ไข
                                                </a>
                                                <a href="manage_teams.php?delete_id=<?php echo $team['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบทีมสีนี้?');">
                                                    <i class="fa-solid fa-trash"></i> ลบ
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted p-4">ยังไม่มีข้อมูลทีมสี</td></tr>
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
