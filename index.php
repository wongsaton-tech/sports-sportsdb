<?php
require_once 'db.php';
require_once 'check_auth.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>SportsDay Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
    <div class="card p-4">
        <h1>Dashboard System</h1>
        <p>หากคุณเห็นหน้านี้ แสดงว่าระบบกลับมาเปิดใช้งานได้แล้ว!</p>
        <a href="manage_matches.php" class="btn btn-primary">ไปหน้าจัดการแมตช์</a>
        <a href="logout.php" class="btn btn-danger">ออกจากระบบ</a>
    </div>
</body>
</html>
