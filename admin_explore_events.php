<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
include 'includes/header.php';

$msg = '';
$error = '';

// Handle Event Management (Delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_event') {
        $id = $_POST['event_id'];
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "EVENT REMOVED FROM TRACK LIST.";
    }
}

$stmt = $pdo->query("SELECT * FROM events ORDER BY category, name");
$events = $stmt->fetchAll();
?>

<!-- Admin Explore Header -->
<div style="padding: 40px;">
    <div class="dashboard-header" style="margin-bottom: 40px;">
        <div class="header-content">
            <div
                style="display: inline-flex; align-items: center; gap: 8px; background: rgba(0, 212, 255, 0.08); border: 1px solid rgba(0, 212, 255, 0.2); padding: 5px 12px; border-radius: 50px; margin-bottom: 12px;">
                <i class="fa-solid fa-compass" style="font-size: 0.7rem; color: var(--accent-1);"></i>
                <span
                    style="font-size: 0.65rem; font-weight: 800; color: var(--accent-1); text-transform: uppercase; letter-spacing: 1.5px;">Tournament
                    Manager</span>
            </div>
            <h1 style="font-family: 'Space Grotesk', sans-serif; font-size: 2.2rem; font-weight: 700; margin: 0;">
                Explore Competition Tracks</h1>
            <p style="color: var(--text-dim); margin-top: 8px;">View all active competitions through the lens of a
                student while maintaining admin control.</p>
        </div>
        <div class="header-actions">
            <a href="admin.php?tab=events" class="btn-start-dash"
                style="text-decoration: none; width: auto; padding: 12px 24px;">
                <i class="fa-solid fa-plus"></i> DEPLOY NEW TRACK
            </a>
        </div>
    </div>

    <?php if ($msg): ?>
        <div
            style="margin-bottom: 30px; padding: 16px 20px; background: rgba(16, 185, 129, 0.06); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); border-radius: 12px; font-size: 0.88rem; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-circle-check"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <!-- Events Grid -->
    <div
        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 32px; padding: 20px 0 60px;">
        <?php foreach ($events as $index => $event): ?>
            <div class="glass event-card-premium"
                style="padding: 0; display: flex; flex-direction: column; overflow: hidden; position: relative; transition: 0.3s; border-color: rgba(0, 212, 255, 0.08); margin-bottom: 20px;">

                <!-- Card Image / Header -->
                <div style="height: 160px; position: relative; overflow: hidden; background: var(--bg-card);">
                    <?php if ($event['image']): ?>
                        <img src="<?= $event['image'] ?>" alt="<?= htmlspecialchars($event['name']) ?>"
                            style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;"
                            class="card-img-top">
                    <?php else: ?>
                        <div
                            style="width: 100%; height: 100%; background: var(--grad-primary); opacity: 0.15; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-microchip"
                                style="font-size: 2.5rem; color: var(--accent-1); opacity: 0.4;"></i>
                        </div>
                    <?php endif; ?>

                    <!-- Overlay Gradient -->
                    <div
                        style="position: absolute; bottom: 0; left: 0; right: 0; height: 100%; background: linear-gradient(to bottom, transparent 0%, rgba(10, 15, 30, 0.9) 100%);">
                    </div>

                    <!-- Category Badge -->
                    <div
                        style="position: absolute; top: 12px; left: 12px; display: inline-flex; align-items: center; gap: 6px; background: rgba(10, 15, 30, 0.6); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); padding: 5px 14px; border-radius: 50px; z-index: 2;">
                        <span
                            style="font-size: 0.55rem; font-weight: 800; color: white; text-transform: uppercase; letter-spacing: 1.5px;"><?= $event['category'] ?></span>
                    </div>

                    <!-- Admin Actions Overlay -->
                    <div style="position: absolute; top: 12px; right: 12px; display: flex; gap: 8px; z-index: 3;">
                        <a href="admin.php?tab=events&edit=<?= $event['id'] ?>" class="btn-coord"
                            style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; border-radius: 8px; background: rgba(124, 58, 237, 0.2);"
                            title="Edit Event">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <form method="POST" onsubmit="return confirm('Truly delete this track? This cannot be undone.')">
                            <input type="hidden" name="action" value="delete_event">
                            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                            <button type="submit" class="btn-coord"
                                style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; border-radius: 8px; color: var(--danger); border-color: rgba(244, 63, 94, 0.2); background: rgba(244, 63, 94, 0.1);"
                                title="Delete Event">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <div style="padding: 24px;">
                    <h3
                        style="font-family: 'Space Grotesk', sans-serif; font-size: 1.3rem; margin-bottom: 10px; color: var(--text-primary); font-weight: 700;">
                        <?= htmlspecialchars($event['name']) ?>
                    </h3>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.6; min-height: 48px;">
                        <?= htmlspecialchars($event['description'] ?: 'No description available for this tournament track.') ?>
                    </p>
                </div>

                <div style="padding: 0 28px 24px; margin-top: auto;">
                    <div
                        style="display: flex; flex-direction: column; gap: 12px; padding-top: 18px; border-top: 1px solid var(--border);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.75rem; color: var(--text-dim);"><i class="fa-solid fa-user-tie"
                                    style="margin-right: 8px;"></i> Coordinator</span>
                            <span
                                style="font-size: 0.78rem; font-weight: 600; color: var(--text-secondary);"><?= $event['coordinator_name'] ?: 'System' ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.75rem; color: var(--text-dim);"><i class="fa-solid fa-location-dot"
                                    style="margin-right: 8px;"></i> Venue</span>
                            <span
                                style="font-size: 0.78rem; font-weight: 600; color: var(--text-secondary);"><?= $event['venue'] ?: 'TBD' ?></span>
                        </div>
                        <div style="margin-top: 8px;">
                            <div
                                style="display: flex; justify-content: space-between; font-size: 0.7rem; color: var(--text-dim); margin-bottom: 6px;">
                                <span>Track Capacity</span>
                                <span
                                    style="font-weight: 700; color: var(--accent-1);"><?= $event['current_participants'] ?>/<?= $event['max_participants'] ?></span>
                            </div>
                            <div
                                style="width: 100%; height: 4px; background: rgba(255,255,255,0.05); border-radius: 4px; overflow: hidden;">
                                <?php $fill = $event['max_participants'] > 0 ? ($event['current_participants'] / $event['max_participants']) * 100 : 0; ?>
                                <div
                                    style="width: <?= $fill ?>%; height: 100%; background: var(--grad-primary); border-radius: 4px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>