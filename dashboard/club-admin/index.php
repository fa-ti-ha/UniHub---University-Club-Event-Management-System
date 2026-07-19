<?php
// dashboard/club-admin/index.php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dashboard-shell.php';
requireRole('club_admin');
define('EXTRA_CSS', 'dashboard.css');
$user = currentUser(); $uid = $user['id'];

// Get the club this admin manages
$clubStmt = $pdo->prepare("SELECT * FROM clubs WHERE admin_id = ? LIMIT 1"); $clubStmt->execute([$uid]); $club = $clubStmt->fetch();
if (!$club) {
    // Maybe they're a member with club_admin role
    $clubStmt2 = $pdo->prepare("SELECT c.* FROM club_members cm JOIN clubs c ON c.id=cm.club_id WHERE cm.user_id=? AND cm.role='club_admin' LIMIT 1");
    $clubStmt2->execute([$uid]); $club = $clubStmt2->fetch();
}

$cid = $club['id'] ?? 0;

$totalMembers  = $cid ? (int)$pdo->prepare("SELECT COUNT(*) FROM club_members WHERE club_id=?")->execute([$cid]) : 0;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM club_members WHERE club_id=?"); $stmt->execute([$cid]); $totalMembers = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM club_join_requests WHERE club_id=? AND status='pending'"); $stmt->execute([$cid]); $pendingReqs = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE club_id=? AND start_date>NOW() AND status='approved'"); $stmt->execute([$cid]); $upcomingEvents = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE club_id=?"); $stmt->execute([$cid]); $totalEvents = (int)$stmt->fetchColumn();

// Recent members
$recentMembers = $pdo->prepare("SELECT u.full_name, u.profile_picture, u.student_id, cm.role, cm.joined_at FROM club_members cm JOIN users u ON u.id=cm.user_id WHERE cm.club_id=? ORDER BY cm.joined_at DESC LIMIT 5");
$recentMembers->execute([$cid]); $recentMembers = $recentMembers->fetchAll();

// Pending requests
$reqStmt = $pdo->prepare("SELECT cjr.*, u.full_name, u.student_id, u.profile_picture, d.name AS dept FROM club_join_requests cjr JOIN users u ON u.id=cjr.user_id LEFT JOIN departments d ON d.id=u.department_id WHERE cjr.club_id=? AND cjr.status='pending' LIMIT 5");
$reqStmt->execute([$cid]); $requests = $reqStmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell('club_admin', $user, 'Dashboard', 'Club Admin Dashboard', $pdo); ?>

<!-- Club Welcome -->
<?php if ($club): ?>
<div class="welcome-card mb-6" style="background:linear-gradient(135deg,#0f172a,#1e3a8a)">
    <img src="<?= clubLogoUrl($club['logo']) ?>" alt="" class="welcome-avatar" style="border-radius:12px" />
    <div class="welcome-text">
        <h2><?= htmlspecialchars($club['name']) ?></h2>
        <p><?= htmlspecialchars(substr($club['short_description'] ?? '', 0, 100)) ?></p>
        <div style="display:flex;gap:0.5rem;margin-top:0.5rem">
            <a href="edit-club.php" class="btn btn-white btn-sm"><i class="ri-edit-line"></i> Edit Club</a>
            <a href="<?= BASE_URL ?>/pages/club-detail.php?slug=<?= $club['slug'] ?>" class="btn btn-sm" style="border:1px solid rgba(255,255,255,0.3);color:#fff"><i class="ri-eye-line"></i> View Public</a>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning"><i class="ri-information-line"></i> No club assigned to your account. Contact Super Admin.</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-row">
    <?php $cards=[['icon'=>'ri-group-line','color'=>'icon-blue','value'=>$totalMembers,'label'=>'Total Members'],['icon'=>'ri-user-add-line','color'=>'icon-warning','value'=>$pendingReqs,'label'=>'Pending Requests'],['icon'=>'ri-calendar-event-line','color'=>'icon-green','value'=>$upcomingEvents,'label'=>'Upcoming Events'],['icon'=>'ri-calendar-2-line','color'=>'icon-purple','value'=>$totalEvents,'label'=>'Total Events']];
    foreach ($cards as $c): ?>
    <div class="stat-card"><div class="stat-card-icon <?= $c['color'] ?>"><i class="<?= $c['icon'] ?>"></i></div><div><div class="stat-card-value"><?= $c['value'] ?></div><div class="stat-card-label"><?= $c['label'] ?></div></div></div>
    <?php endforeach; ?>
</div>

<div class="dashboard-grid">
<div>
    <!-- Pending Join Requests -->
    <div class="card mb-6">
        <div class="card-header"><h3 class="card-title"><i class="ri-user-add-line" style="color:var(--color-warning)"></i> Pending Join Requests</h3><a href="requests.php" class="btn btn-ghost btn-sm">All Requests</a></div>
        <div class="card-body" style="padding:0">
            <?php if ($requests): foreach ($requests as $req): ?>
            <div style="display:flex;align-items:center;gap:1rem;padding:1rem 1.5rem;border-bottom:1px solid var(--color-border)">
                <img src="<?= profilePicUrl($req['profile_picture']) ?>" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover" />
                <div style="flex:1">
                    <strong style="font-size:0.875rem"><?= htmlspecialchars($req['full_name']) ?></strong>
                    <div style="font-size:0.75rem;color:var(--color-text-3)"><?= htmlspecialchars($req['student_id']) ?> · <?= htmlspecialchars($req['dept'] ?? '') ?></div>
                </div>
                <button class="btn btn-success btn-sm" data-admin-action="approve_request" data-id="<?= $req['id'] ?>" data-endpoint="api/clubs.php"><i class="ri-check-line"></i></button>
                <button class="btn btn-danger btn-sm" data-admin-action="reject_request" data-id="<?= $req['id'] ?>" data-endpoint="api/clubs.php" data-confirm="Reject this request?"><i class="ri-close-line"></i></button>
            </div>
            <?php endforeach; else: ?>
            <div class="empty-state" style="padding:2rem"><i class="ri-checkbox-circle-line empty-state-icon" style="font-size:2.5rem;color:var(--color-success)"></i><h3>No pending requests</h3></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Members -->
    <div class="card">
        <div class="card-header"><h3 class="card-title"><i class="ri-group-line" style="color:var(--color-primary)"></i> Recent Members</h3><a href="members.php" class="btn btn-ghost btn-sm">All Members</a></div>
        <div class="card-body" style="padding:0">
            <?php foreach ($recentMembers as $m): ?>
            <div style="display:flex;align-items:center;gap:1rem;padding:0.875rem 1.5rem;border-bottom:1px solid var(--color-border)">
                <img src="<?= profilePicUrl($m['profile_picture']) ?>" alt="" style="width:36px;height:36px;border-radius:50%;object-fit:cover" />
                <div style="flex:1"><strong style="font-size:0.875rem"><?= htmlspecialchars($m['full_name']) ?></strong><div style="font-size:0.75rem;color:var(--color-text-3)"><?= htmlspecialchars($m['student_id']) ?></div></div>
                <span class="badge badge-<?= $m['role']==='president'?'warning':'primary' ?>"><?= ucfirst(str_replace('_',' ',$m['role'])) ?></span>
                <span style="font-size:0.75rem;color:var(--color-text-3)"><?= timeAgo($m['joined_at']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div>
    <!-- Quick Actions -->
    <div class="card mb-4">
        <div class="card-header"><h4 class="card-title">Quick Actions</h4></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:0.75rem">
            <a href="create-event.php" class="btn btn-primary btn-block"><i class="ri-add-circle-line"></i> Create Event</a>
            <a href="requests.php" class="btn btn-ghost btn-block"><i class="ri-user-add-line"></i> View All Requests <?php if($pendingReqs): ?><span class="badge badge-danger" style="margin-left:auto"><?= $pendingReqs ?></span><?php endif; ?></a>
            <a href="members.php" class="btn btn-ghost btn-block"><i class="ri-group-line"></i> Manage Members</a>
            <a href="edit-club.php" class="btn btn-ghost btn-block"><i class="ri-edit-line"></i> Edit Club Info</a>
        </div>
    </div>
</div>
</div>

<?php renderDashboardEnd(); ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<?= toggleSidebarScript(); ?>
