<?php
require_once 'db.php';

// 1. บันทึกผลการแข่งขันเมื่อมีการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_score'])) {
    $match_id = $_POST['match_id'];
    $team_id = $_POST['team_id'];
    $medal = $_POST['medal']; // ทอง, เงิน, ทองแดง, ไม่มี
    $points = intval($_POST['points']);

    try {
        // เช็กก่อนว่าแมตช์นี้ ทีมสีนี้เคยถูกกรอกคะแนนไปหรือยัง ถ้ามีแล้วให้ลบของเก่าออกก่อน (ป้องกันข้อมูลซ้ำ)
        $chk = $pdo->prepare("DELETE FROM match_results WHERE match_id = ? AND team_id = ?");
        $chk->execute([$match_id, $team_id]);

        // บันทึกผลคะแนนใหม่ลงตาราง
        $stmt = $pdo->prepare("INSERT INTO match_results (match_id, team_id, medal, points) VALUES (?, ?, ?, ?)");
        $stmt->execute([$match_id, $team_id, $medal, $points]);
        header("Location: manage_scores.php?success=1");
        exit();
    } catch (\PDOException $e) {
        $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// 2. ดึงข้อมูลตัวเลือก (Matches และ Teams) มาใส่ใน Select Box
$matches = $pdo->query("SELECT id, sport_name, gender_type FROM matches ORDER BY id DESC")->fetchAll();
$teams = $pdo->query("SELECT id, team_name FROM teams ORDER BY id ASC")->fetchAll();

// 3. ดึงรายการผลการแข่งขันทั้งหมดที่เคยบันทึกไว้มาโชว์ในตาราง
$sql = "SELECT r.id, r.medal, r.points, m.sport_name, m.gender_type, t.team_name, t.team_color 
        FROM match_results r
        JOIN matches m ON r.match_id = m.id
        JOIN teams t ON r.team_id = t.id
        ORDER BY r.id DESC";
$results = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกผลคะแนนการแข่งขัน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            font-family: 'Sarabun', sans-serif; 
            background-color: #f2f2f7; /* iOS Light Background */
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
            padding: 10px 16px; 
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
                <li class="nav-item"><a class="nav-link" href="manage_teams.php"><i class="fa-solid fa-palette me-1"></i> จัดการทีมสี</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_categories.php"><i class="fa-solid fa-folder-open me-1"></i> ประเภทกีฬา</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_matches.php"><i class="fa-solid fa-person-running me-1"></i> รายการแข่ง & นักกีฬา</a></li>
                <li class="nav-item"><a class="nav-link active" href="manage_scores.php"><i class="fa-solid fa-star me-1"></i> บันทึกคะแนน</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="fw-bold mb-4">🏅 บันทึกคะแนนและเหรียญรางวัล</h3>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 rounded-3 shadow-sm mb-3">
            <i class="fa-solid fa-circle-check me-1"></i> บันทึกผลการแข่งขันและอัปเดตระบบเรียบร้อยแล้ว!
        </div>
    <?php endif; ?>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger border-0 rounded-3 shadow-sm mb-3"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card ios-card p-2">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">✍️ กรอกผลการแข่งขัน</h5>
                    <form action="manage_scores.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small text-muted">เลือกรายการแข่งขัน</label>
                            <select class="form-select" name="match_id" required>
                                <option value="">-- เลือกแมตช์การแข่ง --</option>
                                <?php foreach ($matches as $m): ?>
                                    <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['sport_name'] . ' (' . $m['gender_type'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">เลือกทีมสีที่ได้รางวัล</label>
                            <select class="form-select" name="team_id" required>
                                <option value="">-- เลือกทีมสี --</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['team_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">เหรียญรางวัลที่ได้รับ</label>
                            <select class="form-select" name="medal" required>
                                <option value="ไม่มี">ไม่มีเหรียญ (ได้เฉพาะคะแนนสะสม)</option>
                                <option value="ทอง">🥇 เหรียญทอง</option>
                                <option value="เงิน">🥈 เหรียญเงิน</option>
                                <option value="ทองแดง">🥉 เหรียญทองแดง</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small text-muted">คะแนนดิบที่ได้รับ (เช่น ชนะได้ 10, แพ้ได้ 3)</label>
                            <input type="number" class="form-control" name="points" value="0" min="0" required>
                        </div>
                        <button type="submit" name="add_score" class="btn btn-warning w-100 fw-bold text-dark">
                            <i class="fa-solid fa-floppy-disk me-1"></i> บันทึกผลคะแนน
                        </button>
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
                                <tr class="text-muted small">
                                    <th>รายการแข่งขัน</th>
                                    <th>ทีมสี</th>
                                    <th class="text-center">เหรียญรางวัล</th>
                                    <th class="text-center">คะแนนดิบ</th>
                                    <th class="text-center">เอกสาร</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($results) > 0): ?>
                                    <?php foreach ($results as $res): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($res['sport_name']); ?></div>
                                            <small class="text-muted font-monospace"><?php echo $res['gender_type']; ?></small>
                                        </td>
                                        <td>
                                            <span class="badge px-3 py-2" style="background-color: <?php echo $res['team_color']; ?>; border-radius: 8px;">
                                                <?php echo htmlspecialchars($res['team_name']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold">
                                            <?php 
                                                if($res['medal'] == 'ทอง') echo '<span class="text-warning">🥇 ทอง</span>';
                                                elseif($res['medal'] == 'เงิน') echo '<span class="text-secondary">🥈 เงิน</span>';
                                                elseif($res['medal'] == 'ทองแดง') echo '<span class="text-danger">🥉 ทองแดง</span>';
                                                else echo '<span class="text-muted">-</span>';
                                            ?>
                                        </td>
                                        <td class="text-center fw-bold text-primary"><?php echo $res['points']; ?> คะแนน</td>
                                        <td class="text-center">
                                            <a href="print_certificate.php?result_id=<?php echo $res['id']; ?>" class="btn btn-sm btn-outline-dark" target="_blank">
                                                <i class="fa-solid fa-certificate text-warning me-1"></i> เกียรติบัตร
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted p-4">ยังไม่มีการบันทึกผลการแข่งขันเข้ามาในระบบ</td></tr>
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
