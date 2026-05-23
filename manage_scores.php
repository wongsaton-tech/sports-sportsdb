<?php
require_once 'db.php';

// 1. ฟังก์ชันลบ
if (isset($_GET['delete'])) {
    $del = $pdo->prepare("DELETE FROM match_results WHERE id = ?");
    $del->execute([intval($_GET['delete'])]);
    header("Location: manage_scores.php?success=deleted");
    exit();
}

// 2. บันทึกผล (คงเดิม)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_score'])) {
    $stmt = $pdo->prepare("INSERT INTO match_results (match_id, team_id, medal, points) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['match_id'], $_POST['team_id'], $_POST['medal'], intval($_POST['points'])]);
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

<table class="table table-striped">
    <thead>
        <tr>
            <th>รายการแข่งขัน</th>
            <th>ทีมสี</th>
            <th>เหรียญ</th>
            <th>คะแนน</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($results as $res): ?>
        <tr>
            <td><?php echo htmlspecialchars($res['sport_name']); ?></td>
            <td><span class="badge" style="background-color: <?php echo $res['team_color']; ?>;"><?php echo $res['team_name']; ?></span></td>
            <td><?php echo $res['medal']; ?></td>
            <td><?php echo $res['points']; ?></td>
            <td>
                <a href="edit_score.php?id=<?php echo $res['id']; ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                <a href="manage_scores.php?delete=<?php echo $res['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบ?');">ลบ</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
