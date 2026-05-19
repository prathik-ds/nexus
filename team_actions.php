<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$user_name = $_SESSION['user']['name'];
$action = $_POST['action'] ?? '';

function generateTeamCode() {
    return strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
}

switch ($action) {

    // ─── CREATE TEAM ────────────────────────────────────────────────────────────
    case 'create_team':
        $event_id  = (int)($_POST['event_id'] ?? 0);
        $team_name = trim($_POST['team_name'] ?? '');

        if (!$event_id || !$team_name) {
            echo json_encode(['success' => false, 'message' => 'Missing event or team name']);
            exit;
        }

        // Validate event is a team event
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND is_team_event = 1");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch();

        if (!$event) {
            echo json_encode(['success' => false, 'message' => 'This is not a team event']);
            exit;
        }

        // Check if event is full
        if ($event['max_participants'] > 0 && $event['current_participants'] >= $event['max_participants']) {
            echo json_encode(['success' => false, 'message' => 'Event is full']);
            exit;
        }

        // Check if user already has a team for this event
        $stmt = $pdo->prepare("SELECT t.id FROM teams t JOIN team_members tm ON t.id = tm.team_id WHERE t.event_id = ? AND tm.user_id = ?");
        $stmt->execute([$event_id, $user_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You already have a team for this event']);
            exit;
        }

        // Generate unique code
        do {
            $code = generateTeamCode();
            $check = $pdo->prepare("SELECT id FROM teams WHERE invite_code = ?");
            $check->execute([$code]);
        } while ($check->fetch());

        try {
            $pdo->beginTransaction();

            // Create team
            $stmt = $pdo->prepare("INSERT INTO teams (event_id, name, leader_user_id, invite_code) VALUES (?, ?, ?, ?)");
            $stmt->execute([$event_id, $team_name, $user_id, $code]);
            $team_id = $pdo->lastInsertId();

            // Add leader as member
            $stmt = $pdo->prepare("INSERT INTO team_members (team_id, user_id, user_name) VALUES (?, ?, ?)");
            $stmt->execute([$team_id, $user_id, $user_name]);

            // Register team leader in registrations
            $stmt = $pdo->prepare("INSERT IGNORE INTO registrations (user_id, event_id, team_id) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $event_id, $team_id]);

            // Update participant count
            $stmt = $pdo->prepare("UPDATE events SET current_participants = current_participants + 1 WHERE id = ?");
            $stmt->execute([$event_id]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Team created!', 'team_code' => $code, 'team_id' => $team_id]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ─── JOIN TEAM ───────────────────────────────────────────────────────────────
    case 'join_team':
        $invite_code = strtoupper(trim($_POST['invite_code'] ?? ''));

        if (!$invite_code) {
            echo json_encode(['success' => false, 'message' => 'Enter an invite code']);
            exit;
        }

        // Find team
        $stmt = $pdo->prepare("SELECT t.*, e.max_team_size, e.min_team_size, e.max_participants, e.current_participants, e.name as event_name FROM teams t JOIN events e ON t.event_id = e.id WHERE t.invite_code = ?");
        $stmt->execute([$invite_code]);
        $team = $stmt->fetch();

        if (!$team) {
            echo json_encode(['success' => false, 'message' => 'Invalid invite code']);
            exit;
        }

        // Check event capacity
        if ($team['max_participants'] > 0 && $team['current_participants'] >= $team['max_participants']) {
            echo json_encode(['success' => false, 'message' => 'Event is at full capacity']);
            exit;
        }

        // Check if user already in a team for this event
        $stmt = $pdo->prepare("SELECT t.id FROM teams t JOIN team_members tm ON t.id = tm.team_id WHERE t.event_id = ? AND tm.user_id = ?");
        $stmt->execute([$team['event_id'], $user_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You already joined/created a team for this event']);
            exit;
        }

        // Check team size limit
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM team_members WHERE team_id = ?");
        $stmt->execute([$team['id']]);
        $current_size = $stmt->fetchColumn();

        if ($team['max_team_size'] > 0 && $current_size >= $team['max_team_size']) {
            echo json_encode(['success' => false, 'message' => 'This team is already full']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO team_members (team_id, user_id, user_name) VALUES (?, ?, ?)");
            $stmt->execute([$team['id'], $user_id, $user_name]);

            $stmt = $pdo->prepare("INSERT IGNORE INTO registrations (user_id, event_id, team_id) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $team['event_id'], $team['id']]);

            $stmt = $pdo->prepare("UPDATE events SET current_participants = current_participants + 1 WHERE id = ?");
            $stmt->execute([$team['event_id']]);

            $pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Joined team: ' . $team['name'],
                'team_name' => $team['name'],
                'event_name' => $team['event_name']
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ─── LEAVE TEAM ─────────────────────────────────────────────────────────────
    case 'leave_team':
        echo json_encode(['success' => false, 'message' => 'Leaving teams or dissolving teams is not permitted after registration.']);
        exit;

        if (!$team_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid team']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT t.*, e.id as event_id FROM teams t JOIN events e ON t.event_id = e.id WHERE t.id = ?");
        $stmt->execute([$team_id]);
        $team = $stmt->fetch();

        if (!$team) {
            echo json_encode(['success' => false, 'message' => 'Team not found']);
            exit;
        }

        // Check member is in the team
        $stmt = $pdo->prepare("SELECT id FROM team_members WHERE team_id = ? AND user_id = ?");
        $stmt->execute([$team_id, $user_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You are not in this team']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // If leader and team has others: dissolve team
            if ($team['leader_user_id'] === $user_id) {
                // Remove all members' registrations
                $stmtM = $pdo->prepare("SELECT user_id FROM team_members WHERE team_id = ?");
                $stmtM->execute([$team_id]);
                $members = $stmtM->fetchAll(PDO::FETCH_COLUMN);

                foreach ($members as $mid) {
                    $pdo->prepare("DELETE FROM registrations WHERE user_id = ? AND event_id = ?")->execute([$mid, $team['event_id']]);
                    $pdo->prepare("UPDATE events SET current_participants = current_participants - 1 WHERE id = ? AND current_participants > 0")->execute([$team['event_id']]);
                }

                $pdo->prepare("DELETE FROM team_members WHERE team_id = ?")->execute([$team_id]);
                $pdo->prepare("DELETE FROM teams WHERE id = ?")->execute([$team_id]);
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Team dissolved (you were the leader)']);
            } else {
                // Just remove this member
                $pdo->prepare("DELETE FROM team_members WHERE team_id = ? AND user_id = ?")->execute([$team_id, $user_id]);
                $pdo->prepare("DELETE FROM registrations WHERE user_id = ? AND event_id = ?")->execute([$user_id, $team['event_id']]);
                $pdo->prepare("UPDATE events SET current_participants = current_participants - 1 WHERE id = ? AND current_participants > 0")->execute([$team['event_id']]);
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Left the team successfully']);
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ─── GET TEAM INFO ───────────────────────────────────────────────────────────
    case 'get_team':
        $event_id = (int)($_POST['event_id'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT t.*, tm_me.user_id as my_uid
            FROM teams t
            JOIN team_members tm_me ON t.id = tm_me.team_id AND tm_me.user_id = ?
            WHERE t.event_id = ?
        ");
        $stmt->execute([$user_id, $event_id]);
        $team = $stmt->fetch();

        if (!$team) {
            echo json_encode(['success' => false, 'message' => 'No team found']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT user_id, user_name FROM team_members WHERE team_id = ?");
        $stmt->execute([$team['id']]);
        $members = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'team' => [
                'id' => $team['id'],
                'name' => $team['name'],
                'invite_code' => $team['invite_code'],
                'leader_user_id' => $team['leader_user_id'],
                'members' => $members
            ]
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?>
