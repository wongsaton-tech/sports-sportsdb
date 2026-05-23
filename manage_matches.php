<?php
require_once 'db.php';

// บันทึกแมตช์การแข่งขันใหม่
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_match'])) {
    $category_id = $_POST['category_id'];
    $sport_name = trim($_POST['sport_name']);
    $gender_type = $_POST['gender_type'];
    $tournament_type = $_POST['tournament_type'];
    $max_players_per_team = intval($_POST['max_players_per_team']);
    $match_datetime = !empty($_POST['match_datetime']) ? $_POST['match_datetime'] : null;

    try {
        $stmt = $pdo->prepare("INSERT INTO matches (category_id, sport_name, gender_type, tournament_type, max_players_per_team, match_datetime) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$category_id, $sport_name, $gender_type, $tournament_type, $max_players_per_team, $match_datetime]);
        header("Location: manage_matches.php?success=1");
        exit();
    } catch (\PDOException $e) { $error_msg = $e->getMessage(); }
}

$categories = $pdo->query("SELECT * FROM sport_categories")->fetchAll();
$matches = $pdo->query("SELECT m.*, c.category_name FROM matches m LEFT JOIN sport_categories c ON m.category_id = c.id ORDER BY m.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สร้างรายการแข่งขันกีฬาสี</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🏃 จัดการและสร้างรายการแข่งขันกีฬา</h2>
        <div>
            <a href="manage_categories.php" class="btn btn-outline-primary">🗂️ จัดการประเภทกีฬา</a>
            <a href="manage_teams.php" class="btn btn-outline-secondary">🎨 จัดการทีมสี</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white"><h5>➕ สร้างรายการแข่งขันใหม่</h5></div>
                <div class="card-body">
                    <form action="manage_matches.php" method="POST">
                        <div class="mb-2">
                            <label class="form-label">กลุ่มประเภทกีฬา (เพื่อสรุปผล)</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">-- เลือกประเภท --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['category_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">ชื่อชนิดกีฬาที่แข่ง</label>
                            <input type="text" class="form-control" name="sport_name" placeholder="เช่น วิ่ง 100 เมตร, ฟุตบอล" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">ประเภท</label>
                            <select class="form-select" name="gender_type">
                                <option value="ชาย">ชาย</option>
                                <option value="หญิง">หญิง</option>
                                <option value="ทั่วไป/ผสม">ทั่วไป/ผสม</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">ลักษณะการแข่งขัน</label>
                            <select class="form-select" name="tournament_type" required>
                                <option value="พบกันหมด">แบบพบกันหมด</option>
                                <option value="คัดออก">แบบคัดออก</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">จำกัดนักกีฬาต่อทีม (คน)</label>
                            <input type="number" class="form-control" name="max_players_per_team" value="1" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">วัน เวลาแข่งขัน</label>
                            <input type="datetime-local" class="form-control" name="match_datetime">
                        </div>
                        <button type="submit" name="add_match" class="btn btn-success w-100">สร้างรายการแข่งขัน</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>กีฬา</th>
                                <th>กลุ่ม</th>
                                <th>ลักษณะ</th>
                                <th>นักกีฬา/ทีม</th>
                                <th>จัดการรายชื่อ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matches as $m): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($m['sport_name']); ?></strong> (<?php echo $m['gender_type']; ?>)</td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($m['category_name'] ?? 'ไม่ได้ระบุ'); ?></span></td>
                                <td><?php echo $m['tournament_type']; ?></td>
                                <td><?php echo $m['max_players_per_team']; ?> คน</td>
                                <td>
                                    <a href="register_athletes.php?match_id=<?php echo $m['id']; ?>" class="btn btn-info btn-sm">👥 รายชื่อนักกีฬา</a>
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
</body>
</html>