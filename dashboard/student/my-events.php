<?php
// dashboard/student/my-events.php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dashboard-shell.php';
requireRole('student');
define('EXTRA_CSS', 'dashboard.css');
$user = currentUser(); $uid = $user['id'];

$events = $pdo->prepare("SELECT e.*, c.name AS club_name, er.status AS reg_status, er.registered_at FROM event_registrations er JOIN events e ON e.id = er.event_id JOIN clubs c ON c.id = e.club_id WHERE er.user_id = ? ORDER BY e.start_date DESC");
$events->execute([$uid]); $events = $events->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell('student', $user, 'My Events', 'My Events', $pdo); ?>

<?php if ($events): ?>
<div class="grid-auto">
    <?php foreach ($events as $ev):
        $now = time(); $s = strtotime($ev['start_date']); $e_ = strtotime($ev['end_date']);
        $status = $now < $s ? 'upcoming' : ($now <= $e_ ? 'ongoing' : 'completed');
    ?>
    <div class="event-card">
        <div class="event-card-banner">
            <img src="<?= eventBannerUrl($ev['banner']) ?>" alt="" />
            <div class="event-card-status">
                <?php match($status) {
                    'upcoming'  => print '<span class="badge badge-info">Upcoming</span>',
                    'ongoing'   => print '<span class="badge badge-success">Ongoing</span>',
                    default     => print '<span class="badge badge-secondary">Completed</span>',
                }; ?>
            </div>
        </div>
        <div class="event-card-body">
            <div class="event-card-club"><i class="ri-building-4-line"></i> <?= htmlspecialchars($ev['club_name']) ?></div>
            <h3 class="event-card-title"><?= htmlspecialchars($ev['title']) ?></h3>
            <div class="event-card-meta">
                <div class="event-meta-row"><i class="ri-calendar-line"></i> <?= formatDateTime($ev['start_date']) ?></div>
                <div class="event-meta-row"><i class="ri-map-pin-line"></i> <?= htmlspecialchars($ev['venue'] ?? 'TBA') ?></div>
                <div class="event-meta-row"><i class="ri-checkbox-circle-line"></i> Reg. Status: <?= getStatusBadge($ev['reg_status']) ?></div>
            </div>
            <div class="event-card-footer">
                <a href="<?= BASE_URL ?>/pages/event-detail.php?id=<?= $ev['id'] ?>" class="btn btn-outline-primary btn-sm">Details</a>
                <?php if ($status === 'upcoming'): ?>
                <button class="btn btn-ghost btn-sm cancel-btn" data-event-id="<?= $ev['id'] ?>"><i class="ri-close-line"></i> Cancel</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state">
    <i class="ri-calendar-event-line empty-state-icon"></i><h3>No event registrations yet</h3>
    <p>Browse and register for upcoming events!</p>
    <a href="<?= BASE_URL ?>/pages/events.php" class="btn btn-primary mt-4">Browse Events</a>
</div>
<?php endif; ?>

<?php renderDashboardEnd(); ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script>
document.querySelectorAll('.cancel-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        if (!confirm('Cancel your registration for this event?')) return;
        const data = await apiPost(BASE_URL+'/api/events.php', {action:'cancel_registration',event_id:parseInt(btn.dataset.eventId)});
        showToast(data.message, data.success?'success':'error');
        if (data.success) btn.closest('.event-card').remove();
    });
});
</script>
<?= toggleSidebarScript(); ?>
