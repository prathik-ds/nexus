<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Standalone leaderboard - no session check required for viewing results
require_once 'config/db.php';

// Fetch all events that have scores
$query = "
    SELECT 
        e.id as event_id,
        e.name as event_name, 
        e.is_team_event, 
        e.date, 
        e.time,
        r.score, 
        r.status, 
        u.name as user_name,
        t.name as team_name,
        t.id as team_id
    FROM events e
    JOIN registrations r ON e.id = r.event_id
    JOIN users u ON r.user_id = u.user_id
    LEFT JOIN teams t ON r.team_id = t.id
    WHERE r.score > 0 OR r.status != 'registered'
    ORDER BY e.date ASC, e.time ASC, e.name, r.score DESC
";

$stmt = $pdo->query($query);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group results by event and then by rank (status or score)
$events = [];
foreach ($results as $row) {
    $e_id = $row['event_id'];
    if (!isset($events[$e_id])) {
        $events[$e_id] = [
            'name' => $row['event_name'],
            'date' => $row['date'],
            'time' => $row['time'],
            'ranks' => [] // Rank 1, 2, 3
        ];
    }
    
    // Determine the logical rank
    $status = strtolower($row['status']);
    $rank_key = 0;
    
    // Explicit value matching first (as defined in admin_leaderboard.php)
    if ($status === 'winner') {
        $rank_key = 1;
    } else if ($status === 'runner') {
        $rank_key = 2;
    } else if ($status === 'third') {
        $rank_key = 3;
    } else {
        // Fallback to fuzzy matching if exact values aren't used
        if (strpos($status, 'winner') !== false || strpos($status, '1st') !== false) $rank_key = 1;
        else if (strpos($status, 'runner') !== false || strpos($status, '2nd') !== false) $rank_key = 2;
        else if (strpos($status, 'third') !== false || strpos($status, '3rd') !== false || strpos($status, 'attend') !== false || strpos($status, 'participated') !== false) {
            $rank_key = 3;
        } else if ($row['score'] > 0) {
            $rank_key = 3;
        }
    }

    // Only proceed if we found a valid rank level
    if ($rank_key < 1 || $rank_key > 3) continue;

    if (!isset($events[$e_id]['ranks'][$rank_key])) {
        $events[$e_id]['ranks'][$rank_key] = [];
    }

    // Handle Teams vs Individuals
    $p_data = null;
    if ($row['is_team_event'] && $row['team_id']) {
        $t_name = $row['team_name'];
        $already_in = false;
        foreach ($events[$e_id]['ranks'][$rank_key] as $p) {
            if ($p['is_team'] && $p['name'] == $t_name) { $already_in = true; break; }
        }
        if (!$already_in) {
            $m_stmt = $pdo->prepare("SELECT u.name FROM users u JOIN registrations r ON u.user_id = r.user_id LEFT JOIN teams t ON r.team_id = t.id WHERE (t.name = ? OR r.team_id = ?) AND r.event_id = ?");
            $m_stmt->execute([$t_name, $row['team_id'], $e_id]);
            $p_data = [
                'name' => $row['team_name'],
                'members' => $m_stmt->fetchAll(PDO::FETCH_COLUMN),
                'score' => $row['score'],
                'team_id' => $t_id,
                'is_team' => true
            ];
        }
    } else if (!$row['is_team_event']) {
        $p_data = [
            'name' => $row['user_name'],
            'score' => $row['score'],
            'is_team' => false
        ];
    }

    if ($p_data) {
        $events[$e_id]['ranks'][$rank_key][] = $p_data;
    }
}

// Transform grouped ranks into Rank 1, Rank 2, Rank 3
$slides = [];
foreach ($events as $id => $data) {
    $final_ranks = [];
    foreach ([1, 2, 3] as $r) {
        if (isset($data['ranks'][$r])) {
            $final_ranks[$r] = $data['ranks'][$r];
        }
    }
    
    if (!empty($final_ranks)) {
        $slides[] = [
            'name' => $data['name'],
            'date' => $data['date'],
            'time' => $data['time'],
            'ranks' => $final_ranks
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vortex Rankings | SCI-FI Leaderboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Space+Grotesk:wght@300;500;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00d4ff;
            --secondary: #7c3aed;
            --accent: #10b981;
            --bg: #04060e;
            --glass: rgba(15, 22, 41, 0.5);
            --border: rgba(0, 212, 255, 0.2);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg);
            color: #fff;
            font-family: 'Space Grotesk', sans-serif;
            overflow: hidden;
            height: 100vh;
        }

        /* Sci-Fi Background */
        .vortex-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: 
                radial-gradient(circle at 50% 50%, rgba(124, 58, 237, 0.1) 0%, transparent 70%),
                radial-gradient(circle at 20% 30%, rgba(0, 212, 255, 0.05) 0%, transparent 50%);
        }

        /* Animated Grid */
        .grid-plane {
            position: absolute;
            width: 200%;
            height: 200%;
            top: -50%;
            left: -50%;
            background-image: 
                linear-gradient(rgba(0, 212, 255, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 212, 255, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            transform: perspective(500px) rotateX(60deg);
            animation: grid-move 20s linear infinite;
        }

        @keyframes grid-move {
            0% { transform: perspective(500px) rotateX(60deg) translateY(0); }
            100% { transform: perspective(500px) rotateX(60deg) translateY(50px); }
        }

        /* Slideshow Container */
        .slideshow-viewport {
            height: 100vh;
            width: 100vw;
            display: flex;
            transition: transform 0.8s cubic-bezier(0.85, 0, 0.15, 1);
        }

        .slide {
            min-width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: relative;
        }

        /* Event Header */
        .event-header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeInDown 0.8s both;
        }

        .event-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 3.5rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 4px;
            background: linear-gradient(135deg, #fff 30%, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 30px rgba(0, 212, 255, 0.3);
            margin-bottom: 5px;
        }

        .event-meta {
            font-family: 'JetBrains Mono', monospace;
            color: var(--primary);
            font-size: 1rem;
            letter-spacing: 2px;
            opacity: 0.8;
        }

        /* Winners Reveal Section */
        .winners-container {
            display: flex;
            gap: 20px;
            align-items: flex-end;
            justify-content: center;
            width: 100%;
            max-width: 1400px;
            height: 500px;
            perspective: 1000px;
        }

        .winner-podium {
            flex: 1;
            min-width: 280px;
            max-width: 400px;
            background: rgba(15, 22, 41, 0.4);
            border: 1px solid rgba(0, 212, 255, 0.1);
            border-radius: 24px 24px 8px 8px;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            position: relative;
            backdrop-filter: blur(20px);
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            opacity: 0;
            transform: translateY(120px) rotateX(-20deg);
            overflow: hidden;
        }

        .winner-podium::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; width: 100%; height: 4px;
            background: var(--primary);
            box-shadow: 0 0 20px var(--primary);
        }

        .rank-1-card::after { background: gold; box-shadow: 0 0 20px gold; }
        .rank-2-card::after { background: silver; box-shadow: 0 0 20px silver; }
        .rank-3-card::after { background: #cd7f32; box-shadow: 0 0 20px #cd7f32; }

        .winner-podium.revealed {
            opacity: 1;
            transform: translateY(0) rotateX(0);
        }

        /* Grouping by Rank */
        .rank-tier {
            display: none;
            gap: 20px;
            width: auto;
        }
        
        .rank-tier.active {
            display: flex;
        }

        /* Podium Heights */
        .rank-1-card { height: 100%; background: rgba(251, 191, 36, 0.05); border-color: rgba(255, 215, 0, 0.3); z-index: 3; }
        .rank-2-card { height: 85%; z-index: 2; }
        .rank-3-card { height: 75%; z-index: 1; }

        .rank-tag {
            font-family: 'Orbitron', sans-serif;
            font-size: 1rem;
            font-weight: 900;
            margin-bottom: 20px;
            padding: 5px 20px;
            border-radius: 4px;
            background: rgba(0,0,0,0.3);
            letter-spacing: 2px;
        }

        .rank-1-card .rank-tag { color: gold; border-left: 4px solid gold; }
        .rank-2-card .rank-tag { color: silver; border-left: 4px solid silver; }
        .rank-3-card .rank-tag { color: #cd7f32; border-left: 4px solid #cd7f32; }

        .winner-name {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 10px;
            color: #fff;
            line-height: 1.2;
        }

        .team-members {
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            color: var(--text-secondary);
            text-align: center;
            background: rgba(255,255,255,0.03);
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .score-val {
            margin-top: auto;
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.5rem;
            color: var(--primary);
            font-weight: 700;
            background: rgba(0, 212, 255, 0.1);
            padding: 4px 16px;
            border-radius: 50px;
        }

        /* Congratulations Overlay */
        .congrats-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            text-align: center;
            pointer-events: none;
            opacity: 0;
            display: none;
        }

        .congrats-overlay.show {
            display: block;
            animation: congrats-pop 1.5s forwards;
        }

        @keyframes congrats-pop {
            0% { opacity: 0; transform: translate(-50%, -50%) scale(0.5); }
            20% { opacity: 1; transform: translate(-50%, -50%) scale(1.1); }
            80% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
            100% { opacity: 0; transform: translate(-50%, -50%) scale(1.5); }
        }

        .congrats-text {
            font-family: 'Orbitron', sans-serif;
            font-size: 6rem;
            font-weight: 900;
            color: #fff;
            text-shadow: 0 0 50px var(--primary), 0 0 100px var(--secondary);
            text-transform: uppercase;
            animation: glitch 1s infinite alternate;
        }

        @keyframes glitch {
            0% { text-shadow: 2px 2px var(--primary), -2px -2px var(--secondary); }
            100% { text-shadow: -2px -2px var(--primary), 2px 2px var(--secondary); }
        }

        /* Controls */
        .nav-controls {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 20px;
            z-index: 1000;
            align-items: center;
        }

        .btn-vortex {
            background: var(--glass);
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 12px 24px;
            border-radius: 8px;
            font-family: 'Orbitron', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: 0.3s;
            backdrop-filter: blur(10px);
            font-size: 0.8rem;
        }

        .btn-reveal {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            border: none;
            color: white;
            min-width: 220px;
        }

        .dot { width: 8px; height: 8px; background: rgba(255,255,255,0.2); border-radius: 50%; transition: 0.3s; }
        .dot.active { background: var(--primary); width: 24px; border-radius: 4px; }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-40px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="vortex-bg">
    <div class="grid-plane"></div>
</div>

<?php if (empty($slides)): ?>
    <div class="slide">
        <h1 class="event-title">SYSTEM STANDBY</h1>
        <p class="event-meta">NO RANKING DATA DETECTED</p>
    </div>
<?php else: ?>
    <div class="slideshow-viewport" id="viewport">
        <?php foreach ($slides as $s_idx => $slide): ?>
            <div class="slide" id="slide-<?= $s_idx ?>">
                <div class="congrats-overlay" id="congrats-<?= $s_idx ?>">
                    <div class="congrats-text">CONGRATULATIONS!</div>
                </div>

                <div class="event-header">
                    <h1 class="event-title"><?= htmlspecialchars($slide['name']) ?></h1>
                    <div class="event-meta">CHRONO-LOG: <?= date('d M Y', strtotime($slide['date'])) ?></div>
                </div>

                <div class="winners-container">
                    <?php 
                    // Explicitly loop through ranks 3, 2, 1 for the reveal sequence positioning
                    for ($rank_level = 3; $rank_level >= 1; $rank_level--): 
                        if (!isset($slide['ranks'][$rank_level])) continue;
                        $group = $slide['ranks'][$rank_level];
                        
                        $label_text = "ERROR";
                        if($rank_level === 1) $label_text = "CHAMPION";
                        if($rank_level === 2) $label_text = "RUNNER-UP";
                        if($rank_level === 3) $label_text = "2ND RUNNER";
                        ?>
                        <div class="rank-tier tier-<?= $rank_level ?>" id="slide-<?= $s_idx ?>-tier-<?= $rank_level ?>">
                            <?php foreach ($group as $p): ?>
                                <div class="winner-podium rank-<?= $rank_level ?>-card" data-rank="<?= $rank_level ?>">
                                    <div class="rank-tag"><?= $label_text ?></div>
                                    <div class="winner-name"><?= htmlspecialchars($p['name']) ?></div>
                                    <?php if (!empty($p['members'])): ?>
                                        <div class="team-members"><?= implode(' • ', array_map('htmlspecialchars', $p['members'])) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="nav-controls">
        <button class="btn-vortex" onclick="prevSlide()"><i class="fa-solid fa-arrow-left"></i></button>
        <button class="btn-vortex btn-reveal" id="revealBtn" onclick="handleReveal()">REVEAL SESSION</button>
        <button class="btn-vortex" onclick="nextSlide()"><i class="fa-solid fa-arrow-right"></i></button>
    </div>
<?php endif; ?>

<script>
    let currentSlide = 0;
    const slidesCount = <?= count($slides) ?>;
    let currentRankReveal = 4; // Reveal sequence starts from 3, 2, 1

    function updateView() {
        document.getElementById('viewport').style.transform = `translateX(-${currentSlide * 100}vw)`;
        resetReveal();
    }

    function resetReveal() {
        currentRankReveal = 4;
        document.getElementById('revealBtn').innerText = "REVEAL SESSION";
        document.querySelectorAll('.winner-podium').forEach(p => p.classList.remove('revealed'));
        document.querySelectorAll('.rank-tier').forEach(t => t.classList.remove('active'));
    }

    function handleReveal() {
        currentRankReveal--;
        const revealBtn = document.getElementById('revealBtn');

        if (currentRankReveal >= 1) {
            const tier = document.getElementById(`slide-${currentSlide}-tier-${currentRankReveal}`);
            if (tier) {
                tier.classList.add('active');
                setTimeout(() => {
                    tier.querySelectorAll('.winner-podium').forEach(p => p.classList.add('revealed'));
                }, 50);

                // Peak at next available rank to show correct label
                let nextRank = currentRankReveal - 1;
                while(nextRank >= 1 && !document.getElementById(`slide-${currentSlide}-tier-${nextRank}`)) {
                    nextRank--;
                }

                if (currentRankReveal === 1) {
                    const congrats = document.getElementById(`congrats-${currentSlide}`);
                    if(congrats) congrats.classList.add('show');
                    revealBtn.innerText = "PROCEED TO NEXT";
                } else {
                    if (nextRank >= 1) {
                        revealBtn.innerText = `REVEAL ${nextRank === 1 ? 'CHAMPIONS' : 'RUNNERS'}`;
                    } else {
                        // All shown or only winners exist
                        if (currentRankReveal > 1) {
                            handleReveal(); // Skip to 1st if others don't exist
                        }
                    }
                }
            } else {
                if (currentRankReveal > 1) {
                    handleReveal(); // Skip empty rank levels
                } else {
                    nextSlide();
                }
            }
        } else {
            nextSlide();
        }
    }

    function nextSlide() {
        if (currentSlide < slidesCount - 1) {
            currentSlide++;
            updateView();
        } else {
            currentSlide = 0;
            updateView();
        }
    }

    function prevSlide() {
        if (currentSlide > 0) {
            currentSlide--;
            updateView();
        }
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowRight') nextSlide();
        if (e.key === 'ArrowLeft') prevSlide();
        if (e.key === ' ') handleReveal();
    });
</script>
</body>
</html>
