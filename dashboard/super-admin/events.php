<?php
// dashboard/super-admin/events.php — Manage all events
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
if ($search) { $where[] = "(e.title LIKE ? OR c.name LIKE ?)"; $s = "%$search%"; $params[] = $s; $params[] = $s; }
if ($status !== 'all') { $where[] = "e.status = ?"; $params[] = $status; }
$whereSQL = implode(' AND ', $where);

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM events e JOIN clubs c ON c.id=e.club_id WHERE $whereSQL");
$totalStmt->execute($params); $total = (int)$totalStmt->fetchColumn();
$pg = paginate($total, $perPage, $page);

$eventsStmt = $pdo->prepare("SELECT e.*, c.name AS club_name FROM events e JOIN clubs c ON c.id=e.club_id WHERE $whereSQL ORDER BY e.created_at DESC LIMIT ? OFFSET ?");
$eventsStmt->execute([...$params, $perPage, $pg['offset']]);
$events = $eventsStmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell('super_admin', $user, 'Manage Events', 'Event Management', $pdo); ?>

<div class="section-toolbar mb-6" style="flex-wrap:wrap;gap:1rem">
    <form method="GET" class="search-bar" style="max-width:320px">
        <i class="ri-search-line search-icon"></i>
        <input type="text" name="q" placeholder="Search events..." value="<?= htmlspecialchars($search) ?>" />
        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>" />
    </form>
    <div class="filter-chips">
        <?php foreach(['all'=>'All','pending'=>'Pending','approved'=>'Approved','cancelled'=>'Cancelled'] as $v=>$l): ?>
        <a href="?q=<?= urlencode($search) ?>&status=<?= $v ?>" class="filter-chip <?= $status===$v?'active':'' ?>"><?= $l ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
<div class="table-wrapper">
<table class="table">
    <thead>
        <tr><th>Event</th><th>Club</th><th>Date</th><th>Reg.</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
        <?php if ($events): foreach ($events as $ev): ?>
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:0.75rem">
                    <img src="<?= eventBannerUrl($ev['banner']) ?>" alt="" style="width:56px;height:36px;border-radius:6px;object-fit:cover" />
                    <div>
                        <strong style="font-size:0.875rem"><?= htmlspecialchars($ev['title']) ?></strong>
                        <div style="font-size:0.75rem;color:var(--color-text-3)"><?= htmlspecialchars($ev['category']) ?> · <?= htmlspecialchars($ev['venue'] ?? 'TBA') ?></div>
                    </div>
                </div>
            </td>
            <td style="font-size:0.875rem"><?= htmlspecialchars($ev['club_name']) ?></td>
            <td style="font-size:0.8rem"><?= formatDate($ev['start_date']) ?></td>
            <td style="font-size:0.875rem"><?= $ev['current_participants'] ?><?= $ev['max_participants']>0 ? '/'.$ev['max_participants'] : '' ?></td>
            <td><?= getStatusBadge($ev['status']) ?></td>
            <td>
                <div class="table-actions">
                    <a href="<?= BASE_URL ?>/pages/event-detail.php?id=<?= $ev['id'] ?>" class="btn btn-ghost btn-sm" title="View"><i class="ri-eye-line"></i></a>
                    <?php if ($ev['status'] === 'pending'): ?>
                    <button class="btn btn-success btn-sm" title="Approve" onclick="eventAction('approve_event',<?= $ev['id'] ?>,this)"><i class="ri-check-line"></i></button>
                    <button class="btn btn-warning btn-sm" title="Reject" onclick="rejectEvent(<?= $ev['id'] ?>,this)"><i class="ri-close-line"></i></button>
                    <?php endif; ?>
                    <button class="btn btn-danger btn-sm" title="Delete" onclick="if(confirm('Delete this event permanently?')) eventAction('delete_event',<?= $ev['id'] ?>,this)"><i class="ri-delete-bin-line"></i></button>
                </div>
            </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--color-text-3)">No events found</td></tr>
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
async function eventAction(action, id, btn) {
    btn.disabled=true; btn.innerHTML='<i class="ri-loader-4-line spin"></i>';
    const d = await apiPost(BASE_URL+'/api/events.php', {action, id});
    showToast(d.message, d.success?'success':'error');
    if(d.success) setTimeout(()=>location.reload(),800);
    else btn.disabled=false;
}
async function rejectEvent(id, btn) {
    const reason = prompt('Rejection reason:'); if(!reason) return;
    btn.disabled=true;
    const d = await apiPost(BASE_URL+'/api/events.php', {action:'reject_event', id, reason});
    showToast(d.message, d.success?'success':'error');
    if(d.success) setTimeout(()=>location.reload(),800);
    else btn.disabled=false;
}
</script>
<?= toggleSidebarScript(); ?>
