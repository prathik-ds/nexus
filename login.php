<?php
include 'includes/header.php';

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        header('Location: dashboard.php');
    } else {
        $error = "Invalid Email or Password.";
    }
}
?>

<div style="min-height: 70vh; display: flex; align-items: center; justify-content: center;">
    <div style="max-width: 420px; width: 100%;">
        <!-- Login Card -->
        <div class="glass" style="padding: 44px 36px; border-color: rgba(124, 58, 237, 0.15);">
            <!-- Header -->
            <div style="text-align: center; margin-bottom: 36px;">
                <div style="margin-bottom: 18px;">
                    <img src="assets/img/loogo - Edited.png" alt="Logo" style="width: 72px; height: 72px; object-fit: contain;">
                </div>
                <h2
                    style="font-family: 'Space Grotesk', sans-serif; font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; background: var(--grad-primary); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">
                    Welcome Back</h2>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Sign in to your Nexus Fest account</p>
            </div>

            <?php if ($error): ?>
                <div
                    style="padding: 12px 16px; border-radius: 12px; background: rgba(244, 63, 94, 0.08); border: 1px solid rgba(244, 63, 94, 0.2); color: var(--danger); margin-bottom: 24px; font-size: 0.85rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label><i class="fa-solid fa-envelope" style="margin-right: 6px; color: var(--accent-1);"></i>Email
                        Address</label>
                    <input type="email" name="email" placeholder="you@nexusfest.com" required>
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-lock"
                            style="margin-right: 6px; color: var(--accent-2);"></i>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-neon"
                    style="width: 100%; margin-top: 10px; padding: 14px; background: rgba(124, 58, 237, 0.08); border-color: var(--accent-2); color: var(--accent-2);">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In
                </button>
            </form>

            <div style="text-align: center; margin-top: 28px;">
                <p style="font-size: 0.85rem; color: var(--text-muted);">
                    New to Nexus Fest?
                    <a href="register.php"
                        style="color: var(--accent-1); text-decoration: none; font-weight: 700;">Create Account →</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>