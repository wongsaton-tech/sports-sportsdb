<?php
require_once 'db.php';

// 1. รับค่าไอดีของแมตช์การแข่งขัน
$match_id = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;

// ดึงข้อมูลรายละเอียดของแมตช์ เพื่อดูชนิดกีฬา และ Max Players
$stmt = $pdo->prepare("SELECT m.*, c.category_name FROM matches m LEFT JOIN sport_categories c ON m.category_id = c.id WHERE m.id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch();

if (!$match) {
    die("ไม่พบรายการแข่งขันนี้");
}

$max_quota = intval($match['max_players_per_team']);

// ================= สเต็บที่ 2: บันทึกข้อมูลแบบอาร์เรย์ (เฉพาะชื่อนักกีฬา) =================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_athletes_group'])) {
    $team_id = intval($_POST['team_id']);
    $athlete_names = $_POST['athlete_names']; // เป็น Array

    try {
        // นับจำนวนเด็กเดิมที่มีอยู่ในระบบของทีมนี้ในแมตช์นี้
        $check = $pdo->prepare("SELECT COUNT(*) FROM athletes WHERE match_id = ? AND team_id = ?");
        $check->execute([$match_id, $team_id]);
        $current_count = intval($check->fetchColumn());

        // นับจำนวนชื่อใหม่ที่กรอกเข้ามาจริง ๆ (ไม่นับช่องว่าง)
        $new_athletes_count = 0;
        foreach ($athlete_names as $name) {
            if (!empty(trim($name))) {
                $new_athletes_count++;
            }
        }

        if (($current_count + $new_athletes_count) > $max_quota) {
            $error_msg = "❌ ไม่สามารถบันทึกได้ เนื่องจากจำนวนนักกีฬารวมจะเกินโควตาที่กำหนดไว้ ($max_quota คน)";
        } else {
            // เตรียมคำสั่ง Insert บันทึกเฉพาะชื่อนักกีฬา
            $insert_stmt = $pdo->prepare("INSERT INTO athletes (team_id, match_id, athlete_name) VALUES (?, ?, ?)");
            
            foreach ($athlete_names as $name) {
                $clean_name = trim($name);

                // บันทึกเฉพาะช่องที่มีการกรอกชื่อเข้ามาเท่านั้น
                if (!empty($clean_name)) {
                    $insert_stmt->execute([$team_id, $match_id, $clean_name]);
                }
            }
            
            header("Location: register_athletes.php?match_id=$match_id&success=1");
            exit();
        }
    } catch (\PDOException $e) {
        $error_msg = "เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage();
    }
}

// ================= สเต็บที่ 3: ระบบลบรายชื่อนักกีฬา =================
if (isset($_GET['delete_athlete_id'])) {
    $pdo->prepare("DELETE FROM athletes WHERE id = ?")->execute([$_GET['delete_athlete_id']]);
    header("Location: register_athletes.php?match_id=$match_id&success=2");
    exit();
}

// ================= สเต็บที่ 4: เตรียมข้อมูลโควตาให้ฝั่ง JavaScript =================
$teams = $pdo->query("SELECT * FROM teams ORDER BY id ASC")->fetchAll();

$quota_tracker = [];
foreach ($teams as $t) {
    $q_stmt = $pdo->prepare("SELECT COUNT(*) FROM athletes WHERE match_id = ? AND team_id = ?");
    $q_stmt->execute([$match_id, $t['id']]);
    $quota_tracker[$t['id']] = intval($q_stmt->fetchColumn());
}

// ดึงรายชื่อนักกีฬาที่ลงทะเบียนแล้วมาแสดงในตาราง (ไม่ดึง student_id)
$athletes_query = $pdo->prepare("SELECT a.*, t.team_name, t.team_color FROM athletes a JOIN teams t ON a.team_id = t.id WHERE a.match_id = ? ORDER BY t.id ASC, a.id ASC");
$athletes_query->execute([$match_id]);
$athlete_list = $athletes_query->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนนักกีฬา - iOS Style</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f2f2f7; color: #1c1c1e; }
        .navbar { background-color: rgba(28, 28, 30, 0.92) !important; backdrop-filter: blur(20px); }
        .ios-card { background: #ffffff; border-radius: 16px !important; border: none !important; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04) !important; }
        .form-control, .form-select { border-radius: 10px; border: 1px solid #d1d1d6; padding: 10px; }
        .btn { border-radius: 10px; padding: 10px; font-weight: 600; }
        .input-group-box { background: #fafafa; border-radius: 12px; padding: 12px; margin-bottom: 10px; border: 1px solid #e5e5ea; }
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
        <h3 class="fw-bold m-0">👥 ลงทะเบียน: <?php echo htmlspecialchars($match['sport_name']); ?> (<?php echo $match['gender_type']; ?>)</h3>
        <p class="text-muted small">โควตาสูงสุดของประเภทกีฬานี้: <span class="badge bg-dark rounded-pill"><?php echo $max_quota; ?> คน / ทีมสี</span></p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 rounded-3 shadow-sm mb-3"><i class="fa-solid fa-circle-check me-1"></i> อัปเดตรายชื่อเรียบร้อยแล้ว!</div>
    <?php endif; ?>
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger border-0 rounded-3 shadow-sm mb-3"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-5">
            <div class="card ios-card p-2">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">✍️ ส่งรายชื่อนักกีฬา</h5>
                    <form action="register_athletes.php?match_id=<?php echo $match_id; ?>" method="POST">
                        
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold">เลือกทีมสีของคุณ</label>
                            <select class="form-select" name="team_id" id="teamSelect" onchange="generateInputFields()" required>
                                <option value="">-- เลือกทีมสีเพื่อแสดงช่องกรอกชื่อ --</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?php echo $t['id']; ?>">
                                        <?php echo htmlspecialchars($t['team_name']); ?> 
                                        (ส่งแล้ว <?php echo $quota_tracker[$t['id']]; ?>/<?php echo $max_quota; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="dynamicInputArea" class="mb-3">
                            <div class="text-center p-4 rounded-3 border border-dashed text-muted small">
                                <i class="fa-solid fa-users fa-2x mb-2 text-black-50"></i><br>
                                กรุณาเลือกทีมสีด้านบนก่อน เพื่อระบุจำนวนช่องกรอกชื่อตามโควตา
                            </div>
                        </div>

                        <button type="submit" name="save_athletes_group" id="submitBtn" class="btn btn-primary w-100 fw-bold d-none">
                            <i class="fa-solid fa-floppy-disk me-1"></i> บันทึกรายชื่อทั้งหมด
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card ios-card p-2">
                <div class="card-body p-0">
                    <div class="p-3"><h5 class="fw-bold m-0">📋 รายชื่อนักกีฬาในแมตช์นี้</h5></div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-muted small">
                                    <th width="30%">ทีมสี</th>
                                    <th width="50%">ชื่อ-นามสกุล นักกีฬา</th>
                                    <th width="20%" class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($athlete_list) > 0): foreach ($athlete_list as $ath): ?>
                                <tr>
                                    <td><span class="badge px-3 py-2" style="background-color: <?php echo $ath['team_color']; ?>; border-radius: 8px;"><?php echo htmlspecialchars($ath['team_name']); ?></span></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($ath['athlete_name']); ?></td>
                                    <td class="text-center">
                                        <a href="register_athletes.php?match_id=<?php echo $match_id; ?>&delete_athlete_id=<?php echo $ath['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('คุณต้องการลบรายชื่อนักกีฬาท่านนี้ใช่หรือไม่?');"><i class="fa-solid fa-user-minus"></i> ลบ</a>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="3" class="text-center text-muted p-4">ยังไม่มีข้อมูลรายชื่อนักกีฬาในแมตช์นี้</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const maxQuota = <?php echo $max_quota; ?>;
const quotaTracker = <?php echo json_encode($quota_tracker); ?>;

function generateInputFields() {
    const teamSelect = document.getElementById('teamSelect');
    const displayArea = document.getElementById('dynamicInputArea');
    const submitBtn = document.getElementById('submitBtn');
    
    const selectedTeamId = teamSelect.value;
    
    if (!selectedTeamId) {
        displayArea.innerHTML = `<div class="text-center p-4 rounded-3 border border-dashed text-muted small"><i class="fa-solid fa-users fa-2x mb-2 text-black-50"></i><br>กรุณาเลือกทีมสีด้านบนก่อน เพื่อระบุจำนวนช่องกรอกชื่อตามโควตา</div>`;
        submitBtn.classList.add('d-none');
        return;
    }
    
    const registeredCount = parseInt(quotaTracker[selectedTeamId]) || 0;
    const availableSlots = maxQuota - registeredCount;
    
    let htmlContent = '';
    
    if (availableSlots <= 0) {
        htmlContent = `
            <div class="alert alert-warning border-0 text-center rounded-3 p-3 mb-0">
                <i class="fa-solid fa-circle-exclamation text-warning fa-lg mb-2"></i><br>
                <strong>โควตาส่งรายชื่อของสีนี้ครบจำนวน ${maxQuota} คนแล้วครับ</strong><br>
                <span class="small text-muted">หากต้องการแก้ไข ให้กดปุ่ม "ลบ" รายชื่อเดิมในตารางขวามือออกก่อน</span>
            </div>`;
        submitBtn.classList.add('d-none');
    } else {
        htmlContent = `<h6 class="fw-bold mb-3 text-success"><i class="fa-solid fa-user-plus me-1"></i> สามารถส่งรายชื่อเพิ่มได้อีก ${availableSlots} คน:</h6>`;
        
        for (let i = 0; i < availableSlots; i++) {
            htmlContent += `
                <div class="input-group-box">
                    <span class="badge bg-secondary mb-2 small">คนที่ ${registeredCount + i + 1}</span>
                    <input type="text" class="form-control form-control-sm" name="athlete_names[]" placeholder="ชื่อ - นามสกุลนักเรียน" ${i === 0 ? 'required' : ''}>
                </div>`;
        }
        submitBtn.classList.remove('d-none');
    }
    
    displayArea.innerHTML = htmlContent;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
