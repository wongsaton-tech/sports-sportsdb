<?php
require_once 'db.php';

// 1. ฟังก์ชันลบข้อมูล
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM match_results WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_scores.php?success=deleted");
    exit();
}

// 2. บันทึกผลการแข่งขัน
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_score'])) {
    $match_id = $_POST['match_id'];
    $team_id = $_POST['team_id'];
    $medal = $_POST['medal'];
    $points = intval($_POST['points']);

    // ลบของเก่าออกก่อนป้องกันข้อมูลซ้ำ
    $chk = $pdo->prepare("DELETE FROM match_results WHERE match_id = ? AND team_id = ?");
    $chk->execute([$match_id, $team_id]);

    $stmt = $pdo->prepare("INSERT INTO match_results (match_id, team_id, medal, points) VALUES (?, ?, ?, ?)");
    $stmt->execute([$match_id, $team_id, $medal, $points]);
    header("Location: manage_scores.php?success=1");
    exit();
}

$matches = $pdo->query("SELECT id, sport_name, gender_type FROM matches ORDER BY id DESC")->fetchAll();
$teams = $pdo->query("SELECT id, team_name FROM teams ORDER BY id ASC")->fetchAll();
$results = $pdo->query("SELECT r.*, m.sport_name, m.gender_type, t.team_name, t.team_color 
                        FROM match_results r
                        JOIN matches m ON r.match_id = m.id
                        JOIN teams t ON r.team_id = t.id
                        ORDER BY r.id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกผลคะแนนการแข่งขัน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body, h1, h2, h3, h4, h5, table, .btn, .nav-link {
            font-family: 'Sarabun', sans-serif !important;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="index.php">🏆 SportsDay Center</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> แดชบอร์ด</a></li>
                <li class="nav-item"><a class="nav-link active" href="manage_scores.php"><i class="fa-solid fa-star me-1"></i> บันทึกคะแนน</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="mb-4">🏅 ระบบบันทึกคะแนนและเหรียญรางวัล</h2>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark fw-bold"><h5>✍️ กรอกผลการแข่งขัน</h5></div>
                <div class="card-body">
                    <form action="manage_scores.php" method="POST">
                        <div class="mb-2">
                            <label class="form-label">เลือกรายการแข่งขัน</label>
                            <select class="form-select" name="match_id" required>
                                <option value="">-- เลือกแมตช์ --</option>
                                <?php foreach ($matches as $m): ?>
                                    <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['sport_name'] . ' (' . $m['gender_type'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">เลือกทีมสี</label>
                            <select class="form-select" name="team_id" required>
                                <option value="">-- เลือกทีมสี --</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['team_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">เหรียญรางวัล</label>
                            <select class="form-select" name="medal" required>
                                <option value="ไม่มี">ไม่มีเหรียญ</option>
                                <option value="ทอง">🥇 ทอง</option>
                                <option value="เงิน">🥈 เงิน</option>
                                <option value="ทองแดง">🥉 ทองแดง</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">คะแนนที่ได้รับ</label>
                            <input type="number" class="form-control" name="points" value="0" min="0" required>
                        </div>
                        <button type="submit" name="add_score" class="btn btn-warning fw-bold w-100">💾 บันทึกคะแนน</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white"><h5>📋 ผลการแข่งขันล่าสุด</h5></div>
                <div class="card-body p-0">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>รายการแข่งขัน</th>
                                <th>ทีมสี</th>
                                <th class="text-center">เหรียญ</th>
                                <th class="text-center">คะแนน</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $res): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($res['sport_name']); ?></strong> (<?php echo $res['gender_type']; ?>)</td>
                                <td><span class="badge" style="background-color: <?php echo $res['team_color']; ?>;"><?php echo htmlspecialchars($res['team_name']); ?></span></td>
                                <td class="text-center">
                                    <?php 
                                        if($res['medal'] == 'ทอง') echo '🥇 ทอง';
                                        elseif($res['medal'] == 'เงิน') echo '🥈 เงิน';
                                        elseif($res['medal'] == 'ทองแดง') echo '🥉 ทองแดง';
                                        else echo '-';
                                    ?>
                                </td>
                                <td class="text-center fw-bold text-primary"><?php echo $res['points']; ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit_score.php?id=<?php echo $res['id']; ?>" class="btn btn-outline-warning"><i class="fa-solid fa-pen"></i></a>
                                        <a href="manage_scores.php?delete=<?php echo $res['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('ยืนยันการลบข้อมูลนี้?');"><i class="fa-solid fa-trash"></i></a>
                                    </div>
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
