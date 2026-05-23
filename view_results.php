<?php
require_once 'db.php';
// ดึงข้อมูลผลการแข่งขัน พร้อมชื่อทีมและชื่อรายการ
$sql = "SELECT m.match_name, t.team_name, r.medal, r.points, r.athlete_name
        FROM match_results r
        JOIN matches m ON r.match_id = m.id
        JOIN teams t ON r.team_id = t.id
        ORDER BY t.team_name, m.match_name";
$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// จัดกลุ่มข้อมูลตามทีมสี
$grouped_data = [];
foreach ($results as $row) {
    $grouped_data[$row['team_name']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สรุปผลการแข่งขันแยกตามทีม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container bg-white p-4 rounded shadow-sm">
    <h2 class="fw-bold mb-4">รายงานผลการแข่งขันแยกตามทีม</h2>
    <a href="index.php" class="btn btn-secondary mb-3">กลับหน้าหลัก</a>

    <?php foreach ($grouped_data as $team_name => $matches): ?>
        <h4 class="mt-4 text-primary fw-bold">ทีม: <?php echo htmlspecialchars($team_name); ?></h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>รายการแข่งขัน</th>
                        <th>ชื่อนักกีฬา</th>
                        <th>เหรียญรางวัล</th>
                        <th>คะแนน</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matches as $m): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($m['match_name']); ?></td>
                        <td><?php echo htmlspecialchars($m['athlete_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($m['medal']); ?></td>
                        <td><?php echo htmlspecialchars($m['points']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>