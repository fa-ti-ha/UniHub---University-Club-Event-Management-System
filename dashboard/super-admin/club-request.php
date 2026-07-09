<?php
// dashboard/super-admin/club-requests.php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dashboard-shell.php';
requireRole('super_admin');
define('EXTRA_CSS', 'dashboard.css');
$user = currentUser();

$filter = sanitize($_GET['filter'] ?? 'pending');
$stmt = $pdo->prepare("SELECT ccr.*, u.full_name AS req_name, u.email AS req_email, u.student_id AS req_student_id FROM club_creation_requests ccr JOIN users u ON u.id=ccr.requested_by WHERE ccr.status=? ORDER BY ccr.created_at DESC");
$stmt->execute([$filter]); $requests = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell('super_admin', $user, 'Club Requests', 'Club Creation Requests', $pdo); ?>

<div class="section-toolbar mb-6">
    <h2 style="font-size:1.25rem;font-weight:700">Club Creation Requests</h2>
    <div class="filter-chips">
        <?php foreach(['pending'=>'⏳ Pending','approved'=>'✓ Approved','rejected'=>'✗ Rejected'] as $v=>$l): ?>
        <a href="?filter=<?= $v ?>" class="filter-chip <?= $filter===$v?'active':'' ?>"><?= $l ?></a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($requests): ?>
<div style="display:flex;flex-direction:column;gap:1.5rem">
<?php foreach ($requests as $req): ?>
<div class="card">
    <div class="card-body">
        <div style="display:grid;grid-template-columns:auto 1fr auto;gap:1.5rem;align-items:start">
            <div>
                <?php if ($req['logo']): ?>
                <img src="<?= UPLOAD_URL ?>clubs/logos/<?= $req['logo'] ?>" alt="" style="width:64px;height:64px;border-radius:12px;object-fit:cover;border:2px solid var(--color-border)" />
                <?php else: ?>
                <div style="width:64px;height:64px;border-radius:12px;background:var(--color-primary-light);display:flex;align-items:center;justify-content:center;font-size:2rem;color:var(--color-primary)"><i class="ri-building-4-line"></i></div>
                <?php endif; ?>
            </div>
            <div>
                <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem">
                    <h3 style="margin:0;font-size:1.1rem"><?= htmlspecialchars($req['club_name']) ?></h3>
                    <?= getStatusBadge($req['status']) ?>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1rem">
                    <div><span style="font-size:0.75rem;color:var(--color-text-3)">Requested by</span><br><strong><?= htmlspecialchars($req['req_name']) ?></strong> (<?= htmlspecialchars($req['req_email']) ?>)</div>
                    <div><span style="font-size:0.75rem;color:var(--color-text-3)">Submitted</span><br><strong><?= timeAgo($req['created_at']) ?></strong></div>
                    <?php if ($req['supervisor_name']): ?><div><span style="font-size:0.75rem;color:var(--color-text-3)">Supervisor</span><br><?= htmlspecialchars($req['supervisor_name']) ?></div><?php endif; ?>
                </div>
                <?php if ($req['description']): ?><p style="color:var(--color-text-2);font-size:0.875rem;margin-bottom:0.75rem"><?= htmlspecialchars(substr($req['description'], 0, 200)) ?>...</p><?php endif; ?>
                <?php if ($req['reason']): ?><p style="background:var(--color-bg-muted);padding:0.75rem;border-radius:8px;font-size:0.8rem;color:var(--color-text-2);border-left:3px solid var(--color-primary)"><strong>Why this club:</strong> <?= htmlspecialchars($req['reason']) ?></p><?php endif; ?>
                <?php if ($req['rejection_reason']): ?><p style="background:#fff1f2;padding:0.75rem;border-radius:8px;font-size:0.8rem;color:#991b1b;border-left:3px solid #ef4444"><strong>Rejection reason:</strong> <?= htmlspecialchars($req['rejection_reason']) ?></p><?php endif; ?>
            </div>
            <?php if ($filter === 'pending'): ?>
            <div style="display:flex;flex-direction:column;gap:0.5rem;min-width:130px">
                <button class="btn btn-success btn-sm" onclick="approveReq(<?= $req['id'] ?>,this)"><i class="ri-check-line"></i> Approve</button>
                <button class="btn btn-danger btn-sm" onclick="rejectReq(<?= $req['id'] ?>,this)"><i class="ri-close-line"></i> Reject</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state"><i class="ri-file-add-line empty-state-icon"></i><h3>No <?= $filter ?> requests</h3><p>Club creation requests will appear here.</p></div>
<?php endif; ?>

<?php renderDashboardEnd(); ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
async function approveReq(id, btn) {
    btn.disabled=true; btn.innerHTML='<i class="ri-loader-4-line spin"></i>';
    const d = await apiPost(BASE_URL+'/api/clubs.php', {action:'approve_creation', id});
    showToast(d.message, d.success?'success':'error');
    if(d.success) setTimeout(()=>location.reload(),800);
    else btn.disabled=false;
}
async function rejectReq(id, btn) {
    const reason = prompt('Enter rejection reason:'); if(!reason) return;
    btn.disabled=true;
    const d = await apiPost(BASE_URL+'/api/clubs.php', {action:'reject_creation', id, reason});
    showToast(d.message, d.success?'success':'error');
    if(d.success) setTimeout(()=>location.reload(),800);
    else btn.disabled=false;
}
</script>
<?= toggleSidebarScript(); ?>
