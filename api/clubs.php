<?php
// ============================================================
// api/clubs.php — Club CRUD, join, approval — BUG FIXED
// ============================================================
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
if (!isLoggedIn()) jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);

$userId = currentUser()['id'];
$role   = currentRole();
$method = $_SERVER['REQUEST_METHOD'];
$body   = $method === 'POST' ? (json_decode(file_get_contents('php://input'), true) ?? []) : [];
$action = $body['action'] ?? $_GET['action'] ?? '';

// ---- JOIN CLUB ----
if ($action === 'join' && $method === 'POST') {
    $clubId = (int)($body['club_id'] ?? 0);
    if (!$clubId) jsonResponse(['success' => false, 'message' => 'Invalid club.']);

    $check = $pdo->prepare("SELECT id FROM club_members WHERE club_id=? AND user_id=?");
    $check->execute([$clubId, $userId]);
    if ($check->fetch()) jsonResponse(['success' => false, 'message' => 'You are already a member of this club.']);

    $check2 = $pdo->prepare("SELECT id FROM club_join_requests WHERE club_id=? AND user_id=? AND status='pending'");
    $check2->execute([$clubId, $userId]);
    if ($check2->fetch()) jsonResponse(['success' => false, 'message' => 'Join request already pending.']);

    $pdo->prepare("INSERT INTO club_join_requests (club_id, user_id) VALUES (?,?)")->execute([$clubId, $userId]);

    $clubRow = $pdo->prepare("SELECT admin_id, name FROM clubs WHERE id=?");
    $clubRow->execute([$clubId]); $club = $clubRow->fetch();
    if ($club && $club['admin_id']) {
        $u = currentUser();
        sendNotification($pdo, $club['admin_id'], 'join_request', 'New Join Request',
            "{$u['full_name']} wants to join {$club['name']}.", $clubId, 'club');
    }
    jsonResponse(['success' => true, 'message' => 'Join request sent! Awaiting approval.']);
}

// ---- APPROVE/REJECT JOIN REQUEST ----
if (in_array($action, ['approve_request','reject_request']) && $method === 'POST') {
    requireAnyRole(['club_admin','super_admin']);
    $reqId  = (int)($body['id'] ?? 0);
    $status = $action === 'approve_request' ? 'approved' : 'rejected';

    $reqStmt = $pdo->prepare("SELECT cjr.*, u.full_name, c.name AS club_name, c.id AS club_id FROM club_join_requests cjr JOIN users u ON u.id=cjr.user_id JOIN clubs c ON c.id=cjr.club_id WHERE cjr.id=?");
    $reqStmt->execute([$reqId]); $request = $reqStmt->fetch();
    if (!$request) jsonResponse(['success' => false, 'message' => 'Request not found.']);

    $pdo->prepare("UPDATE club_join_requests SET status=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?")->execute([$status, $userId, $reqId]);

    if ($status === 'approved') {
        $pdo->prepare("INSERT IGNORE INTO club_members (club_id,user_id) VALUES (?,?)")->execute([$request['club_id'], $request['user_id']]);
        $pdo->prepare("UPDATE clubs SET total_members=total_members+1 WHERE id=?")->execute([$request['club_id']]);
        sendNotification($pdo, $request['user_id'], 'club_approved', 'Join Approved!',
            "Your request to join {$request['club_name']} has been approved!", $request['club_id'], 'club');
    } else {
        sendNotification($pdo, $request['user_id'], 'club_rejected', 'Join Request Rejected',
            "Your request to join {$request['club_name']} was not approved.", $request['club_id'], 'club');
    }
    jsonResponse(['success' => true, 'message' => 'Request ' . $status . '.']);
}

// ---- APPROVE CLUB (Super Admin) ----
if ($action === 'approve_club' && $method === 'POST') {
    requireRole('super_admin');
    $id      = (int)($body['id'] ?? 0);
    $adminId = (int)($body['admin_id'] ?? 0);
    $pdo->prepare("UPDATE clubs SET status='active' WHERE id=?")->execute([$id]);
    if ($adminId) {
        $pdo->prepare("UPDATE users SET role='club_admin' WHERE id=?")->execute([$adminId]);
        sendNotification($pdo, $adminId, 'club_approved', 'Club Approved!', 'Your club is now live!', $id, 'club');
    }
    jsonResponse(['success' => true, 'message' => 'Club approved and activated.']);
}

// ---- SUSPEND CLUB (Super Admin) ----
if ($action === 'suspend_club' && $method === 'POST') {
    requireRole('super_admin');
    $id = (int)($body['id'] ?? 0);
    $pdo->prepare("UPDATE clubs SET status='suspended' WHERE id=?")->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Club suspended.']);
}

// ---- DELETE CLUB (Super Admin) ----
if ($action === 'delete_club' && $method === 'POST') {
    requireRole('super_admin');
    $id = (int)($body['id'] ?? 0);
    $pdo->prepare("DELETE FROM clubs WHERE id=?")->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Club deleted.', 'reload' => true]);
}

// ---- REMOVE MEMBER ----
if ($action === 'remove_member' && $method === 'POST') {
    requireAnyRole(['club_admin','super_admin']);
    $memberId = (int)($body['user_id'] ?? 0);
    $clubId   = (int)($body['club_id'] ?? 0);
    $pdo->prepare("DELETE FROM club_members WHERE user_id=? AND club_id=?")->execute([$memberId, $clubId]);
    $pdo->prepare("UPDATE clubs SET total_members=GREATEST(total_members-1,0) WHERE id=?")->execute([$clubId]);
    sendNotification($pdo, $memberId, 'member_removed', 'Removed from Club', 'You have been removed from the club.', $clubId, 'club');
    jsonResponse(['success' => true, 'message' => 'Member removed.']);
}

// ---- PROMOTE/DEMOTE MEMBER ----
if (in_array($action, ['promote','demote']) && $method === 'POST') {
    requireAnyRole(['club_admin','super_admin']);
    $memberId = (int)($body['user_id'] ?? 0);
    $clubId   = (int)($body['club_id'] ?? 0);
    $newRole  = $action === 'promote' ? 'vice_president' : 'member';
    $pdo->prepare("UPDATE club_members SET role=? WHERE user_id=? AND club_id=?")->execute([$newRole, $memberId, $clubId]);
    jsonResponse(['success' => true, 'message' => 'Member role updated to ' . str_replace('_',' ',$newRole) . '.']);
}

// ---- APPROVE CLUB CREATION REQUEST (Super Admin) ----
if ($action === 'approve_creation' && $method === 'POST') {
    requireRole('super_admin');
    $reqId = (int)($body['id'] ?? 0);

    $reqStmt = $pdo->prepare("SELECT * FROM club_creation_requests WHERE id=?");
    $reqStmt->execute([$reqId]); $request = $reqStmt->fetch();
    if (!$request) jsonResponse(['success' => false, 'message' => 'Request not found.']);

    // Create club from request data (fixed column list — no 'objectives' column in clubs table)
    $slug = slugify($request['club_name']);
    $pdo->prepare("INSERT INTO clubs (name, slug, short_description, full_description, activities, logo, banner, admin_id, status)
                   VALUES (?,?,?,?,?,?,?,?,'active')")
        ->execute([
            $request['club_name'],
            $slug,
            $request['description'],
            $request['description'],
            $request['activities'],
            $request['logo'],
            $request['banner'],
            $request['requested_by'],
        ]);

    $clubId = (int)$pdo->lastInsertId();
    $pdo->prepare("UPDATE users SET role='club_admin' WHERE id=?")->execute([$request['requested_by']]);
    $pdo->prepare("INSERT IGNORE INTO club_members (club_id,user_id,role) VALUES (?,?,'club_admin')")->execute([$clubId, $request['requested_by']]);
    $pdo->prepare("UPDATE club_creation_requests SET status='approved', reviewed_by=?, reviewed_at=NOW() WHERE id=?")->execute([$userId, $reqId]);

    sendNotification($pdo, $request['requested_by'], 'club_approved', 'Club Request Approved!',
        "Your club \"{$request['club_name']}\" has been approved and is now live!", $clubId, 'club');
    jsonResponse(['success' => true, 'message' => 'Club created and approved!', 'reload' => true]);
}

// ---- REJECT CLUB CREATION REQUEST ----
if ($action === 'reject_creation' && $method === 'POST') {
    requireRole('super_admin');
    $reqId  = (int)($body['id'] ?? 0);
    $reason = sanitize($body['reason'] ?? 'Not specified');

    $reqStmt = $pdo->prepare("SELECT * FROM club_creation_requests WHERE id=?");
    $reqStmt->execute([$reqId]); $request = $reqStmt->fetch();

    $pdo->prepare("UPDATE club_creation_requests SET status='rejected', reviewed_by=?, reviewed_at=NOW(), rejection_reason=? WHERE id=?")
        ->execute([$userId, $reason, $reqId]);

    if ($request) {
        sendNotification($pdo, $request['requested_by'], 'club_rejected', 'Club Request Rejected',
            "Your club request \"{$request['club_name']}\" was rejected. Reason: $reason");
    }
    jsonResponse(['success' => true, 'message' => 'Request rejected.', 'reload' => true]);
}

jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
