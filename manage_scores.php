<?php
require_once 'db.php';
require_once 'check_auth.php';
checkRole(['admin', 'scorekeeper']);

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
    <title>บันทึกผลคะแนน - SportsDay Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Sarabun', sans-serif; 
            background-color: #f2f2f7; 
            color: #1c1c1e; 
        }
        .navbar { 
            background-color: rgba(28, 28, 30, 0.92) !important; 
            backdrop-filter: blur(20px); 
        }
        .ios-card { 
            background: #ffffff; 
            border-radius: 16px !important; 
            border: none !important; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04) !important; 
        }
        .form-control, .form-select { 
            border-radius: 10px; 
            border: 1px solid #d1d1d6; 
            padding: 10px; 
        }
        .btn { 
            border-radius: 10px; 
            padding: 10px; 
            font-weight: 600; 
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm py-3">
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
    <h3 class="fw-bold mb-4">🏅 บันทึกผลคะแนนและเหรียญรางวัล</h3>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 rounded-3 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i> 
            <?php echo $_GET['success'] == 'deleted' ? 'ลบข้อมูลเรียบร้อยแล้ว' : 'บันทึกผลการแข่งขันเรียบร้อยแล้ว'; ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <!-- ฟอร์มบันทึกคะแนน -->
        <div class="col-md-5">
            <div class="card ios-card p-2">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">✍️ กรอกผลการแข่งขัน</h5>
                    <form action="manage_scores.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold">เลือกรายการแข่งขัน</label>
                            <select class="form-select" name="match_id" required>
                                <option value="">-- เลือกแมตช์ --</option>
                                <?php foreach ($matches as $m): ?>
                                    <option value="<?php echo $m['id']; ?>">
                                        <?php echo htmlspecialchars($m['sport_name'] . ' (' . $m['gender_type'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold">เลือกทีมสี</label>
                            <select class="form-select" name="team_id" required>
                                <option value="">-- เลือกทีมสี --</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?php echo $t['id']; ?>">
                                        <?php echo htmlspecialchars($t['team_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold">เหรียญรางวัล</label>
                            <select class="form-select" name="medal" required>
                                <option value="ไม่มี">ไม่มีเหรียญ</option>
                                <option value="ทอง">🥇 ทอง</option>
                                <option value="เงิน">🥈 เงิน</option>
                                <option value="ทองแดง">🥉 ทองแดง</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small text-muted fw-bold">คะแนนที่ได้รับ</label>
                            <input type="number" class="form-control" name="points" value="0" min="0" required>
                        </div>
                        <button type="submit" name="add_score" class="btn btn-warning w-100 fw-bold">
                            <i class="fa-solid fa-floppy-disk me-1"></i> บันทึกคะแนน
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ตารางผลการแข่งขัน -->
        <div class="col-md-7">
            <div class="card ios-card p-2">
                <div class="card-body p-0">
                    <div class="p-3 border-bottom">
                        <h5 class="fw-bold m-0">📋 ผลการแข่งขันล่าสุด</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
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
                                    <td>
                                        <strong><?php echo htmlspecialchars($res['sport_name']); ?></strong><br>
                                        <small class="text-muted">(<?php echo $res['gender_type']; ?>)</small>
                                    </td>
                                    <td>
                                        <span class="badge px-3 py-2" style="background-color: <?php echo $res['team_color']; ?>;">
                                            <?php echo htmlspecialchars($res['team_name']); ?>
                                        </span>
                                    </td>
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
                                            <a href="edit_score.php?id=<?php echo $res['id']; ?>" class="btn btn-outline-warning">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <a href="manage_scores.php?delete=<?php echo $res['id']; ?>" 
                                               class="btn btn-outline-danger"
                                               onclick="return confirm('ยืนยันการลบข้อมูลนี้?');">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($results)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            ยังไม่มีข้อมูลผลการแข่งขัน
                                        </td>
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
