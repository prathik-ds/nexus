<?php
// NEXUS FEST AUTO-INSTALLER — PORTABLE EDITION
// Use this to setup the project on any new PC or Server.

$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP/Wamp password
$db   = 'fusionverse_db';

echo "<body style='background: #0f172a; color: #f8fafc; font-family: sans-serif; padding: 40px;'>";
echo "<div style='max-width: 800px; margin: 0 auto; background: rgba(30, 41, 59, 0.5); padding: 30px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);'>";
echo "<h1 style='color: #00d4ff; margin-bottom: 20px;'>NEXUS FEST — Command Setup</h1>";

try {
    // 1. Initial Connection
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Database Creation
    echo "Initializing Core Systems...<br>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<span style='color: #10b981;'>[OK] Database Created Successfully.</span><br><br>";

    $pdo->exec("USE `$db` ");
    
    // 3. Complete Schema
    $tables = [
        "users" => "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(20) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            phone VARCHAR(20) NOT NULL,
            course VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('student','coordinator', 'admin') DEFAULT 'student',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "events" => "CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(50) NOT NULL,
            eligibility_stream VARCHAR(50) DEFAULT 'ALL',
            description TEXT,
            rules TEXT,
            date DATE,
            time TIME,
            venue VARCHAR(255),
            coordinator_name VARCHAR(255),
            coordinator_phone VARCHAR(20),
            coordinator_id VARCHAR(20),
            max_participants INT DEFAULT 0,
            current_participants INT DEFAULT 0,
            image VARCHAR(255),
            is_team_event TINYINT(1) DEFAULT 0,
            min_team_size INT DEFAULT 2,
            max_team_size INT DEFAULT 4,
            status ENUM('active', 'full', 'completed') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "teams" => "CREATE TABLE IF NOT EXISTS teams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            leader_user_id VARCHAR(20) NOT NULL,
            invite_code VARCHAR(10) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (leader_user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )",
        "team_members" => "CREATE TABLE IF NOT EXISTS team_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_id INT NOT NULL,
            user_id VARCHAR(20) NOT NULL,
            user_name VARCHAR(255) NOT NULL,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            UNIQUE(team_id, user_id)
        )",
        "registrations" => "CREATE TABLE IF NOT EXISTS registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(20) NOT NULL,
            event_id INT NOT NULL,
            team_id INT,
            status ENUM('registered', 'winner', 'runner', 'participated') DEFAULT 'registered',
            attendance ENUM('absent', 'present') DEFAULT 'absent',
            score INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            UNIQUE(user_id, event_id)
        )",
        "announcements" => "CREATE TABLE IF NOT EXISTS announcements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            type ENUM('alert', 'update', 'result') DEFAULT 'update',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];

    foreach($tables as $name => $query) {
        $pdo->exec($query);
        echo "<span style='color: #10b981;'>[OK] System Segment: $name Initialized.</span><br>";
    }

    // 4. Seed Data
    echo "<br>Populating Data Banks...<br>";
    
    // Default Admin
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (user_id, name, email, phone, course, password, role) VALUES ('ADMIN001', 'Super Admin', 'admin@nexusfest.com', '1234567890', 'Management', '$admin_pass', 'admin')");
        echo "Master Admin Account Deployed (admin@nexusfest.com / admin123).<br>";
    }

    // Default Events
    $stmt = $pdo->query("SELECT COUNT(*) FROM events");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO events (name, category, eligibility_stream, description, rules, date, time, venue, max_participants) VALUES 
        ('Code Rush', 'IT Track', 'IT', 'Fast-paced coding competition where logic meets speed.', 'Individual. No internet allowed.', '2026-04-10', '10:00:00', 'Lab 1', 50),
        ('Biz Quiz', 'Commerce Track', 'Commerce', 'Test your business knowledge and strategy across rounds.', 'Team of 2. Buzzer format.', '2026-04-10', '11:30:00', 'Main Auditorium', 100),
        ('Fusion Hack', 'IT Track', 'ALL', 'Build the future in this intensive 12-hour build-a-thon.', 'Team of 3-4. Innovative solutions only.', '2026-04-11', '09:00:00', 'Arena North', 30)");
        echo "Standard Competition Tracks Populated.<br>";
    }

    echo "<br><div style='background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; padding: 15px; border-radius: 8px;'>";
    echo "<b style='color: #10b981;'>ALL SYSTEMS NOMINAL.</b><br>";
    echo "The database is ready. For security, please delete this file before going public.";
    echo "</div>";
    echo "<br><a href='index.html' style='display: inline-block; padding: 12px 30px; background: #00d4ff; color: #000; font-weight: bold; border-radius: 8px; text-decoration: none;'>ENTER NEXUS FEST</a>";

} catch (PDOException $e) {
    echo "<br><div style='background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; padding: 15px; border-radius: 8px;'>";
    echo "<b style='color: #ef4444;'>CRITICAL SYSTEM FAILURE:</b><br>" . $e->getMessage();
    echo "</div>";
}
echo "</div></body>";
?>

