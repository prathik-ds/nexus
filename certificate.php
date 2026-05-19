<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/db.php';

if (!isset($_SESSION['user'])) {
    die("Access Denied. Please login first.");
}

$user_id = $_SESSION['user']['user_id'];
$event_id = $_GET['event_id'] ?? null;

if (!$event_id) {
    die("Invalid Request.");
}

$stmt = $pdo->prepare("
    SELECT r.*, e.name as event_name, e.category, u.name as user_name 
    FROM registrations r 
    JOIN events e ON r.event_id = e.id 
    JOIN users u ON r.user_id = u.user_id 
    WHERE r.user_id = ? AND r.event_id = ?
");
$stmt->execute([$user_id, $event_id]);
$data = $stmt->fetch();

if (!$data) {
    die("Registration not found.");
}

if ($data['status'] === 'registered') {
    die("Certificate will be available after participation is verified by the coordinator.");
}

$certificate_type = "PARTICIPATION";
if ($data['status'] === 'winner') $certificate_type = "WINNER";
if ($data['status'] === 'runner') $certificate_type = "RUNNER UP";
if ($data['status'] === 'third') $certificate_type = "SECOND RUNNER UP";

$current_date = date("F j, Y");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1200, initial-scale=0.1">
    <title>Download Award - <?= htmlspecialchars($data['event_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Great+Vibes&family=Pirata+One&family=Montserrat:wght@800;900&family=Outfit:wght@900&display=swap" rel="stylesheet">
    
    <!-- PDF: Pixel-Perfect Screenshot approach -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: #050510;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: auto;
        }

        .cert-wrapper {
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Essential: The Certificate Canvas */
        .cert-canvas {
            width: 297mm;
            height: 210mm;
            min-width: 297mm;
            min-height: 210mm;
            background: url('images/fantasy_cert_bg.jpg') no-repeat center center;
            background-size: cover;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 40px 60px;
            
            border: 22px solid #1a1a2e;
            outline: 3px solid #d4af37;
            outline-offset: -10px;
        }

        /* Fixed Elements Styles */
        .ornament { position: absolute; width: 70px; height: 70px; z-index: 100; background: #d4af37; clip-path: polygon(0% 0%, 100% 0%, 100% 12%, 12% 12%, 12% 100%, 0% 100%); }
        .orn-tl { top: -2px; left: -2px; }
        .orn-tr { top: -2px; right: -2px; transform: rotate(90deg); }
        .orn-bl { bottom: -2px; left: -2px; transform: rotate(-90deg); }
        .orn-br { bottom: -2px; right: -2px; transform: rotate(180deg); }

        .hdr-section { width: 100%; display: flex; justify-content: space-between; align-items: center; z-index: 50; }
        .h-logo { height: 95px; filter: drop-shadow(0 0 15px rgba(255,255,255,0.4)); }
        
        .clg-name { 
            font-family: 'Cinzel Decorative', serif; font-size: 2.3rem; font-weight: 900; text-transform: uppercase; letter-spacing: 3px;
            background: linear-gradient(45deg, #d4af37 25%, #fcf6ba 50%, #d4af37 75%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 5px 12px rgba(0,0,0,1));
        }
        .naac-info { font-family: 'Montserrat', sans-serif; font-size: 0.8rem; font-weight: 900; color: #f1c40f; margin-top: 5px; letter-spacing: 4px; }

        .parchment-content {
            width: 38%; height: 50%; z-index: 10; align-self: center; margin-left: 20px; display: flex; flex-direction: column; justify-content: space-evenly; align-items: center; text-align: center; padding: 20px; color: #111; position: relative;
            background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(3px) brightness(1.1); border-radius: 40px; border: 2px solid rgba(212, 175, 55, 0.15);
        }

        .parch-water { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 280px; opacity: 0.28; z-index: -1; filter: contrast(1.2); }
        .rank-h { font-family: 'Montserrat', sans-serif; font-size: 1rem; font-weight: 900; letter-spacing: 12px; color: #e67e22; text-transform: uppercase; }
        .fest-t { font-family: 'Pirata One', cursive; font-size: 5.5rem; line-height: 1; color: #2c3e50; text-shadow: 3px 3px 0px rgba(255,255,255,0.7); }
        .stu-n { font-family: 'Great Vibes', cursive; font-size: 6rem; font-weight: 900; color: #c0392b; line-height: 1; text-shadow: 0 0 30px rgba(255, 255, 255, 1); }
        .ach-l { font-family: 'Montserrat', sans-serif; font-size: 0.95rem; font-weight: 900; color: #222; }
        .evt-h { color: #16a085; font-size: 1.25rem; font-weight: 950; background: rgba(0,0,0,0.05); padding: 5px 15px; border-radius: 5px; }

        .footer-sec { width: 100%; display: flex; justify-content: space-between; align-items: flex-end; z-index: 55; padding: 0 20px; }
        .footer-block { text-align: center; width: 240px; color: #fff; text-shadow: 0 5px 20px rgba(0,0,0,1); }
        .sig-font { font-family: 'Great Vibes'; font-size: 3rem; border-bottom: 3px solid #d4af37; margin-bottom: 5px; }
        .date-f { font-family: 'Montserrat', sans-serif; font-size: 1.8rem; font-weight: 900; color: #fff; border-bottom: 3px solid rgba(255,255,255,0.4); margin-bottom: 5px; padding-bottom: 5px; }
        .footer-l { font-family: 'Montserrat', sans-serif; font-size: 0.8rem; font-weight: 900; color: #f1c40f; letter-spacing: 3px; text-transform: uppercase; }
        .fest-s { height: 110px; filter: contrast(1.2) drop-shadow(0 0 15px rgba(255,255,255,0.3)); transform: translateY(-10px); }

        /* Controls */
        .controls { position: fixed; bottom: 30px; right: 30px; z-index: 1000; }
        .p-btn {
            background: #f1c40f; color: #000; border: none; padding: 22px 60px; border-radius: 100px;
            font-weight: 900; cursor: pointer; box-shadow: 0 20px 60px rgba(0,0,0,1);
            font-family: 'Montserrat', sans-serif; text-transform: uppercase; letter-spacing: 3px; transition: 0.4s;
            font-size: 1rem;
        }
        .p-btn:hover { background: #fff; transform: scale(1.1); }
        .p-btn.loading { opacity: 0.7; pointer-events: none; }

        /* Larger button on mobile */
        @media only screen and (max-width: 900px) {
            .controls { bottom: 20px; left: 50%; transform: translateX(-50%); right: auto; }
            .p-btn { padding: 28px 80px; font-size: 1.4rem; letter-spacing: 2px; border-radius: 120px; }
        }

        @media print {
            .controls { display: none; }
            body { background: #000; }
            .cert-wrapper { padding: 0; transform: none !important; display: block; }
            .cert-canvas { width: 297mm; height: 210mm; margin: 0; box-shadow: none; border: 22px solid #1a1a2e; }
        }
    </style>
</head>
<body>

    <div class="controls">
        <button id="downloadBtn" class="p-btn">DOWNLOAD ELITE PDF</button>
    </div>

    <div class="cert-wrapper">
        <div id="certificate" class="cert-canvas">
            <div class="ornament orn-tl"></div>
            <div class="ornament orn-tr"></div>
            <div class="ornament orn-bl"></div>
            <div class="ornament orn-br"></div>

            <div class="hdr-section">
                <img src="images/college_logo.png" class="h-logo" alt="Logo">
                <div style="text-align: center;">
                    <h1 class="clg-name">MILAGRES COLLEGE, KALLIANPUR</h1>
                    <div class="naac-info">NAAC RE-ACCREDITED A+ | FUSION SYMPOSIUM 2026</div>
                </div>
                <img src="images/naac_logo.png" class="h-logo" alt="NAAC">
            </div>

            <div class="parchment-content">
                <img src="images/logo.png" class="parch-water" alt="Water">
                <div class="rank-h">CERTIFICATE OF <?= $certificate_type ?></div>
                <h2 class="fest-t">NEXUS FEST</h2>
                <p style="font-family: 'Montserrat', sans-serif; font-size: 0.8rem; font-weight: 800; letter-spacing: 5px; color: #444;">BESTOWED PROUDLY UPON</p>
                <div class="stu-n"><?= htmlspecialchars($data['user_name']) ?></div>
                <p class="ach-l">
                    Outstanding achievement in the <br>
                    <span class="evt-h"><?= htmlspecialchars($data['event_name']) ?></span> Competition.
                </p>
            </div>

            <div class="footer-sec">
                <div class="footer-block">
                    <div class="sig-font">Dr. Vincent Alva</div>
                    <div class="footer-l">Principal</div>
                </div>
                <img src="images/logo.png" class="fest-s" alt="Seal">
                <div class="footer-block">
                    <div class="date-f"><?= $current_date ?></div>
                    <div class="footer-l">Date of Issue</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const { jsPDF } = window.jspdf;

        document.getElementById('downloadBtn').addEventListener('click', async function() {
            const btn = this;
            btn.textContent = 'GENERATING PDF...';
            btn.classList.add('loading');

            // Temporarily reset scale for pixel-perfect capture
            const wrapper = document.querySelector('.cert-wrapper');
            const prevTransform = wrapper.style.transform;
            wrapper.style.transform = 'none';

            await new Promise(r => setTimeout(r, 200)); // Wait for DOM paint

            const canvas = await html2canvas(document.getElementById('certificate'), {
                scale: 3,           // 3x for ultra-sharp quality
                useCORS: true,
                allowTaint: true,
                backgroundColor: null,
                logging: false,
                width: document.getElementById('certificate').offsetWidth,
                height: document.getElementById('certificate').offsetHeight
            });

            // Restore scale after capture
            wrapper.style.transform = prevTransform;

            const imgData = canvas.toDataURL('image/jpeg', 1.0);
            const pdf = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

            const pageW = pdf.internal.pageSize.getWidth();
            const pageH = pdf.internal.pageSize.getHeight();

            // Fit the screenshot exactly into A4 landscape
            pdf.addImage(imgData, 'JPEG', 0, 0, pageW, pageH);
            pdf.save('NexusFest_Certificate_<?= preg_replace('/[^a-zA-Z0-9]/', '_', $data['user_name']) ?>.pdf');

            btn.textContent = 'DOWNLOAD ELITE PDF';
            btn.classList.remove('loading');
        });

        // Mobile Scaling — keeps visual layout locked
        function adjustScale() {
            const wrapper = document.querySelector('.cert-wrapper');
            const viewportWidth = window.innerWidth;
            const targetWidth = 1150;
            if (viewportWidth < targetWidth) {
                const scale = (viewportWidth - 20) / targetWidth;
                wrapper.style.transform = `scale(${scale})`;
                wrapper.style.transformOrigin = 'center top';
                wrapper.style.height = (210 * 3.78 * scale) + 'px';
            } else {
                wrapper.style.transform = 'none';
                wrapper.style.height = 'auto';
            }
        }
        window.addEventListener('resize', adjustScale);
        window.addEventListener('DOMContentLoaded', adjustScale);
    </script>

</body>
</html>