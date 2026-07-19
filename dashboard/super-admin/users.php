<?php
// dashboard/super-admin/users.php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dashboard-shell.php';
requireRole('super_admin');
define('EXTRA_CSS', 'dashboard.css');
$user = currentUser();

$search  = sanitize($_GET['q'] ?? '');
$role    = sanitize($_GET['role'] ?? 'all');
$status  = sanitize($_GET['status'] ?? 'all');
$page    = max(1,(int)($_GET['page']??1));
$perPage = 20;

$where=['1=1']; $params=[];
if ($search) { $where[]="(full_name LIKE ? OR email LIKE ? OR student_id LIKE ?)"; $s="%$search%"; $params[]=$s;$params[]=$s;$params[]=$s; }
if ($role!=='all') { $where[]="role=?"; $params[]=$role; }
if ($status!=='all') { $where[]="status=?"; $params[]=$status; }

$whereSQL = implode(' AND ', $where);
$total = (int)$pdo->prepare("SELECT COUNT(*) FROM users WHERE $whereSQL")->execute($params) ? $pdo->prepare("SELECT COUNT(*) FROM users WHERE $whereSQL")->execute($params) : 0;
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $whereSQL"); $totalStmt->execute($params); $total=(int)$totalStmt->fetchColumn();
$pg = paginate($total, $perPage, $page);

$usersStmt = $pdo->prepare("SELECT u.*, d.name AS dept FROM users u LEFT JOIN departments d ON d.id=u.department_id WHERE $whereSQL ORDER BY u.created_at DESC LIMIT ? OFFSET ?");
$usersStmt->execute([...$params, $perPage, $pg['offset']]); $users = $usersStmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell('super_admin', $user, 'Manage Users', 'User Management', $pdo); ?>

<!-- Toolbar -->
<div class="section-toolbar mb-6" style="flex-wrap:wrap;gap:1rem">
    <form method="GET" class="search-bar" style="max-width:320px">
        <i class="ri-search-line search-icon"></i>
        <input type="text" name="q" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>" />
        <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>" />
        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>" />
    </form>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap">
        <select class="form-control" style="width:auto" onchange="applyFilter('role',this.value)">
            <?php foreach(['all'=>'All Roles','student'=>'Students','club_admin'=>'Club Admins','super_admin'=>'Super Admins'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $role===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-control" style="width:auto" onchange="applyFilter('status',this.value)">
            <?php foreach(['all'=>'All Status','active'=>'Active','blocked'=>'Blocked'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $status===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-ghost btn-sm" data-export-csv="usersTable" data-filename="users"><i class="ri-download-2-line"></i> Export CSV</button>
    </div>
</div>
<p class="text-muted text-sm mb-4">Showing <?= count($users) ?> of <?= $total ?> users</p>

<div class="card">
<div class="table-wrapper">
<table class="table" id="usersTable">
    <thead>
        <tr>
            <th><input type="checkbox" id="selectAll" /></th>
            <th data-sort>Name</th>
            <th data-sort>Student ID</th>
            <th data-sort>Department</th>
            <th data-sort>Role</th>
            <th data-sort>Status</th>
            <th data-sort>Joined</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><input type="checkbox" class="row-check" value="<?= $u['id'] ?>" /></td>
            <td><div class="user-cell"><img src="<?= profilePicUrl($u['profile_picture']) ?>" alt="" /><div><strong><?= htmlspecialchars($u['full_name']) ?></strong><span><?= htmlspecialchars($u['email']) ?></span></div></div></td>
            <td><?= htmlspecialchars($u['student_id'] ?? 'N/A') ?></td>
            <td style="font-size:0.8rem"><?= htmlspecialchars($u['dept'] ?? 'N/A') ?></td>
            <td><?= getStatusBadge($u['role']) ?></td>
            <td><?= getStatusBadge($u['status']) ?></td>
            <td style="font-size:0.8rem"><?= formatDate($u['created_at'],'M d, Y') ?></td>
            <td>
                <div class="table-actions">
                    <?php if ($u['status'] === 'active'): ?>
                    <button class="btn btn-warning btn-sm" title="Block" onclick="userAction('block_user',<?= $u['id'] ?>,this)"><i class="ri-lock-line"></i></button>
                    <?php else: ?>
                    <button class="btn btn-success btn-sm" title="Activate" onclick="userAction('activate_user',<?= $u['id'] ?>,this)"><i class="ri-lock-unlock-line"></i></button>
                    <?php endif; ?>
                    <?php if ($u['id'] != $user['id']): ?>
                    <button class="btn btn-danger btn-sm" title="Delete" onclick="userAction('delete_user',<?= $u['id'] ?>,this,true)"><i class="ri-delete-bin-line"></i></button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<!-- Pagination -->
<?php if ($pg['total_pages'] > 1): ?>
<div class="pagination">
    <?php if ($pg['has_prev']): ?><a href="?q=<?= urlencode($search) ?>&role=<?= $role ?>&status=<?= $status ?>&page=<?= $pg['current']-1 ?>" class="page-item"><i class="ri-arrow-left-s-line"></i></a><?php endif; ?>
    <?php for ($i=1;$i<=$pg['total_pages'];$i++): ?><a href="?q=<?= urlencode($search) ?>&role=<?= $role ?>&status=<?= $status ?>&page=<?= $i ?>" class="page-item <?= $i===$pg['current']?'active':'' ?>"><?= $i ?></a><?php endfor; ?>
    <?php if ($pg['has_next']): ?><a href="?q=<?= urlencode($search) ?>&role=<?= $role ?>&status=<?= $status ?>&page=<?= $pg['current']+1 ?>" class="page-item"><i class="ri-arrow-right-s-line"></i></a><?php endif; ?>
</div>
<?php endif; ?>

<?php renderDashboardEnd(); ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
function applyFilter(key, val) {
    const url = new URL(location.href);
    url.searchParams.set(key, val);
    url.searchParams.delete('page');
    location.href = url.toString();
}
async function userAction(action, id, btn, confirm_=false) {
    if (confirm_ && !confirm('Delete this user permanently?')) return;
    btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line spin"></i>';
    const data = await apiPost(BASE_URL+'/api/users.php', {action, id});
    showToast(data.message, data.success?'success':'error');
    if (data.success && data.reload) location.reload();
    else if (data.success) { btn.disabled=false; location.reload(); }
    else btn.disabled=false;
}
</script>
<?= toggleSidebarScript(); ?>
