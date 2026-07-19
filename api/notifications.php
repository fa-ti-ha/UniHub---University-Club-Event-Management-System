<?php

// api/notifications.php — Fetch & mark notifications

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
if (!isLoggedIn()) jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);

$userId = currentUser()['id'];
$method = $_SERVER['REQUEST_METHOD'];

// ---- GET notifications ----
if ($method === 'GET') {
    $limit = min((int)($_GET['limit'] ?? 10), 50);
    $stmt = $pdo->prepare("SELECT id, type, title, message, is_read, created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$userId, $limit]);
    $notifs = $stmt->fetchAll();

    // Format times
    foreach ($notifs as &$n) {
        $n['created_at'] = timeAgo($n['created_at']);
        $n['is_read']    = (bool)$n['is_read'];
    }
    jsonResponse(['success' => true, 'notifications' => $notifs]);
}

// ---- POST actions ----
if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? '';

    if ($action === 'mark_all_read') {
        $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$userId]);
        jsonResponse(['success' => true, 'message' => 'All notifications marked as read.']);
    }

    if ($action === 'mark_read') {
        $id = (int)($body['id'] ?? 0);
        $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([$id, $userId]);
        jsonResponse(['success' => true]);
    }

    if ($action === 'delete') {
        $id = (int)($body['id'] ?? 0);
        $pdo->prepare("DELETE FROM notifications WHERE id=? AND user_id=?")->execute([$id, $userId]);
        jsonResponse(['success' => true, 'message' => 'Notification deleted.']);
    }
}
//fixed some issues
jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
