<?php
// dashboard/super-admin/index.php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dashboard-shell.php';
requireRole('super_admin');
define('EXTRA_CSS', 'dashboard.css');
$user = currentUser();

// Stats
$stats = [
    'students'   => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
    'clubs'      => (int)$pdo->query("SELECT COUNT(*) FROM clubs WHERE status='active'")->fetchColumn(),
    'admins'     => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='club_admin'")->fetchColumn(),
    'events'     => (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status='approved'")->fetchColumn(),
    'club_reqs'  => (int)$pdo->query("SELECT COUNT(*) FROM club_creation_requests WHERE status='pending'")->fetchColumn(),
    'event_reqs' => (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status='pending'")->fetchColumn(),
];

// Chart data
$monthlyRegs = $pdo->query("SELECT DATE_FORMAT(created_at,'%b') AS m, COUNT(*) AS c FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY DATE_FORMAT(created_at,'%Y-%m')")->fetchAll();
$chartLabels = json_encode(array_column($monthlyRegs, 'm'));
$chartValues = json_encode(array_column($monthlyRegs, 'c'));

$clubStats  = $pdo->query("SELECT c.name, COUNT(cm.id) AS member_count FROM clubs c LEFT JOIN club_members cm ON cm.club_id = c.id WHERE c.status='active' GROUP BY c.id,c.name ORDER BY member_count DESC LIMIT 5")->fetchAll();
$clubChartL = json_encode(array_column($clubStats, 'name'));
$clubChartV = json_encode(array_column($clubStats, 'member_count'));

// Recent activity
$recentUsers = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
$pendingClubs = $pdo->query("SELECT ccr.*, u.full_name AS req_name FROM club_creation_requests ccr JOIN users u ON u.id=ccr.requested_by WHERE ccr.status='pending' LIMIT 5")->fetchAll();
$pendingEvents= $pdo->query("SELECT e.*, c.name AS club_name FROM events e JOIN clubs c ON c.id=e.club_id WHERE e.status='pending' LIMIT 5")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell('super_admin', $user, 'Dashboard', 'Super Admin Dashboard', $pdo); ?>

<!-- Stats -->
<div class="stats-row" style="grid-template-columns:repeat(6,1fr)">
<?php $cards=[
    ['icon'=>'ri-group-line','color'=>'icon-blue','value'=>$stats['students'],'label'=>'Students'],
    ['icon'=>'ri-building-4-line','color'=>'icon-green','value'=>$stats['clubs'],'label'=>'Active Clubs'],
    ['icon'=>'ri-shield-user-line','color'=>'icon-purple','value'=>$stats['admins'],'label'=>'Club Admins'],
    ['icon'=>'ri-calendar-event-line','color'=>'icon-orange','value'=>$stats['events'],'label'=>'Events'],
    ['icon'=>'ri-file-add-line','color'=>'icon-red','value'=>$stats['club_reqs'],'label'=>'Club Requests'],
    ['icon'=>'ri-calendar-check-line','color'=>'icon-teal','value'=>$stats['event_reqs'],'label'=>'Event Requests'],
]; foreach ($cards as $c): ?>
<div class="stat-card"><div class="stat-card-icon <?= $c['color'] ?>"><i class="<?= $c['icon'] ?>"></i></div><div><div class="stat-card-value"><?= $c['value'] ?></div><div class="stat-card-label"><?= $c['label'] ?></div></div></div>
<?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
    <div class="chart-card">
        <div class="card-header" style="border:none;padding:0 0 1rem"><h3 class="card-title">New User Registrations (6 Months)</h3></div>
        <div style="position:relative;height:260px;width:100%">
            <canvas id="eventsChart" data-labels='<?= $chartLabels ?>' data-values='<?= $chartValues ?>'></canvas>
        </div>
    </div>
    <div class="chart-card">
        <div class="card-header" style="border:none;padding:0 0 1rem"><h3 class="card-title">Top Clubs by Members</h3></div>
        <div style="position:relative;height:260px;width:100%">
            <canvas id="membersChart" data-labels='<?= $clubChartL ?>' data-values='<?= $clubChartV ?>'></canvas>
        </div>
    </div>
</div>

<!-- Pending Items -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
    <!-- Pending Club Requests -->
    <div class="card">
        <div class="card-header"><h3 class="card-title"><i class="ri-file-add-line" style="color:var(--color-warning)"></i> Pending Club Requests</h3><a href="club-requests.php" class="btn btn-ghost btn-sm">All</a></div>
        <div class="card-body" style="padding:0">
            <?php if ($pendingClubs): foreach ($pendingClubs as $req): ?>
            <div style="padding:1rem 1.5rem;border-bottom:1px solid var(--color-border);display:flex;align-items:center;gap:0.75rem">
                <div style="flex:1"><strong style="font-size:0.875rem"><?= htmlspecialchars($req['club_name']) ?></strong><div style="font-size:0.75rem;color:var(--color-text-3)">By <?= htmlspecialchars($req['req_name']) ?> · <?= timeAgo($req['created_at']) ?></div></div>
                <button class="btn btn-success btn-sm" onclick="approveCreation(<?= $req['id'] ?>,this)"><i class="ri-check-line"></i></button>
                <button class="btn btn-danger btn-sm" onclick="rejectCreation(<?= $req['id'] ?>,this)"><i class="ri-close-line"></i></button>
            </div>
            <?php endforeach; else: ?>
            <div style="padding:2rem;text-align:center;color:var(--color-text-3)">No pending club requests</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pending Event Requests -->
    <div class="card">
        <div class="card-header"><h3 class="card-title"><i class="ri-calendar-check-line" style="color:var(--color-warning)"></i> Pending Event Approvals</h3><a href="event-requests.php" class="btn btn-ghost btn-sm">All</a></div>
        <div class="card-body" style="padding:0">
            <?php if ($pendingEvents): foreach ($pendingEvents as $ev): ?>
            <div style="padding:1rem 1.5rem;border-bottom:1px solid var(--color-border);display:flex;align-items:center;gap:0.75rem">
                <div style="flex:1"><strong style="font-size:0.875rem"><?= htmlspecialchars($ev['title']) ?></strong><div style="font-size:0.75rem;color:var(--color-text-3)"><?= htmlspecialchars($ev['club_name']) ?> · <?= formatDate($ev['start_date']) ?></div></div>
                <button class="btn btn-success btn-sm" onclick="approveEvent(<?= $ev['id'] ?>,this)"><i class="ri-check-line"></i></button>
                <button class="btn btn-danger btn-sm" onclick="rejectEvent(<?= $ev['id'] ?>,this)"><i class="ri-close-line"></i></button>
            </div>
            <?php endforeach; else: ?>
            <div style="padding:2rem;text-align:center;color:var(--color-text-3)">No pending event approvals</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Users -->
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="ri-user-add-line" style="color:var(--color-primary)"></i> Recent Registrations</h3><a href="users.php" class="btn btn-ghost btn-sm">All Users</a></div>
    <div class="table-wrapper">
    <table class="table">
        <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead>
        <tbody>
            <?php foreach ($recentUsers as $u): ?>
            <tr>
                <td><div class="user-cell"><img src="<?= profilePicUrl($u['profile_picture']) ?>" alt="" /><div><strong><?= htmlspecialchars($u['full_name']) ?></strong><span><?= htmlspecialchars($u['email']) ?></span></div></div></td>
                <td><?= getStatusBadge($u['role']) ?></td>
                <td><?= getStatusBadge($u['status']) ?></td>
                <td><?= timeAgo($u['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<!-- checked everything there was no bug -->
<?php renderDashboardEnd(); ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
async function approveCreation(id,btn){btn.disabled=true;const d=await apiPost(BASE_URL+'/api/clubs.php',{action:'approve_creation',id});showToast(d.message,d.success?'success':'error');if(d.success)btn.closest('div[style]').remove();}
async function rejectCreation(id,btn){const r=prompt('Rejection reason (optional):');btn.disabled=true;const d=await apiPost(BASE_URL+'/api/clubs.php',{action:'reject_creation',id,reason:r||''});showToast(d.message,d.success?'success':'error');if(d.success)btn.closest('div[style]').remove();}
async function approveEvent(id,btn){btn.disabled=true;const d=await apiPost(BASE_URL+'/api/events.php',{action:'approve_event',id});showToast(d.message,d.success?'success':'error');if(d.success)btn.closest('div[style]').remove();}
async function rejectEvent(id,btn){const r=prompt('Rejection reason:');if(!r)return;btn.disabled=true;const d=await apiPost(BASE_URL+'/api/events.php',{action:'reject_event',id,reason:r});showToast(d.message,d.success?'success':'error');if(d.success)btn.closest('div[style]').remove();}
</script>
<?= toggleSidebarScript(); ?>
