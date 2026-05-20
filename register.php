<?php
include 'includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $course = $_POST['course'] ?? '';
    $year = $_POST['year'] ?? '';
    $roll_no = $_POST['roll_no'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Name, Email, and Password are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Unique ID generation
        $user_id = 'NXS-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (user_id, name, email, phone, course, year, roll_no, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $name, $email, $phone, $course, $year, $roll_no, $hashedPassword]);
            
            // Auto-login after registration
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $newUser = $stmt->fetch();
            
            if ($newUser) {
                $_SESSION['user'] = $newUser;
                header('Location: dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Email or User ID already exists.";
            } else {
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>

<style>
    .auth-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0 16px;
    }
    .auth-grid-full {
        grid-column: span 2;
    }
    @media (max-width: 500px) {
        .auth-grid {
            grid-template-columns: 1fr;
        }
        .auth-grid-full {
            grid-column: span 1;
        }
    }
</style>

<div style="min-height: 70vh; display: flex; align-items: center; justify-content: center; padding: 40px 0;">
    <div style="max-width: 480px; width: 100%;">
        <!-- Register Card -->
        <div class="glass" style="padding: 44px 36px; border-color: rgba(0, 212, 255, 0.12);">
            <!-- Header -->
            <div style="text-align: center; margin-bottom: 36px;">
                <div style="margin-bottom: 18px;">
                    <img src="images/nexus_logo.png" alt="Logo" style="width: 72px; height: 72px; object-fit: contain;">
                </div>
                <h2 style="font-family: 'Space Grotesk', sans-serif; font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; background: var(--grad-cool); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">Join Nexus Fest</h2>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Create your participant account</p>
            </div>

            <?php if ($error): ?>
                <div style="padding: 12px 16px; border-radius: 12px; background: rgba(244, 63, 94, 0.08); border: 1px solid rgba(244, 63, 94, 0.2); color: var(--danger); margin-bottom: 24px; font-size: 0.85rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="auth-grid">
                    <div class="form-group auth-grid-full">
                        <label><i class="fa-solid fa-user" style="margin-right: 6px; color: var(--accent-1);"></i>Full Name</label>
                        <input type="text" name="name" placeholder="John Doe" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-envelope" style="margin-right: 6px; color: var(--accent-2);"></i>Email</label>
                        <input type="email" name="email" placeholder="you@email.com" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-phone" style="margin-right: 6px; color: var(--accent-3);"></i>Phone</label>
                        <input type="text" name="phone" placeholder="9876543210" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-graduation-cap" style="margin-right: 6px; color: var(--accent-4);"></i>Course</label>
                        <select name="course" required>
                            <option value="" disabled selected>Select Course</option>
                            <option value="BCA">BCA</option>
                            <option value="BCOM">BCOM</option>
                            <option value="BA">BA</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-calendar-alt" style="margin-right: 6px; color: var(--accent-5);"></i>Year</label>
                        <select name="year" required>
                            <option value="" disabled selected>Select Year</option>
                            <option value="1st year">1st year</option>
                            <option value="2nd year">2nd year</option>
                            <option value="3rd year">3rd year</option>
                        </select>
                    </div>
                    <div class="form-group auth-grid-full">
                        <label><i class="fa-solid fa-id-card" style="margin-right: 6px; color: var(--accent-2);"></i>Roll Number</label>
                        <input type="text" name="roll_no" placeholder="Enter your roll number" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-lock" style="margin-right: 6px; color: var(--accent-1);"></i>Password</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-shield-check" style="margin-right: 6px; color: var(--accent-2);"></i>Confirm Password</label>
                        <input type="password" name="confirm_password" placeholder="••••••••" required>
                    </div>
                </div>
                <button type="submit" class="btn-neon" style="width: 100%; margin-top: 10px; padding: 14px; background: rgba(0, 212, 255, 0.06); border-color: var(--accent-1); color: var(--accent-1);">
                    <i class="fa-solid fa-rocket"></i> Create Account
                </button>
            </form>

            <div style="text-align: center; margin-top: 28px;">
                <p style="font-size: 0.85rem; color: var(--text-muted);">
                    Already have an account? 
                    <a href="login.php" style="color: var(--accent-2); text-decoration: none; font-weight: 700;">Sign In →</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>