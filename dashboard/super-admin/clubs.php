<?php
// dashboard/super-admin/clubs.php — Manage all clubs
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dashboard-shell.php';
requireRole('super_admin');
define('EXTRA_CSS', 'dashboard.css');
$user = currentUser();

$search = sanitize($_GET['q'] ?? '');
$status = sanitize($_GET['status'] ?? 'all');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

$where = ['1=1']; $params = [];
if ($search) { $where[] = "(c.name LIKE ? OR c.category LIKE ?)"; $s = "%$search%"; $params[] = $s; $params[] = $s; }
if ($status !== 'all') { $where[] = "c.status = ?"; $params[] = $status; }
$whereSQL = implode(' AND ', $where);

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM clubs c WHERE $whereSQL");
$totalStmt->execute($params); $total = (int)$totalStmt->fetchColumn();
$pg = paginate($total, $perPage, $page);

$clubsStmt = $pdo->prepare("SELECT c.*, u.full_name AS admin_name FROM clubs c LEFT JOIN users u ON u.id = c.admin_id WHERE $whereSQL ORDER BY c.created_at DESC LIMIT ? OFFSET ?");
$clubsStmt->execute([...$params, $perPage, $pg['offset']]);
$clubs = $clubsStmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell('super_admin', $user, 'Manage Clubs', 'Club Management', $pdo); ?>

<div class="section-toolbar mb-6" style="flex-wrap:wrap;gap:1rem">
    <form method="GET" class="search-bar" style="max-width:320px">
        <i class="ri-search-line search-icon"></i>
        <input type="text" name="q" placeholder="Search clubs..." value="<?= htmlspecialchars($search) ?>" />
        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>" />
    </form>
    <div style="display:flex;gap:0.75rem;align-items:center">
        <div class="filter-chips">
            <?php foreach(['all'=>'All','active'=>'Active','pending'=>'Pending','suspended'=>'Suspended','rejected'=>'Rejected'] as $v=>$l): ?>
            <a href="?q=<?= urlencode($search) ?>&status=<?= $v ?>" class="filter-chip <?= $status===$v?'active':'' ?>"><?= $l ?></a>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-ghost btn-sm" data-export-csv="clubsTable" data-filename="clubs"><i class="ri-download-2-line"></i> Export</button>
    </div>
</div>

<p class="text-muted text-sm mb-4">Showing <?= count($clubs) ?> of <?= $total ?> clubs</p>

<div class="card">
<div class="table-wrapper">
<table class="table" id="clubsTable">
    <thead>
        <tr><th>Club</th><th>Category</th><th>Admin</th><th>Members</th><th>Status</th><th>Created</th><th>Actions</th></tr>
    </thead>
    <tbody>
        <?php if ($clubs): foreach ($clubs as $club): ?>
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:0.75rem">
                    <img src="<?= clubLogoUrl($club['logo']) ?>" alt="" style="width:40px;height:40px;border-radius:8px;object-fit:cover" />
                    <div>
                        <strong style="font-size:0.875rem"><?= htmlspecialchars($club['name']) ?></strong>
                        <div style="font-size:0.75rem;color:var(--color-text-3)"><?= htmlspecialchars(substr($club['short_description'] ?? '', 0, 50)) ?>...</div>
                    </div>
                </div>
            </td>
            <td><?= htmlspecialchars($club['category']) ?></td>
            <td style="font-size:0.875rem"><?= htmlspecialchars($club['admin_name'] ?? 'N/A') ?></td>
            <td><strong><?= $club['total_members'] ?></strong></td>
            <td><?= getStatusBadge($club['status']) ?></td>
            <td style="font-size:0.8rem"><?= formatDate($club['created_at'], 'M d, Y') ?></td>
            <td>
                <div class="table-actions">
                    <a href="<?= BASE_URL ?>/pages/club-detail.php?slug=<?= $club['slug'] ?>" class="btn btn-ghost btn-sm" title="View"><i class="ri-eye-line"></i></a>
                    <?php if ($club['status'] !== 'active'): ?>
                    <button class="btn btn-success btn-sm" title="Approve" onclick="clubAction('approve_club',<?= $club['id'] ?>,<?= (int)$club['admin_id'] ?>,this)"><i class="ri-check-line"></i></button>
                    <?php endif; ?>
                    <?php if ($club['status'] === 'active'): ?>
                    <button class="btn btn-warning btn-sm" title="Suspend" onclick="clubAction('suspend_club',<?= $club['id'] ?>,0,this)"><i class="ri-pause-circle-line"></i></button>
                    <?php endif; ?>
                    <button class="btn btn-danger btn-sm" title="Delete" onclick="if(confirm('Delete this club permanently?')) clubAction('delete_club',<?= $club['id'] ?>,0,this)"><i class="ri-delete-bin-line"></i></button>
                </div>
            </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--color-text-3)">No clubs found</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
</div>

<?php if ($pg['total_pages'] > 1): ?>
<div class="pagination">
    <?php if ($pg['has_prev']): ?><a href="?q=<?= urlencode($search) ?>&status=<?= $status ?>&page=<?= $pg['current']-1 ?>" class="page-item"><i class="ri-arrow-left-s-line"></i></a><?php endif; ?>
    <?php for ($i=1;$i<=$pg['total_pages'];$i++): ?><a href="?q=<?= urlencode($search) ?>&status=<?= $status ?>&page=<?= $i ?>" class="page-item <?= $i===$pg['current']?'active':'' ?>"><?= $i ?></a><?php endfor; ?>
    <?php if ($pg['has_next']): ?><a href="?q=<?= urlencode($search) ?>&status=<?= $status ?>&page=<?= $pg['current']+1 ?>" class="page-item"><i class="ri-arrow-right-s-line"></i></a><?php endif; ?>
</div>
<?php endif; ?>

<?php renderDashboardEnd(); ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
async function clubAction(action, id, adminId, btn) {
    btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line spin"></i>';
    const data = await apiPost(BASE_URL+'/api/clubs.php', {action, id, admin_id: adminId});
    showToast(data.message, data.success?'success':'error');
    if (data.success) setTimeout(()=>location.reload(), 800);
    else btn.disabled = false;
}
</script>
<?= toggleSidebarScript(); ?>
