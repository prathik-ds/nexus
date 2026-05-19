<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user']['user_id'];
    $event_id = $_POST['event_id'] ?? '';

    if (empty($event_id)) {
        header('Location: events.php?error=Missing+Event+ID');
        exit;
    }

    // Check if event exists and check eligibility
    $stmt = $pdo->prepare("SELECT eligibility_stream, current_participants, max_participants FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();

    if (!$event) {
        header('Location: events.php?error=Event+not+found');
        exit;
    }

    // Eligibility check
    $userCourse = $_SESSION['user']['course']; // BCA, BCOM, or BA
    $requiredStream = $event['eligibility_stream']; // IT, Commerce, Art, or ALL

    if ($requiredStream !== 'ALL') {
        $qualified = false;
        if ($requiredStream === 'IT' && $userCourse === 'BCA') $qualified = true;
        if ($requiredStream === 'Commerce' && $userCourse === 'BCOM') $qualified = true;
        if ($requiredStream === 'Art' && $userCourse === 'BA') $qualified = true;

        if (!$qualified) {
            $msg = "Registration Error: This event is restricted to $requiredStream students only.";
            header('Location: events.php?error=' . urlencode($msg));
            exit;
        }
    }

    if ($event['max_participants'] > 0 && $event['current_participants'] >= $event['max_participants']) {
        header('Location: events.php?error=Event+is+already+full');
        exit;
    }

    // Check for duplicate registration
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE user_id = ? AND event_id = ?");
    $stmt->execute([$user_id, $event_id]);
    if ($stmt->fetchColumn() > 0) {
        header('Location: events.php?error=Already+registered+for+this+event');
        exit;
    }

    // Process registration
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO registrations (user_id, event_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $event_id]);

        $stmt = $pdo->prepare("UPDATE events SET current_participants = current_participants + 1 WHERE id = ?");
        $stmt->execute([$event_id]);

        $pdo->commit();
        header('Location: events.php?success=1');
    } catch (PDOException $e) {
        $pdo->rollBack();
        header('Location: events.php?error=' . urlencode($e->getMessage()));
    }
} else {
    header('Location: events.php');
}
?>
