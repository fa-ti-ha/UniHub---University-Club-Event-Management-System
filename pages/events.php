<?php
// pages/events.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

define('PAGE_TITLE', 'Events');

$search  = sanitize($_GET['q'] ?? '');
$filter  = sanitize($_GET['filter'] ?? 'upcoming');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$where  = ["e.status = 'approved'"];
$params = [];

if ($search) { $where[] = "(e.title LIKE ? OR e.description LIKE ? OR c.name LIKE ?)"; $s = "%$search%"; $params[] = $s; $params[] = $s; $params[] = $s; }

$now = date('Y-m-d H:i:s');
match($filter) {
    'ongoing'   => ($where[] = "e.start_date <= ? AND e.end_date >= ?") && ($params[] = $now) && ($params[] = $now),
    'completed' => ($where[] = "e.end_date < ?") && ($params[] = $now),
    default     => ($where[] = "e.start_date > ?") && ($params[] = $now),
};

$whereSQL = implode(' AND ', $where);
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM events e JOIN clubs c ON c.id = e.club_id WHERE $whereSQL");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$pg    = paginate($total, $perPage, $page);

$orderBy = ($filter === 'completed') ? 'e.start_date DESC' : 'e.start_date ASC';
$eventsStmt = $pdo->prepare("SELECT e.*, c.name AS club_name FROM events e JOIN clubs c ON c.id = e.club_id WHERE $whereSQL ORDER BY $orderBy LIMIT ? OFFSET ?");
$eventsStmt->execute([...$params, $perPage, $pg['offset']]);
$events = $eventsStmt->fetchAll();

// Registered event IDs for this user
$registeredIds = [];
if (isLoggedIn()) {
    $uid = currentUser()['id'];
    $regStmt = $pdo->prepare("SELECT event_id FROM event_registrations WHERE user_id = ?");
    $regStmt->execute([$uid]);
    $registeredIds = $regStmt->fetchAll(PDO::FETCH_COLUMN);
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="page-header">
    <div class="container">
        <h1><i class="ri-calendar-event-line"></i> Events</h1>
        <p>Discover workshops, competitions, cultural nights & more</p>
    </div>
</section>

<section class="section-sm" style="background:var(--color-bg)">
<div class="container">
    <!-- Toolbar -->
    <div class="section-toolbar mb-6">
        <form method="GET" class="search-bar" style="max-width:360px">
            <i class="ri-search-line search-icon"></i>
            <input type="text" name="q" placeholder="Search events..." value="<?= htmlspecialchars($search) ?>" />
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>" />
        </form>
        <div class="filter-chips">
            <?php foreach (['upcoming' => '🔜 Upcoming', 'ongoing' => '🟢 Ongoing', 'completed' => '✓ Completed'] as $val => $label): ?>
            <a href="?q=<?= urlencode($search) ?>&filter=<?= $val ?>" class="filter-chip <?= $filter === $val ? 'active' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <p class="text-muted mb-4 text-sm">Showing <?= count($events) ?> of <?= $total ?> events</p>

    <?php if ($events): ?>
    <div class="grid-auto">
        <?php foreach ($events as $event):
            $registered = in_array($event['id'], $registeredIds);
            $isFull = $event['max_participants'] > 0 && $event['current_participants'] >= $event['max_participants'];
            $deadlinePassed = $event['registration_deadline'] && strtotime($event['registration_deadline']) < time();
            $pct = $event['max_participants'] > 0 ? min(100, round($event['current_participants'] / $event['max_participants'] * 100)) : 0;
        ?>
        <div class="event-card">
            <div class="event-card-banner">
                <img src="<?= eventBannerUrl($event['banner']) ?>" alt="<?= htmlspecialchars($event['title']) ?>" loading="lazy" />
                <div class="event-card-status">
                    <?php
                    $now_ = time();
                    $s = strtotime($event['start_date']); $e_ = strtotime($event['end_date']);
                    if ($now_ < $s) echo '<span class="badge badge-info">Upcoming</span>';
                    elseif ($now_ <= $e_) echo '<span class="badge badge-success">Ongoing</span>';
                    else echo '<span class="badge badge-secondary">Completed</span>';
                    ?>
                </div>
            </div>
            <div class="event-card-body">
                <div class="event-card-club"><i class="ri-building-4-line"></i> <?= htmlspecialchars($event['club_name']) ?></div>
                <h3 class="event-card-title"><?= htmlspecialchars($event['title']) ?></h3>
                <div class="event-card-meta">
                    <div class="event-meta-row"><i class="ri-calendar-line"></i> <?= formatDateTime($event['start_date']) ?></div>
                    <div class="event-meta-row"><i class="ri-map-pin-line"></i> <?= htmlspecialchars($event['venue'] ?? 'TBA') ?></div>
                    <?php if ($event['registration_deadline']): ?>
                    <div class="event-meta-row"><i class="ri-time-line"></i> Deadline: <?= formatDate($event['registration_deadline']) ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($event['max_participants'] > 0): ?>
                <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
                <div class="event-spots mb-3"><?= $event['current_participants'] ?>/<?= $event['max_participants'] ?> registered</div>
                <?php endif; ?>
                <div class="event-card-footer">
                    <a href="<?= BASE_URL ?>/pages/event-detail.php?id=<?= $event['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="ri-eye-line"></i> Details</a>
                    <?php if ($registered): ?>
                    <span class="badge badge-success" style="padding:0.4rem 0.75rem">✓ Registered</span>
                    <?php elseif ($isFull): ?>
                    <span class="badge badge-danger" style="padding:0.4rem 0.75rem">Full</span>
                    <?php elseif ($deadlinePassed): ?>
                    <span class="badge badge-secondary" style="padding:0.4rem 0.75rem">Closed</span>
                    <?php elseif (isLoggedIn()): ?>
                    <button class="btn btn-primary btn-sm reg-btn" data-event-id="<?= $event['id'] ?>"><i class="ri-calendar-check-line"></i> Register</button>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-primary btn-sm">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($pg['total_pages'] > 1): ?>
    <div class="pagination">
        <?php if ($pg['has_prev']): ?><a href="?q=<?= urlencode($search) ?>&filter=<?= $filter ?>&page=<?= $pg['current']-1 ?>" class="page-item"><i class="ri-arrow-left-s-line"></i></a><?php endif; ?>
        <?php for ($i=1;$i<=$pg['total_pages'];$i++): ?><a href="?q=<?= urlencode($search) ?>&filter=<?= $filter ?>&page=<?= $i ?>" class="page-item <?= $i===$pg['current']?'active':'' ?>"><?= $i ?></a><?php endfor; ?>
        <?php if ($pg['has_next']): ?><a href="?q=<?= urlencode($search) ?>&filter=<?= $filter ?>&page=<?= $pg['current']+1 ?>" class="page-item"><i class="ri-arrow-right-s-line"></i></a><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state">
        <i class="ri-calendar-event-line empty-state-icon"></i>
        <h3>No <?= $filter ?> events found</h3>
        <p>Check back soon or try a different filter.</p>
    </div>
    <?php endif; ?>
</div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
document.querySelectorAll('.reg-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line spin"></i>';
        const data = await apiPost(BASE_URL + '/api/events.php', { action:'register', event_id: parseInt(btn.dataset.eventId) });
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) { btn.outerHTML = '<span class="badge badge-success" style="padding:0.4rem 0.75rem">✓ Registered</span>'; }
        else { btn.disabled = false; btn.innerHTML = '<i class="ri-calendar-check-line"></i> Register'; }
    });
});
</script>
