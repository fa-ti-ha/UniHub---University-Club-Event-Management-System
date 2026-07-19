<?php
// dashboard/club-admin/members.php
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

$search = sanitize($_GET['q'] ?? '');
$where = ['cm.club_id = ?']; $params = [$cid];
if ($search) { $where[] = "(u.full_name LIKE ? OR u.student_id LIKE ?)"; $s = "%$search%"; $params[]=$s; $params[]=$s; }
$whereSQL = implode(' AND ', $where);

$members = $pdo->prepare("SELECT u.*, cm.role AS club_role, cm.joined_at, d.name AS dept FROM club_members cm JOIN users u ON u.id=cm.user_id LEFT JOIN departments d ON d.id=u.department_id WHERE $whereSQL ORDER BY cm.joined_at DESC");
$members->execute($params); $members = $members->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell('club_admin', $user, 'Members', 'Member Management', $pdo); ?>

<div class="section-toolbar mb-6">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700"><?= $club['name'] ?? 'My Club' ?> — <?= count($members) ?> Members</h2>
    </div>
    <form method="GET" class="search-bar" style="max-width:320px">
        <i class="ri-search-line search-icon"></i>
        <input type="text" name="q" placeholder="Search members..." value="<?= htmlspecialchars($search) ?>" />
    </form>
</div>

<div class="card">
<div class="table-wrapper">
<table class="table" id="membersTable">
    <thead>
        <tr>
            <th><input type="checkbox" id="selectAll" /></th>
            <th data-sort>Member</th>
            <th data-sort>Student ID</th>
            <th data-sort>Department</th>
            <th data-sort>Role</th>
            <th data-sort>Join Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($members as $m): ?>
        <tr>
            <td><input type="checkbox" class="row-check" value="<?= $m['id'] ?>" /></td>
            <td>
                <div class="user-cell">
                    <img src="<?= profilePicUrl($m['profile_picture']) ?>" alt="" />
                    <div><strong><?= htmlspecialchars($m['full_name']) ?></strong><span><?= htmlspecialchars($m['email']) ?></span></div>
                </div>
            </td>
            <td><?= htmlspecialchars($m['student_id'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($m['dept'] ?? 'N/A') ?></td>
            <td><?= getStatusBadge($m['club_role']) ?></td>
            <td><?= formatDate($m['joined_at'], 'M d, Y') ?></td>
            <td>
                <div class="table-actions">
                    <?php if ($m['club_role'] === 'member'): ?>
                    <button class="btn btn-outline-primary btn-sm" data-admin-action="promote" data-id="<?= $m['id'] ?>" data-endpoint="api/clubs.php" title="Promote" onclick="document.body.dispatchEvent(new CustomEvent('admin-action',{detail:{action:'promote',user_id:<?= $m['id'] ?>,club_id:<?= $cid ?>}}))"><i class="ri-arrow-up-line"></i></button>
                    <?php else: ?>
                    <button class="btn btn-ghost btn-sm" data-admin-action="demote" data-id="<?= $m['id'] ?>" data-endpoint="api/clubs.php" title="Demote"><i class="ri-arrow-down-line"></i></button>
                    <?php endif; ?>
                    <button class="btn btn-danger btn-sm remove-btn" data-user-id="<?= $m['id'] ?>" data-club-id="<?= $cid ?>" title="Remove" onclick="removeMember(<?= $m['id'] ?>,<?= $cid ?>,this)"><i class="ri-user-unfollow-line"></i></button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php renderDashboardEnd(); ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
async function removeMember(userId, clubId, btn) {
    if (!confirm('Remove this member from the club?')) return;
    btn.disabled = true;
    const data = await apiPost(BASE_URL+'/api/clubs.php', {action:'remove_member',user_id:userId,club_id:clubId});
    showToast(data.message, data.success?'success':'error');
    if (data.success) btn.closest('tr').remove();
    else btn.disabled = false;
}
</script>
<?= toggleSidebarScript(); ?>
