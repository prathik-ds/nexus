<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
include 'includes/header.php';

// Handle Score Updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_score') {
        $reg_id = $_POST['reg_id'];
        $score = 0; // Forced score removal
        $status = $_POST['status'];

        $stmt = $pdo->prepare("SELECT event_id, team_id FROM registrations WHERE id = ?");
        $stmt->execute([$reg_id]);
        $r_info = $stmt->fetch();

        if ($r_info && $r_info['team_id']) {
            $t_stmt = $pdo->prepare("SELECT name FROM teams WHERE id = ?");
            $t_stmt->execute([$r_info['team_id']]);
            $team_name = $t_stmt->fetchColumn();

            if ($team_name) {
                $pdo->prepare("
                    UPDATE registrations r 
                    JOIN teams t ON r.team_id = t.id 
                    SET r.score = ?, r.status = ? 
                    WHERE t.name = ? AND r.event_id = ?
                ")->execute([$score, $status, $team_name, $r_info['event_id']]);
            } else {
                $pdo->prepare("UPDATE registrations SET score = ?, status = ? WHERE team_id = ? AND event_id = ?")->execute([$score, $status, $r_info['team_id'], $r_info['event_id']]);
            }
        } else {
            $pdo->prepare("UPDATE registrations SET score = ?, status = ? WHERE id = ?")->execute([$score, $status, $reg_id]);
        }
        $msg = "RANKING UPDATED FOR PARTICIPANT.";
    }
}

// Fetch Leaderboard Data
$stmt = $pdo->query("SELECT r.*, u.name as user_name, e.name as event_name, e.is_team_event, t.name as team_name, t.id as t_id FROM registrations r JOIN users u ON r.user_id = u.user_id JOIN events e ON r.event_id = e.id LEFT JOIN teams t ON r.team_id = t.id WHERE r.score > 0 OR r.status != 'registered' ORDER BY e.name, r.score DESC");
$allLeaderboard = $stmt->fetchAll();

// Group by event for easier management
$eventsLeaderboard = [];
foreach ($allLeaderboard as $row) {
    if (!isset($eventsLeaderboard[$row['event_name']])) {
        $eventsLeaderboard[$row['event_name']] = [];
    }
    
    if ($row['is_team_event']) {
        $t_key = $row['team_name'] ?: ($row['t_id'] ?: 'ind_'. $row['id']);
        if (!isset($eventsLeaderboard[$row['event_name']][$t_key])) {
            $eventsLeaderboard[$row['event_name']][$t_key] = $row;
            $eventsLeaderboard[$row['event_name']][$t_key]['members'] = [];
        }
        // This query for members is slow but okay for admin panel
        $mst = $pdo->prepare("SELECT u.name FROM users u JOIN registrations r2 ON u.user_id = r2.user_id LEFT JOIN teams t ON r2.team_id = t.id WHERE (t.name = ? OR r2.team_id = ?) AND r2.event_id = ?");
        $mst->execute([$row['team_name'], $row['t_id'], $row['event_id']]);
        $eventsLeaderboard[$row['event_name']][$t_key]['members'] = $mst->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $eventsLeaderboard[$row['event_name']][] = $row;
    }
}
?>

<div style="padding: 40px;">
    <div class="dashboard-header" style="margin-bottom: 40px;">
        <div class="header-content">
            <div
                style="display: inline-flex; align-items: center; gap: 8px; background: rgba(124, 58, 237, 0.08); border: 1px solid rgba(124, 58, 237, 0.2); padding: 5px 12px; border-radius: 50px; margin-bottom: 12px;">
                <i class="fa-solid fa-crown" style="font-size: 0.7rem; color: var(--accent-2);"></i>
                <span
                    style="font-size: 0.65rem; font-weight: 800; color: var(--accent-2); text-transform: uppercase; letter-spacing: 1.5px;">Scoring
                    Engine</span>
            </div>
            <h1 style="font-family: 'Space Grotesk', sans-serif; font-size: 2.2rem; font-weight: 700; margin: 0;">
                Leaderboard Management</h1>
            <p style="color: var(--text-dim); margin-top: 8px;">Audit performance, validate scores, and declare
                tournament winners.</p>
        </div>
        <div class="header-actions">
            <a href="leaderboard.php" target="_blank" class="btn-coord"
                style="text-decoration: none; background: rgba(0, 212, 255, 0.06); color: var(--accent-1); border-color: var(--accent-1);">
                <i class="fa-solid fa-eye"></i> View Public Rankings
            </a>
        </div>
    </div>

    <?php if (isset($msg)): ?>
        <div
            style="margin-bottom: 30px; padding: 16px 20px; background: rgba(16, 185, 129, 0.06); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); border-radius: 12px; font-size: 0.88rem; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-circle-check"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <?php if (empty($eventsLeaderboard)): ?>
        <div class="glass-panel" style="padding: 80px; text-align: center;">
            <div
                style="width: 72px; height: 72px; background: rgba(124, 58, 237, 0.06); border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                <i class="fa-solid fa-ranking-star" style="font-size: 1.8rem; color: var(--accent-2);"></i>
            </div>
            <p style="color: var(--text-secondary); font-size: 1.1rem; margin-bottom: 8px;">No participants scored yet</p>
            <p style="color: var(--text-dim); font-size: 0.82rem;">Use the Registration Desk or Coordinator portal to post
                scores.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 40px;">
            <?php foreach ($eventsLeaderboard as $eventName => $participants): ?>
                <div class="glass-panel" style="padding: 0; overflow: hidden; border-color: rgba(0, 212, 255, 0.1);">
                    <div
                        style="padding: 24px 30px; background: rgba(0, 212, 255, 0.02); border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                        <h2
                            style="font-family: 'Space Grotesk', sans-serif; font-size: 1.25rem; margin: 0; color: var(--accent-1); font-weight: 700;">
                            <i class="fa-solid fa-trophy" style="margin-right: 10px; font-size: 1rem;"></i>
                            <?= htmlspecialchars($eventName) ?>
                        </h2>
                        <span
                            style="font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px;">
                            <?= count($participants) ?> Participants Evaluated
                        </span>
                    </div>

                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border);">
                                    <th
                                        style="padding: 16px 30px; text-align: left; color: var(--text-dim); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1.5px;">
                                        Student</th>
                                    <th
                                        style="padding: 16px 30px; text-align: center; color: var(--text-dim); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1.5px;">
                                        Status</th>
                                    <th
                                        style="padding: 16px 30px; text-align: right; color: var(--text-dim); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1.5px;">
                                        Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participants as $p): ?>
                                    <tr style="border-bottom: 1px solid var(--border); transition: 0.2s;"
                                        onmouseover="this.style.background='rgba(255,255,255,0.01)'"
                                        onmouseout="this.style.background='transparent'">
                                        <td style="padding: 18px 30px;">
                                            <?php if ($p['is_team_event'] && $p['team_name']): ?>
                                                <div style="font-weight: 700; color: var(--accent-1); font-size: 1rem;">
                                                    <i class="fa-solid fa-users"></i> <?= htmlspecialchars($p['team_name']) ?>
                                                </div>
                                                <div style="font-size: 0.72rem; color: var(--text-dim); margin-top: 4px;">
                                                    Members: <?= htmlspecialchars(implode(', ', $p['members'] ?? [])) ?>
                                                </div>
                                            <?php else: ?>
                                                <div style="font-weight: 700; color: var(--text-primary);">
                                                    <?= htmlspecialchars($p['user_name']) ?>
                                                </div>
                                                <div style="font-size: 0.72rem; color: var(--text-dim);"><?= $p['user_id'] ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 18px 30px; text-align: center;">
                                            <span
                                                style="padding: 4px 12px; border-radius: 6px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: <?= $p['status'] == 'winner' ? 'rgba(251, 191, 36, 0.1)' : 'rgba(124, 58, 237, 0.1)' ?>; color: <?= $p['status'] == 'winner' ? 'var(--accent-5)' : 'var(--accent-2)' ?>;">
                                                <?= $p['status'] ?>
                                            </span>
                                        </td>
                                        <td style="padding: 18px 30px; text-align: right;">
                                            <button onclick='openScoreModal(<?= json_encode($p) ?>)' class="btn-coord"
                                                style="padding: 6px 16px; font-size: 0.7rem;">
                                                <i class="fa-solid fa-pen-to-square"></i> EDIT
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Score Edit Modal -->
<div id="scoreModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(4, 6, 14, 0.85); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
    <div class="glass-panel"
        style="background: rgba(15, 22, 41, 0.98); padding: 36px; border-radius: 20px; border: 1px solid rgba(0, 212, 255, 0.2); max-width: 400px; width: 90%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3
                style="font-family: 'Space Grotesk', sans-serif; font-size: 1.2rem; color: var(--accent-1); margin: 0; font-weight: 700;">
                Update Performance</h3>
            <button onclick="closeScoreModal()"
                style="background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 1.2rem;"><i
                    class="fa-solid fa-xmark"></i></button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="update_score">
            <input type="hidden" name="reg_id" id="modal-reg-id">

            <div style="margin-bottom: 20px;">
                <div id="modal-user-name" style="font-weight: 700; color: var(--text-primary); font-size: 1rem;">Student
                    Name</div>
                <div id="modal-event-name" style="font-size: 0.8rem; color: var(--accent-2); margin-top: 4px;">Event
                    Title</div>
            </div>


            <div class="form-group" style="margin-bottom: 28px;">
                <label
                    style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: block;">Achievement
                    Status</label>
                <select name="status" id="modal-status"
                    style="width: 100%; background: rgba(0,0,0,0.2); border: 1px solid var(--border); padding: 12px; color: white; border-radius: 10px; font-size: 0.88rem;">
                    <option value="registered">Registered (Pending)</option>
                    <option value="attended">Attended / Participated</option>
                    <option value="winner">🏆 Winner / First Place</option>
                    <option value="runner">🥈 Runner Up</option>
                    <option value="third">🥉 Third Place</option>
                </select>
            </div>

            <button type="submit" class="btn-neon"
                style="width: 100%; padding: 14px; background: var(--grad-primary); border: none; color: white;">
                <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<script>
    function openScoreModal(data) {
        document.getElementById('modal-reg-id').value = data.id;
        document.getElementById('modal-user-name').innerText = data.user_name;
        document.getElementById('modal-event-name').innerText = data.event_name;
        document.getElementById('modal-score').value = data.score;
        document.getElementById('modal-status').value = data.status;
        document.getElementById('scoreModal').style.display = 'flex';
    }

    function closeScoreModal() {
        document.getElementById('scoreModal').style.display = 'none';
    }

    // Close modal on outside click
    window.onclick = function (event) {
        let modal = document.getElementById('scoreModal');
        if (event.target == modal) {
            closeScoreModal();
        }
    }
</script>

<?php include 'includes/footer.php'; ?>