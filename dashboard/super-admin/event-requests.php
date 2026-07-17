<?php
// dashboard/super-admin/event-requests.php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dashboard-shell.php';
requireRole('super_admin');
define('EXTRA_CSS', 'dashboard.css');
$user = currentUser();

$filter = sanitize($_GET['filter'] ?? 'pending');
$stmt = $pdo->prepare("SELECT e.*, c.name AS club_name, c.slug AS club_slug, u.full_name AS creator_name FROM events e JOIN clubs c ON c.id=e.club_id JOIN users u ON u.id=e.created_by WHERE e.status=? ORDER BY e.created_at DESC");
$stmt->execute([$filter]); $events = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell('super_admin', $user, 'Event Requests', 'Event Approval Requests', $pdo); ?>

<div class="section-toolbar mb-6">
    <h2 style="font-size:1.25rem;font-weight:700">Event Requests</h2>
    <div class="filter-chips">
        <?php foreach(['pending'=>'⏳ Pending','approved'=>'✓ Approved','cancelled'=>'✗ Cancelled'] as $v=>$l): ?>
        <a href="?filter=<?= $v ?>" class="filter-chip <?= $filter===$v?'active':'' ?>"><?= $l ?></a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($events): ?>
<div style="display:flex;flex-direction:column;gap:1.25rem">
<?php foreach ($events as $ev): ?>
<div class="card">
    <div class="card-body">
        <div style="display:grid;grid-template-columns:auto 1fr auto;gap:1.5rem;align-items:start">
            <img src="<?= eventBannerUrl($ev['banner']) ?>" alt="" style="width:100px;height:68px;border-radius:10px;object-fit:cover" />
            <div>
                <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem">
                    <h3 style="margin:0;font-size:1rem"><?= htmlspecialchars($ev['title']) ?></h3>
                    <span class="badge badge-info"><?= htmlspecialchars($ev['category']) ?></span>
                    <?= getStatusBadge($ev['status']) ?>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:1rem;font-size:0.8rem;color:var(--color-text-3);margin-bottom:0.75rem">
                    <span><i class="ri-building-4-line"></i> <?= htmlspecialchars($ev['club_name']) ?></span>
                    <span><i class="ri-user-line"></i> By <?= htmlspecialchars($ev['creator_name']) ?></span>
                    <span><i class="ri-calendar-line"></i> <?= formatDateTime($ev['start_date']) ?></span>
                    <span><i class="ri-map-pin-line"></i> <?= htmlspecialchars($ev['venue'] ?? 'TBA') ?></span>
                    <span><i class="ri-group-line"></i> Max: <?= $ev['max_participants'] ?: 'Unlimited' ?></span>
                </div>
                <p style="font-size:0.85rem;color:var(--color-text-2);margin:0"><?= htmlspecialchars(substr($ev['description'] ?? '', 0, 180)) ?>...</p>
            </div>
            <?php if ($filter === 'pending'): ?>
            <div style="display:flex;flex-direction:column;gap:0.5rem;min-width:130px">
                <a href="<?= BASE_URL ?>/pages/event-detail.php?id=<?= $ev['id'] ?>" class="btn btn-ghost btn-sm" target="_blank"><i class="ri-eye-line"></i> Preview</a>
                <button class="btn btn-success btn-sm" onclick="approveEv(<?= $ev['id'] ?>,this)"><i class="ri-check-line"></i> Approve</button>
                <button class="btn btn-danger btn-sm" onclick="rejectEv(<?= $ev['id'] ?>,this)"><i class="ri-close-line"></i> Reject</button>
            </div>
            <?php else: ?>
            <div><a href="<?= BASE_URL ?>/pages/event-detail.php?id=<?= $ev['id'] ?>" class="btn btn-ghost btn-sm"><i class="ri-eye-line"></i> View</a></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state"><i class="ri-calendar-check-line empty-state-icon"></i><h3>No <?= $filter ?> event requests</h3><p>Event approval requests from Club Admins will appear here.</p></div>
<?php endif; ?>

<?php renderDashboardEnd(); ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
async function approveEv(id,btn){btn.disabled=true;const d=await apiPost(BASE_URL+'/api/events.php',{action:'approve_event',id});showToast(d.message,d.success?'success':'error');if(d.success)setTimeout(()=>location.reload(),800);else btn.disabled=false;}
async function rejectEv(id,btn){const r=prompt('Enter rejection reason:');if(!r)return;btn.disabled=true;const d=await apiPost(BASE_URL+'/api/events.php',{action:'reject_event',id,reason:r});showToast(d.message,d.success?'success':'error');if(d.success)setTimeout(()=>location.reload(),800);else btn.disabled=false;}
</script>
<?= toggleSidebarScript(); ?>
