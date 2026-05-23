<?php
require_once 'db.php';

// รับค่า ID ที่ต้องการแก้ไข
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ดึงข้อมูลรายการนั้นๆ ขึ้นมาโชว์ในฟอร์ม
$stmt = $pdo->prepare("SELECT * FROM match_results WHERE id = ?");
$stmt->execute([$id]);
$result = $stmt->fetch();

if (!$result) {
    die("ไม่พบข้อมูลที่ต้องการแก้ไข");
}

// อัปเดตข้อมูลเมื่อกดบันทึก
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_score'])) {
    $sql = "UPDATE match_results SET medal = ?, points = ? WHERE id = ?";
    $pdo->prepare($sql)->execute([$_POST['medal'], intval($_POST['points']), $id]);
    header("Location: manage_scores.php?success=updated");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขผลคะแนน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow-sm col-md-6 mx-auto">
        <div class="card-header bg-warning"><h5>✏️ แก้ไขผลการแข่งขัน</h5></div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label>เหรียญรางวัล</label>
                    <select class="form-select" name="medal">
                        <option value="ไม่มี" <?php if($result['medal']=='ไม่มี') echo 'selected'; ?>>ไม่มี</option>
                        <option value="ทอง" <?php if($result['medal']=='ทอง') echo 'selected'; ?>>🥇 ทอง</option>
                        <option value="เงิน" <?php if($result['medal']=='เงิน') echo 'selected'; ?>>🥈 เงิน</option>
                        <option value="ทองแดง" <?php if($result['medal']=='ทองแดง') echo 'selected'; ?>>🥉 ทองแดง</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>คะแนน</label>
                    <input type="number" class="form-control" name="points" value="<?php echo $result['points']; ?>" required>
                </div>
                <button type="submit" name="update_score" class="btn btn-primary">บันทึกการแก้ไข</button>
                <a href="manage_scores.php" class="btn btn-secondary">ยกเลิก</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>