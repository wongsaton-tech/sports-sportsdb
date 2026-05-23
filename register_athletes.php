<?php
require_once 'db.php';

$match_id = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;

// ดึงรายละเอียดแมตช์
$stmt = $pdo->prepare("SELECT m.*, c.category_name FROM matches m LEFT JOIN sport_categories c ON m.category_id = c.id WHERE m.id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch();

if (!$match) { die("ไม่พบรายการแข่งขันนี้"); }

// บันทึกรายชื่อนักกีฬา
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_athlete'])) {
    $team_id = $_POST['team_id'];
    $athlete_name = trim($_POST['athlete_name']);
    $student_id = trim($_POST['student_id']);

    // เช็กโควตาว่าเต็มหรือยัง
    $check = $pdo->prepare("SELECT COUNT(*) FROM athletes WHERE match_id = ? AND team_id = ?");
    $check->execute([$match_id, $team_id]);
    $current_players = $check->fetchColumn();

    if ($current_players >= $match['max_players_per_team']) {
        $error_msg = "ทีมสีนี้มีรายชื่อนักกีฬาเต็มโควตาจำนวน " . $match['max_players_per_team'] . " คนแล้ว!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO athletes (team_id, match_id, athlete_name, student_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$team_id, $match_id, $athlete_name, $student_id]);
            header("Location: register_athletes.php?match_id=$match_id&success=1");
            exit();
        } catch (\PDOException $e) { $error_msg = $e->getMessage(); }
    }
}

// ลบรายชื่อนักกีฬา
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
    <title>ลงทะเบียนนักกีฬา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="mb-4">
        <a href="manage_matches.php" class="btn btn-secondary btn-sm">⬅️ กลับหน้าจัดการแข่งขัน</a>
        <h2 class="mt-2">👥 ลงชื่อนักกีฬา: <?php echo htmlspecialchars($match['sport_name']); ?> (<?php echo $match['gender_type']; ?>)</h2>
        <p class="text-muted">ลักษณะการแข่ง: <?php echo $match['tournament_type']; ?> | โควตานักกีฬา: ไม่เกิน <strong><?php echo $match['max_players_per_team']; ?></strong> คน/ทีมสี</p>
    </div>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-dark"><h5>✍️ ส่งรายชื่อนักกีฬา</h5></div>
                <div class="card-body">
                    <form action="register_athletes.php?match_id=<?php echo $match_id; ?>" method="POST">
                        <div class="mb-2">
                            <label class="form-label">เลือกทีมสี</label>
                            <select class="form-select" name="team_id" required>
                                <option value="">-- เลือกทีมสี --</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?php echo $t['id']; ?>"><?php echo $t['team_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">ชื่อ-นามสกุล นักกีฬา</label>
                            <input type="text" class="form-control" name="athlete_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รหัสนักเรียน (ถ้ามี)</label>
                            <input type="text" class="form-control" name="student_id">
                        </div>
                        <button type="submit" name="add_athlete" class="btn btn-primary w-100">บันทึกรายชื่อ</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>ทีมสี</th>
                                <th>รหัสนักเรียน</th>
                                <th>ชื่อ-นามสกุลนักกีฬา</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($athlete_list) > 0): ?>
                                <?php foreach ($athlete_list as $ath): ?>
                                <tr>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $ath['team_color']; ?>;">
                                            <?php echo htmlspecialchars($ath['team_name']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($ath['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($ath['athlete_name']); ?></td>
                                    <td class="text-center">
                                        <a href="register_athletes.php?match_id=<?php echo $match_id; ?>&delete_athlete_id=<?php echo $ath['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('ลบรายชื่อนักกีฬาท่านนี้?');">ลบ/แก้ไข</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted">ยังไม่มีการส่งรายชื่อนักกีฬาในรายการนี้</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>