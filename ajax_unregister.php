<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid Security Token']);
        exit;
    }

    $user_id = $_SESSION['user']['user_id'];
    $event_id = $_POST['event_id'] ?? '';

    if ($_SESSION['user']['role'] === 'student') {
        echo json_encode(['success' => false, 'message' => 'Unregistration is not permitted for students. Please contact an administrator if you need to cancel.']);
        exit;
    }

    if (empty($event_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing Event ID']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Check if registration exists
        $stmt = $pdo->prepare("SELECT id FROM registrations WHERE user_id = ? AND event_id = ?");
        $stmt->execute([$user_id, $event_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Registration not found.");
        }

        // Delete registration
        $stmt = $pdo->prepare("DELETE FROM registrations WHERE user_id = ? AND event_id = ?");
        $stmt->execute([$user_id, $event_id]);

        // Decrement participant count
        $stmt = $pdo->prepare("UPDATE events SET current_participants = GREATEST(0, current_participants - 1) WHERE id = ?");
        $stmt->execute([$event_id]);

        // Handle teams (if any)
        // Note: For individual events this does nothing. For team events, it follows the existing logic.
        $stmt = $pdo->prepare("SELECT id FROM teams WHERE event_id = ? AND leader_user_id = ?");
        $stmt->execute([$event_id, $user_id]);
        $team = $stmt->fetch();

        if ($team) {
            // Dissolve the entire team
            $stmt = $pdo->prepare("DELETE FROM team_members WHERE team_id = ?");
            $stmt->execute([$team['id']]);
            $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
            $stmt->execute([$team['id']]);
        } else {
            // Just remove the user from any teams they are part of for this event
            $stmt = $pdo->prepare("
                DELETE tm FROM team_members tm
                JOIN teams t ON tm.team_id = t.id
                WHERE tm.user_id = ? AND t.event_id = ?
            ");
            $stmt->execute([$user_id, $event_id]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Successfully unregistered']);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}
?>
