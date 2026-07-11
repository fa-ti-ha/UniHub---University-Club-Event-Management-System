<?php
// ============================================================
// api/events.php — Event registration & approval
// ============================================================
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
if (!isLoggedIn()) jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);

$userId = currentUser()['id'];
$role   = currentRole();
$method = $_SERVER['REQUEST_METHOD'];
$body   = $method === 'POST' ? (json_decode(file_get_contents('php://input'), true) ?? []) : [];
$action = $_GET['action'] ?? $body['action'] ?? '';

// ---- REGISTER FOR EVENT ----
if ($action === 'register' && $method === 'POST') {
    $eventId = (int)($body['event_id'] ?? 0);
    if (!$eventId) jsonResponse(['success' => false, 'message' => 'Invalid event.']);

    $event = $pdo->prepare("SELECT * FROM events WHERE id = ? AND status = 'approved'");
    $event->execute([$eventId]);
    $ev = $event->fetch();
    if (!$ev) jsonResponse(['success' => false, 'message' => 'Event not found or not available.']);

    // Check deadline
    if ($ev['registration_deadline'] && strtotime($ev['registration_deadline']) < time()) {
        jsonResponse(['success' => false, 'message' => 'Registration deadline has passed.']);
    }

    // Check capacity
    if ($ev['max_participants'] > 0 && $ev['current_participants'] >= $ev['max_participants']) {
        jsonResponse(['success' => false, 'message' => 'Event is full.']);
    }

    // Already registered?
    $check = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ?");
    $check->execute([$eventId, $userId]);
    if ($check->fetch()) jsonResponse(['success' => false, 'message' => 'You are already registered.']);

    $status = $ev['registration_type'] === 'auto' ? 'confirmed' : 'pending';
    $pdo->prepare("INSERT INTO event_registrations (event_id, user_id, status) VALUES (?,?,?)")->execute([$eventId, $userId, $status]);
    $pdo->prepare("UPDATE events SET current_participants = current_participants + 1 WHERE id = ?")->execute([$eventId]);

    $user = currentUser();
    sendNotification($pdo, $userId, 'event_registered', 'Event Registration', "You registered for \"{$ev['title']}\".", $eventId, 'event');
    sendNotification($pdo, $ev['created_by'], 'new_registration', 'New Registration', "{$user['full_name']} registered for \"{$ev['title']}\".", $eventId, 'event');

    jsonResponse(['success' => true, 'message' => 'Registered successfully! Status: ' . ucfirst($status)]);
}

// ---- CANCEL REGISTRATION ----
if ($action === 'cancel_registration' && $method === 'POST') {
    $eventId = (int)($body['event_id'] ?? 0);
    $pdo->prepare("DELETE FROM event_registrations WHERE event_id = ? AND user_id = ?")->execute([$eventId, $userId]);
    $pdo->prepare("UPDATE events SET current_participants = GREATEST(current_participants - 1, 0) WHERE id = ?")->execute([$eventId]);
    jsonResponse(['success' => true, 'message' => 'Registration cancelled.']);
}

// ---- APPROVE EVENT (Super Admin) ----
if ($action === 'approve_event' && $method === 'POST') {
    requireRole('super_admin');
    $id = (int)($body['id'] ?? 0);
    $pdo->prepare("UPDATE events SET status = 'approved' WHERE id = ?")->execute([$id]);

    $ev = $pdo->prepare("SELECT created_by, title FROM events WHERE id = ?");
    $ev->execute([$id]);
    $event = $ev->fetch();
    if ($event) {
        sendNotification($pdo, $event['created_by'], 'event_approved', 'Event Approved!', "Your event \"{$event['title']}\" has been approved!", $id, 'event');
    }
    jsonResponse(['success' => true, 'message' => 'Event approved.']);
}

// ---- REJECT EVENT (Super Admin) ----
if ($action === 'reject_event' && $method === 'POST') {
    requireRole('super_admin');
    $id     = (int)($body['id'] ?? 0);
    $reason = sanitize($body['reason'] ?? '');
    $pdo->prepare("UPDATE events SET status = 'cancelled' WHERE id = ?")->execute([$id]);

    $ev = $pdo->prepare("SELECT created_by, title FROM events WHERE id = ?");
    $ev->execute([$id]);
    $event = $ev->fetch();
    if ($event) {
        sendNotification($pdo, $event['created_by'], 'event_rejected', 'Event Rejected', "Your event \"{$event['title']}\" was rejected. " . ($reason ? "Reason: $reason" : ''), $id, 'event');
    }
    jsonResponse(['success' => true, 'message' => 'Event rejected.', 'reload' => true]);
}

// ---- DELETE EVENT (Super Admin) ----
if ($action === 'delete_event' && $method === 'POST') {
    requireRole('super_admin');
    $id = (int)($body['id'] ?? 0);
    $pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Event deleted.', 'reload' => true]);
}

jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
