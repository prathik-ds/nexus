<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'fusionverse_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("ALTER TABLE registrations ADD COLUMN IF NOT EXISTS attendance ENUM('absent', 'present') DEFAULT 'absent'");
    echo "Successfully updated current database!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
