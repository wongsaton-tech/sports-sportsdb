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
        $edit_datetime = !empty($match_to_edit['match_datetime']) ? date('Y-m-d\TH:i', strtotime($match_to_edit['match_datetime'])) : '';
    }
}

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
                $stmt = $pdo->prepare("UPDATE matches SET category_id = ?, sport_name = ?, gender_type = ?, tournament_type = ?, max_players_per_team = ?, match_datetime = ? WHERE id = ?");
                $stmt->execute([$category_id, $sport_name, $gender_type, $tournament_type, $max_players_per_team, $match_datetime, $target_id]);
                header("Location: manage_matches.php?success=3");
                exit();
            } else {
                $stmt = $pdo->prepare("INSERT INTO matches (category_id, sport_name, gender_type, tournament_type, max_players_per_team, match_datetime) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$category_id, $sport_name, $gender_type, $tournament_type, $max_players_per_team, $match_datetime]);
                header("Location: manage_matches.php?success=1");
                exit();
            }
        } catch (\PDOException $e) { $error_msg = $e->getMessage(); }
    }
}

if (isset($_GET['delete_id'])) {
    try {
        $pdo->prepare("DELETE FROM matches WHERE id = ?")->execute([$_GET['delete_id']]);
        header("Location: manage_matches.php?success=2");
        exit();
    } catch (\PDOException $e) { $error_msg = "⚠️ รายการนี้มีรายชื่อนักกีฬาหรือคะแนนผูกอยู่ ไม่สามารถลบได้"; }
}

$categories = $pdo->query("SELECT * FROM sport_categories ORDER BY category_name ASC")->fetchAll();
$matches = $pdo->query("SELECT m.*, c.category_name FROM matches m LEFT JOIN sport_categories c ON m.category_id = c.id ORDER BY m.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการแข่งขันกีฬา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f2f2f7; color: #1c1c1e; }
        .navbar { background-color: rgba(28, 28, 30, 0.92) !important; backdrop-filter: blur(20px); }
        .ios-card { background: #ffffff; border-radius: 16px !important; border: none !important; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04) !important; }
        .form-control, .form-select { border-radius: 10px; border: 1px solid #d1d1d6; padding: 8px 12px; }
        .btn { border-radius: 10px; padding: 8px 16px; font-weight: 600; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="index.php">🏆 SportsDay Center</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> แดชบอร์ด</a></li>
                <li class="nav-item"><a class="nav-link active" href="manage_matches.php"><i class="fa-solid fa-person-running me-1"></i> รายการแข่ง & นักกีฬา</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="fw-bold mb-4">🏃 จัดการรายการแข่งขันกีฬา</h3>

    <?php if (isset($error_msg)): ?><div class="alert alert-danger rounded-3 border-0"><?php echo $error_msg; ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card ios-card p-2">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><?php echo $edit_mode ? 'แก้ไขข้อมูลแมตช์' : 'สร้างรายการใหม่'; ?></h5>
                    <form action="manage_matches.php" method="POST">
                        <input type="hidden" name="is_edit" value="<?php echo $edit_mode ? '1' : '0'; ?>">
                        <input type="hidden" name="target_id" value="<?php echo $edit_id; ?>">

                        <div class="mb-2"><label class="small text-muted">กลุ่มประเภทกีฬา</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">-- เลือกประเภท --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $edit_category_id) ? 'selected' : ''; ?>><?php echo $cat['category_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="small text-muted">ชื่อชนิดกีฬา</label>
                            <input type="text" class="form-control" name="sport_name" value="<?php echo htmlspecialchars($edit_sport_name); ?>" required>
                        </div>
                        <div class="mb-2"><label class="small text-muted">ประเภทนักกีฬา</label>
                            <select class="form-select" name="gender_type">
                                <option value="ชาย" <?php echo ($edit_gender_type=='ชาย')?'selected':''; ?>>ชาย</option>
                                <option value="หญิง" <?php echo ($edit_gender_type=='หญิง')?'selected':''; ?>>หญิง</option>
                                <option value="ทั่วไป/ผสม" <?php echo ($edit_gender_type=='ทั่วไป/ผสม')?'selected':''; ?>>ทั่วไป/ผสม</option>
                            </select>
                        </div>
                        <div class="mb-2"><label class="small text-muted">กติกาการแข่ง</label>
                            <select class="form-select" name="tournament_type">
                                <option value="พบกันหมด" <?php echo ($edit_tournament_type=='พบกันหมด')?'selected':''; ?>>พบกันหมด</option>
                                <option value="คัดออก" <?php echo ($edit_tournament_type=='คัดออก')?'selected':''; ?>>คัดออก</option>
                            </select>
                        </div>
                        <div class="mb-2"><label class="small text-muted">จำกัดจำนวนคนต่อทีม</label>
                            <input type="number" class="form-control" name="max_players_per_team" value="<?php echo $edit_max_players; ?>" min="1">
                        </div>
                        <div class="mb-3"><label class="small text-muted">วัน เวลาแข่งขัน</label>
                            <input type="datetime-local" class="form-control" name="match_datetime" value="<?php echo $edit_datetime; ?>">
                        </div>
                        <button type="submit" name="save_match" class="btn <?php echo $edit_mode ? 'btn-warning' : 'btn-success'; ?> w-100">บันทึกข้อมูล</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card ios-card p-2">
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr class="text-muted small"><th>รายการแข่งขัน</th><th>กลุ่ม</th><th>รายละเอียด</th><th class="text-center">การจัดการ</th></tr></thead>
                        <tbody>
                            <?php foreach ($matches as $m): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($m['sport_name']); ?> <span class="badge bg-light text-dark font-monospace small"><?php echo $m['gender_type']; ?></span></div>
                                    <small class="text-muted"><?php echo !empty($m['match_datetime']) ? date('d/m/Y H:i', strtotime($m['match_datetime'])) : '-'; ?></small>
                                </td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?php echo htmlspecialchars($m['category_name']); ?></span></td>
                                <td class="small text-muted"><?php echo $m['tournament_type']; ?><br><i class="fa-solid fa-users me-1"></i><?php echo $m['max_players_per_team']; ?> คน</td>
                                <td class="text-center">
                                    <a href="register_athletes.php?match_id=<?php echo $m['id']; ?>" class="btn btn-sm btn-info text-white"><i class="fa-solid fa-users"></i></a>
                                    <a href="manage_matches.php?edit_id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-warning"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <a href="manage_matches.php?delete_id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ลบ?');"><i class="fa-solid fa-trash"></i></a>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
