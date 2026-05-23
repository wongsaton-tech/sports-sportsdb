<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการข้อมูลกีฬาสีโรงเรียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .menu-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="text-center p-4 bg-white rounded shadow-sm mb-4">
        <h1 class="display-5 fw-bold text-primary">🏆 Sports Day Control Center</h1>
        <p class="lead text-muted mb-0">ระบบจัดการฐานข้อมูลการแข่งขันกีฬาสีออนไลน์ (เชื่อมต่อคลาวด์ Aiven)</p>
    </div>

    <div class="row g-4 justify-content-center">
        
        <div class="col-md-4">
            <div class="card h-100 shadow-sm menu-card border-start border-primary border-4" onclick="location.href='manage_teams.php'">
                <div class="card-body text-center p-4">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 d-inline-block mb-3">
                        <i class="fa-solid fa-palette fa-2x"></i>
                    </div>
                    <h4 class="card-title fw-bold">🎨 จัดการทีมสี</h4>
                    <p class="card-text text-muted">เพิ่ม/ลบกลุ่มทีมสีประจำโรงเรียน กำหนดรหัสสีเพื่อใช้ในการแสดงผลและจำแนกข้อมูลในระบบ</p>
                    <a href="manage_teams.php" class="btn btn-primary w-100 mt-2">เปิดหน้าจัดการทีมสี <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm menu-card border-start border-dark border-4" onclick="location.href='manage_categories.php'">
                <div class="card-body text-center p-4">
                    <div class="bg-dark bg-opacity-10 text-dark rounded-circle p-3 d-inline-block mb-3">
                        <i class="fa-solid fa-folder-open fa-2x"></i>
                    </div>
                    <h4 class="card-title fw-bold">🗂️ จัดการประเภทกีฬา</h4>
                    <p class="card-text text-muted">กำหนดกลุ่มประเภทกรีฑาและกีฬาหลัก (เช่น ประเภทลู่, ประเภทลาน, กีฬาพื้นบ้าน) เพื่อใช้แยกสถิติและสรุปเหรียญรางวัล</p>
                    <a href="manage_categories.php" class="btn btn-dark w-100 mt-2">เปิดหน้าประเภทกีฬา <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm menu-card border-start border-success border-4" onclick="location.href='manage_matches.php'">
                <div class="card-body text-center p-4">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 d-inline-block mb-3">
                        <i class="fa-solid fa-person-running fa-2x"></i>
                    </div>
                    <h4 class="card-title fw-bold">🏃 รายการแข่ง & นักกีฬา</h4>
                    <p class="card-text text-muted">สร้างแมตช์กีฬา กำหนดโควตาจำนวนผู้เล่นต่อทีมสี และคลิกต่อเนื่องเพื่อลงทะเบียนรายชื่อนักเรียนแยกตามชนิดกีฬา</p>
                    <a href="manage_matches.php" class="btn btn-success w-100 mt-2">เปิดระบบจัดการแข่งขัน <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

    </div>

    <div class="text-center mt-5 pb-5 text-muted">
        <small><i class="fa-solid fa-cloud text-info"></i> Database Status: <span class="badge bg-success">Online (Aiven Cloud)</span></small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
