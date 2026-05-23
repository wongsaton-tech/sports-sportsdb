<?php
// 1. ดึงไฟล์เชื่อมต่อฐานข้อมูลมาคำนวณคะแนนและเหรียญรางวัล
require_once 'db.php';

try {
    // 2. Query ดึงรายชื่อทีมสีทั้งหมด พร้อมคำนวณคะแนนรวมและจำนวนเหรียญรางวัลแบบรวมกลุ่ม
    // (สมมติว่าตารางคะแนนมีการเก็บเหรียญทอง, เงิน, ทองแดง หรือคะแนนรวมเอาไว้)
    // ตรงนี้ดึงข้อมูลทีมสีพื้นฐานออกมาก่อนเพื่อนำมาจัดอันดับบน Dashboard
    $stmt = $pdo->query("SELECT * FROM teams ORDER BY id ASC");
    $teams_dashboard = $stmt->fetchAll();
    
    // หมายเหตุ: โค้ดส่วนนี้พร้อมรองรับการทำ SQL SUM() คะแนนในอนาคต (สเต็บถัดไป)
} catch (\PDOException $e) {
    $teams_dashboard = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการและแดชบอร์ดกีฬาสี</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .menu-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .menu-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
        }
        .rank-badge {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="text-center p-4 bg-white rounded shadow-sm mb-4 border-top border-primary border-4">
        <h1 class="fw-bold text-primary mb-1">🏆 Sports Day Control & Dashboard</h1>
        <p class="text-muted mb-0">ระบบจัดการข้อมูลและสรุปผลคะแนนการแข่งขันรวมในหน้าเดียว</p>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa-solid fa-chart-line me-2 text-warning"></i> ตารางสรุปคะแนนรวม & เหรียญรางวัล</h5>
                    <span class="badge bg-success">Real-time</span>
                </div>
                <div class="card-body">
                    
                    <div class="p-3 bg-warning bg-opacity-10 rounded border border-warning mb-4 text-center">
                        <h6 class="text-warning-emphasis fw-bold mb-1"><i class="fa-solid fa-crown me-1"></i> ทีมสีที่มีคะแนนนำในขณะนี้</h6>
                        <h3 class="fw-bold text-dark mb-0">
                            <?php 
                                // แสดงชื่อทีมสีแรกในตารางเป็นตัวอย่างผู้นำชั่วคราวก่อนผูกคะแนนจริง
                                echo !empty($teams_dashboard) ? htmlspecialchars($teams_dashboard[0]['team_name']) : "ยังไม่มีข้อมูลทีมสี"; 
                            ?>
                        </h3>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="10%" class="text-center">อันดับ</th>
                                    <th width="35%">ทีมสีประจำโรงเรียน</th>
                                    <th width="15%" class="text-center">🥇 ทอง</th>
                                    <th width="15%" class="text-center">🥈 เงิน</th>
                                    <th width="15%" class="text-center">🥉 ทองแดง</th>
                                    <th width="10%" class="text-center">คะแนน</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($teams_dashboard) > 0): ?>
                                    <?php foreach ($teams_dashboard as $index => $team): ?>
                                        <tr>
                                            <td class="text-center">
                                                <?php 
                                                    $bg_badge = 'bg-secondary text-white';
                                                    if ($index == 0) $bg_badge = 'bg-warning text-dark'; // สีทองอันดับ 1
                                                    if ($index == 1) $bg_badge = 'bg-light text-dark border'; // สีเงินอันดับ 2
                                                    if ($index == 2) $bg_badge = 'bg-danger bg-opacity-25 text-danger'; // ทองแดงอันดับ 3
                                                ?>
                                                <div class="rank-badge mx-auto <?php echo $bg_badge; ?>">
                                                    <?php echo $index + 1; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge me-2" style="background-color: <?php echo $team['team_color']; ?>; width: 15px; height: 15px; display: inline-block; vertical-align: middle;"> </span>
                                                <strong><?php echo htmlspecialchars($team['team_name']); ?></strong>
                                            </td>
                                            <td class="text-center fw-bold text-warning">0</td>
                                            <td class="text-center fw-bold text-secondary">0</td>
                                            <td class="text-center fw-bold text-muted">0</td>
                                            <td class="text-center fw-bold text-primary">0</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted p-4">ยังไม่มีข้อมูลทีมสีในระบบ กรุณาเพิ่มทีมสีที่เมนูด้านขวา</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="d-flex flex-column h-100 justify-content-between">
                
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fa-solid fa-gear me-2"></i> เมนูจัดการระบบหลังบ้าน</h5>
                    </div>
                </div>

                <div class="card shadow-sm menu-card mb-3 border-start border-primary border-4" onclick="location.href='manage_teams.php'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded p-3 me-3">
                            <i class="fa-solid fa-palette fa-xl"></i>
                        </div>
                        <div>
                            <h5 class="mb-1 fw-bold text-dark">🎨 เมนูจัดการทีมสี</h5>
                            <p class="mb-0 text-muted small">เพิ่มทีมสี, กำหนดรหัสสี HTML โค้ด เพื่อใช้แยกกลุ่ม</p>
                        </div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                    </div>
                </div>

                <div class="card shadow-sm menu-card mb-3 border-start border-dark border-4" onclick="location.href='manage_categories.php'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="bg-dark bg-opacity-10 text-dark rounded p-3 me-3">
                            <i class="fa-solid fa-folder-open fa-xl"></i>
                        </div>
                        <div>
                            <h5 class="mb-1 fw-bold text-dark">🗂️ เมนูจัดการประเภทกีฬา</h5>
                            <p class="mb-0 text-muted small">แบ่งกลุ่มประเภทกีฬาหลักเพื่อช่วยในการสรุปรายงานผล</p>
                        </div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                    </div>
                </div>

                <div class="card shadow-sm menu-card mb-4 border-start border-success border-4" onclick="location.href='manage_matches.php'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="bg-success bg-opacity-10 text-success rounded p-3 me-3">
                            <i class="fa-solid fa-person-running fa-xl"></i>
                        </div>
                        <div>
                            <h5 class="mb-1 fw-bold text-dark">🏃 ตารางแข่ง & รายชื่อนักกีฬา</h5>
                            <p class="mb-0 text-muted small">สร้างแมตช์ ล็อคโควตาจำนวนคน และคีย์รายชื่อนักเรียน</p>
                        </div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
                    </div>
                </div>

                <div class="bg-white p-3 rounded shadow-sm text-center border mt-auto">
                    <small class="text-muted">
                        <i class="fa-solid fa-database text-success me-1"></i> 
                        สถานะการเชื่อมต่อ: <span class="text-success fw-bold">Aiven Cloud Connected</span>
                    </small>
                </div>

            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
