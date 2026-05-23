<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_score'])) {
    $match_id = $_POST['match_id'];
    $team_id = $_POST['team_id'];
    $medal = $_POST['medal'];
    $points = intval($_POST['points']);

    try {
        $pdo->prepare("DELETE FROM match_results WHERE match_id = ? AND team_id = ?")->execute([$match_id, $team_id]);
        $pdo->prepare("INSERT INTO match_results (match_id, team_id, medal, points) VALUES (?, ?, ?, ?)")->execute([$match_id, $team_id, $medal, $points]);
        header("Location: manage_scores.php?success=1");
        exit();
    } catch (\PDOException $e) { $error_msg = $e->getMessage(); }
}

$matches = $pdo->query("SELECT id, sport_name, gender_type FROM matches ORDER BY id DESC")->fetchAll();
$teams = $pdo->query("SELECT id, team_name FROM teams ORDER BY id ASC")->fetchAll();
$results = $pdo->query("SELECT r.*, m.sport_name, m.gender_type, t.team_name, t.team_color FROM match_results r JOIN matches m ON r.match_id = m.id JOIN teams t ON r.team_id = t.id ORDER BY r.id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกผลคะแนน</title>
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
                <li class="nav-item"><a class="nav-link" href="manage_matches.php"><i class="fa-solid fa-person-running me-1"></i> รายการแข่ง & นักกีฬา</a></li>
                <li class="nav-item"><a class="nav-link active" href="manage_scores.php"><i class="fa-solid fa-star me-1"></i> บันทึกคะแนน</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="fw-bold mb-4">🏅 บันทึกคะแนนและรางวัล</h3>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card ios-card p-2">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">กรอกผลการแข่ง</h5>
                    <form action="manage_scores.php" method="POST">
                        <div class="mb-2"><label class="small text-muted">รายการแข่งขัน</label>
                            <select class="form-select" name="match_id" required>
                                <option value="">-- เลือกแมตช์ --</option>
                                <?php foreach ($matches as $m): ?><option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['sport_name'].' ('.$m['gender_type'].')'); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="small text-muted">ทีมสี</label>
                            <select class="form-select" name="team_id" required>
                                <option value="">-- เลือกทีมสี --</option>
                                <?php foreach ($teams as $t): ?><option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['team_name']); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="small text-muted">เหรียญรางวัล</label>
                            <select class="form-select" name="medal" required>
                                <option value="ไม่มี">ไม่มีเหรียญ (ได้เฉพาะคะแนนดิบ)</option>
                                <option value="ทอง">🥇 เหรียญทอง</option>
                                <option value="เงิน">🥈 เหรียญเงิน</option>
                                <option value="ทองแดง">🥉 เหรียญทองแดง</option>
                            </select>
                        </div>
                        <div class="mb-3"><label class="small text-muted">คะแนนดิบสะสม</label>
                            <input type="number" class="form-control" name="points" value="0" min="0" required>
                        </div>
                        <button type="submit" name="add_score" class="btn btn-warning w-100 fw-bold">บันทึกคะแนน</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card ios-card p-2">
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr class="text-muted small"><th>รายการแข่งขัน</th><th>ทีมสี</th><th class="text-center">เหรียญ</th><th class="text-center">คะแนน</th></tr></thead>
                        <tbody>
                            <?php if(count($results) > 0): foreach ($results as $res): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($res['sport_name']); ?> <span class="badge bg-light text-muted small"><?php echo $res['gender_type']; ?></span></td>
                                <td><span class="badge px-3 py-2" style="background-color: <?php echo $res['team_color']; ?>; border-radius: 8px;"><?php echo htmlspecialchars($res['team_name']); ?></span></td>
                                <td class="text-center fw-bold">
                                    <?php 
                                        if($res['medal'] == 'ทอง') echo '<span class="text-warning">🥇 ทอง</span>';
                                        elseif($res['medal'] == 'เงิน') echo '<span class="text-secondary">🥈 เงิน</span>';
                                        elseif($res['medal'] == 'ทองแดง') echo '<span class="text-danger">🥉 ทองแดง</span>';
                                        else echo '<span class="text-muted">-</span>';
                                    ?>
                                </td>
                                <td class="text-center fw-bold text-primary"><?php echo $res['points']; ?></td>
                            </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="4" class="text-center text-muted p-4">ยังไม่มีผลการแข่งขันบันทึกไว้</td></tr>
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
