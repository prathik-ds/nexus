<?php
include 'includes/header.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.html');
    exit;
}

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Create Event
    if ($action === 'create_event') {
        $name = $_POST['name'];
        $category = $_POST['category'];
        $description = $_POST['description'] ?? '';
        $rules = $_POST['rules'];
        $date = $_POST['date'];
        $time = $_POST['time'];
        $venue = $_POST['venue'];
        $coord_name = $_POST['coordinator_name'];
        $coord_phone = $_POST['coordinator_phone'];
        $coord_id = isset($_POST['coordinator_id']) && is_array($_POST['coordinator_id']) ? implode(',', array_filter($_POST['coordinator_id'])) : null;
        $max_p = $_POST['max_participants'];
        $is_team = isset($_POST['is_team_event']) ? 1 : 0;
        $min_ts = (int) ($_POST['min_team_size'] ?? 2);
        $max_ts = (int) ($_POST['max_team_size'] ?? 4);

        $stmt = $pdo->prepare("INSERT INTO events (name, category, description, rules, date, time, venue, coordinator_name, coordinator_phone, coordinator_id, max_participants, is_team_event, min_team_size, max_team_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $category, $description, $rules, $date, $time, $venue, $coord_name, $coord_phone, $coord_id, $max_p, $is_team, $min_ts, $max_ts]);
        $msg = "EVENT CREATED SUCCESSFULLY.";
    }

    // Update Event
    elseif ($action === 'update_event') {
        $id = $_POST['event_id'];
        $name = $_POST['name'];
        $category = $_POST['category'];
        $description = $_POST['description'] ?? '';
        $rules = $_POST['rules'];
        $date = $_POST['date'];
        $time = $_POST['time'];
        $venue = $_POST['venue'];
        $coord_name = $_POST['coordinator_name'];
        $coord_phone = $_POST['coordinator_phone'];
        $coord_id = isset($_POST['coordinator_id']) && is_array($_POST['coordinator_id']) ? implode(',', array_filter($_POST['coordinator_id'])) : null;
        $max_p = $_POST['max_participants'];
        $is_team = isset($_POST['is_team_event']) ? 1 : 0;
        $min_ts = (int) ($_POST['min_team_size'] ?? 2);
        $max_ts = (int) ($_POST['max_team_size'] ?? 4);

        $stmt = $pdo->prepare("UPDATE events SET name=?, category=?, description=?, rules=?, date=?, time=?, venue=?, coordinator_name=?, coordinator_phone=?, coordinator_id=?, max_participants=?, is_team_event=?, min_team_size=?, max_team_size=? WHERE id=?");
        $stmt->execute([$name, $category, $description, $rules, $date, $time, $venue, $coord_name, $coord_phone, $coord_id, $max_p, $is_team, $min_ts, $max_ts, $id]);
        $msg = "EVENT UPDATED SUCCESSFULLY.";
    }

    // Delete Event
    elseif ($action === 'delete_event') {
        $id = $_POST['event_id'];
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "EVENT DELETED SUCCESSFULLY.";
    }
}

$events = $pdo->query("SELECT * FROM events ORDER BY category, name")->fetchAll();
$coordinators = $pdo->query("SELECT user_id, name FROM users WHERE role = 'coordinator'")->fetchAll();
?>

<div style="padding: 40px;">
    <div class="dashboard-header">
        <div class="header-content">
            <h1>Event Database</h1>
            <p>Deploy competitions, assign coordinators, and manage tournament tracks.</p>
        </div>
        <div class="header-actions">
            <button onclick="resetForm()" class="btn-start-dash" style="width: auto; padding: 12px 24px;">
                <i class="fa-solid fa-plus"></i> NEW TOURNAMENT TRACK
            </button>
        </div>
    </div>

    <?php if ($msg): ?>
        <div
            style="margin-bottom: 30px; padding: 15px; background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: var(--success); border-radius: 12px; font-size: 0.9rem;">
            <i class="fa-solid fa-circle-check"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 400px; gap: 30px;">
        <!-- Events Table -->
        <div class="glass-panel-dash" style="padding: 30px;">
            <h2 style="font-family: 'Outfit'; font-size: 1.5rem; margin-bottom: 30px;">Registered Events</h2>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <th
                                style="padding: 16px; text-align: left; color: var(--text-dim); font-size: 0.75rem; text-transform: uppercase;">
                                Event</th>
                            <th
                                style="padding: 16px; text-align: left; color: var(--text-dim); font-size: 0.75rem; text-transform: uppercase;">
                                Coordinator</th>
                            <th
                                style="padding: 16px; text-align: center; color: var(--text-dim); font-size: 0.75rem; text-transform: uppercase;">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $ev): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 16px;">
                                    <div style="font-weight: 600; display:flex; align-items:center; gap:8px;">
                                        <?= htmlspecialchars($ev['name']) ?>
                                        <?php if (!empty($ev['is_team_event'])): ?>
                                            <span
                                                style="font-size:0.6rem; font-weight:800; background:rgba(124,58,237,0.15); color:#a855f7; border:1px solid rgba(124,58,237,0.3); padding:2px 8px; border-radius:50px; letter-spacing:1px;">TEAM</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        <?= $ev['category'] ?> Track
                                        <?php if (!empty($ev['is_team_event'])): ?>
                                            · <?= $ev['min_team_size'] ?>–<?= $ev['max_team_size'] ?> members
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="padding: 16px;">
                                    <div style="font-size: 0.85rem; color: var(--text-main);">
                                        <?= htmlspecialchars($ev['coordinator_name'] ?: 'System') ?></div>
                                    <div style="font-size: 0.7rem; color: var(--text-dim);">
                                        <?= htmlspecialchars($ev['coordinator_id'] ?: '-') ?></div>
                                </td>
                                <td style="padding: 16px; text-align: center;">
                                    <div style="display: flex; gap: 8px; justify-content: center;">
                                        <button onclick='fillEditForm(<?= json_encode($ev) ?>)' class="btn-coord"
                                            style="padding: 6px 12px; font-size: 0.65rem; background: var(--bg-card); color: var(--primary); border-color: var(--primary);">EDIT</button>
                                        <form method="POST" onsubmit="return confirm('Delete this event?');"
                                            style="display:inline;">
                                            <input type="hidden" name="action" value="delete_event">
                                            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                            <button type="submit" class="btn-coord"
                                                style="padding: 6px 12px; font-size: 0.65rem; background: rgba(239, 68, 68, 0.1); color: var(--danger); border-color: var(--danger);">DEL</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Event Form -->
        <div class="glass-panel-dash" style="padding: 30px;">
            <h3 id="form-title"
                style="font-family: 'Outfit'; font-size: 1.2rem; margin-bottom: 25px; color: var(--primary);">Deploy New
                Track</h3>
            <form id="event-form" method="POST">
                <input type="hidden" name="action" id="form-action" value="create_event">
                <input type="hidden" name="event_id" id="form-event-id">

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="color: var(--text-dim); font-size: 0.7rem;">Event Name</label>
                    <input type="text" name="name" id="field-name" required
                        style="background: var(--bg-dark); border: 1px solid var(--border); border-radius: 8px;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label style="color: var(--text-dim); font-size: 0.7rem;">Track</label>
                        <select name="category" id="field-category"
                            style="background: var(--bg-dark); border: 1px solid var(--border); border-radius: 8px;">
                            <option value="IT">IT Track</option>
                            <option value="Commerce">Commerce</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="color: var(--text-dim); font-size: 0.7rem;">Cap</label>
                        <input type="number" name="max_participants" id="field-max-p" value="50"
                            style="background: var(--bg-dark); border: 1px solid var(--border); border-radius: 8px;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="color: var(--text-dim); font-size: 0.7rem;">Portal Assignment (Coord.)</label>
                    <select name="coordinator_id[]" id="field-coord-id" multiple
                        style="background: var(--bg-dark); border: 1px solid var(--border); border-radius: 8px; height: 120px; font-size: 0.8rem; padding: 5px;">
                        <option value="">-- No Assignment --</option>
                        <?php foreach ($coordinators as $c): ?>
                            <option value="<?= $c['user_id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['user_id'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: var(--text-muted); font-size: 0.6rem; display: block; margin-top: 4px;">Hold
                        Ctrl to select multiple coordinators.</small>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                    <div class="form-group"><label style="color: var(--text-dim); font-size: 0.7rem;">Coord.
                            Name</label><input type="text" name="coordinator_name" id="field-coord-n"
                            style="background: var(--bg-dark); border: 1px solid var(--border); border-radius: 8px;">
                    </div>
                    <div class="form-group"><label style="color: var(--text-dim); font-size: 0.7rem;">Coord.
                            Phone</label><input type="text" name="coordinator_phone" id="field-coord-p"
                            style="background: var(--bg-dark); border: 1px solid var(--border); border-radius: 8px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                    <div class="form-group"><label style="color: var(--text-dim); font-size: 0.7rem;">Date</label><input
                            type="date" name="date" id="field-date"
                            style="background: var(--bg-dark); border: 1px solid var(--border); border-radius: 8px;">
                    </div>
                    <div class="form-group"><label style="color: var(--text-dim); font-size: 0.7rem;">Time</label><input
                            type="time" name="time" id="field-time"
                            style="background: var(--bg-dark); border: 1px solid var(--border); border-radius: 8px;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="color: var(--text-dim); font-size: 0.7rem;">Venue</label>
                    <input type="text" name="venue" id="field-venue"
                        style="background: var(--bg-dark); border: 1px solid var(--border); border-radius: 8px;">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="color: var(--text-dim); font-size: 0.7rem;">Rules & Instructions</label>
                    <textarea name="rules" id="field-rules"
                        style="height: 80px; background: var(--bg-dark); border: 1px solid var(--border); border-radius: 8px;"></textarea>
                </div>

                <!-- Team Event Toggle -->
                <div
                    style="margin-bottom: 18px; padding: 18px; background: rgba(124,58,237,0.05); border: 1px solid rgba(124,58,237,0.15); border-radius: 12px;">
                    <label
                        style="display:flex; justify-content:space-between; align-items:center; cursor:pointer; margin-bottom:14px; padding: 12px 16px; background: rgba(0,0,0,0.2); border-radius: 12px; border: 1px solid rgba(124,58,237,0.15);">
                        <span style="color:#c4b5fd; font-size:0.85rem; font-weight:700;"><i
                                class="fa-solid fa-users"></i> &nbsp;Team Event</span>
                        <input type="checkbox" name="is_team_event" id="field-is-team" class="mobile-toggle"
                            onchange="toggleTeamFields()">
                    </label>
                    <div id="team-size-fields" style="display:none; grid-template-columns:1fr 1fr; gap:12px;">
                        <div class="form-group">
                            <label style="color: var(--text-dim); font-size: 0.7rem;">Min Team Size</label>
                            <input type="number" name="min_team_size" id="field-min-ts" value="2" min="1" max="20"
                                style="background: var(--bg-dark); border: 1px solid var(--border); border-radius: 8px;">
                        </div>
                        <div class="form-group">
                            <label style="color: var(--text-dim); font-size: 0.7rem;">Max Team Size</label>
                            <input type="number" name="max_team_size" id="field-max-ts" value="4" min="1" max="20"
                                style="background: var(--bg-dark); border: 1px solid var(--border); border-radius: 8px;">
                        </div>
                    </div>
                </div>

                <button type="submit" id="submit-btn" class="btn-start-dash">DEPLOY TRACK</button>
            </form>
        </div>
    </div>
</div>

<script>
    function fillEditForm(event) {
        document.getElementById('form-title').innerText = 'Update Track';
        document.getElementById('form-title').style.color = '#a855f7';
        document.getElementById('form-action').value = 'update_event';
        document.getElementById('form-event-id').value = event.id;
        document.getElementById('field-name').value = event.name;
        document.getElementById('field-category').value = event.category;
        document.getElementById('field-max-p').value = event.max_participants;
        document.getElementById('field-date').value = event.date;
        document.getElementById('field-time').value = event.time;
        document.getElementById('field-venue').value = event.venue;
        document.getElementById('field-coord-n').value = event.coordinator_name;
        document.getElementById('field-coord-p').value = event.coordinator_phone;
        const coordSelect = document.getElementById('field-coord-id');
        const assignedIds = (event.coordinator_id || '').split(',');
        Array.from(coordSelect.options).forEach(opt => {
            opt.selected = assignedIds.includes(opt.value);
        });
        document.getElementById('field-rules').value = event.rules;
        document.getElementById('field-is-team').checked = event.is_team_event == 1;
        document.getElementById('field-min-ts').value = event.min_team_size || 2;
        document.getElementById('field-max-ts').value = event.max_team_size || 4;
        toggleTeamFields();
        document.getElementById('submit-btn').innerText = 'UPDATE TRACK';
    }

    function toggleTeamFields() {
        const isTeam = document.getElementById('field-is-team').checked;
        document.getElementById('team-size-fields').style.display = isTeam ? 'grid' : 'none';
    }

    function resetForm() {
        document.getElementById('form-title').innerText = 'Deploy New Track';
        document.getElementById('form-title').style.color = '#6366f1';
        document.getElementById('form-action').value = 'create_event';
        document.getElementById('event-form').reset();
        document.getElementById('submit-btn').innerText = 'DEPLOY TRACK';
    }
</script>

<?php include 'includes/footer.php'; ?>