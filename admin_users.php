<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
include 'includes/header.php'; 

$msg = '';
$error = '';

// Handle Role Updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_role') {
        $u_id = $_POST['user_id'];
        $new_role = $_POST['role'];
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $stmt->execute([$new_role, $u_id]);
        $msg = "ACCESS LEVEL UPDATED FOR USER: " . $u_id;
    }
    elseif ($_POST['action'] === 'delete_user') {
        $u_id = $_POST['user_id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$u_id]);
        $msg = "USER REMOVED FROM GLOBAL DIRECTORY.";
    }
}

// Search Logic
$search = $_GET['q'] ?? '';
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE name LIKE ? OR user_id LIKE ? OR email LIKE ? ORDER BY role DESC, created_at DESC");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY role DESC, created_at DESC");
}
$all_users = $stmt->fetchAll();
?>

<div style="padding: 40px;">
    <div class="dashboard-header" style="margin-bottom: 40px;">
        <div class="header-content">
            <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); padding: 5px 12px; border-radius: 50px; margin-bottom: 12px;">
                <i class="fa-solid fa-users-gear" style="font-size: 0.7rem; color: var(--success);"></i>
                <span style="font-size: 0.65rem; font-weight: 800; color: var(--success); text-transform: uppercase; letter-spacing: 1.5px;">Identity Vault</span>
            </div>
            <h1 style="font-family: 'Space Grotesk', sans-serif; font-size: 2.2rem; font-weight: 700; margin: 0;">User Directory</h1>
            <p style="color: var(--text-dim); margin-top: 8px;">Manage permissions, verify identities, and control platform access.</p>
        </div>
        <div class="header-actions" style="display: flex; gap: 15px;">
             <form method="GET" style="position: relative;">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search users..." style="padding: 12px 18px 12px 42px; background: rgba(0,0,0,0.2); border: 1px solid var(--border); border-radius: 12px; color: white; font-size: 0.85rem; width: 260px;">
                <i class="fa-solid fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-dim); font-size: 0.9rem;"></i>
             </form>
        </div>
    </div>

    <?php if($msg): ?>
        <div style="margin-bottom: 30px; padding: 16px 20px; background: rgba(16, 185, 129, 0.06); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); border-radius: 12px; font-size: 0.88rem; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-circle-check"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <div class="glass-panel" style="padding: 0; overflow: hidden; border-color: rgba(16, 185, 129, 0.1);">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <th style="padding: 16px 30px; text-align: left; color: var(--text-dim); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1.5px;">User Profile</th>
                        <th style="padding: 16px 30px; text-align: left; color: var(--text-dim); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1.5px;">Contact Details</th>
                        <th style="padding: 16px 30px; text-align: center; color: var(--text-dim); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1.5px;">Access Role</th>
                        <th style="padding: 16px 30px; text-align: right; color: var(--text-dim); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1.5px;">Manage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($all_users as $u): ?>
                    <tr style="border-bottom: 1px solid var(--border); transition: 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.01)'" onmouseout="this.style.background='transparent'">
                        <td style="padding: 18px 30px;">
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <div style="width: 42px; height: 42px; border-radius: 12px; background: <?= $u['role'] == 'admin' ? 'var(--grad-primary)' : ($u['role'] == 'coordinator' ? 'var(--grad-cool)' : 'rgba(255,255,255,0.05)') ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; color: white;">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($u['name']) ?></div>
                                    <div style="font-size: 0.72rem; color: var(--text-dim); font-family: 'JetBrains Mono', monospace;"><?= $u['user_id'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 18px 30px;">
                            <div style="font-size: 0.88rem; color: var(--text-secondary);"><?= htmlspecialchars($u['email']) ?></div>
                            <div style="font-size: 0.7rem; color: var(--text-dim); margin-top: 2px;"><?= htmlspecialchars($u['phone']) ?></div>
                        </td>
                        <td style="padding: 18px 30px; text-align: center;">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_role">
                                <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                <select name="role" onchange="this.form.submit()" style="padding: 6px 12px; background: rgba(0,0,0,0.2); border: 1px solid var(--border); color: white; border-radius: 8px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; cursor: pointer;">
                                    <option value="user" <?= $u['role'] == 'user' ? 'selected' : '' ?>>Student</option>
                                    <option value="coordinator" <?= $u['role'] == 'coordinator' ? 'selected' : '' ?>>Coordinator</option>
                                    <option value="admin" <?= $u['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </form>
                        </td>
                        <td style="padding: 18px 30px; text-align: right;">
                            <form method="POST" onsubmit="return confirm('Account termination: <?= htmlspecialchars($u['name']) ?>. Proceed?')" style="display:inline;">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                <button type="submit" class="btn-coord" style="width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 10px; color: var(--danger); border-color: rgba(244, 63, 94, 0.2);">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($all_users)): ?>
                        <tr><td colspan="4" style="padding: 60px; text-align: center; color: var(--text-dim);">No users match your search criteria.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
