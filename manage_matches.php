<?php
require_once 'db.php';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างรายการแข่งขันกีฬาสี</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="index.php">🏆 SportsDay Center</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fa-solid fa-chart-line me-1"></i> แดชบอร์ด</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_teams.php"><i class="fa-solid fa-palette me-1"></i> จัดการทีมสี</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_categories.php"><i class="fa-solid fa-folder-open me-1"></i> ประเภทกีฬา</a></li>
                <li class="nav-item"><a class="nav-link active" href="manage_matches.php"><i class="fa-solid fa-person-running me-1"></i> รายการแข่ง & นักกีฬา</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="mb-4">🏃 จัดการและสร้างรายการแข่งขันกีฬา</h2>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white"><h5 class="mb-0">➕ สร้างรายการแข่งขันใหม่</h5></div>
                <div class="card-body">
                    <form action="manage_matches.php" method="POST">
                        <div class="mb-2">
                            <label class="form-label">กลุ่มประเภทกีฬา</label>
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
                            <tr><th>กีฬา</th><th>กลุ่ม</th><th>ลักษณะ</th><th>นักกีฬา/ทีม</th><th>จัดการรายชื่อ</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matches as $m): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($m['sport_name']); ?></strong> (<?php echo $m['gender_type']; ?>)</td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($m['category_name'] ?? 'ไม่ได้ระบุ'); ?></span></td>
                                <td><?php echo $m['tournament_type']; ?></td>
                                <td><?php echo $m['max_players_per_team']; ?> คน</td>
                                <td><a href="register_athletes.php?match_id=<?php echo $m['id']; ?>" class="btn btn-info btn-sm">👥 รายชื่อนักกีฬา</a></td>
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
