<?php
// Performance Optimization: Gzip compression via output buffering
if (!in_array('ob_gzhandler', ob_list_handlers()) && !ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
} else {
    ob_start();
}

// Session Security Configuration
if (session_status() === PHP_SESSION_NONE) {
    // Only set params before session starts
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']), // Enable if HTTPS is on
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    if (function_exists('random_bytes')) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

function isActive($page)
{
    return basename($_SERVER['PHP_SELF']) == $page ? 'active' : '';
}

$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
$current_page = basename($_SERVER['PHP_SELF']);
$dashboard_pages = ['dashboard.php', 'coordinator.php', 'admin.php', 'admin_explore_events.php', 'admin_leaderboard.php', 'admin_users.php', 'events.php', 'leaderboard.php'];
$is_dashboard_page = ($user !== null && in_array($current_page, $dashboard_pages));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEXUS FEST 2026 | BCA IT Fest</title>
    <meta name="description"
        content="NEXUS FEST 2026 — The premier BCA IT Fest celebrating innovation, code, and technology. Register today!">
    <link rel="stylesheet" href="assets/css/styles.css">
    <?php if ($current_page == 'index.php'): ?>
        <link rel="stylesheet" href="assets/css/splash.css">
        <script src="assets/js/splash.js" defer></script>
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" href="assets/img/loogo - Edited.png">
</head>

<body>

    <?php if ($current_page == 'index.php'): ?>
        <!-- ═══════════════════════════════════════════
         NEXUS FEST SPLASH SCREEN
         ═══════════════════════════════════════════ -->
        <div id="splash-screen">
            <div class="splash-content">
                <div class="splash-logo-container">
                    <div class="splash-glow"></div>
                    <img src="assets/img/loogo - Edited.png" alt="Nexus Fest Logo" class="splash-logo">
                </div>
                <div class="splash-text">
                    <h1 class="splash-title">NEXUS FEST</h1>
                    <p class="splash-subtitle">BCA | BCOM | BA • IT FEST 2026</p>
                </div>
                <div class="loader-bar">
                    <div class="loader-progress"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($is_dashboard_page): ?>
        <!-- ═══════════════════════════════════════════
             DASHBOARD LAYOUT — BCA NEXUS FEST COMMAND CENTER
             ═══════════════════════════════════════════ -->
        <div class="bg-gradient-dash"></div>

        <!-- Mobile Bottom Nav - REPLACED HAMBURGER PER REQUEST -->
        <div class="mobile-nav-bar">
            <a href="index.php" class="mobile-nav-item <?= isActive('index.php') ?>">
                <i class="fa-solid fa-house"></i>
                <span>Home</span>
            </a>
            <a href="events.php" class="mobile-nav-item <?= isActive('events.php') ?>">
                <i class="fa-solid fa-trophy"></i>
                <span>Events</span>
            </a>
            <a href="dashboard.php" class="mobile-nav-item <?= isActive('dashboard.php') ?>">
                <i class="fa-solid fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="leaderboard.php" class="mobile-nav-item <?= isActive('leaderboard.php') ?>">
                <i class="fa-solid fa-ranking-star"></i>
                <span>Ranks</span>
            </a>
            <?php if ($user && ($user['role'] === 'coordinator' || $user['role'] === 'admin')): ?>
                <a href="<?= $user['role'] === 'admin' ? 'admin.php' : 'coordinator.php' ?>"
                    class="mobile-nav-item <?= isActive('admin.php') || isActive('coordinator.php') ?>">
                    <i class="fa-solid fa-user-shield"></i>
                    <span>Staff</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- Mobile Top Bar - HAMBURGER REMOVED -->
        <div class="mobile-top-bar" style="z-index: 1002;">
            <div style="display: flex; align-items: center; gap: 5px;">
                <a href="index.php"
                    style="text-decoration: none; font-family: 'Space Grotesk', sans-serif; font-weight: 700; color: white; display: flex; align-items: center; gap: 10px; margin-left: 5px;">
                    <img src="assets/img/loogo - Edited.png" alt="Logo" style="width: 32px; height: 32px; object-fit: contain; border-radius: 8px;">
                    <span style="letter-spacing: -0.5px;">NEXUS FEST</span>
                </a>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <a href="logout.php"
                    style="color: var(--danger); font-size: 1.1rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: rgba(239, 68, 68, 0.05); border-radius: 10px;"
                    title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </div>

        <div class="app-wrapper">
            <!-- Sidebar -->
            <aside class="sidebar">
                <a href="index.php" class="sidebar-brand" style="text-decoration: none;">
                    <img src="assets/img/loogo - Edited.png" alt="Logo" style="width: 44px; height: 44px; object-fit: contain; border-radius: 12px; margin-right: 12px;">
                    <div class="brand-name" style="font-family: 'Space Grotesk', sans-serif;">
                        <h2 style="font-size: 1.1rem; font-weight: 700; letter-spacing: -0.5px; text-transform: none;">
                            NEXUS FEST</h2>
                        <p
                            style="font-size: 0.65rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 2px;">
                            BCA IT FEST</p>
                    </div>
                </a>

                <ul class="sidebar-menu">
                    <!-- MAIN SECTION -->
                    <?php if ($user && $user['role'] !== 'admin'): ?>
                        <div class="menu-label-dash">MAIN</div>
                        <li class="menu-item">
                            <a href="index.php" class="menu-link-dash">
                                <i class="fa-solid fa-house"></i>
                                <span>Home Page</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="dashboard.php" class="menu-link-dash <?= isActive('dashboard.php') ?>">
                                <i class="fa-solid fa-chart-line"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="events.php" class="menu-link-dash <?= isActive('events.php') ?>">
                                <i class="fa-solid fa-trophy"></i>
                                <span>Explore Events</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="leaderboard.php" class="menu-link-dash <?= isActive('leaderboard.php') ?>">
                                <i class="fa-solid fa-ranking-star"></i>
                                <span>Leaderboard</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($user): ?>
                        <?php if ($user['role'] === 'coordinator'): ?>
                            <!-- COORDINATOR PANEL -->
                            <div class="menu-label-dash">COORDINATOR</div>
                            <li class="menu-item">
                                <a href="coordinator.php" class="menu-link-dash <?= isActive('coordinator.php') ?>">
                                    <i class="fa-solid fa-calendar-check"></i>
                                    <span>Assigned Events</span>
                                </a>
                            </li>

                        <?php endif; ?>

                        <?php if ($user['role'] === 'admin'): ?>
                            <!-- ADMINISTRATION -->
                            <div class="menu-label-dash">ADMINISTRATION</div>
                            <li class="menu-item">
                                <a href="<?= $current_page == 'admin.php' ? 'javascript:void(0)' : 'admin.php?tab=dashboard' ?>"
                                    onclick="<?= $current_page == 'admin.php' ? 'showTab(\'dashboard\')' : '' ?>"
                                    data-tab="dashboard"
                                    class="menu-link-dash <?= isActive('admin.php') && (!isset($_GET['tab']) || $_GET['tab'] == 'dashboard') ? 'active' : '' ?>">
                                    <i class="fa-solid fa-chart-pie"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="<?= $current_page == 'admin.php' ? 'javascript:void(0)' : 'admin.php?tab=users' ?>"
                                    onclick="<?= $current_page == 'admin.php' ? 'showTab(\'users\')' : '' ?>"
                                    data-tab="users"
                                    class="menu-link-dash <?= isset($_GET['tab']) && $_GET['tab'] == 'users' ? 'active' : '' ?>">
                                    <i class="fa-solid fa-users-gear"></i>
                                    <span>User Management</span>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="<?= $current_page == 'admin.php' ? 'javascript:void(0)' : 'admin.php?tab=events' ?>"
                                    onclick="<?= $current_page == 'admin.php' ? 'showTab(\'events\')' : '' ?>"
                                    data-tab="events"
                                    class="menu-link-dash <?= isset($_GET['tab']) && $_GET['tab'] == 'events' ? 'active' : '' ?>">
                                    <i class="fa-solid fa-calendar-alt"></i>
                                    <span>Event Management</span>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="<?= $current_page == 'admin.php' ? 'javascript:void(0)' : 'admin.php?tab=regs' ?>"
                                    onclick="<?= $current_page == 'admin.php' ? 'showTab(\'regs\')' : '' ?>"
                                    data-tab="regs"
                                    class="menu-link-dash <?= isset($_GET['tab']) && $_GET['tab'] == 'regs' ? 'active' : '' ?>">
                                    <i class="fa-solid fa-clipboard-check"></i>
                                    <span>Registration Desk</span>
                                </a>
                            </li>

                            <div class="menu-label-dash" style="margin-top: 20px;">QUICK LINKS</div>
                            <li class="menu-item">
                                <a href="admin_explore_events.php"
                                    class="menu-link-dash <?= isActive('admin_explore_events.php') ?>">
                                    <i class="fa-solid fa-compass"></i>
                                    <span>Explore Tracks</span>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="admin_leaderboard.php" class="menu-link-dash <?= isActive('admin_leaderboard.php') ?>">
                                    <i class="fa-solid fa-crown"></i>
                                    <span>Leaderboard</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>


                    <!-- ACCOUNT SECTION -->
                    <div class="menu-label-dash">ACCOUNT</div>
                    <?php if ($user && $user['role'] !== 'admin'): ?>
                        <li class="menu-item">
                            <a href="dashboard.php" class="menu-link-dash">
                                <i class="fa-solid fa-user-circle"></i>
                                <span>My Profile</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="menu-item">
                        <a href="logout.php" class="menu-link-dash" style="color: var(--danger)">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </aside>

            <!-- Mobile Overlay -->
            <div id="sidebar-overlay" onclick="toggleSidebar()"
                style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 1000; display: none; opacity: 0; transition: opacity 0.3s ease;">
            </div>

            <script>
                function toggleSidebar() {
                    const sidebar = document.querySelector('.sidebar');
                    const overlay = document.getElementById('sidebar-overlay');
                    const isOpen = sidebar.classList.contains('open');

                    if (isOpen) {
                        sidebar.classList.remove('open');
                        overlay.style.opacity = '0';
                        setTimeout(() => { overlay.style.display = 'none'; }, 300);
                        document.body.style.overflow = '';
                    } else {
                        overlay.style.display = 'block';
                        setTimeout(() => { overlay.style.opacity = '1'; }, 10);
                        sidebar.classList.add('open');
                        document.body.style.overflow = 'hidden';
                    }
                }
            </script>

            <!-- Main Content Section -->
            <main class="main-content-dash">
                <!-- Content injected here -->
                <div class="content-body" style="flex: 1;">
                <?php else: ?>
                    <!-- ═══════════════════════════════════════════
             PUBLIC PAGES — BCA NEXUS FEST THEME
             ═══════════════════════════════════════════ -->
                    <div class="cyber-grid"></div>
                    <div class="glow-bg"></div>
                    <div class="scanline"></div>

                    <nav>
                        <a href="index.php" class="logo neon-text-blue">NEXUS FEST</a>
                        <button class="nav-hamburger"
                            onclick="document.querySelector('.nav-links').classList.toggle('open')">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <ul class="nav-links">
                            <li><a href="index.php" class="<?= isActive('index.php') ?>">Home</a></li>
                            <li><a href="events.php" class="<?= isActive('events.php') ?>">Events</a></li>
                            <li><a href="leaderboard.php" class="<?= isActive('leaderboard.php') ?>">Leaderboard</a></li>
                            <?php if ($user): ?>
                                <li><a href="dashboard.php" class="<?= isActive('dashboard.php') ?>">Dashboard</a></li>
                                <?php if ($user['role'] === 'coordinator'): ?>
                                    <li><a href="coordinator.php"
                                            class="neon-text-purple <?= isActive('coordinator.php') ?>">Coordinator</a></li>
                                <?php endif; ?>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <li><a href="admin.php" class="neon-text-pink <?= isActive('admin.php') ?>">Admin</a></li>
                                <?php endif; ?>
                                <li><a href="logout.php" style="color: var(--danger)">Logout</a></li>
                            <?php else: ?>
                                <li><a href="login.php" class="<?= isActive('login.php') ?>">Login</a></li>
                                <li><a href="register.php" class="btn-neon"
                                        style="padding: 10px 24px; font-size: 0.75rem">Register</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <div class="container">
                    <?php endif; ?>