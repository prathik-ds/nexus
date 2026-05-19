<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
include 'includes/header.php';

$userinfo = $_SESSION['user'];
$user_id = $userinfo['user_id'];

// Fetch user's registered events with scores
$stmt = $pdo->prepare("
    SELECT e.*, r.status as reg_status, r.score, r.team_id,
           t.name as team_name, t.invite_code as team_code, t.leader_user_id as team_leader
    FROM events e 
    JOIN registrations r ON e.id = r.event_id 
    LEFT JOIN teams t ON r.team_id = t.id
    WHERE r.user_id = ? ORDER BY e.date
");
$stmt->execute([$user_id]);
$myEvents = $stmt->fetchAll();

// Fetch public announcements — cached in session for 2 minutes to reduce DB load
// With 600 students on dashboard, this prevents 600 identical queries per 2-min window
$announcements = [];
$cache_key = 'announcements_cache';
$cache_ttl = 120; // seconds
if (!isset($_SESSION[$cache_key]) || (time() - $_SESSION[$cache_key . '_time']) > $cache_ttl) {
    $stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
    $_SESSION[$cache_key] = $stmt->fetchAll();
    $_SESSION[$cache_key . '_time'] = time();
}
$announcements = $_SESSION[$cache_key];
?>

<style>
    @media (max-width: 768px) {
        .dash-page-wrap {
            padding: 0 !important;
        }

        .dash-main-grid {
            display: flex !important;
            flex-direction: column !important;
            gap: 14px !important;
            padding: 14px !important;
        }

        .dash-sidebar-col {
            order: 2;
        }

        .dash-events-col {
            order: 1;
        }

        .dash-events-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    }
</style>

<div class="dash-page-wrap" style="padding: 40px;">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <h1>
                <span style="font-size: 1.5rem;">👋</span>
                Welcome, <?= htmlspecialchars($userinfo['name']) ?>
            </h1>
            <p>Track your registrations and access your digital entry passes.</p>
        </div>
        <div class="header-actions">
            <span class="status-tag"
                style="background: rgba(124, 58, 237, 0.08); color: var(--accent-2); padding: 8px 18px; font-size: 0.78rem; border: 1px solid rgba(124, 58, 237, 0.2); border-radius: 10px;">
                <i class="fa-solid fa-id-badge" style="margin-right: 4px;"></i>
                <?= $user_id ?>
            </span>
        </div>
    </div>

    <div class="dash-main-grid" style="display: grid; grid-template-columns: 1fr 350px; gap: 30px; margin-top: 30px;">
        <!-- Registrations Table -->
        <div class="dash-events-col glass-panel" style="padding: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 style="font-family: 'Space Grotesk', sans-serif; font-size: 1.4rem; font-weight: 700;">My
                    Registrations</h2>
                <a href="events.php" class="btn-coord" style="text-decoration: none;">
                    <i class="fa-solid fa-compass"></i> Browse Events
                </a>
            </div>

            <?php if (empty($myEvents)): ?>
                <div style="text-align: center; padding: 60px 0;">
                    <div
                        style="width: 72px; height: 72px; background: rgba(0, 212, 255, 0.06); border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                        <i class="fa-solid fa-calendar-plus" style="font-size: 1.8rem; color: var(--accent-1);"></i>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 1.05rem; margin-bottom: 8px;">No events registered
                        yet</p>
                    <p style="color: var(--text-dim); font-size: 0.82rem; margin-bottom: 24px;">Explore competitions and
                        sign up to compete!</p>
                    <a href="events.php" class="btn-neon"
                        style="padding: 12px 32px; text-decoration: none; font-size: 0.82rem;">
                        <i class="fa-solid fa-rocket"></i> Explore Events
                    </a>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <th
                                    style="padding: 16px; text-align: left; color: var(--text-dim); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1.5px; font-family: 'Space Grotesk', sans-serif;">
                                    Event</th>
                                <th
                                    style="padding: 16px; text-align: left; color: var(--text-dim); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1.5px; font-family: 'Space Grotesk', sans-serif;">
                                    Schedule</th>
                                <th
                                    style="padding: 16px; text-align: left; color: var(--text-dim); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1.5px; font-family: 'Space Grotesk', sans-serif;">
                                    Team</th>
                                <th
                                    style="padding: 16px; text-align: center; color: var(--text-dim); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1.5px; font-family: 'Space Grotesk', sans-serif;">
                                    Status</th>
                                <th
                                    style="padding: 16px; text-align: right; color: var(--text-dim); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1.5px; font-family: 'Space Grotesk', sans-serif;">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myEvents as $ev): ?>
                                <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;"
                                    onmouseover="this.style.background='rgba(0, 212, 255, 0.02)'"
                                    onmouseout="this.style.background='transparent'">
                                    <td style="padding: 16px;" data-label="Event">
                                        <div style="font-weight: 700; color: var(--text-primary); font-size: 0.95rem;">
                                            <?= htmlspecialchars($ev['name']) ?>
                                        </div>
                                        <div
                                            style="font-size: 0.72rem; color: var(--text-dim); display: flex; align-items: center; gap: 6px; margin-top: 3px;">
                                            <?php
                                            $dash_cat_color = '#00d4ff'; // IT = Water
                                            if ($ev['category'] == 'Commerce')
                                                $dash_cat_color = '#ff5722'; // Commerce = Flame
                                            if ($ev['category'] == 'ART')
                                                $dash_cat_color = '#22c55e'; // Art = Forest
                                            ?>
                                            <span
                                                style="width: 6px; height: 6px; border-radius: 50%; background: <?= $dash_cat_color ?>;"></span>
                                            <?= $ev['category'] ?>
                                        </div>
                                    </td>
                                    <td style="padding: 16px;" data-label="Schedule">
                                        <div style="font-size: 0.9rem; color: var(--text-primary);">
                                            <?= date('d M, Y', strtotime($ev['date'])) ?>
                                        </div>
                                        <div style="font-size: 0.72rem; color: var(--text-dim);">
                                            <?= date('h:i A', strtotime($ev['time'])) ?>
                                        </div>
                                    </td>
                                    <!-- Team info -->
                                    <td style="padding: 16px;" data-label="Team">
                                        <?php if (!empty($ev['team_id'])): ?>
                                            <div style="font-size:0.8rem; font-weight:700; color:#c4b5fd;">
                                                <?= htmlspecialchars($ev['team_name']) ?>
                                            </div>
                                            <div
                                                style="font-size:0.65rem; color:#5b6a8a; font-family:'JetBrains Mono',monospace; letter-spacing:2px; margin-top:2px;">
                                                <?= htmlspecialchars($ev['team_code']) ?>
                                                <?php if ($ev['team_leader'] === $user_id): ?>
                                                    &nbsp;<span style="color:#fbbf24; font-size:0.6rem;">LEADER</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color:#5b6a8a; font-size:0.75rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 16px; text-align: center;" data-label="Status">
                                        <?php
                                        $statusColor = 'var(--success)';
                                        $statusBg = 'rgba(16, 185, 129, 0.08)';
                                        $statusBorder = 'rgba(16, 185, 129, 0.2)';
                                        if ($ev['reg_status'] == 'winner') {
                                            $statusColor = 'var(--accent-5)';
                                            $statusBg = 'rgba(251, 191, 36, 0.08)';
                                            $statusBorder = 'rgba(251, 191, 36, 0.2)';
                                        } elseif ($ev['reg_status'] == 'attended') {
                                            $statusColor = 'var(--accent-1)';
                                            $statusBg = 'rgba(0, 212, 255, 0.08)';
                                            $statusBorder = 'rgba(0, 212, 255, 0.2)';
                                        }
                                        ?>
                                        <span
                                            style="padding: 5px 12px; border-radius: 8px; font-size: 0.62rem; font-weight: 800; background: <?= $statusBg ?>; color: <?= $statusColor ?>; text-transform: uppercase; letter-spacing: 1px; border: 1px solid <?= $statusBorder ?>;">
                                            <?= strtoupper($ev['reg_status']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 16px; text-align: right; display: flex; gap: 8px; justify-content: flex-end;"
                                        data-label="Actions">
                                        <button
                                            onclick="showTicket('<?= $ev['id'] ?>', '<?= htmlspecialchars(addslashes($ev['name'])) ?>')"
                                            class="btn-coord"
                                            style="padding: 6px 14px; font-size: 0.68rem; background: rgba(0, 212, 255, 0.06); color: var(--accent-1); border: 1px solid rgba(0, 212, 255, 0.2);">
                                            <i class="fa-solid fa-qrcode"></i> Ticket
                                        </button>

                                        <?php if ($ev['reg_status'] !== 'registered'): ?>
                                            <a href="certificate.php?event_id=<?= $ev['id'] ?>" target="_blank" class="btn-coord"
                                                style="padding: 6px 14px; font-size: 0.68rem; background: rgba(251, 191, 36, 0.06); color: var(--accent-5); border: 1px solid rgba(251, 191, 36, 0.2); text-decoration: none;">
                                                <i class="fa-solid fa-award"></i> Cert
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($ev['is_team_event']): ?>
                                            <button
                                                onclick="manageTeam(<?= $ev['id'] ?>, '<?= htmlspecialchars(addslashes($ev['name'])) ?>')"
                                                class="btn-coord"
                                                style="padding: 6px 14px; font-size: 0.68rem; background: rgba(124, 58, 237, 0.06); color: var(--accent-2); border: 1px solid rgba(124, 58, 237, 0.2);">
                                                <i class="fa-solid fa-users-gear"></i> Team
                                            </button>
                                        <?php endif; ?>

                                        <!-- Unregister (CANCEL) logic removed per user request -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Info -->
        <div class="dash-sidebar-col" style="display: flex; flex-direction: column; gap: 24px;">
            <!-- QR Entry Info -->
            <div class="glass-panel"
                style="padding: 28px; text-align: center; border-color: rgba(0, 212, 255, 0.12); position: relative; overflow: hidden;">
                <div
                    style="position: absolute; top: -30px; right: -30px; width: 100px; height: 100px; background: radial-gradient(circle, rgba(0, 212, 255, 0.08), transparent 70%); border-radius: 50%;">
                </div>
                <div
                    style="width: 56px; height: 56px; background: rgba(0, 212, 255, 0.06); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                    <i class="fa-solid fa-ticket" style="font-size: 1.5rem; color: var(--accent-1);"></i>
                </div>
                <h3
                    style="font-family: 'Space Grotesk', sans-serif; font-size: 1.05rem; margin-bottom: 12px; color: var(--text-primary); font-weight: 700;">
                    Event Entry System</h3>
                <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.7;">Present the QR ticket for
                    each specific event to the coordinator for attendance verification.</p>
            </div>

            <!-- Profile Card -->
            <div class="glass-panel" style="padding: 28px;">
                <h3
                    style="font-family: 'Space Grotesk', sans-serif; font-size: 1.05rem; margin-bottom: 22px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-user-circle" style="color: var(--accent-2);"></i>
                    Profile
                </h3>
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <div
                        style="padding: 12px 14px; background: rgba(0, 0, 0, 0.15); border-radius: 12px; border: 1px solid var(--border);">
                        <div
                            style="font-size: 0.65rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 4px;">
                            Course</div>
                        <div style="font-size: 0.9rem; font-weight: 600; color: var(--text-primary);">
                            <?= htmlspecialchars($userinfo['course']) ?>
                        </div>
                    </div>
                    <?php if (!empty($userinfo['year'])): ?>
                        <div
                            style="padding: 12px 14px; background: rgba(0, 0, 0, 0.15); border-radius: 12px; border: 1px solid var(--border);">
                            <div
                                style="font-size: 0.65rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 4px;">
                                Year</div>
                            <div style="font-size: 0.9rem; font-weight: 600; color: var(--text-primary);">
                                <?= htmlspecialchars($userinfo['year']) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($userinfo['roll_no'])): ?>
                        <div
                            style="padding: 12px 14px; background: rgba(0, 0, 0, 0.15); border-radius: 12px; border: 1px solid var(--border);">
                            <div
                                style="font-size: 0.65rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 4px;">
                                Roll Number</div>
                            <div style="font-size: 0.9rem; font-weight: 600; color: var(--text-primary);">
                                <?= htmlspecialchars($userinfo['roll_no']) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div
                        style="padding: 12px 14px; background: rgba(0, 0, 0, 0.15); border-radius: 12px; border: 1px solid var(--border);">
                        <div
                            style="font-size: 0.65rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 4px;">
                            Email</div>
                        <div
                            style="font-size: 0.85rem; font-weight: 600; color: var(--text-primary); word-break: break-all;">
                            <?= htmlspecialchars($userinfo['email']) ?>
                        </div>
                    </div>
                    <?php if (!empty($userinfo['phone'])): ?>
                        <div
                            style="padding: 12px 14px; background: rgba(0, 0, 0, 0.15); border-radius: 12px; border: 1px solid var(--border);">
                            <div
                                style="font-size: 0.65rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 4px;">
                                Phone</div>
                            <div style="font-size: 0.9rem; font-weight: 600; color: var(--text-primary);">
                                <?= htmlspecialchars($userinfo['phone']) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QR Modal -->
<div id="qrModal" onclick="if(event.target === this) closeModal()"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(4, 6, 14, 0.9); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(15px); cursor: pointer;">
    <div class="glass-panel"
        style="background: rgba(15, 22, 41, 0.97); padding: 40px; text-align: center; border-radius: 24px; border: 1px solid rgba(124, 58, 237, 0.2); max-width: 370px; width: 90%; position: relative; overflow: hidden; cursor: default;">
        <div
            style="position: absolute; top: -40px; left: -40px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(124, 58, 237, 0.08), transparent 70%); border-radius: 50%;">
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 id="modal-event-name"
                style="font-family: 'Space Grotesk', sans-serif; font-size: 1.15rem; color: var(--accent-2); margin: 0; font-weight: 700;">
                Event Ticket</h3>
            <button onclick="closeModal()"
                style="background: rgba(244, 63, 94, 0.12); border: 1px solid var(--danger); color: var(--danger); cursor: pointer; font-size: 1.2rem; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; position: relative; z-index: 2001;"
                aria-label="Close Ticket"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div id="qrcode"
            style="background: white; padding: 18px; display: inline-block; border-radius: 16px; margin-bottom: 20px; min-width: 200px; min-height: 200px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);">
        </div>

        <div style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.6;">
            Present this QR code to the event coordinator
        </div>
        <div
            style="margin-top: 14px; font-family: 'JetBrains Mono', monospace; font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 2px; padding: 8px 16px; background: rgba(0, 0, 0, 0.2); border-radius: 8px; display: inline-block;">
            ID: <?= $user_id ?>
        </div>
    </div>
</div>

<!-- Team Management Modal -->
<div id="teamModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(4, 6, 14, 0.85); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(10px);">
    <div class="glass-panel"
        style="background: rgba(15, 22, 41, 0.98); padding: 40px; border-radius: 24px; border: 1px solid rgba(124, 58, 237, 0.3); max-width: 450px; width: 90%; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 id="team-modal-title"
                style="font-family: 'Space Grotesk', sans-serif; color: var(--accent-2); margin: 0;">Team Management
            </h3>
            <button onclick="closeTeamModal()"
                style="background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 1.2rem;"><i
                    class="fa-solid fa-xmark"></i></button>
        </div>

        <input type="hidden" id="manage-event-id">

        <div id="team-content">
            <div style="text-align: center; padding: 20px;">
                <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 2rem; color: var(--accent-1);"></i>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    let qrObj = null;

    function showTicket(eventId, eventName) {
        document.getElementById('modal-event-name').innerText = eventName;

        const qrContainer = document.getElementById("qrcode");
        qrContainer.innerHTML = '';

        const qrData = "EVENT_QR|<?= $user_id ?>|" + eventId;

        qrObj = new QRCode(qrContainer, {
            text: qrData,
            width: 200,
            height: 200,
            colorDark: "#0f1629",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        document.getElementById('qrModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('qrModal').style.display = 'none';
        if (qrObj) {
            qrObj.clear();
        }
    }

    // --- Team Management Logic ---
    function manageTeam(eventId, eventName) {
        document.getElementById('manage-event-id').value = eventId;
        document.getElementById('team-modal-title').innerText = eventName + " Team";
        document.getElementById('teamModal').style.display = 'flex';
        refreshTeamInfo(eventId);
    }

    function closeTeamModal() {
        document.getElementById('teamModal').style.display = 'none';
    }

    function refreshTeamInfo(eventId) {
        const content = document.getElementById('team-content');
        content.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fa-solid fa-circle-notch fa-spin"></i></div>';

        const formData = new FormData();
        formData.append('action', 'get_team');
        formData.append('event_id', eventId);

        fetch('team_actions.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    renderTeamManage(data.team);
                } else {
                    renderTeamEntry();
                }
            });
    }

    function renderTeamEntry() {
        const content = document.getElementById('team-content');
        content.innerHTML = `
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <div style="padding: 15px; background: rgba(0, 212, 255, 0.05); border-radius: 12px; border: 1px dashed var(--border);">
                    <h4 style="font-size: 0.85rem; margin-bottom: 12px; color: var(--accent-1);">CREATE A NEW TEAM</h4>
                    <input type="text" id="new-team-name" placeholder="Team Name" style="width: 100%; padding: 10px; background: rgba(0,0,0,0.2); border: 1px solid var(--border); border-radius: 8px; color: white; margin-bottom: 10px;">
                    <button onclick="createTeam()" class="btn-neon" style="width: 100%; font-size: 0.75rem;">Create Team</button>
                </div>
                
                <div style="text-align: center; color: var(--text-dim); font-size: 0.7rem;">— OR —</div>
                
                <div style="padding: 15px; background: rgba(124, 58, 237, 0.05); border-radius: 12px; border: 1px dashed var(--border);">
                    <h4 style="font-size: 0.85rem; margin-bottom: 12px; color: var(--accent-2);">JOIN EXISTING TEAM</h4>
                    <input type="text" id="join-invite-code" placeholder="Invite Code (e.g. XJ2K9L)" style="width: 100%; padding: 10px; background: rgba(0,0,0,0.2); border: 1px solid var(--border); border-radius: 8px; color: white; text-transform: uppercase; margin-bottom: 10px;">
                    <button onclick="joinTeam()" class="btn-neon" style="width: 100%; font-size: 0.75rem; border-color: var(--accent-2); color: var(--accent-2);">Join Team</button>
                </div>
            </div>
        `;
    }

    function renderTeamManage(team) {
        const currentUserId = "<?= $user_id ?>";
        const isLeader = team.leader_user_id === currentUserId;
        const content = document.getElementById('team-content');

        let membersHtml = '';
        team.members.forEach(m => {
            membersHtml += `
                <div style="display: flex; justify-content: space-between; padding: 10px; background: rgba(255,255,255,0.03); border-radius: 8px; margin-bottom: 6px; font-size: 0.85rem;">
                    <span>${m.user_name} ${m.user_id === team.leader_user_id ? '<i class="fa-solid fa-crown" style="color:#fbbf24; font-size:0.7rem; margin-left:5px;"></i>' : ''}</span>
                    <span style="color: var(--text-dim); font-size: 0.72rem;">${m.user_id}</span>
                </div>
            `;
        });

        content.innerHTML = `
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="font-size: 1.4rem; font-weight: 800; color: white;">${team.name}</div>
                <div style="margin-top: 10px;">
                    <span style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px;">Invite Code</span>
                    <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.5rem; color: var(--accent-1); letter-spacing: 4px; margin-top: 5px; background: rgba(0,212,255,0.05); padding: 10px; border-radius: 12px; border: 1px solid rgba(0,212,255,0.2);">${team.invite_code}</div>
                </div>
            </div>

            <div style="margin-bottom: 25px;">
                <h4 style="font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">TEAM MEMBERS (${team.members.length})</h4>
                ${membersHtml}
            </div>

            <div style="text-align: center; color: var(--text-dim); font-size: 0.7rem; padding: 10px; border: 1px dashed var(--border); border-radius: 12px;">
                <i class="fa-solid fa-lock" style="margin-right: 5px;"></i> Team structure is locked after registration.
            </div>
        `;
    }

    function createTeam() {
        const name = document.getElementById('new-team-name').value;
        const eventId = document.getElementById('manage-event-id').value;
        if (!name) return alert("Please enter team name");

        const formData = new FormData();
        formData.append('action', 'create_team');
        formData.append('event_id', eventId);
        formData.append('team_name', name);

        fetch('team_actions.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert("Team Created Successfully!");
                    location.reload();
                } else alert(data.message);
            });
    }

    function joinTeam() {
        const code = document.getElementById('join-invite-code').value;
        if (!code) return alert("Please enter invite code");

        const formData = new FormData();
        formData.append('action', 'join_team');
        formData.append('invite_code', code);

        fetch('team_actions.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert("Joined Team!");
                    location.reload();
                } else alert(data.message);
            });
    }

    // Unregistration logic removed.
</script>

<?php include 'includes/footer.php'; ?>