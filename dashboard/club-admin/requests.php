<?php
// dashboard/club-admin/requests.php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dashboard-shell.php';
requireRole('club_admin');
define('EXTRA_CSS', 'dashboard.css');
$user = currentUser(); $uid = $user['id'];

$clubStmt = $pdo->prepare("SELECT * FROM clubs WHERE admin_id=? LIMIT 1"); $clubStmt->execute([$uid]); $club = $clubStmt->fetch();
if (!$club) { $s2 = $pdo->prepare("SELECT c.* FROM club_members cm JOIN clubs c ON c.id=cm.club_id WHERE cm.user_id=? AND cm.role='club_admin' LIMIT 1"); $s2->execute([$uid]); $club = $s2->fetch(); }
$cid = $club['id'] ?? 0;

$filter = sanitize($_GET['filter'] ?? 'pending');
$requests = $pdo->prepare("SELECT cjr.*, u.full_name, u.student_id, u.profile_picture, u.email, d.name AS dept FROM club_join_requests cjr JOIN users u ON u.id=cjr.user_id LEFT JOIN departments d ON d.id=u.department_id WHERE cjr.club_id=? AND cjr.status=? ORDER BY cjr.requested_at DESC");
$requests->execute([$cid, $filter]); $requests = $requests->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell('club_admin', $user, 'Join Requests', 'Join Requests', $pdo); ?>

<div class="section-toolbar mb-6">
    <h2 style="font-size:1.25rem;font-weight:700">Join Requests — <?= htmlspecialchars($club['name'] ?? '') ?></h2>
    <div class="filter-chips">
        <?php foreach (['pending'=>'⏳ Pending','approved'=>'✓ Approved','rejected'=>'✗ Rejected'] as $val=>$label): ?>
        <a href="?filter=<?= $val ?>" class="filter-chip <?= $filter===$val?'active':'' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
<div class="table-wrapper">
<table class="table">
    <thead>
        <tr>
            <th>Student</th><th>Student ID</th><th>Department</th><th>Requested</th>
            <?php if ($filter==='pending'): ?><th>Actions</th><?php endif; ?>
            <?php if ($filter!=='pending'): ?><th>Status</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if ($requests): foreach ($requests as $r): ?>
        <tr>
            <td><div class="user-cell"><img src="<?= profilePicUrl($r['profile_picture']) ?>" alt="" /><div><strong><?= htmlspecialchars($r['full_name']) ?></strong><span><?= htmlspecialchars($r['email']) ?></span></div></div></td>
            <td><?= htmlspecialchars($r['student_id'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($r['dept'] ?? 'N/A') ?></td>
            <td><?= timeAgo($r['requested_at']) ?></td>
            <?php if ($filter==='pending'): ?>
            <td>
                <div class="table-actions">
                    <button class="btn btn-success btn-sm" onclick="handleRequest(<?= $r['id'] ?>,'approve_request',this)"><i class="ri-check-line"></i> Approve</button>
                    <button class="btn btn-danger btn-sm" onclick="handleRequest(<?= $r['id'] ?>,'reject_request',this)"><i class="ri-close-line"></i> Reject</button>
                </div>
            </td>
            <?php else: ?>
            <td><?= getStatusBadge($r['status']) ?></td>
            <?php endif; ?>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--color-text-3)">No <?= $filter ?> requests</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
</div>

<?php renderDashboardEnd(); ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
async function handleRequest(id, action, btn) {
    btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line spin"></i>';
    const data = await apiPost(BASE_URL+'/api/clubs.php', {action, id});
    showToast(data.message, data.success?'success':'error');
    if (data.success) btn.closest('tr').remove();
    else btn.disabled = false;
}
</script>
<?= toggleSidebarScript(); ?>
