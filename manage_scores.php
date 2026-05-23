<?php
require_once 'db.php';

// 1. บันทึกผลการแข่งขัน
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_score'])) {
    $match_id = $_POST['match_id'];
    $team_id = $_POST['team_id'];
    $athlete_name = $_POST['athlete_name']; // เพิ่มชื่อนักกีฬา
    $medal = $_POST['medal'];
    $points = intval($_POST['points']);

    try {
        $stmt = $pdo->prepare("INSERT INTO match_results (match_id, team_id, athlete_name, medal, points) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$match_id, $team_id, $athlete_name, $medal, $points]);
        header("Location: manage_scores.php?success=1");
        exit();
    } catch (PDOException $e) { $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage(); }
}

// ดึงข้อมูลสำหรับ Select Box
$matches = $pdo->query("SELECT id, sport_name FROM matches ORDER BY id DESC")->fetchAll();
$teams = $pdo->query("SELECT id, team_name FROM teams ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการผลการแข่งขัน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container bg-white p-4 rounded shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">จัดการผลการแข่งขัน</h2>
        <div>
            <a href="index.php" class="btn btn-secondary">กลับหน้าหลัก</a>
            <a href="view_results.php" class="btn btn-info text-white">ดูรายงานสรุปผล (ตาราง)</a>
        </div>
    </div>

    <form method="POST" class="row g-3 mb-5 p-3 border rounded">
        <div class="col-md-3">
            <select name="match_id" class="form-select" required>
                <option value="">เลือกรายการแข่งขัน</option>
                <?php foreach($matches as $m): ?>
                    <option value="<?=$m['id']?>"><?=$m['sport_name']?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="team_id" class="form-select" required>
                <option value="">เลือกทีมสี</option>
                <?php foreach($teams as $t): ?>
                    <option value="<?=$t['id']?>"><?=$t['team_name']?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" name="athlete_name" class="form-control" placeholder="ชื่อนักกีฬา" required>
        </div>
        <div class="col-md-2">
            <select name="medal" class="form-select">
                <option value="ทอง">ทอง</option>
                <option value="เงิน">เงิน</option>
                <option value="ทองแดง">ทองแดง</option>
            </select>
        </div>
        <div class="col-md-1">
            <input type="number" name="points" class="form-control" placeholder="คะแนน" required>
        </div>
        <div class="col-md-1">
            <button type="submit" name="add_score" class="btn btn-primary w-100">บันทึก</button>
        </div>
    </form>
</div>
</body>
</html>
