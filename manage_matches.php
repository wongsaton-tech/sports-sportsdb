<?php
require_once 'db.php';

$edit_mode = false;
$edit_id = '';
$edit_category_id = '';
$edit_sport_name = '';
$edit_gender_type = '';
$edit_tournament_type = '';
$edit_max_players = 1;
$edit_datetime = '';

// ================= สเต็บที่ 1: ดึงข้อมูลรายการแข่งขันมาเข้าฟอร์มเมื่อกด "แก้ไข" (GET) =================
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
    $stmt->execute([$edit_id]);
    $match_to_edit = $stmt->fetch();
    
    if ($match_to_edit) {
        $edit_mode = true;
        $edit_category_id = $match_to_edit['category_id'];
        $edit_sport_name = $match_to_edit['sport_name'];
        $edit_gender_type = $match_to_edit['gender_type'];
        $edit_tournament_type = $match_to_edit['tournament_type'];
        $edit_max_players = $match_to_edit['max_players_per_team'];
        // แปลงฟอร์แมตวันที่ให้ตรงกับที่ input datetime-local ต้องการ (Y-m-d\TH:i)
        $edit_datetime = !empty($match_to_edit['match_datetime']) ? date('Y-m-d\TH:i', strtotime($match_to_edit['match_datetime'])) : '';
    }
}

// ================= สเต็บที่ 2: ระบบบันทึกข้อมูล (ทั้งสร้างใหม่ และ อัปเดตแก้ไข) =================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_match'])) {
    $category_id = $_POST['category_id'];
    $sport_name = trim($_POST['sport_name']);
    $gender_type = $_POST['gender_type'];
    $tournament_type = $_POST['tournament_type'];
    $max_players_per_team = intval($_POST['max_players_per_team']);
    $match_datetime = !empty($_POST['match_datetime']) ? $_POST['match_datetime'] : null;
    
    $is_edit = isset($_POST['is_edit']) && $_POST['is_edit'] == '1';
    $target_id = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;

    if (!empty($category_id) && !empty($sport_name)) {
        try {
            if ($is_edit) {
                // เคสแก้ไขอัปเดตข้อมูล
                $stmt = $pdo->prepare("UPDATE matches SET category_id = ?, sport_name = ?, gender_type = ?, tournament_type = ?, max_players_per_team = ?, match_datetime = ? WHERE id = ?");
                $stmt->execute([$category_id, $sport_name, $gender_type, $tournament_type, $max_players_per_team, $match_datetime, $target_id]);
                header("Location: manage_matches.php?success=3");
                exit();
            } else {
                // เคสสร้างรายการแข่งขันใหม่
                $stmt = $pdo->prepare("INSERT INTO matches (category_id, sport_name, gender_type, tournament_type, max_players_per_team, match_datetime) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$category_id, $sport_name, $gender_type, $tournament_type, $max_players_per_team, $match_datetime]);
                header("Location: manage_matches.php?success=1");
                exit();
            }
        } catch (\PDOException $e) {
            $error_msg = "เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage();
        }
    }
}

// ================= สเต็บที่ 3: ระบบลบรายการแข่งขันพร้อมดัก Error ความปลอดภัย =================
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM matches WHERE id = ?");
        $stmt->execute([$delete_id]);
        header("Location: manage_matches.php?success=2");
        exit();
    } catch (\PDOException $e) {
        // หากเกิด Foreign Key Constraint Error (เพราะมีรายชื่อนักกีฬาหรือคะแนนผูกอยู่) ระบบจะแจ้งเตือนแทนการปล่อยให้เว็บล่ม
        $error_msg = "⚠️ ไม่สามารถลบรายการแข่งขันนี้ได้ เนื่องจากมีรายชื่อนักเรียนลงทะเบียนแข่ง หรือมีการบันทึกคะแนนผลการแข่งขันในระบบแล้ว (กรุณาลบข้อมูลเหล่านั้นก่อน)";
    }
}

// ดึงข้อมูลตัวเลือกประเภทกีฬา และรายการแมตช์ทั้งหมดมาแสดงผล
$categories = $pdo->query("SELECT * FROM sport_categories ORDER BY category_name ASC")->fetchAll();
$matches = $pdo->query("SELECT m.*, c.category_name FROM matches m LEFT JOIN sport_categories c ON m.category_id = c.id ORDER BY m.id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างและจัดการรายการแข่งขันกีฬาสี</title>
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
                <li class="nav-item"><a class="nav-link" href="manage_teams.php"><i class="fa-solid fa-palette me-1"></i> จัดการทีมสี</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_categories.php"><i class="fa-solid fa-folder-open me-1"></i> ประเภทกีฬา</a></li>
                <li class="nav-item"><a class="nav-link active" href="manage_matches.php"><i class="fa-solid fa-person-running me-1"></i> รายการแข่ง & นักกีฬา</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_scores.php"><i class="fa-solid fa-star me-1"></i> บันทึกคะแนน</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="mb-4">🏃 จัดการและสร้างรายการแข่งขันกีฬา</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-1"></i>
            <?php 
                if($_GET['success'] == 1) echo "สร้างรายการแข่งขันใหม่สำเร็จ!";
                if($_GET['success'] == 2) echo "ลบรายการแข่งขันเรียบร้อยแล้ว!";
                if($_GET['success'] == 3) echo "แก้ไขข้อมูลรายการแข่งขันสำเร็จ!";
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-start border-4 <?php echo $edit_mode ? 'border-warning' : 'border-success'; ?>">
                <div class="card-header <?php echo $edit_mode ? 'bg-warning text-dark' : 'bg-success text-white'; ?> fw-bold">
                    <h5 class="mb-0"><?php echo $edit_mode ? '📝 แก้ไขรายการแข่งขัน' : '➕ สร้างรายการแข่งขันใหม่'; ?></h5>
                </div>
                <div class="card-body">
                    <form action="manage_matches.php" method="POST">
                        <input type="hidden" name="is_edit" value="<?php echo $edit_mode ? '1' : '0'; ?>">
                        <input type="hidden" name="target_id" value="<?php echo $edit_id; ?>">

                        <div class="mb-2">
                            <label class="form-label fw-bold">กลุ่มประเภทกีฬา</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">-- เลือกประเภท --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $edit_category_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold">ชื่อชนิดกีฬาที่แข่ง</label>
                            <input type="text" class="form-control" name="sport_name" value="<?php echo htmlspecialchars($edit_sport_name); ?>" placeholder="เช่น วิ่ง 100 เมตร, ฟุตบอล" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold">ประเภทนักกีฬา</label>
                            <select class="form-select" name="gender_type">
                                <option value="ชาย" <?php echo ($edit_gender_type == 'ชาย') ? 'selected' : ''; ?>>ชาย</option>
                                <option value="หญิง" <?php echo ($edit_gender_type == 'หญิง') ? 'selected' : ''; ?>>หญิง</option>
                                <option value="ทั่วไป/ผสม" <?php echo ($edit_gender_type == 'ทั่วไป/ผสม') ? 'selected' : ''; ?>>ทั่วไป/ผสม</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold">ลักษณะการแข่งขัน</label>
                            <select class="form-select" name="tournament_type" required>
                                <option value="พบกันหมด" <?php echo ($edit_tournament_type == 'พบกันหมด') ? 'selected' : ''; ?>>แบบพบกันหมด</option>
                                <option value="คัดออก" <?php echo ($edit_tournament_type == 'คัดออก') ? 'selected' : ''; ?>>แบบคัดออก</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold">จำกัดนักกีฬาต่อทีมสี (คน)</label>
                            <input type="number" class="form-control" name="max_players_per_team" value="<?php echo $edit_max_players; ?>" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">วัน เวลาแข่งขัน</label>
                            <input type="datetime-local" class="form-control" name="match_datetime" value="<?php echo $edit_datetime; ?>">
                        </div>

                        <div class="row g-2">
                            <div class="<?php echo $edit_mode ? 'col-6' : 'col-12'; ?>">
                                <button type="submit" name="save_match" class="btn <?php echo $edit_mode ? 'btn-warning fw-bold' : 'btn-success'; ?> w-100">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> <?php echo $edit_mode ? 'อัปเดตรายการ' : 'สร้างรายการแข่งขัน'; ?>
                                </button>
                            </div>
                            <?php if ($edit_mode): ?>
                                <div class="col-6">
                                    <a href="manage_matches.php" class="btn btn-secondary w-100">ยกเลิก</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white"><h5>📋 รายการแข่งขันทั้งหมด</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>รายการแข่งขัน</th>
                                    <th>กลุ่ม</th>
                                    <th>ลักษณะ/โควตา</th>
                                    <th class="text-center" width="35%">การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($matches) > 0): ?>
                                    <?php foreach ($matches as $m): ?>
                                    <tr <?php echo ($edit_id == $m['id']) ? 'class="table-warning"' : ''; ?>>
                                        <td>
                                            <strong><?php echo htmlspecialchars($m['sport_name']); ?></strong> 
                                            <span class="badge bg-opacity-10 text-dark bg-dark small"><?php echo $m['gender_type']; ?></span>
                                            <?php if(!empty($m['match_datetime'])): ?>
                                                <div class="text-muted small mt-1"><i class="fa-regular fa-calendar-days me-1"></i> <?php echo date('d/m/Y H:i', strtotime($m['match_datetime'])); ?> น.</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($m['category_name'] ?? 'ไม่ได้ระบุ'); ?></span></td>
                                        <td>
                                            <div class="small text-dark"><?php echo $m['tournament_type']; ?></div>
                                            <div class="small text-muted"><i class="fa-solid fa-users me-1"></i><?php echo $m['max_players_per_team']; ?> คน/สี</div>
                                        </td>
                                        <td class="text-center">
                                            <a href="register_athletes.php?match_id=<?php echo $m['id']; ?>" class="btn btn-info btn-sm text-white" title="ลงทะเบียนนักกีฬา">
                                                <i class="fa-solid fa-users"></i> นักกีฬา
                                            </a>
                                            <a href="manage_matches.php?edit_id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-warning">
                                                <i class="fa-solid fa-pen-to-square"></i> แก้ไข
                                            </a>
                                            <a href="manage_matches.php?delete_id=<?php echo $m['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบรายการแข่งขันนี้? ข้อมูลในรายชื่อนักกีฬาและผลการแข่งจะหายไปด้วยหากไม่มีการตรวจเช็ค');">
                                                <i class="fa-solid fa-trash"></i> ลบ
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted p-4">ยังไม่มีข้อมูลรายการแข่งขันในระบบ</td></tr>
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
