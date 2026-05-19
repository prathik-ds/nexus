<?php
// NEXUS TEAM FEATURE MIGRATION
// Run once, then delete this file.

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'fusionverse_db';

echo "<h2 style='font-family: sans-serif;'>NEXUS TEAM MIGRATION</h2>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Add team columns to events
    echo "Adding team columns to events...<br>";
    $pdo->exec("ALTER TABLE events 
        ADD COLUMN IF NOT EXISTS is_team_event TINYINT(1) DEFAULT 0,
        ADD COLUMN IF NOT EXISTS min_team_size INT DEFAULT 2,
        ADD COLUMN IF NOT EXISTS max_team_size INT DEFAULT 4");
    echo "<b>&#10003; events table updated.</b><br><br>";

    // 2. Add team_id to registrations
    echo "Adding team_id to registrations...<br>";
    $pdo->exec("ALTER TABLE registrations ADD COLUMN IF NOT EXISTS team_id INT DEFAULT NULL");
    echo "<b>&#10003; registrations table updated.</b><br><br>";

    // 3. Create teams table
    echo "Creating teams table...<br>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS teams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        leader_user_id VARCHAR(20) NOT NULL,
        invite_code VARCHAR(10) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        FOREIGN KEY (leader_user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
    echo "<b>&#10003; teams table created.</b><br><br>";

    // 4. Create team_members table
    echo "Creating team_members table...<br>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS team_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        team_id INT NOT NULL,
        user_id VARCHAR(20) NOT NULL,
        user_name VARCHAR(255) NOT NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE(team_id, user_id)
    )");
    echo "<b>&#10003; team_members table created.</b><br><br>";

    echo "<b style='color: green;'>&#10003; MIGRATION COMPLETE. You may now delete this file.</b>";
    echo "<br><br><a href='index.html' style='display:inline-block;padding:10px 20px;background:#7c3aed;color:#fff;border-radius:5px;text-decoration:none;'>Go to Home</a>";

} catch (PDOException $e) {
    die("<br><b style='color:red;'>MIGRATION ERROR: " . $e->getMessage() . "</b>");
}
?>
