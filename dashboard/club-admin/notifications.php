<?php
// dashboard/club-admin/notifications.php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dashboard-shell.php';
requireRole('club_admin');
define('EXTRA_CSS', 'dashboard.css');
$user = currentUser(); $uid = $user['id'];

$pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$uid]);
$notifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50");
$notifs->execute([$uid]); $notifs = $notifs->fetchAll();
$icons = ['club_approved'=>'checkbox-circle','club_rejected'=>'close-circle','event_approved'=>'calendar-check','join_request'=>'user-add','member_removed'=>'user-unfollow','new_registration'=>'user-follow'];

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell('club_admin', $user, 'Notifications', 'Notifications', $pdo); ?>
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="ri-notification-3-line" style="color:var(--color-primary)"></i> Notifications</h3><span class="text-muted text-sm"><?= count($notifs) ?> total</span></div>
    <div class="card-body" style="padding:0.5rem">
        <?php if ($notifs): foreach ($notifs as $n): $icon = $icons[$n['type']] ?? 'bell'; ?>
        <div class="notification-item">
            <div class="notif-icon-lg"><i class="ri-<?= $icon ?>-line"></i></div>
            <div class="notif-content" style="flex:1"><strong><?= htmlspecialchars($n['title']) ?></strong><p><?= htmlspecialchars($n['message']) ?></p><time><?= formatDateTime($n['created_at']) ?></time></div>
        </div>
        <?php endforeach; else: ?>
        <div class="empty-state" style="padding:3rem"><i class="ri-notification-off-line empty-state-icon"></i><h3>No notifications yet</h3></div>
        <?php endif; ?>
    </div>
</div>
<?php renderDashboardEnd(); ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?= toggleSidebarScript(); ?>
//fixed some issues