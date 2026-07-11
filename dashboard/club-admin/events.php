<?php
// dashboard/club-admin/events.php
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

$events = $pdo->prepare("SELECT e.*, (SELECT COUNT(*) FROM event_registrations WHERE event_id=e.id) AS registrations FROM events e WHERE e.club_id=? ORDER BY e.start_date DESC");
$events->execute([$cid]); $events = $events->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell('club_admin', $user, 'Events', 'My Club Events', $pdo); ?>

<div class="section-toolbar mb-6">
    <h2 style="font-size:1.25rem;font-weight:700">Events — <?= htmlspecialchars($club['name'] ?? '') ?></h2>
    <a href="create-event.php" class="btn btn-primary"><i class="ri-add-line"></i> Create Event</a>
</div>

<div class="card">
<div class="table-wrapper">
<table class="table">
    <thead><tr><th>Event</th><th>Date</th><th>Venue</th><th>Registrations</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
        <?php if ($events): foreach ($events as $ev): ?>
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:0.75rem">
                    <img src="<?= eventBannerUrl($ev['banner']) ?>" alt="" style="width:48px;height:36px;border-radius:6px;object-fit:cover" />
                    <div><strong style="font-size:0.875rem"><?= htmlspecialchars($ev['title']) ?></strong><div style="font-size:0.75rem;color:var(--color-text-3)"><?= htmlspecialchars($ev['category']) ?></div></div>
                </div>
            </td>
            <td style="font-size:0.875rem"><?= formatDate($ev['start_date']) ?></td>
            <td style="font-size:0.875rem"><?= htmlspecialchars($ev['venue'] ?? 'TBA') ?></td>
            <td>
                <?= $ev['registrations'] ?> / <?= $ev['max_participants'] > 0 ? $ev['max_participants'] : '∞' ?>
                <?php if ($ev['max_participants'] > 0): $pct = min(100, round($ev['registrations']/$ev['max_participants']*100)); ?>
                <div class="progress-bar" style="margin-top:4px;height:3px"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
                <?php endif; ?>
            </td>
            <td><?= getStatusBadge($ev['status']) ?></td>
            <td>
                <div class="table-actions">
                    <a href="<?= BASE_URL ?>/pages/event-detail.php?id=<?= $ev['id'] ?>" class="btn btn-ghost btn-sm" title="View"><i class="ri-eye-line"></i></a>
                </div>
            </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--color-text-3)"><i class="ri-calendar-line" style="font-size:2rem;display:block;margin-bottom:0.5rem"></i>No events yet. <a href="create-event.php" class="text-primary">Create your first event</a></td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
</div>

<?php renderDashboardEnd(); ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?= toggleSidebarScript(); ?>
