<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.html');
    exit;
}
include 'includes/header.php';

$msg = '';
$error = '';

// Handle POST actions for each function
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. User Management Functions
    if ($action === 'create_user') {
        $u_id = $_POST['user_id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $role = $_POST['role'];
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (user_id, name, email, phone, course, password, role) VALUES (?, ?, ?, ?, 'General', ?, ?)");
            $stmt->execute([$u_id, $name, $email, $phone, $pass, $role]);
            $msg = "USER CREATED SUCCESSFULLY.";
        } catch (Exception $e) {
            $error = "FAILED TO CREATE USER: ID/EMAIL ALREADY EXISTS.";
        }
    } elseif ($action === 'update_user_role') {
        $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?")->execute([$_POST['role'], $_POST['user_id']]);
        $msg = "USER ROLE UPDATED.";
    } elseif ($action === 'delete_user') {
        $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$_POST['user_id']]);
        $msg = "USER REMOVED FROM SYSTEM.";
    } elseif ($action === 'update_password') {
        $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([$new_pass, $_POST['user_id']]);
        $msg = "PASSWORD RESET SUCCESSFUL.";
    }

    // 2. Event Management Functions
    elseif ($action === 'create_event') {
        $name = $_POST['name'];
        $category = $_POST['category'];
        $description = $_POST['description'] ?? '';
        $rules = $_POST['rules'];
        $date = $_POST['date'];
        $time = $_POST['time'];
        $venue = $_POST['venue'];
        $max_p = $_POST['max_participants'];
        $coord_ids = $_POST['coordinator_ids'] ?? [];

        $image_path = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], 'assets/img/events/' . $filename)) {
                $image_path = 'assets/img/events/' . $filename;
            }
        }

        // Fetch multiple coord details
        $c_names = [];
        $c_phones = [];
        if (!empty($coord_ids)) {
            $placeholders = str_repeat('?,', count($coord_ids) - 1) . '?';
            $cst = $pdo->prepare("SELECT name, phone FROM users WHERE user_id IN ($placeholders)");
            $cst->execute($coord_ids);
            $c_datas = $cst->fetchAll();
            foreach ($c_datas as $cd) {
                $c_names[] = $cd['name'];
                $c_phones[] = $cd['phone'];
            }
        }
        $c_name = implode(', ', $c_names);
        $c_phone = implode(', ', $c_phones);
        $coord_id = implode(',', $coord_ids);

        $is_team = isset($_POST['is_team_event']) ? 1 : 0;
        $min_ts = (int) ($_POST['min_team_size'] ?? 2);
        $max_ts = (int) ($_POST['max_team_size'] ?? 4);
        $eligibility = $_POST['eligibility_stream'];

        $stmt = $pdo->prepare("INSERT INTO events (name, category, eligibility_stream, description, rules, date, time, venue, coordinator_name, coordinator_phone, coordinator_id, max_participants, image, is_team_event, min_team_size, max_team_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $category, $eligibility, $description, $rules, $date, $time, $venue, $c_name, $c_phone, $coord_id, $max_p, $image_path, $is_team, $min_ts, $max_ts]);
        $msg = "EVENT TRACK DEPLOYED.";
    } elseif ($action === 'update_event') {
        $id = $_POST['event_id'];
        $description = $_POST['description'] ?? '';
        $coord_ids = $_POST['coordinator_ids'] ?? [];

        $c_names = [];
        $c_phones = [];
        if (!empty($coord_ids)) {
            $placeholders = str_repeat('?,', count($coord_ids) - 1) . '?';
            $cst = $pdo->prepare("SELECT name, phone FROM users WHERE user_id IN ($placeholders)");
            $cst->execute($coord_ids);
            $c_datas = $cst->fetchAll();
            foreach ($c_datas as $cd) {
                $c_names[] = $cd['name'];
                $c_phones[] = $cd['phone'];
            }
        }
        $c_name = implode(', ', $c_names);
        $c_phone = implode(', ', $c_phones);
        $coord_id = implode(',', $coord_ids);

        $image_path = $_POST['existing_logo'] ?? null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], 'assets/img/events/' . $filename)) {
                $image_path = 'assets/img/events/' . $filename;
            }
        }

        $stmt = $pdo->prepare("UPDATE events SET name=?, category=?, eligibility_stream=?, description=?, rules=?, date=?, time=?, venue=?, coordinator_name=?, coordinator_phone=?, coordinator_id=?, max_participants=?, image=?, is_team_event=?, min_team_size=?, max_team_size=? WHERE id=?");
        $is_team = isset($_POST['is_team_event']) ? 1 : 0;
        $min_ts = (int) ($_POST['min_team_size'] ?? 2);
        $max_ts = (int) ($_POST['max_team_size'] ?? 4);
        $eligibility = $_POST['eligibility_stream'];
        $stmt->execute([$_POST['name'], $_POST['category'], $eligibility, $description, $_POST['rules'], $_POST['date'], $_POST['time'], $_POST['venue'], $c_name, $c_phone, $coord_id, $_POST['max_participants'], $image_path, $is_team, $min_ts, $max_ts, $id]);
        $msg = "EVENT TRACK UPDATED.";
    }

    // 3. Registration Management Functions
    elseif ($action === 'update_reg_status') {
        $reg_id = $_POST['reg_id'];
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
                    SET r.status = ? 
                    WHERE t.name = ? AND r.event_id = ?
                ")->execute([$status, $team_name, $r_info['event_id']]);
            } else {
                $pdo->prepare("UPDATE registrations SET status = ? WHERE team_id = ? AND event_id = ?")->execute([$status, $r_info['team_id'], $r_info['event_id']]);
            }
        } else {
            $pdo->prepare("UPDATE registrations SET status = ? WHERE id = ?")->execute([$status, $reg_id]);
        }
        $msg = "PARTICIPANT STATUS UPDATED.";
    }
}

// Data Fetching
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$events_count = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$total_regs = $pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();

$participation_data = $pdo->query("SELECT name, current_participants as count FROM events ORDER BY current_participants DESC LIMIT 6")->fetchAll();
$all_events = $pdo->query("SELECT * FROM events ORDER BY name")->fetchAll();
$all_users = $pdo->query("SELECT * FROM users ORDER BY role DESC, name")->fetchAll();
$coordinators = $pdo->query("SELECT user_id, name FROM users WHERE role = 'coordinator'")->fetchAll();
$all_regs_raw = $pdo->query("SELECT r.*, u.name as user_name, e.name as event_name, e.is_team_event, t.name as team_name, t.id as t_id FROM registrations r JOIN users u ON r.user_id = u.user_id JOIN events e ON r.event_id = e.id LEFT JOIN teams t ON r.team_id = t.id ORDER BY e.name, r.created_at DESC")->fetchAll();

$events_with_regs = [];
foreach ($all_regs_raw as $reg) {
    $events_with_regs[$reg['event_name']][] = $reg;
}
?>

<div class="admin-main-wrapper">
    <div class="dashboard-header-modern">
        <div class="header-content">
            <h1 style="display: flex; align-items: center; gap: 12px;">
                <div class="admin-icon-glow"><i class="fa-solid fa-bolt-lightning"></i></div>
                Command Center
            </h1>
            <p>Master control panel for Nexus Fest events and users.</p>
        </div>
        <div class="header-status-badge">
            <i class="fa-solid fa-shield-halved"></i> MASTER ADMIN
        </div>
    </div>

    <?php if ($msg): ?>
        <div
            style="margin-bottom: 20px; padding: 15px; background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: var(--success); border-radius: 12px; font-size: 0.9rem;">
            <i class="fa-solid fa-circle-check"></i> <?= $msg ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div
            style="margin-bottom: 20px; padding: 15px; background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); color: var(--danger); border-radius: 12px; font-size: 0.9rem;">
            <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- Navigation Tabs -->
    <div class="tab-container">
        <button onclick="showTab('dashboard')" class="tab-btn active" id="tab-dashboard"><i
                class="fa-solid fa-chart-pie" style="margin-right: 8px;"></i> DASHBOARD</button>
        <button onclick="showTab('users')" class="tab-btn" id="tab-users"><i class="fa-solid fa-users"
                style="margin-right: 8px;"></i> USER MANAGEMENT</button>
        <button onclick="showTab('events')" class="tab-btn" id="tab-events"><i class="fa-solid fa-calendar-alt"
                style="margin-right: 8px;"></i> EVENT MANAGEMENT</button>
        <button onclick="showTab('regs')" class="tab-btn" id="tab-regs"><i class="fa-solid fa-clipboard-check"
                style="margin-right: 8px;"></i> REGISTRATION DESK</button>
    </div>

    <!-- TAB: DASHBOARD (Overview) -->
    <div id="content-dashboard" class="admin-tab-content">
        <div class="stats-grid-modern">
            <div class="stat-card-modern purple">
                <div class="stat-icon"><i class="fa-solid fa-user-group"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Participants</span>
                    <span class="stat-value"><?= $total_users ?></span>
                </div>
            </div>
            <div class="stat-card-modern cyan">
                <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Active Events</span>
                    <span class="stat-value"><?= $events_count ?></span>
                </div>
            </div>
            <div class="stat-card-modern emerald">
                <div class="stat-icon"><i class="fa-solid fa-ticket"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Registered</span>
                    <span class="stat-value"><?= $total_regs ?></span>
                </div>
            </div>
        </div>

        <div class="glass-panel-dash" style="padding: 24px; margin-top: 24px;">
            <h2
                style="font-family: 'Space Grotesk', sans-serif; font-size: 1.1rem; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-chart-line" style="color: var(--accent-1);"></i>
                Participation Analytics
            </h2>
            <div style="height: 320px; position: relative;"><canvas id="participationChart"></canvas></div>
        </div>
    </div>

    <!-- TAB: USER MANAGEMENT -->
    <div id="content-users" class="admin-tab-content" style="display: none;">
        <div class="admin-grid-layout user-grid">
            <div class="glass-panel-dash" style="padding: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h2 style="font-family: 'Outfit'; font-size: 1.4rem; margin: 0;">Account Directory</h2>
                    <a href="export_data.php?type=users" class="btn-coord" style="text-decoration: none;">
                        <i class="fa-solid fa-file-export"></i> EXPORT CSV
                    </a>
                </div>
                <div style="overflow-x: auto;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>USER</th>
                                <th style="text-align: center;">ROLE</th>
                                <th style="text-align: right;">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $u): ?>
                                <tr>
                                    <td data-label="User">
                                        <div style="font-weight: 600;"><?= htmlspecialchars($u['name']) ?></div>
                                        <div style="font-size: 0.65rem; color: var(--text-dim);"><?= $u['email'] ?> •
                                            <?= $u['phone'] ?>
                                        </div>
                                        <div
                                            style="font-size: 0.6rem; color: var(--accent-1); margin-top: 4px; font-weight: 600;">
                                            <?= $u['user_id'] ?> | <?= $u['course'] ?> | <?= $u['year'] ?> | Roll:
                                            <?= $u['roll_no'] ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;" data-label="Role">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_user_role">
                                            <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                            <select name="role" onchange="this.form.submit()" class="modern-select"
                                                style="padding: 6px 12px; width: auto; display: inline-block;">
                                                <option value="student" <?= $u['role'] == 'student' || $u['role'] == 'user' ? 'selected' : '' ?>>Student</option>
                                                <option value="coordinator" <?= $u['role'] == 'coordinator' ? 'selected' : '' ?>>Coord</option>
                                                <option value="admin" <?= $u['role'] == 'admin' ? 'selected' : '' ?>>Admin
                                                </option>
                                            </select>
                                        </form>
                                    </td>
                                    <td style="text-align: right;" data-label="Actions">
                                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                            <button type="button" class="btn-icon-danger"
                                                style="background: rgba(124, 58, 237, 0.1); color: var(--accent-2); border-color: var(--accent-2);"
                                                onclick="let p = prompt('Enter new password for <?= htmlspecialchars(addslashes($u['name'])) ?>:'); if(p){ let f = document.createElement('form'); f.method='POST'; f.innerHTML='<input type=\'hidden\' name=\'action\' value=\'update_password\'><input type=\'hidden\' name=\'user_id\' value=\'<?= $u['user_id'] ?>\'><input type=\'hidden\' name=\'new_password\' value=\''+p+'\'>'; document.body.appendChild(f); f.submit(); }"
                                                title="Reset Password">
                                                <i class="fa-solid fa-key"></i>
                                            </button>
                                            <form method="POST" onsubmit="return confirm('Delete user?');">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                                <button type="submit" class="btn-icon-danger" title="Delete User"><i
                                                        class="fa-solid fa-trash-can"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="glass-panel-dash" style="padding: 30px;">
                <h3 style="font-family: 'Outfit'; font-size: 1.1rem; color: var(--primary); margin-bottom: 20px;">
                    Onboard New User</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create_user">
                    <div class="form-group" style="margin-bottom: 15px;"><label class="modern-label">User ID / Reg
                            No.</label><input type="text" name="user_id" class="modern-input" required
                            placeholder="e.g. STD1001"></div>
                    <div class="form-group" style="margin-bottom: 15px;"><label class="modern-label">Full
                            Name</label><input type="text" name="name" class="modern-input" required
                            placeholder="John Doe"></div>
                    <div class="form-group" style="margin-bottom: 15px;"><label class="modern-label">Email
                            Address</label><input type="email" name="email" class="modern-input" required
                            placeholder="john@example.com"></div>
                    <div class="form-group" style="margin-bottom: 15px;"><label class="modern-label">Phone
                            Number</label><input type="text" name="phone" class="modern-input" required
                            placeholder="1234567890"></div>
                    <div class="form-group" style="margin-bottom: 15px;"><label class="modern-label">Role</label><select
                            name="role" class="modern-select">
                            <option value="student">Student / User</option>
                            <option value="coordinator">Coordinator</option>
                            <option value="admin">Administrator</option>
                        </select></div>
                    <div class="form-group" style="margin-bottom: 25px;"><label class="modern-label">Default
                            Password</label><input type="password" name="password" class="modern-input" required
                            placeholder="********"></div>
                    <button type="submit" class="btn-start-dash">ADD ACCOUNT</button>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB: EVENT MANAGEMENT -->
    <div id="content-events" class="admin-tab-content" style="display: none;">
        <div class="admin-grid-layout event-grid">
            <div class="glass-panel-dash" style="padding: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h2 style="font-family: 'Outfit'; font-size: 1.4rem;  margin: 0;">Competition Tracks</h2>
                    <a href="export_data.php?type=events" class="btn-coord" style="text-decoration: none;">
                        <i class="fa-solid fa-file-export"></i> EXPORT CSV
                    </a>
                </div>
                <div style="overflow-x: auto;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>EVENT</th>
                                <th style="text-align: center;">VENUE</th>
                                <th style="text-align: right;">MANAGEMENT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_events as $ev): ?>
                                <tr>
                                    <td data-label="Event"
                                        onclick="editEvent(<?= htmlspecialchars(json_encode($ev), ENT_QUOTES, 'UTF-8') ?>)"
                                        style="cursor: pointer;">
                                        <div style="font-weight: 600; color: var(--primary);">
                                            <?= htmlspecialchars($ev['name']) ?>
                                        </div>
                                        <div style="font-size: 0.7rem; color: var(--text-dim); margin-top: 4px;"><i
                                                class="fa-solid fa-layer-group"></i> <?= $ev['category'] ?> Track</div>
                                    </td>
                                    <td data-label="Venue"
                                        onclick="editEvent(<?= htmlspecialchars(json_encode($ev), ENT_QUOTES, 'UTF-8') ?>)"
                                        style="text-align: center; cursor: pointer;">
                                        <i class="fa-solid fa-location-dot text-dim"></i> <?= $ev['venue'] ?: 'TBD' ?>
                                    </td>
                                    <td style="text-align: right;" data-label="Management">
                                        <div
                                            style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                                            <a href="coordinator.php?manage_event=<?= $ev['id'] ?>" class="btn-coord"
                                                style="text-decoration: none; padding: 6px 12px; font-size: 0.65rem; background: rgba(0, 212, 255, 0.08); color: var(--accent-1); border-color: var(--accent-1);">
                                                <i class="fa-solid fa-camera"></i> MANAGE ATTENDANCE
                                            </a>
                                            <div style="font-size: 0.65rem; color: var(--text-dim); cursor: pointer;"
                                                onclick="editEvent(<?= htmlspecialchars(json_encode($ev), ENT_QUOTES, 'UTF-8') ?>)">
                                                <i class="fa-solid fa-pen-to-square"></i> Edit Config
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="glass-panel-dash" style="padding: 30px;">
                <h3 id="ev-form-title"
                    style="font-family: 'Outfit'; font-size: 1.1rem; color: var(--primary); margin-bottom: 20px;">Deploy
                    New Event</h3>
                <form id="ev-form" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="ev-action" value="create_event">
                    <input type="hidden" name="event_id" id="ev-id">
                    <input type="hidden" name="existing_logo" id="ev-existing-logo">

                    <!-- Section 1: Basic Information -->
                    <div style="margin-bottom: 20px;">
                        <div
                            style="font-size: 0.65rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-info-circle"></i> Basic Track Info
                        </div>
                        <div class="form-group" style="margin-bottom: 15px;"><label class="modern-label">Track
                                Title</label><input type="text" name="name" id="ev-name" class="modern-input" required
                                placeholder="e.g. Code Rush"></div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div><label class="modern-label">Track Type</label><select name="category" id="ev-cat"
                                    class="modern-select">
                                    <option value="IT">IT Track</option>
                                    <option value="Commerce">Commerce Track</option>
                                    <option value="ART">Art Track</option>
                                </select></div>
                            <div>
                                <label class="modern-label">Restricted To (Stream)</label>
                                <select name="eligibility_stream" id="ev-eligibility" class="modern-select">
                                    <option value="ALL">OPEN TO ALL</option>
                                    <option value="IT">IT Only (BCA)</option>
                                    <option value="Commerce">Commerce Only (BCOM)</option>
                                    <option value="Art">Art Only (BA)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Branding (The Requested Logo Section) -->
                    <div
                        style="margin-bottom: 25px; padding: 20px; background: rgba(0, 212, 255, 0.03); border: 1px dashed rgba(0, 212, 255, 0.2); border-radius: 12px;">
                        <div
                            style="font-size: 0.65rem; color: var(--accent-1); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; font-weight: 700;">
                            <i class="fa-solid fa-palette"></i> Event Branding Section
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label class="modern-label">Event Logo / Header Image</label>
                            <label class="mobile-file-upload">
                                <input type="file" name="logo" id="ev-logo" accept="image/*"
                                    onchange="previewLogo(this)">
                                <div class="mobile-file-upload-content">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <span>Tap to Browse Images</span>
                                    <span style="font-size: 0.65rem; color: var(--text-dim); font-weight: 400;">JPEG,
                                        PNG, WEBP</span>
                                </div>
                            </label>
                            <div id="logo-preview-container"
                                style="margin-top: 15px; display: none; text-align: center;">
                                <div style="font-size: 0.6rem; color: var(--text-dim); margin-bottom: 8px;">LIVE PREVIEW
                                </div>
                                <img id="logo-preview" src="#" alt="Preview"
                                    style="max-width: 100%; height: 120px; border-radius: 12px; object-fit: cover; border: 1px solid var(--border-bright); box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="modern-label">Short Tagline / Description</label>
                            <textarea name="description" id="ev-desc" class="modern-textarea" style="height: 60px;"
                                placeholder="Brief overview for the event card..."></textarea>
                        </div>
                    </div>

                    <!-- Section 3: Logistics -->
                    <div style="margin-bottom: 20px;">
                        <div
                            style="font-size: 0.65rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-clock"></i> Schedule & Logistics
                        </div>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label class="modern-label">Assign Coordinators</label>

                            <!-- Search Bar for Coordinators -->
                            <input type="text" id="coord-search" class="modern-input"
                                placeholder="Search by name or ID..."
                                style="margin-bottom: 10px; padding: 10px 14px; font-size: 0.8rem; border-color: rgba(0, 212, 255, 0.3); border-radius: 8px;"
                                onkeyup="filterCoordinators()">

                            <div id="ev-coord-container"
                                style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; max-height: 220px; overflow-y: auto; background: rgba(0,0,0,0.2); padding: 12px; border-radius: 10px; border: 1px solid var(--border);">
                                <?php foreach ($coordinators as $c): ?>
                                    <label class="coord-label-item"
                                        style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--text-primary); cursor: pointer; padding: 4px; border-radius: 6px; transition: background 0.2s;"
                                        onmouseover="this.style.background='rgba(255,255,255,0.05)'"
                                        onmouseout="this.style.background='transparent'">
                                        <input type="checkbox" name="coordinator_ids[]" class="ev-coord-checkbox"
                                            value="<?= $c['user_id'] ?>"
                                            style="width: 16px; height: 16px; accent-color: var(--accent-1); cursor: pointer;"
                                            onchange="updateCoordCount()">
                                        <span class="coord-name-text"><?= htmlspecialchars($c['name']) ?></span> <span
                                            class="coord-id-text"
                                            style="color: var(--text-dim); font-size: 0.7rem;">(<?= $c['user_id'] ?>)</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div
                                style="margin-top: 8px; display: flex; justify-content: space-between; font-size: 0.75rem;">
                                <span id="coord-count-text" style="color: var(--accent-1); font-weight: 600;">0
                                    selected</span>
                                <a href="javascript:void(0)" onclick="clearCoordSelection()"
                                    style="color: var(--danger); text-decoration: none; font-weight: 600;"><i
                                        class="fa-solid fa-xmark"></i> Clear Selection</a>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div><label class="modern-label">Date</label><input type="date" name="date" id="ev-date"
                                    class="modern-input"></div>
                            <div><label class="modern-label">Time</label><input type="time" name="time" id="ev-time"
                                    class="modern-input"></div>
                        </div>
                        <div><label class="modern-label">Max Capacity</label><input type="number"
                                name="max_participants" id="ev-max" value="50" class="modern-input"></div>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;"><label class="modern-label">Venue</label><input
                            type="text" name="venue" id="ev-venue" class="modern-input"
                            placeholder="e.g. Main Auditorium"></div>
                    <div class="form-group" style="margin-bottom: 25px;"><label class="modern-label">Full Rules
                            (Modal)</label><textarea name="rules" id="ev-rules" class="modern-textarea"
                            style="height: 100px; resize: vertical;" placeholder="Detailed rules..."></textarea></div>
            </div>

            <!-- Section 4: Team Event Settings -->
            <div
                style="margin-bottom: 25px; padding: 18px; background: rgba(124,58,237,0.05); border: 1px solid rgba(124,58,237,0.2); border-radius: 12px;">
                <div
                    style="font-size: 0.65rem; color: #a855f7; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; font-weight: 700;">
                    <i class="fa-solid fa-users"></i> Team Event Settings
                </div>
                <label
                    style="display:flex; justify-content: space-between; align-items:center; cursor:pointer; margin-bottom:18px; padding: 12px 16px; background: rgba(0,0,0,0.2); border-radius: 12px; border: 1px solid rgba(124,58,237,0.15);">
                    <span style="color:#c4b5fd; font-size:0.85rem; font-weight:700;">Enable Team Registration</span>
                    <input type="checkbox" name="is_team_event" id="ev-is-team" class="mobile-toggle"
                        onchange="toggleEvTeamFields()">
                </label>
                <div id="ev-team-size-fields" style="display:none; grid-template-columns:1fr 1fr; gap:15px;">
                    <div>
                        <label class="modern-label">Min Team Size</label>
                        <input type="number" name="min_team_size" id="ev-min-ts" value="2" min="1" max="20"
                            class="modern-input">
                    </div>
                    <div>
                        <label class="modern-label">Max Team Size</label>
                        <input type="number" name="max_team_size" id="ev-max-ts" value="4" min="1" max="20"
                            class="modern-input">
                    </div>
                </div>
            </div>

            <button type="submit" id="ev-submit" class="btn-start-dash">DEPLOY EVENT TRACK</button>
            <button type="button" onclick="resetEvForm()" class="btn-coord"
                style="width: 100%; border: none; margin-top: 10px; opacity: 0.7;">CLEAR FORM</button>
            </form>
        </div>
    </div>
</div>

<!-- TAB: REGISTRATION DESK -->
<div id="content-regs" class="admin-tab-content" style="display: none;">
    <div class="glass-panel-dash" style="padding: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 style="font-family: 'Outfit'; font-size: 1.4rem;">Flow Management</h2>
            <div style="display: flex; gap: 10px;">
                <a href="export_data.php?type=participation" class="btn-coord"
                    style="text-decoration: none; padding: 6px 16px; font-size: 0.7rem; color: var(--secondary); border-color: var(--secondary);">
                    <i class="fa-solid fa-file-csv"></i> CSV EXPORT
                </a>
                <button onclick="window.print()" class="btn-coord"
                    style="padding: 6px 16px; font-size: 0.7rem; border: 1px solid var(--primary); color: var(--primary);"><i
                        class="fa-solid fa-file-pdf"></i> RECORD PRINT</button>
            </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php $idx = 0;
            foreach ($events_with_regs as $event_name => $registrations):
                $idx++; ?>
                <div class="event-group-panel"
                    style="background: rgba(255,255,255,0.01); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; overflow: hidden;">
                    <div onclick="toggleAccordion('event-grp-<?= $idx ?>', this)"
                        style="background: rgba(124, 58, 237, 0.03); padding: 14px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: background 0.3s;">
                        <h3
                            style="font-family: 'Outfit'; font-size: 0.95rem; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 12px;">
                            <i class="fa-solid fa-chevron-right accordion-caret"
                                style="transition: transform 0.3s; font-size: 0.7rem; color: var(--accent-1);"></i>
                            <span><?= htmlspecialchars($event_name) ?></span>
                            <span
                                style="font-size: 0.6rem; background: rgba(0,212,255,0.1); color: var(--accent-1); padding: 2px 8px; border-radius: 4px; margin-left: 5px;">
                                <?= count($registrations) ?> Enrolled
                            </span>
                        </h3>
                        <div
                            style="font-size: 0.6rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px;">
                            Click to view</div>
                    </div>
                    <div id="event-grp-<?= $idx ?>"
                        style="display: none; padding: 0; border-top: 1px solid rgba(255,255,255,0.03);">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th style="padding-left: 24px;">PARTICIPANT</th>
                                    <th style="text-align: center;">APPROVAL FLOW</th>
                                    <th style="text-align: right; padding-right: 24px;">DATETIME</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $display_regs = $registrations;
                                // Determine if this event is team-based (check any reg)
                                $first_reg = reset($registrations);
                                if ($first_reg && $first_reg['is_team_event']) {
                                    $grouped_adm = [];
                                    foreach ($registrations as $reg_adm) {
                                        $t_key_adm = $reg_adm['team_name'] ?: ($reg_adm['t_id'] ?: 'indiv_' . $reg_adm['id']);
                                        if ($reg_adm['team_name'] || $reg_adm['t_id']) {
                                            if (!isset($grouped_adm[$t_key_adm])) {
                                                $grouped_adm[$t_key_adm] = $reg_adm;
                                                $grouped_adm[$t_key_adm]['member_names'] = [];
                                            }
                                            $grouped_adm[$t_key_adm]['member_names'][] = $reg_adm['user_name'];
                                        } else {
                                            $reg_adm['member_names'] = [$reg_adm['user_name']];
                                            $grouped_adm['indiv_' . $reg_adm['id']] = $reg_adm;
                                        }
                                    }
                                    $display_regs = array_values($grouped_adm);
                                }

                                foreach ($display_regs as $r): ?>
                                    <tr>
                                        <td data-label="Participant" style="padding-left: 24px;">
                                            <?php if ($r['is_team_event'] && $r['team_name']): ?>
                                                <div style="font-weight: 700; color: var(--accent-1); font-size: 0.95rem;">
                                                    <i class="fa-solid fa-users"></i> <?= htmlspecialchars($r['team_name']) ?>
                                                </div>
                                                <div style="font-size: 0.72rem; color: var(--text-dim); margin-top: 4px;">
                                                    Members: <?= htmlspecialchars(implode(', ', $r['member_names'])) ?>
                                                </div>
                                            <?php else: ?>
                                                <div style="font-weight: 600; font-size: 0.95rem;">
                                                    <?= htmlspecialchars($r['user_name']) ?>
                                                </div>
                                                <div style="font-size: 0.72rem; color: var(--text-dim);">ID: <?= $r['user_id'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;" data-label="Status">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="update_reg_status">
                                                <input type="hidden" name="reg_id" value="<?= $r['id'] ?>">
                                                <?php
                                                $statusClass = '';
                                                if ($r['status'] == 'registered')
                                                    $statusClass = 'pending';
                                                elseif ($r['status'] == 'participated')
                                                    $statusClass = 'approved';
                                                elseif ($r['status'] == 'winner' || $r['status'] == 'runner')
                                                    $statusClass = 'winner';
                                                ?>
                                                <select name="status" onchange="this.form.submit()"
                                                    class="status-select <?= $statusClass ?>">
                                                    <option value="registered" <?= $r['status'] == 'registered' ? 'selected' : '' ?>>
                                                        PENDING APPROVAL</option>
                                                    <option value="participated" <?= $r['status'] == 'participated' ? 'selected' : '' ?>>APPROVED /
                                                        PLAYED</option>
                                                    <option value="winner" <?= $r['status'] == 'winner' ? 'selected' : '' ?>>🏆
                                                        WINNER
                                                    </option>
                                                    <option value="runner" <?= $r['status'] == 'runner' ? 'selected' : '' ?>>🥈
                                                        RUNNER-UP</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td style="text-align: right; font-size: 0.8rem; color: var(--text-muted); padding-right: 24px;"
                                            data-label="Time">
                                            <i class="fa-regular fa-clock"></i>
                                            <?= date('d M, h:i A', strtotime($r['created_at'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($events_with_regs)): ?>
                <div class="glass-panel-dash" style="padding: 60px; text-align: center; color: var(--text-dim);">
                    <i class="fa-solid fa-clipboard-question"
                        style="font-size: 3rem; opacity: 0.2; margin-bottom: 20px; display: block;"></i>
                    No active registrations found in the system.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<!-- Styles for Tabs & Inputs -->
<style>
    /* Admin UI Overhaul Styles */
    .admin-main-wrapper {
        padding: 32px;
        max-width: 1400px;
        margin: 0 auto;
    }

    .dashboard-header-modern {
        background: linear-gradient(135deg, rgba(15, 22, 41, 0.4), rgba(4, 6, 14, 0.6));
        border: 1px solid var(--border);
        border-radius: 24px;
        padding: 32px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        position: relative;
        overflow: hidden;
    }

    .admin-icon-glow {
        width: 48px;
        height: 48px;
        background: rgba(0, 212, 255, 0.1);
        border: 1px solid rgba(0, 212, 255, 0.3);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent-1);
        font-size: 1.2rem;
        box-shadow: 0 0 20px rgba(0, 212, 255, 0.1);
    }

    .header-status-badge {
        background: rgba(124, 58, 237, 0.1);
        border: 1px solid rgba(124, 58, 237, 0.2);
        color: #a855f7;
        padding: 8px 18px;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    /* Stats Grid Overhaul */
    .stats-grid-modern {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card-modern {
        background: rgba(15, 22, 41, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.05);
        padding: 24px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        gap: 20px;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
    }

    .stat-card-modern::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.03) 0%, transparent 70%);
        pointer-events: none;
    }

    .stat-card-modern:hover {
        transform: translateY(-5px);
        background: rgba(15, 22, 41, 0.6);
        border-color: rgba(255, 255, 255, 0.1);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        position: relative;
        z-index: 2;
    }

    .stat-card-modern.purple .stat-icon {
        background: rgba(124, 58, 237, 0.15);
        color: #a855f7;
        box-shadow: 0 0 20px rgba(124, 58, 237, 0.1);
    }

    .stat-card-modern.cyan .stat-icon {
        background: rgba(0, 212, 255, 0.15);
        color: #00d4ff;
        box-shadow: 0 0 20px rgba(0, 212, 255, 0.1);
    }

    .stat-card-modern.emerald .stat-icon {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.1);
    }

    .stat-info {
        display: flex;
        flex-direction: column;
        z-index: 2;
    }

    .stat-label {
        font-size: 0.75rem;
        color: var(--text-dim);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 4px;
    }

    .stat-value {
        font-size: 2.2rem;
        font-weight: 800;
        color: white;
        line-height: 1;
        font-family: 'Space Grotesk', sans-serif;
    }

    /* Admin Grid Layouts */
    .admin-grid-layout {
        display: grid;
        gap: 30px;
    }

    .user-grid {
        grid-template-columns: 1fr 350px;
    }

    .event-grid {
        grid-template-columns: 1fr 380px;
    }

    /* Mobile Adaptations */
    @media (max-width: 992px) {
        .admin-main-wrapper {
            padding: 12px;
        }

        .dashboard-header-modern {
            flex-direction: column;
            align-items: flex-start;
            padding: 20px;
            border-radius: 16px;
        }

        .stats-grid-modern {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .stat-card-modern {
            padding: 16px;
        }

        .admin-grid-layout {
            grid-template-columns: 1fr !important;
            gap: 20px;
        }

        /* Allow tabs to scroll or wrap without disappearing */
        .tab-container {
            display: flex !important;
            flex-wrap: nowrap;
            overflow-x: auto;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
    }

    @media (max-width: 768px) {
        .glass-panel-dash {
            padding: 18px !important;
        }

        .tab-container {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--bg-void);
            padding: 10px 10px 5px;
            margin: 0 -16px 20px;
        }

        .tab-btn {
            padding: 10px 16px;
            font-size: 0.75rem;
        }

        /* Mobile Table-to-Card Transformation */
        .modern-table,
        .modern-table tbody,
        .modern-table tr,
        .modern-table td {
            display: block;
            width: 100%;
            box-sizing: border-box;
        }

        .modern-table thead {
            display: none;
        }

        .modern-table tr {
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modern-table td {
            text-align: right !important;
            padding: 12px 0 12px 110px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
            min-height: 48px;
            word-break: break-word;
        }

        .modern-table td:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .modern-table td:first-child {
            padding-top: 0;
        }

        .modern-table td::before {
            content: attr(data-label);
            position: absolute;
            left: 0;
            top: 14px;
            width: 100px;
            text-align: left;
            font-size: 0.65rem;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }

        .modern-table td[data-label="Participant"] {
            padding-left: 0 !important;
            text-align: left !important;
        }

        .modern-table td[data-label="Participant"]::before {
            display: none;
        }

        .modern-input,
        .modern-select,
        .modern-textarea {
            padding: 10px 14px;
            font-size: 0.95rem;
            /* Larger font size prevents iOS auto-zoom */
        }
    }

    /* Tab Navigation */
    .tab-container {
        display: flex;
        gap: 8px;
        margin-bottom: 30px;
        padding-bottom: 2px;
        overflow-x: auto;
        scrollbar-width: none;
    }

    .tab-container::-webkit-scrollbar {
        display: none;
    }

    .tab-btn {
        background: rgba(15, 22, 41, 0.3);
        border: 1px solid var(--border);
        color: var(--text-dim);
        padding: 12px 24px;
        font-family: 'Space Grotesk', sans-serif;
        font-weight: 700;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.25s ease;
        border-radius: 12px;
        white-space: nowrap;
    }

    .tab-btn:hover {
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-primary);
    }

    .tab-btn.active {
        background: rgba(124, 58, 237, 0.12);
        border-color: rgba(124, 58, 237, 0.3);
        color: #a855f7;
    }

    /* Form Components */
    .modern-label {
        display: block;
        font-size: 0.75rem;
        color: var(--text-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }

    .modern-input,
    .modern-select,
    .modern-textarea {
        width: 100%;
        padding: 12px 16px;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid var(--border);
        color: white;
        border-radius: 10px;
        font-size: 0.85rem;
        transition: all 0.3s ease;
    }

    /* Table Styling */
    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .modern-table th {
        padding: 16px;
        text-align: left;
        font-size: 0.7rem;
        color: var(--text-dim);
        text-transform: uppercase;
        letter-spacing: 1px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .modern-table td {
        padding: 16px;
        border-bottom: 1px solid var(--border);
        font-size: 0.85rem;
    }

    /* Buttons */
    .btn-icon-danger {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: 1px solid var(--danger);
        width: 32px;
        height: 32px;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Status Select */
    .status-select {
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 700;
        border: 1px solid var(--border);
        background-color: rgba(15, 22, 41, 0.5);
        color: white;
    }

    .status-select.pending {
        color: var(--accent-1);
    }

    .status-select.approved {
        color: var(--accent-3);
    }

    .status-select.winner {
        color: var(--accent-5);
    }

    /* Accordion Styles */
    .event-group-panel:hover .accordion-caret {
        transform: scale(1.2);
    }

    .event-group-panel.active .accordion-caret {
        transform: rotate(90deg);
    }

    .event-group-panel.active {
        border-color: rgba(124, 58, 237, 0.3);
        background: rgba(255, 255, 255, 0.03);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.onload = function () {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        const editId = urlParams.get('edit');

        if (tab) showTab(tab);

        if (editId && tab === 'events') {
            // Find event data in the PHP-generated $all_events array
            const allEvents = <?= json_encode($all_events) ?>;
            const eventToEdit = allEvents.find(e => e.id == editId);
            if (eventToEdit) {
                editEvent(eventToEdit);
            }
        }
    }
    function showTab(t) {
        document.querySelectorAll('.admin-tab-content').forEach(c => {
            c.style.opacity = '0';
            c.style.display = 'none';
        });
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        // Also update sidebar links active state
        document.querySelectorAll('.menu-link-dash').forEach(l => {
            if (l.getAttribute('onclick')) l.classList.remove('active');
            if (l.getAttribute('data-tab') === t) l.classList.add('active');
        });

        const target = document.getElementById('content-' + t);
        if (target) {
            target.style.display = 'block';
            setTimeout(() => target.style.opacity = '1', 50);
        }

        const tabBtn = document.getElementById('tab-' + t);
        if (tabBtn) tabBtn.classList.add('active');

        // Update URL without reload
        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?tab=' + t;
        window.history.pushState({ path: newUrl }, '', newUrl);
    }

    function editEvent(ev) {
        showTab('events');
        document.getElementById('ev-form-title').innerText = 'Update Competition Track';
        document.getElementById('ev-action').value = 'update_event';
        document.getElementById('ev-id').value = ev.id;
        document.getElementById('ev-name').value = ev.name;
        document.getElementById('ev-desc').value = ev.description || '';
        document.getElementById('ev-cat').value = ev.category;
        document.getElementById('ev-eligibility').value = ev.eligibility_stream || 'ALL';
        document.getElementById('ev-max').value = ev.max_participants;

        // Handle Multiple Coordinators
        const coordCheckboxes = document.querySelectorAll('.ev-coord-checkbox');
        coordCheckboxes.forEach(cb => cb.checked = false);
        if (ev.coordinator_id) {
            const ids = ev.coordinator_id.split(',');
            coordCheckboxes.forEach(cb => {
                if (ids.includes(cb.value)) cb.checked = true;
            });
        }
        updateCoordCount();
        document.getElementById('ev-date').value = ev.date;
        document.getElementById('ev-time').value = ev.time;
        document.getElementById('ev-venue').value = ev.venue;
        document.getElementById('ev-rules').value = ev.rules;
        document.getElementById('ev-existing-logo').value = ev.image || '';

        const preview = document.getElementById('logo-preview');
        if (ev.image) {
            preview.src = ev.image;
            document.getElementById('logo-preview-container').style.display = 'block';
        } else {
            document.getElementById('logo-preview-container').style.display = 'none';
        }

        document.getElementById('ev-submit').innerText = 'SAVE CHANGES';
        document.getElementById('ev-submit').style.background = 'linear-gradient(135deg, var(--secondary), var(--primary))';
        document.getElementById('ev-form').scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Populate team fields
        const isTeam = ev.is_team_event == 1;
        document.getElementById('ev-is-team').checked = isTeam;
        document.getElementById('ev-min-ts').value = ev.min_team_size || 2;
        document.getElementById('ev-max-ts').value = ev.max_team_size || 4;
        toggleEvTeamFields();

        // Highlight form momentarily
        const formPanel = document.getElementById('ev-form').closest('.glass-panel-dash');
        formPanel.style.boxShadow = '0 0 20px rgba(99, 102, 241, 0.4)';
        setTimeout(() => { formPanel.style.boxShadow = ''; }, 1500);
    }

    function previewLogo(input) {
        if (input.files && input.files[0]) {
            // Update the span text to show the file name
            const labelSpan = input.closest('.mobile-file-upload').querySelector('span');
            labelSpan.innerText = input.files[0].name;
            labelSpan.style.color = 'var(--accent-1)';

            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('logo-preview').src = e.target.result;
                document.getElementById('logo-preview-container').style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function resetEvForm() {
        document.getElementById('ev-form-title').innerText = 'Deploy New Event Track';
        document.getElementById('ev-action').value = 'create_event';
        document.getElementById('ev-form').reset();
        document.getElementById('ev-existing-logo').value = '';
        document.getElementById('logo-preview-container').style.display = 'none';
        document.getElementById('ev-submit').innerText = 'DEPLOY TRACK';
        document.getElementById('ev-submit').style.background = 'linear-gradient(135deg, var(--primary), var(--secondary))';
        document.getElementById('ev-is-team').checked = false;
        document.getElementById('ev-team-size-fields').style.display = 'none';

        // Clear multiple coordinator selection
        const coordCheckboxes = document.querySelectorAll('.ev-coord-checkbox');
        coordCheckboxes.forEach(cb => cb.checked = false);
        const searchInput = document.getElementById('coord-search');
        if (searchInput) { searchInput.value = ''; filterCoordinators(); }
        updateCoordCount();
    }

    function toggleEvTeamFields() {
        const isTeam = document.getElementById('ev-is-team').checked;
        document.getElementById('ev-team-size-fields').style.display = isTeam ? 'grid' : 'none';
    }

    function filterCoordinators() {
        const filter = document.getElementById('coord-search').value.toLowerCase();
        const labels = document.querySelectorAll('.coord-label-item');
        labels.forEach(label => {
            const name = label.querySelector('.coord-name-text').textContent.toLowerCase();
            const id = label.querySelector('.coord-id-text').textContent.toLowerCase();
            if (name.includes(filter) || id.includes(filter)) {
                label.style.display = 'flex';
            } else {
                label.style.display = 'none';
            }
        });
    }

    function clearCoordSelection() {
        const coordCheckboxes = document.querySelectorAll('.ev-coord-checkbox');
        coordCheckboxes.forEach(cb => cb.checked = false);
        updateCoordCount();
    }

    function updateCoordCount() {
        const selected = document.querySelectorAll('.ev-coord-checkbox:checked').length;
        document.getElementById('coord-count-text').innerText = selected + ' selected';
    }

    // Interactive Charts Optimization
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.font.family = 'Plus Jakarta Sans';

    // Check if chart element exists before initializing
    const chartCtx = document.getElementById('participationChart');
    if (chartCtx) {
        new Chart(chartCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($participation_data, 'name') ?: []) ?>,
                datasets: [{
                    label: 'Entries',
                    data: <?= json_encode(array_column($participation_data, 'count') ?: []) ?>,
                    backgroundColor: 'rgba(99, 102, 241, 0.5)',
                    borderColor: '#6366f1',
                    borderWidth: 2,
                    borderRadius: 8,
                    hoverBackgroundColor: 'rgba(168, 85, 247, 0.7)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { precision: 0 } },
                    x: { grid: { display: false } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.9)',
                        titleFont: { size: 14, family: 'Outfit' },
                        bodyFont: { size: 14, weight: 'bold' },
                        padding: 12,
                        cornerRadius: 8,
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1
                    }
                },
                animation: { duration: 2000, easing: 'easeOutQuart' }
            }
        });
    }

    function toggleAccordion(id, header) {
        const content = document.getElementById(id);
        const panel = header.parentElement;
        const isHidden = content.style.display === 'none';

        // Toggle visibility
        content.style.display = isHidden ? 'block' : 'none';

        // Toggle active class for caret rotation
        if (isHidden) {
            panel.classList.add('active');
        } else {
            panel.classList.remove('active');
        }
    }
</script>

<?php include 'includes/footer.php'; ?>