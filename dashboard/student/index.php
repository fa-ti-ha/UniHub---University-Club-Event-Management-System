<?php
// dashboard/student/index.php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dashboard-shell.php';
requireRole('student');

define('PAGE_TITLE', 'Student Dashboard');
define('EXTRA_CSS', 'dashboard.css');

$user = currentUser();
$uid  = $user['id'];

// Stats
$clubsJoined  = (int)$pdo->prepare("SELECT COUNT(*) FROM club_members WHERE user_id = ?")->execute([$uid]) ? $pdo->query("SELECT COUNT(*) FROM club_members WHERE user_id = $uid")->fetchColumn() : 0;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM club_members WHERE user_id = ?"); $stmt->execute([$uid]); $clubsJoined = (int)$stmt->fetchColumn();
$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE user_id = ?"); $stmt2->execute([$uid]); $eventsReg = (int)$stmt2->fetchColumn();
$stmt3 = $pdo->prepare("SELECT COUNT(*) FROM event_registrations er JOIN events e ON e.id = er.event_id WHERE er.user_id = ? AND e.start_date > NOW()"); $stmt3->execute([$uid]); $upcoming = (int)$stmt3->fetchColumn();
$stmt4 = $pdo->prepare("SELECT COUNT(*) FROM club_join_requests WHERE user_id = ? AND status = 'pending'"); $stmt4->execute([$uid]); $pending = (int)$stmt4->fetchColumn();

// My Clubs
$myClubs = $pdo->prepare("SELECT c.*, cm.role AS my_role FROM club_members cm JOIN clubs c ON c.id = cm.club_id WHERE cm.user_id = ? ORDER BY cm.joined_at DESC LIMIT 6");
$myClubs->execute([$uid]); $myClubs = $myClubs->fetchAll();

// Upcoming events I'm registered for
$myEvents = $pdo->prepare("SELECT e.*, c.name AS club_name, er.status AS reg_status FROM event_registrations er JOIN events e ON e.id = er.event_id JOIN clubs c ON c.id = e.club_id WHERE er.user_id = ? AND e.start_date > NOW() ORDER BY e.start_date ASC LIMIT 5");
$myEvents->execute([$uid]); $myEvents = $myEvents->fetchAll();

// Notifications
$notifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notifs->execute([$uid]); $notifs = $notifs->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell('student', $user, 'Dashboard', 'My Dashboard', $pdo); ?>

<!-- Welcome Card -->
<div class="welcome-card">
    <img src="<?= profilePicUrl($user['profile_picture']) ?>" alt="" class="welcome-avatar" />
    <div class="welcome-text">
        <h2>Welcome back, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?>! 👋</h2>
        <p><i class="ri-id-card-line"></i> <?= htmlspecialchars($user['student_id'] ?? 'N/A') ?></p>
        <?php if ($user['department_id']): $dept = $pdo->prepare("SELECT name FROM departments WHERE id=?"); $dept->execute([$user['department_id']]); $deptName = $dept->fetchColumn(); ?>
        <p><i class="ri-book-2-line"></i> <?= htmlspecialchars($deptName) ?> — Batch <?= htmlspecialchars($user['batch'] ?? '') ?></p>
        <?php endif; ?>
        <span class="student-id mt-2">Student Dashboard</span>
    </div>
</div>

<!-- Stats -->
<div class="stats-row">
    <?php $cards = [
        ['icon'=>'ri-building-4-line','color'=>'icon-blue',  'value'=>$clubsJoined,'label'=>'Clubs Joined'],
        ['icon'=>'ri-calendar-line',  'color'=>'icon-green', 'value'=>$eventsReg,  'label'=>'Events Registered'],
        ['icon'=>'ri-timer-flash-line','color'=>'icon-purple','value'=>$upcoming,   'label'=>'Upcoming Events'],
        ['icon'=>'ri-time-line',      'color'=>'icon-orange','value'=>$pending,     'label'=>'Pending Requests'],
    ];
    foreach ($cards as $c): ?>
    <div class="stat-card">
        <div class="stat-card-icon <?= $c['color'] ?>"><i class="<?= $c['icon'] ?>"></i></div>
        <div><div class="stat-card-value"><?= $c['value'] ?></div><div class="stat-card-label"><?= $c['label'] ?></div></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="dashboard-grid">
    <!-- My Clubs -->
    <div>
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title"><i class="ri-building-4-line" style="color:var(--color-primary)"></i> My Clubs</h3>
                <a href="my-clubs.php" class="btn btn-ghost btn-sm">View All</a>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:0.75rem">
                <?php if ($myClubs): foreach ($myClubs as $club): ?>
                <div class="mini-club-card">
                    <img src="<?= clubLogoUrl($club['logo']) ?>" alt="" class="mini-club-logo" />
                    <div class="mini-club-info">
                        <strong><?= htmlspecialchars($club['name']) ?></strong>
                        <span><?= $club['total_members'] ?> members</span>
                    </div>
                    <span class="mini-club-role"><?= ucfirst(str_replace('_',' ',$club['my_role'])) ?></span>
                    <a href="<?= BASE_URL ?>/pages/club-detail.php?slug=<?= $club['slug'] ?>" class="btn btn-ghost btn-sm">View</a>
                </div>
                <?php endforeach; else: ?>
                <div class="empty-state" style="padding:2rem"><i class="ri-building-4-line empty-state-icon" style="font-size:2.5rem"></i><h3>No clubs yet</h3><p>Browse and join clubs!</p><a href="<?= BASE_URL ?>/pages/clubs.php" class="btn btn-primary btn-sm">Browse Clubs</a></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Events -->
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="ri-calendar-event-line" style="color:var(--color-primary)"></i> Upcoming Events</h3><a href="my-events.php" class="btn btn-ghost btn-sm">View All</a></div>
            <div class="card-body">
                <?php if ($myEvents): foreach ($myEvents as $ev): ?>
                <div class="mini-event-card">
                    <div class="mini-event-date">
                        <div class="day"><?= date('d', strtotime($ev['start_date'])) ?></div>
                        <div class="mon"><?= date('M', strtotime($ev['start_date'])) ?></div>
                    </div>
                    <div class="mini-event-info">
                        <strong><?= htmlspecialchars($ev['title']) ?></strong>
                        <span><?= htmlspecialchars($ev['club_name']) ?> · <?= htmlspecialchars($ev['venue'] ?? 'TBA') ?></span>
                    </div>
                    <?= getStatusBadge($ev['reg_status']) ?>
                </div>
                <?php endforeach; else: ?>
                <div class="empty-state" style="padding:1.5rem;text-align:center"><i class="ri-calendar-line" style="font-size:2rem;opacity:0.3"></i><p style="margin-top:0.5rem;color:var(--color-text-2)">No upcoming events</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    <div>
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="ri-notification-3-line" style="color:var(--color-primary)"></i> Notifications</h3><a href="notifications.php" class="btn btn-ghost btn-sm">All</a></div>
            <div class="card-body" style="padding:0.5rem">
                <?php if ($notifs): foreach ($notifs as $n): ?>
                <div class="notification-item <?= !$n['is_read'] ? 'unread' : '' ?>">
                    <div class="notif-icon-lg"><i class="ri-bell-line"></i></div>
                    <div class="notif-content">
                        <strong><?= htmlspecialchars($n['title']) ?></strong>
                        <p><?= htmlspecialchars(substr($n['message'],0,80)) ?>...</p>
                        <time><?= timeAgo($n['created_at']) ?></time>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <div class="empty-state" style="padding:2rem"><i class="ri-notification-off-line empty-state-icon" style="font-size:2.5rem"></i><p>No notifications</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php renderDashboardEnd(); ?>
</div><!-- dashboard-body -->
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?= toggleSidebarScript(); ?>
