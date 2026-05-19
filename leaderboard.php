<?php 
include 'includes/header.php'; 

// Fetch Top Performers across all events - group by team if team event
$stmt = $pdo->query("SELECT 
                        GROUP_CONCAT(u.name SEPARATOR ' • ') as member_names, 
                        e.name as event_name, 
                        r.status, 
                        t.name as team_name,
                        e.id as e_id
                     FROM registrations r 
                     JOIN users u ON r.user_id = u.user_id 
                     JOIN events e ON r.event_id = e.id 
                     LEFT JOIN teams t ON r.team_id = t.id 
                     WHERE r.status IN ('winner', 'runner', 'third') 
                     GROUP BY e.id, CASE WHEN t.name IS NOT NULL THEN t.name ELSE r.user_id END
                     ORDER BY e.name ASC, 
                              CASE WHEN LOWER(r.status) = 'winner' THEN 1 
                                   WHEN LOWER(r.status) = 'runner' THEN 2 
                                   WHEN LOWER(r.status) = 'third' THEN 3 
                                   ELSE 4 END ASC, 
                              t.name ASC
                     LIMIT 50");
$topScores = $stmt->fetchAll();
?>

<!-- Page Header -->
<div style="margin-bottom: 50px; text-align: center; padding-top: 20px;">
    <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(124, 58, 237, 0.06); border: 1px solid rgba(124, 58, 237, 0.15); padding: 6px 16px; border-radius: 50px; margin-bottom: 16px;">
        <i class="fa-solid fa-crown" style="font-size: 0.7rem; color: var(--accent-5);"></i>
        <span style="font-size: 0.7rem; font-weight: 700; color: var(--accent-2); text-transform: uppercase; letter-spacing: 2px;">Rankings</span>
    </div>
    <h1 style="font-family: 'Space Grotesk', sans-serif; font-size: 3rem; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 10px;">
        <span style="background: var(--grad-warm); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">Hall of Fame</span>
    </h1>
    <p style="color: var(--text-secondary); max-width: 400px; margin: 0 auto; font-size: 0.9rem;">Celebrating the top performers of Nexus Fest 2026</p>
</div>

<style>
    .hof-table td {
        padding: 18px 24px;
        vertical-align: middle;
    }
    .hof-table th {
        padding: 18px 24px;
    }
    @media (max-width: 768px) {
        /* Container adjustments */
        .glass-hof {
            padding: 0 12px !important;
            background: transparent !important; 
            border: none !important;
            box-shadow: none !important;
        }
        
        /* Hide table headers natively on mobile */
        .hof-table thead {
            display: none !important;
        }

        /* Transform table row into a 3-column Grid card */
        .hof-table tr {
            display: grid !important;
            grid-template-columns: 60px 1fr auto !important;
            grid-template-areas: 
                "medal participant badge"
                "medal event event";
            align-items: center;
            gap: 4px 15px !important;
            padding: 20px 18px !important;
            margin-bottom: 16px !important;
            background: rgba(15, 22, 41, 0.45) !important;
            backdrop-filter: blur(10px);
            border-radius: 20px !important;
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3) !important;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .hof-table tr:active {
            transform: scale(0.98);
        }

        /* Subtle Glow Overlay */
        .hof-table tr::after {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, transparent 50%);
            pointer-events: none;
        }

        /* Stand-out Border with Rank Color */
        .hof-table tr::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 4px; height: 100%;
            background: var(--grad-primary);
            box-shadow: 4px 0 15px rgba(124, 58, 237, 0.2);
        }

        /* Clean up standard table-card td overrides */
        .hof-table td {
            display: block !important;
            padding: 0 !important;
            min-height: auto !important;
            text-align: left !important;
            border: none !important;
        }
        .hof-table td:before {
            display: none !important; /* Hide data labels */
        }

        /* Position elements to Grid areas */
        .hof-table td[data-label="Standing"] {
            grid-area: medal;
            display: flex !important;
            align-items: center;
            justify-content: center;
        }
        .hof-table td[data-label="Standing"] span {
            font-size: 2.2rem !important; /* Make Medals Huge */
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.4));
        }

        .hof-table td[data-label="Participant"] {
            grid-area: participant;
        }
        .hof-table .participant-cell {
            gap: 12px !important;
        }
        .hof-table .participant-cell > div:last-child > div {
            font-size: 1rem !important; /* Name text size */
        }

        .hof-table td[data-label="Event"] {
            grid-area: event;
            /* Indent text to match name text (avatar 34px + gap 12px) */
            padding-left: 46px !important; 
        }
        .hof-table td[data-label="Event"] > div > div {
            font-size: 0.78rem !important;
            color: var(--text-muted) !important;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .hof-table td[data-label="Status"] {
            grid-area: badge;
            justify-self: end !important;
            align-self: start !important;
        }
    }
    @media (max-width: 480px) {
        .hof-table tr {
            padding: 14px 12px !important;
            gap: 2px 10px !important;
        }
        .hof-table td[data-label="Event"] {
            padding-left: 44px !important; 
        }
        .hof-table td[data-label="Standing"] span {
            font-size: 1.8rem !important;
        }
    }
</style>

<!-- Leaderboard Card -->
<div class="glass glass-hof" style="max-width: 1000px; margin: 0 auto; padding: 0; overflow: hidden; border-color: rgba(124, 58, 237, 0.12);">
    <?php if(empty($topScores)): ?>
        <div style="text-align: center; padding: 80px 20px;">
            <div style="width: 72px; height: 72px; background: rgba(124, 58, 237, 0.08); border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                <i class="fa-solid fa-hourglass-half" style="font-size: 1.8rem; color: var(--accent-2);"></i>
            </div>
            <p style="color: var(--text-secondary); font-size: 1.1rem; margin-bottom: 8px;">Leaderboard is warming up...</p>
            <p style="color: var(--text-dim); font-size: 0.82rem;">Check back once the competitions begin!</p>
        </div>
    <?php else: ?>
        <table class="hof-table" style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border);">
                    <th style="font-family: 'Space Grotesk', sans-serif; font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;">Standing</th>
                    <th style="font-family: 'Space Grotesk', sans-serif; font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;">Participant</th>
                    <th style="font-family: 'Space Grotesk', sans-serif; font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;">Event</th>
                    <th style="font-family: 'Space Grotesk', sans-serif; font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700; text-align: right;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($topScores as $row): ?>
                    <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;" onmouseover="this.style.background='rgba(0, 212, 255, 0.03)'" onmouseout="this.style.background='transparent'">
                        <td data-label="Standing">
                            <?php if (strtolower($row['status']) == 'winner'): ?>
                                <span style="font-size: 1.2rem;" title="1st Place">🥇</span>
                            <?php elseif (strtolower($row['status']) == 'runner'): ?>
                                <span style="font-size: 1.2rem;" title="2nd Place">🥈</span>
                            <?php else: ?>
                                <span style="font-size: 1.2rem;" title="Participant">🏅</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Participant">
                            <div class="participant-cell" style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 34px; height: 34px; border-radius: 10px; background: <?= strtolower($row['status']) == 'winner' ? 'var(--grad-primary)' : 'rgba(100, 130, 200, 0.1)' ?>; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; color: white; flex-shrink: 0;">
                                    <?= strtoupper(substr($row['team_name'] ?: $row['member_names'], 0, 1)) ?>
                                </div>
                                <div>
                                    <?php if($row['team_name']): ?>
                                        <div style="font-weight: 800; color: var(--accent-2); font-size: 0.95rem; margin-bottom: 2px;"><i class="fa-solid fa-users" style="font-size: 0.8rem;"></i> <?= htmlspecialchars($row['team_name']) ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-secondary); opacity: 0.8;">Members: <?= htmlspecialchars($row['member_names']) ?></div>
                                    <?php else: ?>
                                        <div style="font-weight: 700; color: var(--text-primary); font-size: 1rem;"><?= htmlspecialchars($row['member_names']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td data-label="Event">
                            <div>
                                <div style="font-size: 0.85rem; color: var(--text-secondary);"><?= htmlspecialchars($row['event_name']) ?></div>
                            </div>
                        </td>
                        <td style="text-align: right;" data-label="Status">
                            <span class="status-badge" style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.65rem; padding: 5px 12px; border-radius: 6px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; background: <?= strtolower($row['status']) == 'winner' ? 'rgba(251, 191, 36, 0.1)' : 'rgba(124, 58, 237, 0.1)' ?>; color: <?= strtolower($row['status']) == 'winner' ? 'var(--accent-5)' : 'var(--accent-2)' ?>; border: 1px solid <?= strtolower($row['status']) == 'winner' ? 'rgba(251, 191, 36, 0.2)' : 'rgba(124, 58, 237, 0.2)' ?>;">
                                <i class="<?= strtolower($row['status']) == 'winner' ? 'fa-solid fa-crown' : 'fa-solid fa-star' ?>"></i>
                                <?= strtoupper($row['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
