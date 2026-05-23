<?php
require_once 'db.php';

// รับค่าไอดีผลการแข่งขันมาเพื่อดึงชื่อนักกีฬาและชนิดกีฬา
$result_id = isset($_GET['result_id']) ? intval($_GET['result_id']) : 0;

try {
    // Query ข้อมูลแบบละเอียด: ดึงชื่อกีฬา, ประเภท, เหรียญรางวัล, ชื่อนักกีฬา และรหัสนักเรียนมารวมกัน
    $sql = "SELECT r.medal, m.sport_name, m.gender_type, t.team_name,
                   a.athlete_name, a.student_id
            FROM match_results r
            JOIN matches m ON r.match_id = m.id
            JOIN teams t ON r.team_id = t.id
            -- เชื่อมเพื่อดึงรายชื่อนักเรียนในทีมสีนั้นที่ลงแข่งในแมตช์นั้น ๆ
            LEFT JOIN athletes a ON a.match_id = m.id AND a.team_id = t.id
            WHERE r.id = ?
            LIMIT 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$result_id]);
    $data = $stmt->fetch();

    if (!$data) {
        die("<div class='container mt-5 alert alert-danger text-center'>ไม่พบข้อมูลผลการแข่งขัน หรือยังไม่มีการลงทะเบียนรายชื่อนักกีฬาในระบบ</div>");
    }
} catch (\PDOException $e) {
    die("ข้อผิดพลาดระบบ: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เกียรติบัตรออนไลน์ - <?php echo htmlspecialchars($data['athlete_name'] ?? 'นักกีฬา'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f2f2f7;
            color: #1c1c1e;
        }
        /* ควบคุมสไตล์หน้าจอพรีวิว (Screen Mode) */
        .preview-container {
            max-width: 842px; /* ขนาด A4 แนวนอนตามสัดส่วนพิกเซล */
            margin: 40px auto;
        }
        
        /* 🏆 ดีไซน์ตัวเกียรติบัตรสไตล์โมเดิร์นคลีน */
        .certificate-frame {
            width: 100%;
            height: 595px; /* สัดส่วน A4 แนวนอน */
            background: #ffffff;
            border-radius: 12px;
            padding: 50px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 15px solid #1c1c1e; /* ขอบนอกสีดำหรู */
            outline: 2px dashed #d1a153; /* เส้นประสีทองด้านใน */
            outline-offset: -10px;
            display: flex;
            flex-column: column;
            justify-content: space-between;
            align-items: center;
            text-center: center;
        }
        .cert-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #1c1c1e;
            letter-spacing: 1px;
        }
        .cert-title {
            font-size: 1.1rem;
            color: #d1a153; /* สีทองโมเดิร์น */
            font-weight: 600;
            margin-top: 5px;
        }
        .cert-body {
            margin-top: 30px;
        }
        .student-name {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1c1c1e;
            border-bottom: 2px solid #d1a153;
            display: inline-block;
            padding: 0 30px 5px 30px;
            margin: 15px 0;
        }
        .cert-text {
            font-size: 1.2rem;
            color: #48484a;
            line-height: 1.8;
        }
        .sport-highlight {
            color: #1c1c1e;
            font-weight: 600;
        }
        .medal-badge {
            font-size: 1.3rem;
            font-weight: 700;
            color: #d1a153;
        }
        .cert-footer {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 40px;
            padding: 0 40px;
        }
        .signature-line {
            width: 180px;
            border-top: 1px solid #aeaeaf;
            margin-top: 40px;
            font-size: 0.9rem;
            color: #8e8e93;
        }

        /* 🖨️ คำสั่งสำหรับซ่อนปุ่มควบคุมเวลาสั่งปริ้นออกกระดาษ/PDF */
        @media print {
            body { background: #ffffff; padding: 0; margin: 0; }
            .no-print { display: none !important; }
            .preview-container { margin: 0; max-width: 100%; }
            .certificate-frame { 
                border-radius: 0; 
                box-shadow: none; 
                height: 100vh; /* บังคับให้เต็มหน้ากระดาษ */
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<div class="container mt-4 no-print">
    <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded-3 shadow-sm mb-2">
        <a href="manage_scores.php" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> กลับหน้าบันทึกคะแนน</a>
        <button onclick="window.print();" class="btn btn-primary btn-sm px-4 fw-bold">
            <i class="fa-solid fa-print me-1"></i> พิมพ์เกียรติบัตร (เซฟเป็น PDF)
        </button>
    </div>
</div>

<div class="preview-container">
    <div class="certificate-frame text-center">
        
        <div class="cert-header w-100">
            <div class="text-center mb-2">
                <i class="fa-solid fa-trophy fa-3x text-warning"></i>
            </div>
            <h2>เกียรติบัตรแสดงความยินดี</h2>
            <div class="cert-title">การแข่งขันกีฬาสีประจำปีโรงเรียน</div>
        </div>

        <div class="cert-body w-100">
            <div class="cert-text">เกียรติบัตรฉบับนี้ให้ไว้เพื่อแสดงว่า</div>
            
            <div class="student-name">
                <?php echo htmlspecialchars($data['athlete_name'] ?? ' ไม่พบรายชื่อนักกีฬาในระบบ '); ?>
            </div>
            
            <div class="cert-text">
                สังกัด <span class="sport-highlight"><?php echo htmlspecialchars($data['team_name']); ?></span> ได้เข้าร่วมและประสบความสำเร็จในรางวัล 
                <span class="medal-badge">
                    <?php 
                        if ($data['medal'] == 'ทอง') echo '🥇 ชนะเลิศเหรียญทอง';
                        elseif ($data['medal'] == 'เงิน') echo '🥈 รองชนะเลิศอันดับ 1 เหรียญเงิน';
                        elseif ($data['medal'] == 'ทองแดง') echo '🥉 รองชนะเลิศอันดับ 2 เหรียญทองแดง';
                        else echo '🏅 เข้าร่วมการแข่งขัน';
                    ?>
                </span>
                <br>ในการแข่งขันชนิดกีฬา <span class="sport-highlight"><?php echo htmlspecialchars($data['sport_name'] . ' (' . $data['gender_type'] . ')'); ?></span>
            </div>
        </div>

        <div class="cert-footer">
            <div class="text-center">
                <div class="medal-badge" style="font-size: 1.1rem; letter-spacing: 2px;">SPORTS DAY 2026</div>
                <div class="small text-muted">ให้ไว้ ณ วันที่แข่งขัน</div>
            </div>
            <div class="text-center">
                <div class="signature-line">ผู้จัดการแข่งขัน</div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>