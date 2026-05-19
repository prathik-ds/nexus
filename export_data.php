<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/db.php';

// Check if logged in and has appropriate role
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['role'] !== 'coordinator')) {
    header('Location: index.html');
    exit;
}

$type = $_GET['type'] ?? 'participation';
$event_id = $_GET['event_id'] ?? null;
$filename = "NexusFest_Export_" . ucfirst($type) . "_" . date('Y-m-d_His') . ".csv";

// Check permissions for coordinators - they can only export their own events' participation
if ($_SESSION['user']['role'] === 'coordinator') {
    if ($type !== 'participation' || !$event_id) {
        die("Unauthorized access. Coordinators can only export participation data for their assigned events.");
    }
    
    // Verify event ownership
    $stmt = $pdo->prepare("SELECT id FROM events WHERE id = ? AND coordinator_id = ?");
    $stmt->execute([$event_id, $_SESSION['user']['user_id']]);
    if (!$stmt->fetch()) {
        die("Unauthorized: You are not assigned to this event.");
    }
}

// Clean any previous output to ensure clean CSV
if (ob_get_length()) ob_clean();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if ($type === 'items' && $_SESSION['user']['role'] === 'admin') {
    // Exporting all users/items if needed, but the request was for "event and participation"
}

if ($type === 'users' && $_SESSION['user']['role'] === 'admin') {
    fputcsv($output, ['User ID', 'Name', 'Email', 'Phone', 'Role', 'Registration Date']);
    $stmt = $pdo->query("SELECT user_id, name, email, phone, role, created_at FROM users ORDER BY role, name");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
} elseif ($type === 'events' && $_SESSION['user']['role'] === 'admin') {
    fputcsv($output, ['ID', 'Event Name', 'Category', 'Coordinator', 'Max Cap', 'Current Reg', 'Date', 'Time', 'Venue', 'Is Team Event']);
    $stmt = $pdo->query("SELECT id, name, category, coordinator_name, max_participants, current_participants, date, time, venue, is_team_event FROM events ORDER BY date, time");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
} elseif ($type === 'participation') {
    fputcsv($output, ['Event', 'User ID', 'Name', 'Email', 'Phone', 'Attendance', 'Score', 'Status', 'Team Name', 'Team Code', 'Registered At']);
    
    $query = "SELECT e.name as event_name, u.user_id, u.name as user_name, u.email, u.phone, r.attendance, r.score, r.status, t.name as team_name, t.invite_code, r.created_at 
              FROM registrations r 
              JOIN users u ON r.user_id = u.user_id 
              JOIN events e ON r.event_id = e.id
              LEFT JOIN teams t ON r.team_id = t.id";
    
    $params = [];
    if ($event_id) {
        $query .= " WHERE r.event_id = ?";
        $params[] = $event_id;
    }
    
    $query .= " ORDER BY e.name, r.status DESC, u.name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit;
