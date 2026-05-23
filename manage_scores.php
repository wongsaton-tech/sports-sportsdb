<?php
require_once 'db.php';
require_once 'check_auth.php';
checkRole(['admin', 'scorekeeper']);

$selected_match_id = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;

// ==================== บันทึกผลการแข่งขันแบบ Batch ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_all_scores'])) {
    $match_id = intval($_POST['match_id']);
    
    try {
        $pdo->beginTransaction();
        
        // ลบข้อมูลเก่าของ match นี้ทั้งหมดก่อน
        $pdo->prepare("DELETE FROM match_results WHERE match_id = ?")->execute([$match_id]);
        
        // บันทึกข้อมูลใหม่
        $stmt = $pdo->prepare("INSERT INTO match_results (match_id, team_id, medal, points) VALUES (?, ?, ?, ?)");
        
        $team_ids = $_POST['team_id'] ?? [];
        $medals = $_POST['medal'] ?? [];
        $points = $_POST['points'] ?? [];
        
        foreach ($team_ids as $index => $team_id) {
            $team_id = intval($team_id);
            $medal = $medals[$index] ?? 'ไม่มี';
            $point = intval($points[$index] ?? 0);
            
            if ($team_id > 0) {
                $stmt->execute([$match_id, $team_id, $medal, $point]);
            }
        }
        
        $pdo->commit();
        header("Location: manage_scores.php?match_id=$match_id&success=1");
        exit();
        
    } catch (\PDOException $e) {
        $pdo->rollBack();
        $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// ==================== ดึงข้อมูล ====================
$matches = $pdo->query("SELECT m.*, c.category_name 
                        FROM matches m 
                        LEFT JOIN sport_categories c ON m.category_id = c.id 
                        ORDER BY m.id DESC")->fetchAll();

// ถ้าเลือก match แล้ว ดึงทีมที่ลงทะเบียน
$teams_in_match = [];
$current_match = null;

if ($selected_match_id > 0) {
    $stmt = $pdo->prepare("SELECT m.*, c.category_name 
                           FROM matches m 
                           LEFT JOIN sport_categories c ON m.category_id = c.id 
                           WHERE m.id = ?");
    $stmt->execute([$selected_match_id]);
    $current_match = $stmt->fetch();
    
    $teams_query = $pdo->prepare("SELECT DISTINCT t.id, t.team_name, t.team_color 
                                  FROM athletes a 
                                  JOIN teams t ON a.team_id = t.id 
                                  WHERE a.match_id = ? 
                                  ORDER BY t.team_name");
    $teams_query->execute([$selected_match_id]);
    $teams_in_match = $teams_query->fetchAll();
    
    // ดึงผลเดิมที่มีอยู่
    $existing_results = $pdo->prepare("SELECT team_id, medal, points FROM match_results WHERE match_id = ?");
    $existing_results->execute([$selected_match_id]);
    $results_map = [];
    foreach ($existing_results->fetchAll() as $r) {
        $results_map[$r['team_id']] = $r;
    }
}
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
        body { font-family: 'Sarabun', sans-serif; background-color: #f2f2f7; color: #1c1c1e; }
        .navbar { background-color: rgba(28, 28, 30, 0.92) !important; backdrop-filter: blur(20px); }
        .ios-card { background: #ffffff; border-radius: 16px !important; border: none !important; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04) !important; }
        .form-control, .form-select { border-radius: 10px; border: 1px solid #d1d1d6; padding: 10px; }
        .btn { border-radius: 10px; padding: 10px; font-weight: 600; }
        .team-row { transition: all 0.2s; }
        .team-row:hover { background-color: #f8f9fa; }
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
    <h3 class="fw-bold mb-4">🏅 บันทึกผลคะแนนการแข่งขัน</h3>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 rounded-3 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i> บันทึกผลการแข่งขันเรียบร้อยแล้ว
        </div>
    <?php endif; ?>
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger border-0 rounded-3 mb-4"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-12">
            <div class="card ios-card p-3">
                <div class="card-body">
                    <form method="GET" action="manage_scores.php" class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">เลือกรายการแข่งขัน</label>
                            <select class="form-select" name="match_id" onchange="this.form.submit()" required>
                                <option value="">-- เลือกรายการแข่งขัน --</option>
                                <?php foreach ($matches as $m): ?>
                                    <option value="<?php echo $m['id']; ?>" <?php echo $m['id'] == $selected_match_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($m['sport_name'] . ' (' . $m['gender_type'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($selected_match_id > 0 && $current_match): ?>
        <div class="col-12">
            <div class="card ios-card p-3">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">
                        📋 <?php echo htmlspecialchars($current_match['sport_name']); ?> 
                        <small class="text-muted">(<?php echo $current_match['gender_type']; ?>)</small>
                    </h5>

                    <form method="POST" action="manage_scores.php?match_id=<?php echo $selected_match_id; ?>">
                        <input type="hidden" name="match_id" value="<?php echo $selected_match_id; ?>">
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ทีมสี</th>
                                        <th class="text-center">เหรียญรางวัล</th>
                                        <th class="text-center">คะแนน</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($teams_in_match) > 0): ?>
                                        <?php foreach ($teams_in_match as $index => $team): 
                                            $existing = $results_map[$team['id']] ?? ['medal' => 'ไม่มี', 'points' => 0];
                                        ?>
                                        <tr class="team-row">
                                            <td class="fw-bold">
                                                <span class="badge px-3 py-2 me-2" style="background-color: <?php echo $team['team_color']; ?>;">
                                                    <?php echo htmlspecialchars($team['team_name']); ?>
                                                </span>
                                                <input type="hidden" name="team_id[]" value="<?php echo $team['id']; ?>">
                                            </td>
                                            <td>
                                                <select class="form-select" name="medal[]">
                                                    <option value="ไม่มี" <?php echo $existing['medal']=='ไม่มี'?'selected':''; ?>>ไม่มีเหรียญ</option>
                                                    <option value="ทอง" <?php echo $existing['medal']=='ทอง'?'selected':''; ?>>🥇 ทอง</option>
                                                    <option value="เงิน" <?php echo $existing['medal']=='เงิน'?'selected':''; ?>>🥈 เงิน</option>
                                                    <option value="ทองแดง" <?php echo $existing['medal']=='ทองแดง'?'selected':''; ?>>🥉 ทองแดง</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control text-center" 
                                                       name="points[]" value="<?php echo $existing['points']; ?>" min="0">
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-5">
                                                ยังไม่มีทีมลงทะเบียนในรายการนี้
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" name="save_all_scores" class="btn btn-success w-100 mt-3">
                            <i class="fa-solid fa-floppy-disk me-2"></i> บันทึกผลการแข่งขันทั้งหมด
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
