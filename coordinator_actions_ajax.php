<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] !== 'coordinator' && $_SESSION['user']['role'] !== 'admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'qr_attendance') {
    $scanned_user_id = $_POST['user_id'];
    $event_id = $_POST['event_id'];
    $c_id = $_SESSION['user']['user_id'];

    try {
        if ($_SESSION['user']['role'] === 'admin') {
            $is_owner = true;
        } else {
            $c_id = $_SESSION['user']['user_id'];
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE id = ? AND FIND_IN_SET(?, coordinator_id)");
            $stmt->execute([$event_id, $c_id]);
            $is_owner = $stmt->fetchColumn() > 0;
        }

        if (!$is_owner) {
            echo json_encode(['success' => false, 'message' => 'You are not assigned to this event.']);
            exit;
        }

        // 2. Check if student is registered for this event
        $stmt = $pdo->prepare("SELECT r.id, u.name, r.status FROM registrations r JOIN users u ON r.user_id = u.user_id WHERE r.user_id = ? AND r.event_id = ?");
        $stmt->execute([$scanned_user_id, $event_id]);
        $registration = $stmt->fetch();

        if ($registration) {
            // 3. Mark Attendance and Auto-Promote if applicable
            $newStatus = ($registration['status'] === 'registered' || empty($registration['status'])) ? 'participated' : $registration['status'];
            
            $stmt = $pdo->prepare("UPDATE registrations SET attendance = 'present', status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $registration['id']]);
            
            echo json_encode([
                'success' => true, 
                'student_name' => $registration['name'], 
                'message' => 'Attendance Marked Successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Student is not registered for this specific event.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
}
?>
