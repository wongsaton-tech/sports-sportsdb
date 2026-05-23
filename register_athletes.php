<?php
require_once 'db.php';

$match_id = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;
$stmt = $pdo->prepare("SELECT m.*, c.category_name FROM matches m LEFT JOIN sport_categories c ON m.category_id = c.id WHERE m.id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch();

if (!$match) { die("ไม่พบรายการแข่งขันนี้"); }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_athlete'])) {
    $team_id = $_POST['team_id'];
    $athlete_name = trim($_POST['athlete_name']);
    $student_id = trim($_POST['student_id']);

    $check = $pdo->prepare("SELECT COUNT(*) FROM athletes WHERE match_id = ? AND team_id = ?");
    $check->execute([$match_id, $team_id]);
    
    if ($check->fetchColumn() >= $match['max_players_per_team']) {
        $error_msg = "❌ โควตานักกีฬาของสีนี้เต็มแล้ว (จำกัด " . $match['max_players_per_team'] . " คน)";
    } else {
        $pdo->prepare("INSERT INTO athletes (team_id, match_id, athlete_name, student_id) VALUES (?, ?, ?, ?)")->execute([$team_id, $match_id, $athlete_name, $student_id]);
        header("Location: register_athletes.php?match_id=$match_id&success=1");
        exit();
    }
}

if (isset($_GET['delete_athlete_id'])) {
    $pdo->prepare("DELETE FROM athletes WHERE id = ?")->execute([$_GET['delete_athlete_id']]);
    header("Location: register_athletes.php?match_id=$match_id&success=2");
    exit();
}

$teams = $pdo->query("SELECT * FROM teams")->fetchAll();
$athletes = $pdo->prepare("SELECT a.*, t.team_name, t.team_color FROM athletes a JOIN teams t ON a.team_id = t.id WHERE a.match_id = ? ORDER BY t.id, a.id");
$athletes->execute([$match_id]);
$athlete_list = $athletes->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนนักกีฬา</title>
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
                <li class="nav-item"><a class="nav-link" href="manage_teams.php"><i class="fa-solid fa-palette me-1"></i> จัดการทีมสี</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_categories.php"><i class="fa-solid fa-folder-open me-1"></i> ประเภทกีฬา</a></li>
                <li class="nav-item"><a class="nav-link active" href="manage_matches.php"><i class="fa-solid fa-person-running me-1"></i> รายการแข่ง & นักกีฬา</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_scores.php"><i class="fa-solid fa-star me-1"></i> บันทึกคะแนน</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="mb-4">
        <a href="manage_matches.php" class="btn btn-light btn-sm mb-2"><i class="fa-solid fa-arrow-left me-1"></i> ย้อนกลับ</a>
        <h3 class="fw-bold m-0">👥 รายชื่อนักกีฬา: <?php echo htmlspecialchars($match['sport_name']); ?> (<?php echo $match['gender_type']; ?>)</h3>
        <p class="text-muted small mb-0">โควตาสูงสุด: <?php echo $match['max_players_per_team']; ?> คนต่อหนึ่งทีมสี</p>
    </div>

    <?php if (isset($error_msg)): ?><div class="alert alert-danger border-0 rounded-3"><?php echo $error_msg; ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card ios-card p-2">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">ส่งรายชื่อนักเรียน</h5>
                    <form action="register_athletes.php?match_id=<?php echo $match_id; ?>" method="POST">
                        <div class="mb-2"><label class="small text-muted">ทีมสี</label>
                            <select class="form-select" name="team_id" required>
                                <option value="">-- เลือกทีมสี --</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?php echo $t['id']; ?>"><?php echo $t['team_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="small text-muted">ชื่อ-นามสกุล</label>
                            <input type="text" class="form-control" name="athlete_name" required placeholder="เด็กชาย สมชาย ใจดี">
                        </div>
                        <div class="mb-3"><label class="small text-muted">รหัสนักเรียน</label>
                            <input type="text" class="form-control" name="student_id" placeholder="รหัสประจำตัว">
                        </div>
                        <button type="submit" name="add_athlete" class="btn btn-primary w-100">บันทึกรายชื่อ</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card ios-card p-2">
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr class="text-muted small"><th>ทีมสี</th><th>รหัสนักเรียน</th><th>รายชื่อนักกีฬา</th><th class="text-center">จัดการ</th></tr></thead>
                        <tbody>
                            <?php if(count($athlete_list) > 0): foreach ($athlete_list as $ath): ?>
                            <tr>
                                <td><span class="badge px-3 py-2" style="background-color: <?php echo $ath['team_color']; ?>; border-radius: 8px;"><?php echo htmlspecialchars($ath['team_name']); ?></span></td>
                                <td class="font-monospace text-muted"><?php echo htmlspecialchars($ath['student_id']); ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($ath['athlete_name']); ?></td>
                                <td class="text-center"><a href="register_athletes.php?match_id=<?php echo $match_id; ?>&delete_athlete_id=<?php echo $ath['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ลบ?');"><i class="fa-solid fa-trash"></i></a></td>
                            </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="4" class="text-center text-muted p-4">ยังไม่มีข้อมูลส่งรายชื่อลงทะเบียนในแมตช์นี้</td></tr>
                            <?php endif; ?>
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
