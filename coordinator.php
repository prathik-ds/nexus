<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] !== 'coordinator' && $_SESSION['user']['role'] !== 'admin')) {
    header('Location: index.html');
    exit;
}
include 'includes/header.php';


$c_id = $_SESSION['user']['user_id'];
$msg = $_GET['msg'] ?? '';

// Fetch assigned events (Admins see all events for management)
if ($_SESSION['user']['role'] === 'admin') {
    $stmt = $pdo->prepare("SELECT * FROM events ORDER BY date, time");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE FIND_IN_SET(?, coordinator_id) ORDER BY date, time");
    $stmt->execute([$c_id]);
}
$assignedEvents = $stmt->fetchAll();

// Handle active event selection
$active_event_id = $_GET['manage_event'] ?? null;
$active_event = null;
$participants = [];

if ($active_event_id) {
    // Verify ownership (Skip check if Admin)
    if ($_SESSION['user']['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$active_event_id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND FIND_IN_SET(?, coordinator_id)");
        $stmt->execute([$active_event_id, $c_id]);
    }
    $active_event = $stmt->fetch();

    if ($active_event) {
        $stmt = $pdo->prepare("SELECT u.name, u.email, u.phone, u.course, u.year, u.roll_no, r.user_id, r.id as reg_id, r.score, r.status, r.attendance, r.team_id, t.name as team_name FROM users u JOIN registrations r ON u.user_id = r.user_id LEFT JOIN teams t ON r.team_id = t.id WHERE r.event_id = ? ORDER BY t.name, u.name");
        $stmt->execute([$active_event_id]);
        $participants = $stmt->fetchAll();
    }
}
?>

<style>
    @media (max-width: 768px) {
        .dashboard-header {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 20px;
            padding: 20px !important;
        }

        .search-box {
            width: 100% !important;
        }

        .events-grid-dash {
            grid-template-columns: 1fr !important;
            padding: 0 16px !important;
        }

        .coord-manage-header {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 20px;
        }

        .coord-header-actions {
            width: 100%;
            flex-direction: column !important;
        }

        .coord-header-actions button,
        .coord-header-actions a {
            width: 100%;
            text-align: center;
        }

        .coord-manage-title {
            font-size: 1.5rem !important;
        }

        /* Table to Card Stack */
        .coord-table thead {
            display: none;
        }

        .coord-tr {
            display: block !important;
            padding: 16px !important;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            margin-bottom: 12px;
            border: 1px solid var(--border);
        }

        .coord-tr form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .coord-td-info,
        .coord-td-controls,
        .coord-td-status,
        .coord-td-action {
            display: block !important;
            padding: 0 !important;
            width: 100% !important;
            text-align: left !important;
            border: none !important;
        }
        
        .coord-td-controls select {
            font-size: 0.85rem !important;
        }
    }
</style>

<?php if (empty($active_event_id)): ?>
    <div style="padding: 20px;">
<?php else: ?>
    <div style="padding: 15px;">
<?php endif; ?>
    <div class="dashboard-header">
        <div class="header-content">
            <h1>
                <span class="brand-icon"
                    style="background: rgba(99, 102, 241, 0.2); border: 1px solid var(--primary); color: var(--primary); width: 36px; height: 36px; font-size: 0.9rem;">
                    <i class="fa-solid fa-calendar-days"></i>
                </span>
                My Assigned Events
            </h1>
            <p>Manage check-ins, status updates, and results for your events.</p>
        </div>
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Search your events..." id="eventSearch">
        </div>
    </div>
</div>

<?php if ($msg): ?>
    <div
        style="margin: 0 40px 20px; padding: 15px; background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: var(--success); border-radius: 12px; font-size: 0.9rem;">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<?php if (!$active_event_id): ?>
    <!-- Grid View of Events -->
    <div class="events-grid-dash">
        <?php foreach ($assignedEvents as $ev): ?>
            <?php
            // Get participant count for this event
            $pst = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ?");
            $pst->execute([$ev['id']]);
            $pCount = $pst->fetchColumn();
            ?>
            <div class="event-card-dash">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <span class="status-tag"
                        style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 4px 10px; border-radius: 6px; font-size: 0.6rem; font-weight: 800; text-transform: uppercase;">
                        ASSIGNED
                    </span>
                    <span style="font-size: 0.75rem; color: var(--text-dim);">
                        <i class="fa-regular fa-clock" style="margin-right: 5px;"></i>
                        <?= date('h:i A', strtotime($ev['time'])) ?>
                    </span>
                </div>

                <h3 style="font-family: 'Outfit'; font-size: 1.3rem; margin-bottom: 10px;"><?= htmlspecialchars($ev['name']) ?>
                </h3>

                <div
                    style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-location-dot" style="color: var(--primary);"></i>
                    <?= $ev['venue'] ?: 'Venue TBD' ?>
                </div>

                <div style="display: flex; gap: 12px; margin-bottom: 25px;">
                    <div class="stat-box-dash">
                        <span style="font-size: 1.1rem; font-weight: 800; color: var(--primary);"><?= $pCount ?></span>
                        <span
                            style="font-size: 0.6rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px;">Registered</span>
                    </div>
                    <div class="stat-box-dash">
                        <span
                            style="font-size: 1.1rem; font-weight: 800; color: var(--secondary);"><?= $ev['category'] ?></span>
                        <span
                            style="font-size: 0.6rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px;">Track</span>
                    </div>
                </div>

                <a href="coordinator.php?manage_event=<?= $ev['id'] ?>" class="btn-start-dash"
                    style="text-decoration: none; display: block; text-align: center; margin-bottom: 10px;">
                    MANAGE EVENT
                </a>
                <a href="coordinator.php?manage_event=<?= $ev['id'] ?>&view=results"
                    style="text-decoration: none; display: block; text-align: center; color: var(--text-muted); font-size: 0.75rem; font-weight: 600; padding: 10px; border-radius: 10px; border: 1px solid var(--border); transition: 0.3s;"
                    onmouseover="this.style.borderColor='var(--secondary)'; this.style.color='var(--text-main)'"
                    onmouseout="this.style.borderColor='var(--border)'; this.style.color='var(--text-muted)'">
                    <i class="fa-solid fa-trophy" style="margin-right: 6px;"></i> LEADERBOARD / RESULTS
                </a>
            </div>
        <?php endforeach; ?>

        <?php if (empty($assignedEvents)): ?>
            <div class="glass-panel-dash" style="grid-column: 1/-1; text-align: center; padding: 80px; border-style: dashed;">
                <i class="fa-solid fa-calendar-xmark"
                    style="font-size: 3rem; color: var(--text-dim); opacity: 0.3; margin-bottom: 20px;"></i>
                <h3 style="color: var(--text-muted); font-family: 'Outfit';">No Assignments Yet</h3>
                <p style="color: var(--text-dim); font-size: 0.9rem;">Check back later or contact Admin for track duties.</p>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <!-- Management View for Selection Event -->
    <div class="manage-view-container" style="padding: 0 20px 40px;">
        <div class="glass-panel coord-manage-panel" style="padding: 30px; margin-bottom: 30px;">
            <div class="coord-manage-header"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div>
                    <a href="coordinator.php"
                        style="color: var(--primary); text-decoration: none; font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <i class="fa-solid fa-arrow-left"></i> BACK TO EVENTS
                    </a>
                    <h2 class="coord-manage-title" style="font-size: 2rem; font-family: 'Outfit', sans-serif;">
                        <?= htmlspecialchars($active_event['name']) ?>
                    </h2>
                    <p style="color: var(--text-dim)">Manage participant attendance and track event status.</p>
                </div>
                <div class="coord-header-actions" style="display: flex; gap: 12px;">
                    <button onclick="startScanner()" class="btn-coord" style="padding: 10px 20px;">
                        <i class="fa-solid fa-camera"></i> SCAN ATTENDANCE
                    </button>
                    <a href="export_data.php?type=participation&event_id=<?= $active_event_id ?>" class="btn-coord"
                        style="padding: 10px 20px; background: rgba(0, 212, 255, 0.08); border-color: rgba(0, 212, 255, 0.2); color: var(--accent-1);">
                        <i class="fa-solid fa-file-csv"></i> EXPORT CSV
                    </a>
                </div>
            </div>

            <?php $view = $_GET['view'] ?? 'manage'; ?>
            <!-- Tab Navigation (High-Performance Segmented Bar) -->
            <div class="coord-tabs-bar" style="display: flex; gap: 8px; margin-bottom: 25px;">
                <a href="coordinator.php?manage_event=<?= $active_event_id ?>&view=manage" 
                   class="coord-tab <?= $view !== 'results' ? 'active' : '' ?>"
                   style="flex: 1; height: 44px; display: flex; align-items: center; justify-content: center; gap: 8px; border-radius: 10px; text-decoration: none; font-size: 0.75rem; font-weight: 800; border: 1px solid <?= $view !== 'results' ? 'var(--accent-1)' : 'rgba(255,255,255,0.05)' ?>; background: <?= $view !== 'results' ? 'rgba(0, 212, 255, 0.05)' : 'transparent' ?>; color: <?= $view !== 'results' ? 'var(--accent-1)' : 'var(--text-dim)' ?>;">
                    <i class="fa-solid fa-users-viewfinder"></i> ATTENDANCE
                </a>
                <a href="coordinator.php?manage_event=<?= $active_event_id ?>&view=results" 
                   class="coord-tab <?= $view === 'results' ? 'active' : '' ?>"
                   style="flex: 1; height: 44px; display: flex; align-items: center; justify-content: center; gap: 8px; border-radius: 10px; text-decoration: none; font-size: 0.75rem; font-weight: 800; border: 1px solid <?= $view === 'results' ? 'var(--accent-3)' : 'rgba(255,255,255,0.05)' ?>; background: <?= $view === 'results' ? 'rgba(16, 185, 129, 0.05)' : 'transparent' ?>; color: <?= $view === 'results' ? 'var(--accent-3)' : 'var(--text-dim)' ?>;">
                    <i class="fa-solid fa-trophy"></i> EVENT RESULTS
                </a>
            </div>

            <!-- QR Scanner Fullscreen Modal -->
            <div id="scanner-container" 
                style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 99999; background: rgba(0, 0, 0, 0.95); backdrop-filter: blur(10px); padding: 20px; flex-direction: column; justify-content: center; align-items: center;">
                
                <div style="width: 100%; max-width: 500px; padding: 20px; text-align: center;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                        <div style="text-align: left;">
                            <h3 style="font-size: 1.4rem; color: var(--primary); font-family: 'Outfit'; margin: 0;">Vortex Scanner</h3>
                            <p style="font-size: 0.75rem; color: var(--text-dim); margin-top: 4px;">Point camera at Event QR</p>
                        </div>
                        <button onclick="stopScanner()"
                            style="background: rgba(244, 63, 94, 0.1); border: 1px solid var(--danger); color: var(--danger); cursor: pointer; padding: 12px 18px; border-radius: 12px; font-weight: 800; font-size: 0.75rem; display: flex; align-items: center; gap: 8px; transition: 0.2s;">
                            <i class="fa-solid fa-xmark"></i> EXIT
                        </button>
                    </div>

                    <div style="position: relative; width: 100%; aspect-ratio: 1/1; max-width: 400px; margin: 0 auto; border-radius: 30px; overflow: hidden; border: 2px solid var(--primary); box-shadow: 0 0 30px rgba(99, 102, 241, 0.3);">
                        <div id="reader" style="width: 100% !important; border: none !important;"></div>
                        <!-- Scanner Overlay Box -->
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 70%; height: 70%; border: 2px dashed rgba(255,255,255,0.4); border-radius: 20px; pointer-events: none;"></div>
                    </div>

                    <div id="scanner-msg" style="margin-top: 30px; padding: 16px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 16px; color: var(--text-main); font-size: 0.9rem; font-weight: 600;">
                        Initializing Optical Sensor...
                    </div>
                </div>
            </div>

            <!-- Participant Table -->
            <div style="overflow-x: auto;">
                <table class="coord-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <th
                                style="padding: 16px; text-align: left; color: var(--text-dim); font-size: 0.75rem; text-transform: uppercase;">
                                Participant</th>
                            <th
                                style="padding: 16px; text-align: center; color: var(--text-dim); font-size: 0.75rem; text-transform: uppercase;">
                                Attendance</th>
                            <th
                                style="padding: 16px; text-align: center; color: var(--text-dim); font-size: 0.75rem; text-transform: uppercase;">
                                Status</th>
                            <th
                                style="padding: 16px; text-align: right; color: var(--text-dim); font-size: 0.75rem; text-transform: uppercase;">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $display_participants = $participants;
                        if ($active_event && $active_event['is_team_event'] && $view === 'results') {
                            $grouped = [];
                            foreach ($participants as $p) {
                                // For results, only consider present members as part of the team
                                if ($p['attendance'] !== 'present') continue;

                                $t_key = $p['team_name'] ?: ($p['team_id'] ?: 'indiv_' . $p['reg_id']);
                                if ($p['team_name'] || $p['team_id']) {
                                    if (!isset($grouped[$t_key])) {
                                        $grouped[$t_key] = $p;
                                        $grouped[$t_key]['member_names'] = [];
                                    }
                                    $grouped[$t_key]['member_names'][] = $p['name'];
                                } else {
                                    $p['member_names'] = [$p['name']];
                                    $grouped['indiv_' . $p['reg_id']] = $p;
                                }
                            }
                            $display_participants = array_values($grouped);
                        }

                        foreach ($display_participants as $p): 
                            // If we are in results view, only show winners/runners or those marked as participated
                            if ($view === 'results' && !in_array($p['status'], ['winner', 'runner', 'participated', 'third'])) {
                                continue;
                            }
                        ?>
                            <tr class="coord-tr" style="border-bottom: 1px solid var(--border);">
                                <form class="coord-form" action="coordinator_actions.php" method="POST">
                                    <input type="hidden" name="reg_id" value="<?= $p['reg_id'] ?>">
                                    <input type="hidden" name="event_id" value="<?= $active_event_id ?>">
                                    <td class="coord-td-info" style="padding: 16px;">
                                        <div style="font-weight: 600;">
                                            <?php if ($view === 'results' && !empty($p['team_name'])): ?>
                                                <div style="color: var(--accent-1); font-size: 1.1rem; margin-bottom: 4px;">
                                                    <i class="fa-solid fa-users-gear"></i> <?= htmlspecialchars($p['team_name']) ?>
                                                </div>
                                                <div style="font-size: 0.85rem; color: var(--text-secondary); opacity: 0.8;">
                                                    <i class="fa-solid fa-id-card-clip"></i> <?= implode(' • ', array_map('htmlspecialchars', $p['member_names'])) ?>
                                                </div>
                                            <?php else: ?>
                                                <?= htmlspecialchars($p['name']) ?>
                                                <?php if ($p['team_name']): ?>
                                                    <span
                                                        style="background: rgba(124, 58, 237, 0.15); color: var(--accent-2); padding: 2px 6px; border-radius: 4px; font-size: 0.65rem; margin-left: 6px;"><i
                                                            class="fa-solid fa-users"></i>
                                                        <?= htmlspecialchars($p['team_name']) ?></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                                            <?php if ($view !== 'results' || empty($p['team_name'])): ?>
                                                <?= $p['user_id'] ?> • <?= $p['course'] ?> • <?= $p['year'] ?>
                                            <?php else: ?>
                                                Multi-Participant Team Record
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($view !== 'results' || empty($p['team_name'])): ?>
                                            <div style="font-size: 0.65rem; color: var(--accent-1); margin-top: 2px;">Roll:
                                                <?= $p['roll_no'] ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="coord-td-controls" style="grid-column: span 2; padding: 10px 0;">
                                        <div style="display: flex; gap: 8px; width: 100%; align-items: center;">
                                            <!-- Attendance Dropdown [Violet] -->
                                            <div style="flex: 1; position: relative;">
                                                <select name="attendance" onchange="this.form.submit()" 
                                                    style="width: 100%; height: 38px; background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.3); color: var(--accent-2); border-radius: 8px; padding: 0 10px; font-size: 0.7rem; font-weight: 700; appearance: none; cursor: pointer;">
                                                    <option value="absent" <?= $p['attendance'] == 'absent' ? 'selected' : '' ?>>ABSENT</option>
                                                    <option value="present" <?= $p['attendance'] == 'present' ? 'selected' : '' ?>>PRESENT</option>
                                                </select>
                                                <i class="fa-solid fa-chevron-down" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 0.6rem; color: var(--accent-2); pointer-events: none;"></i>
                                            </div>

                                            <!-- Status Dropdown [Cyan] -->
                                            <div style="flex: 1.5; position: relative;">
                                                <select name="status" onchange="this.form.submit()" 
                                                    style="width: 100%; height: 38px; background: rgba(0, 212, 255, 0.05); border: 1px solid rgba(0, 212, 255, 0.3); color: var(--accent-1); border-radius: 8px; padding: 0 10px; font-size: 0.7rem; font-weight: 700; appearance: none; cursor: pointer;">
                                                    <option value="registered" <?= $p['status'] == 'registered' ? 'selected' : '' ?>>REGISTERED</option>
                                                    <option value="participated" <?= $p['status'] == 'participated' ? 'selected' : '' ?>>PARTICIPATED</option>
                                                    <option value="winner" <?= $p['status'] == 'winner' ? 'selected' : '' ?>>WINNER</option>
                                                    <option value="runner" <?= $p['status'] == 'runner' ? 'selected' : '' ?>>RUNNER</option>
                                                </select>
                                                <i class="fa-solid fa-chevron-down" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 0.6rem; color: var(--accent-1); pointer-events: none;"></i>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="coord-td-actions" style="padding: 16px; text-align: right;">
                                        <div style="display: flex; justify-content: flex-end; gap: 10px; align-items: center;">
                                            <button type="submit" class="btn-coord"
                                                style="padding: 6px 16px; font-size: 0.7rem;">UPDATE</button>
                                            <button type="button" class="btn-icon-danger"
                                                style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); color: var(--danger); width: 28px; height: 28px; border-radius: 6px; cursor: pointer;"
                                                onclick="if(confirm('REMOVE THIS PARTICIPANT?')){
                                                    const form = document.createElement('form');
                                                    form.method = 'POST';
                                                    form.action = 'coordinator_actions.php';
                                                    
                                                    const act = document.createElement('input');
                                                    act.type='hidden'; act.name='action'; act.value='delete_registration';
                                                    form.appendChild(act);
                                                    
                                                    const csrf = document.createElement('input');
                                                    csrf.type='hidden'; csrf.name='csrf_token'; csrf.value='<?= $_SESSION['csrf_token'] ?>';
                                                    form.appendChild(csrf);
                                                    
                                                    const rid = document.createElement('input');
                                                    rid.type='hidden'; rid.name='reg_id'; rid.value='<?= $p['reg_id'] ?>';
                                                    form.appendChild(rid);
                                                    
                                                    const eid = document.createElement('input');
                                                    eid.type='hidden'; eid.name='event_id'; eid.value='<?= $active_event_id ?>';
                                                    form.appendChild(eid);
                                                    
                                                    document.body.appendChild(form);
                                                    form.submit();
                                                }">
                                                <i class="fa-solid fa-trash-can" style="font-size: 0.75rem;"></i>
                                            </button>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($participants)): ?>
                            <tr>
                                <td colspan="5" style="padding: 60px; text-align: center; color: var(--text-dim);">No
                                    participants found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    let html5QrcodeScanner = null;

    function startScanner() {
        document.getElementById('scanner-container').style.display = 'flex';
        document.getElementById('scanner-msg').innerText = "Initializing camera...";

        if (!html5QrcodeScanner) {
            html5QrcodeScanner = new Html5Qrcode("reader");
        }
        const activeEventId = "<?= $active_event_id ?>";

        const qrCodeSuccessCallback = (decodedText, decodedResult) => {
            stopScanner(); // Stop scanning immediately after detecting

            // Check if it's the new EVENT_QR format
            if (decodedText.startsWith("EVENT_QR|")) {
                const parts = decodedText.split("|");
                if (parts.length === 3) {
                    const scannedUserId = parts[1];
                    const scannedEventId = parts[2];

                    if (scannedEventId !== activeEventId) {
                        document.getElementById('scanner-msg').innerHTML = "<b style='color: var(--danger);'>ERROR: EVENT MISMATCH!</b><br>This ticket is for a different event.";
                        alert("ERROR: This QR ticket is for a different event! It cannot be used here.");
                        return;
                    }

                    document.getElementById('scanner-msg').innerHTML = "<b style='color: var(--success);'>TICKET VALIDATED:</b> " + scannedUserId;
                    markAttendance(scannedUserId);
                } else {
                    document.getElementById('scanner-msg').innerHTML = "<b style='color: var(--danger);'>ERROR:</b> Invalid ticket format.";
                }
            } else {
                document.getElementById('scanner-msg').innerHTML = "<b style='color: var(--danger);'>ERROR: GLOBAL QR REJECTED</b><br>Please ask the participant to open their specific Event Ticket from their dashboard.";
                alert("Global QR Passes are no longer accepted. The participant must generate an event-specific ticket from their dashboard.");
            }
        };

        html5QrcodeScanner.start({ facingMode: "environment" }, { fps: 10, qrbox: 250 }, qrCodeSuccessCallback)
            .then(() => {
                document.getElementById('scanner-msg').innerText = "Scanner Ready. Scan Participant Event Ticket.";
            })
            .catch(err => {
                document.getElementById('scanner-msg').innerText = "Camera error: " + err;
            });
    }

    function stopScanner() {
        const container = document.getElementById('scanner-container');
        if (html5QrcodeScanner && html5QrcodeScanner.isScanning) {
            html5QrcodeScanner.stop().then(() => {
                container.style.display = 'none';
                try { html5QrcodeScanner.clear(); } catch (e) { }
                html5QrcodeScanner = null;
            }).catch(() => {
                container.style.display = 'none';
                html5QrcodeScanner = null;
            });
        } else {
            container.style.display = 'none';
            if (html5QrcodeScanner) {
                try {
                    html5QrcodeScanner.clear();
                } catch (e) { }
                html5QrcodeScanner = null;
            }
        }
    }

    function markAttendance(userId) {
        fetch('coordinator_actions_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `user_id=${userId}&event_id=<?= $active_event_id ?>&action=qr_attendance`
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert("Success: " + data.student_name + " marked present.");
                    location.reload();
                } else alert("Error: " + data.message);
            });
    }

    document.getElementById('eventSearch')?.addEventListener('keyup', (e) => {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('.event-card-dash').forEach(card => {
            const name = card.querySelector('h3').innerText.toLowerCase();
            card.style.display = name.includes(term) ? 'block' : 'none';
        });
    });
</script>

<?php include 'includes/footer.php'; ?>