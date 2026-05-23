<?php
require_once 'db.php';
require_once 'check_auth.php';
checkRole(['admin', 'scorekeeper']);

$match_id = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;

// ดึงข้อมูลแมตช์
$stmt = $pdo->prepare("SELECT m.*, c.category_name FROM matches m LEFT JOIN sport_categories c ON m.category_id = c.id WHERE m.id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch();

if (!$match) {
    die("ไม่พบรายการแข่งขันนี้");
}

$max_quota = intval($match['max_players_per_team']);

// ==================== แก้ไขชื่อนักกีฬา ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_athlete'])) {
    $athlete_id = intval($_POST['athlete_id']);
    $new_name = trim($_POST['athlete_name']);
    
    if (!empty($new_name)) {
        $pdo->prepare("UPDATE athletes SET athlete_name = ? WHERE id = ?")
            ->execute([$new_name, $athlete_id]);
        header("Location: register_athletes.php?match_id=$match_id&success=edited");
        exit();
    }
}

// ==================== เพิ่มนักกีฬา ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_athletes_group'])) {
    $team_id = intval($_POST['team_id']);
    $athlete_names = $_POST['athlete_names'] ?? [];

    try {
        $check = $pdo->prepare("SELECT COUNT(*) FROM athletes WHERE match_id = ? AND team_id = ?");
        $check->execute([$match_id, $team_id]);
        $current_count = $check->fetchColumn();

        $new_count = count(array_filter($athlete_names, fn($n) => trim($n) !== ''));

        if (($current_count + $new_count) > $max_quota) {
            $error_msg = "❌ จำนวนนักกีฬาเกินโควตาที่กำหนด ($max_quota คน)";
        } else {
            $insert = $pdo->prepare("INSERT INTO athletes (team_id, match_id, athlete_name) VALUES (?, ?, ?)");
            foreach ($athlete_names as $name) {
                $clean = trim($name);
                if (!empty($clean)) {
                    $insert->execute([$team_id, $match_id, $clean]);
                }
            }
            header("Location: register_athletes.php?match_id=$match_id&success=1");
            exit();
        }
    } catch (\PDOException $e) {
        $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// ==================== ลบนักกีฬา ====================
if (isset($_GET['delete_athlete_id'])) {
    $pdo->prepare("DELETE FROM athletes WHERE id = ?")->execute([$_GET['delete_athlete_id']]);
    header("Location: register_athletes.php?match_id=$match_id&success=2");
    exit();
}

// ดึงข้อมูลทีมและโควตา
$teams = $pdo->query("SELECT * FROM teams ORDER BY id ASC")->fetchAll();

$quota_tracker = [];
foreach ($teams as $t) {
    $q = $pdo->prepare("SELECT COUNT(*) FROM athletes WHERE match_id = ? AND team_id = ?");
    $q->execute([$match_id, $t['id']]);
    $quota_tracker[$t['id']] = $q->fetchColumn();
}

// ดึงรายชื่อนักกีฬาทั้งหมด
$athletes_query = $pdo->prepare("SELECT a.*, t.team_name, t.team_color 
                                 FROM athletes a 
                                 JOIN teams t ON a.team_id = t.id 
                                 WHERE a.match_id = ? 
                                 ORDER BY t.team_name, a.id ASC");
$athletes_query->execute([$match_id]);
$athlete_list = $athletes_query->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนนักกีฬา - SportsDay Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f2f2f7; color: #1c1c1e; }
        .navbar { background-color: rgba(28, 28, 30, 0.92) !important; backdrop-filter: blur(20px); }
        .ios-card { background: #ffffff; border-radius: 16px !important; border: none !important; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04) !important; }
        .form-control { border-radius: 10px; padding: 10px; }
        .btn { border-radius: 10px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="index.php">🏆 SportsDay Center</a>
        <a href="manage_matches.php" class="btn btn-light btn-sm">← กลับหน้าจัดการรายการแข่ง</a>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="fw-bold">👥 ลงทะเบียนนักกีฬา: <?php echo htmlspecialchars($match['sport_name']); ?> (<?php echo $match['gender_type']; ?>)</h3>
    <p class="text-muted">โควตา: <strong><?php echo $max_quota; ?> คน/ทีม</strong></p>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_GET['success'] == 'edited' ? '✅ แก้ไขชื่อนักกีฬาเรียบร้อยแล้ว' : '✅ บันทึกรายชื่อเรียบร้อยแล้ว'; ?>
        </div>
    <?php endif; ?>
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- ฟอร์มเพิ่มนักกีฬา -->
        <div class="col-md-5">
            <div class="card ios-card p-3">
                <h5 class="fw-bold mb-3">✍️ เพิ่มรายชื่อนักกีฬา</h5>
                <form action="register_athletes.php?match_id=<?php echo $match_id; ?>" method="POST">
                    <div class="mb-3">
                        <label class="form-label">เลือกทีมสี</label>
                        <select class="form-select" name="team_id" id="teamSelect" onchange="generateInputFields()" required>
                            <option value="">-- เลือกทีมสี --</option>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?php echo $t['id']; ?>">
                                    <?php echo htmlspecialchars($t['team_name']); ?> 
                                    (<?php echo $quota_tracker[$t['id']]; ?>/<?php echo $max_quota; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="dynamicInputArea" class="mb-3"></div>

                    <button type="submit" name="save_athletes_group" id="submitBtn" class="btn btn-primary w-100 d-none">
                        <i class="fa-solid fa-floppy-disk"></i> บันทึกรายชื่อ
                    </button>
                </form>
            </div>
        </div>

        <!-- รายชื่อนักกีฬา -->
        <div class="col-md-7">
            <div class="card ios-card p-3">
                <h5 class="fw-bold mb-3">📋 รายชื่อนักกีฬาที่ลงทะเบียนแล้ว</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ทีมสี</th>
                                <th>ชื่อนักกีฬา</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($athlete_list as $a): ?>
                            <tr>
                                <td>
                                    <span class="badge px-3 py-2" style="background-color: <?php echo $a['team_color']; ?>;">
                                        <?php echo htmlspecialchars($a['team_name']); ?>
                                    </span>
                                </td>
                                <td class="fw-bold"><?php echo htmlspecialchars($a['athlete_name']); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-warning me-1" 
                                            onclick="editAthlete(<?php echo $a['id']; ?>, '<?php echo htmlspecialchars($a['athlete_name']); ?>')">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <a href="register_athletes.php?match_id=<?php echo $match_id; ?>&delete_athlete_id=<?php echo $a['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('ลบชื่อนักกีฬาคนนี้?');">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($athlete_list)): ?>
                                <tr><td colspan="3" class="text-center text-muted py-4">ยังไม่มีรายชื่อนักกีฬา</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal แก้ไขชื่อ -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">แก้ไขชื่อนักกีฬา</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="register_athletes.php?match_id=<?php echo $match_id; ?>">
                <div class="modal-body">
                    <input type="hidden" name="athlete_id" id="edit_athlete_id">
                    <div class="mb-3">
                        <label class="form-label">ชื่อ - นามสกุล</label>
                        <input type="text" class="form-control" name="athlete_name" id="edit_athlete_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" name="edit_athlete" class="btn btn-warning">บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function generateInputFields() {
    const teamId = document.getElementById('teamSelect').value;
    const area = document.getElementById('dynamicInputArea');
    const btn = document.getElementById('submitBtn');

    if (!teamId) {
        area.innerHTML = `<div class="text-center p-4 border border-dashed rounded-3 text-muted">กรุณาเลือกทีมสีก่อน</div>`;
        btn.classList.add('d-none');
        return;
    }

    const registered = <?php echo json_encode($quota_tracker); ?>[teamId] || 0;
    const available = <?php echo $max_quota; ?> - registered;

    if (available <= 0) {
        area.innerHTML = `<div class="alert alert-warning">โควต้าของทีมนี้เต็มแล้ว</div>`;
        btn.classList.add('d-none');
    } else {
        let html = `<h6 class="text-success">เพิ่มได้อีก ${available} คน</h6>`;
        for (let i = 0; i < available; i++) {
            html += `
                <div class="mb-2">
                    <input type="text" class="form-control" name="athlete_names[]" 
                           placeholder="ชื่อนักกีฬาคนที่ ${registered + i + 1}" ${i===0 ? 'required' : ''}>
                </div>`;
        }
        area.innerHTML = html;
        btn.classList.remove('d-none');
    }
}

function editAthlete(id, name) {
    document.getElementById('edit_athlete_id').value = id;
    document.getElementById('edit_athlete_name').value = name;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
