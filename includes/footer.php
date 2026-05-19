    <?php 
    $user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
    $current_page = basename($_SERVER['PHP_SELF']);
    $dashboard_pages = ['dashboard.php', 'coordinator.php', 'admin.php', 'admin_explore_events.php', 'admin_leaderboard.php', 'admin_users.php', 'events.php', 'leaderboard.php'];
    $is_dashboard_page = ($user !== null && in_array($current_page, $dashboard_pages));
    ?>

    <?php if ($is_dashboard_page): ?>
                </div> <!-- Close content-body -->
                <footer style="margin-top: auto; padding: 30px 40px; text-align: center; border-top: 1px solid var(--border); color: var(--text-dim); font-size: 0.7rem; line-height: 1.6;">
                    <p style="margin-bottom: 4px; font-weight: 500;">Created by <span style="color: var(--accent-1); font-weight: 700;">Team of CodeGeeks</span></p>
                    <p style="margin-bottom: 12px; font-size: 0.65rem; opacity: 0.8;">Milagres College Kallianpur</p>
                    <p style="letter-spacing: 1px; text-transform: uppercase; font-weight: 700; color: var(--text-muted);">&copy; 2026 NEXUS FEST_PROTOCOLS. ALL RIGHTS RESERVED.</p>
                </footer>
            </main> <!-- Close main-content-dash -->
        </div> <!-- Close app-wrapper -->
    <?php else: ?>
        </div> <!-- Close Container -->
        <footer style="margin-top: 80px; padding: 50px 24px 30px; text-align: center; border-top: 1px solid var(--border); background: rgba(4, 6, 14, 0.95); backdrop-filter: blur(20px); position: relative; overflow: hidden;">
            <div style="position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 200px; height: 1px; background: linear-gradient(90deg, transparent, var(--accent-1), var(--accent-2), transparent);"></div>
            <p style="font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 1.1rem; margin-bottom: 8px;">
                <span style="background: var(--grad-primary); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">NEXUS FEST 2026</span>
            </p>
            <p style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; margin-bottom: 4px;">Created by Team of CodeGeeks</p>
            <p style="font-size: 0.7rem; color: var(--text-dim); margin-bottom: 18px;">Milagres College Kallianpur</p>
            <p style="font-size: 0.65rem; color: var(--text-muted); letter-spacing: 2px; text-transform: uppercase;">&copy; 2026 NEXUS FEST_PROTOCOLS. ALL RIGHTS RESERVED.</p>
        </footer>
    <?php endif; ?>

    <script src="assets/js/script.js"></script>
</body>
</html>

