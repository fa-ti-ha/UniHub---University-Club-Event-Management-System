<?php
// ============================================================
// index.php — Landing / Home Page
// ============================================================
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

define('PAGE_TITLE', 'Home');

// Fetch stats
$stats = [];
$stats['clubs']    = (int)$pdo->query("SELECT COUNT(*) FROM clubs WHERE status = 'active'")->fetchColumn();
$stats['students'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role != 'super_admin' AND status = 'active'")->fetchColumn();
$stats['events']   = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'approved'")->fetchColumn();
$stats['upcoming'] = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'approved' AND start_date > NOW()")->fetchColumn();

// Latest clubs (6)
$clubsStmt = $pdo->query("SELECT * FROM clubs WHERE status = 'active' ORDER BY created_at DESC LIMIT 6");
$latestClubs = $clubsStmt->fetchAll();

// Upcoming events (4)
$eventsStmt = $pdo->query("SELECT e.*, c.name AS club_name FROM events e JOIN clubs c ON c.id = e.club_id WHERE e.status = 'approved' AND e.start_date > NOW() ORDER BY e.start_date ASC LIMIT 4");
$upcomingEvents = $eventsStmt->fetchAll();

// If logged in, redirect to dashboard
if (isLoggedIn() && isset($_GET['dash'])) {
    dashboardRedirect();
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- ============================================================
     HERO SECTION
     ============================================================ -->
<section class="hero" id="hero">
    <div class="hero-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <div class="container">
        <div class="hero-content animate-fade-up">
            <div class="hero-badge">
                <i class="ri-building-4-line"></i>
                UniHub University — Official Platform
            </div>
            <h1 class="hero-title">
                Connect. Collaborate.<br>
                <span class="gradient-text">Grow Together.</span>
            </h1>
            <p class="hero-description">
                The premier platform for discovering clubs, attending events, and building your university experience. Join hundreds of students making memories that last a lifetime.
            </p>
            <div class="hero-actions">
                <a href="<?= BASE_URL ?>/pages/clubs.php" class="btn btn-white btn-xl">
                    <i class="ri-team-line"></i> Join Clubs
                </a>
                <a href="<?= BASE_URL ?>/pages/events.php" class="btn btn-outline-primary btn-xl" style="border-color:rgba(255,255,255,0.4);color:#fff;">
                    <i class="ri-calendar-event-line"></i> Explore Events
                </a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="stat-num" data-counter="<?= $stats['clubs'] ?>"><?= $stats['clubs'] ?></span>
                    <span class="stat-label">Active Clubs</span>
                </div>
                <div class="hero-stat">
                    <span class="stat-num" data-counter="<?= $stats['students'] ?>"><?= $stats['students'] ?></span>
                    <span class="stat-label">Members</span>
                </div>
                <div class="hero-stat">
                    <span class="stat-num" data-counter="<?= $stats['events'] ?>"><?= $stats['events'] ?></span>
                    <span class="stat-label">Events</span>
                </div>
                <div class="hero-stat">
                    <span class="stat-num" data-counter="<?= $stats['upcoming'] ?>"><?= $stats['upcoming'] ?></span>
                    <span class="stat-label">Upcoming</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     FEATURES SECTION
     ============================================================ -->
<section class="section" style="background: var(--color-bg);">
    <div class="container">
        <div class="section-header animate-on-scroll">
            <span class="section-label">Why UniHub?</span>
            <h2 class="section-title">Everything You Need in One Place</h2>
            <p class="section-subtitle">Manage your university club life, discover events, and connect with fellow students seamlessly.</p>
        </div>
        <div class="features-grid">
            <?php
            $features = [
                ['icon' => 'ri-team-line',         'color' => 'icon-blue',   'title' => 'Join University Clubs',   'desc' => 'Browse and join clubs that match your interests. From coding to cultural arts, find your tribe.'],
                ['icon' => 'ri-calendar-event-line','color' => 'icon-green',  'title' => 'Discover Events',         'desc' => 'Stay updated on upcoming workshops, competitions, cultural nights, and sporting events.'],
                ['icon' => 'ri-shield-star-line',   'color' => 'icon-purple', 'title' => 'Manage Clubs',            'desc' => 'Club admins can manage members, approve requests, and organize club activities effortlessly.'],
                ['icon' => 'ri-user-add-line',      'color' => 'icon-orange', 'title' => 'Register for Events',     'desc' => 'One-click event registration with real-time seat tracking and automatic confirmations.'],
                ['icon' => 'ri-award-line',         'color' => 'icon-teal',   'title' => 'Build Your Portfolio',    'desc' => 'Track your club memberships, event participations, and achievements in one profile.'],
                ['icon' => 'ri-message-3-line',     'color' => 'icon-red',    'title' => 'Connect with Students',   'desc' => 'Build lasting connections with like-minded peers across departments and batches.'],
            ];
            foreach ($features as $i => $f):
            ?>
            <div class="feature-card animate-on-scroll" style="animation-delay: <?= $i * 0.08 ?>s">
                <div class="feature-icon <?= $f['color'] ?>"><i class="<?= $f['icon'] ?>"></i></div>
                <h3><?= $f['title'] ?></h3>
                <p><?= $f['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     STATS SECTION
     ============================================================ -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <?php
            $statItems = [
                ['icon' => 'ri-building-4-line',    'num' => $stats['clubs'],    'label' => 'Total Clubs'],
                ['icon' => 'ri-group-line',          'num' => $stats['students'], 'label' => 'Active Students'],
                ['icon' => 'ri-calendar-2-line',     'num' => $stats['events'],   'label' => 'Total Events'],
                ['icon' => 'ri-timer-flash-line',    'num' => $stats['upcoming'], 'label' => 'Upcoming Events'],
            ];
            foreach ($statItems as $s):
            ?>
            <div class="stat-item animate-on-scroll">
                <div class="stat-icon"><i class="<?= $s['icon'] ?>"></i></div>
                <span class="stat-number" data-counter="<?= $s['num'] ?>"><?= $s['num'] ?></span>
                <span class="stat-label"><?= $s['label'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     LATEST CLUBS SECTION
     ============================================================ -->
<section class="section" style="background: var(--color-bg-muted);">
    <div class="container">
        <div class="section-header animate-on-scroll">
            <span class="section-label">Clubs</span>
            <h2 class="section-title">Explore Our Clubs</h2>
            <p class="section-subtitle">Find the perfect community for your interests and passion.</p>
        </div>
        <?php if ($latestClubs): ?>
        <div class="grid-auto">
            <?php foreach ($latestClubs as $i => $club): ?>
            <div class="club-card animate-on-scroll" style="animation-delay: <?= $i * 0.07 ?>s">
                <div class="club-card-header">
                    <img src="<?= clubBannerUrl($club['banner']) ?>" alt="<?= htmlspecialchars($club['name']) ?> banner" class="club-card-banner" />
                    <img src="<?= clubLogoUrl($club['logo']) ?>" alt="<?= htmlspecialchars($club['name']) ?> logo" class="club-card-logo" />
                </div>
                <div class="club-card-body">
                    <span class="club-card-category"><?= htmlspecialchars($club['category']) ?></span>
                    <h3 class="club-card-name"><?= htmlspecialchars($club['name']) ?></h3>
                    <p class="club-card-desc"><?= htmlspecialchars(substr($club['short_description'] ?? '', 0, 100)) ?>...</p>
                    <div class="club-card-meta">
                        <span><i class="ri-group-line"></i> <?= $club['total_members'] ?> members</span>
                        <span><i class="ri-calendar-line"></i> <?= formatDate($club['created_at'], 'M Y') ?></span>
                    </div>
                    <div class="club-card-actions">
                        <a href="<?= BASE_URL ?>/pages/club-detail.php?slug=<?= urlencode($club['slug']) ?>" class="btn btn-outline-primary btn-sm" style="flex:1">View Details</a>
                        <?php if (isLoggedIn()): ?>
                        <button class="btn btn-primary btn-sm join-club-btn" data-club-id="<?= $club['id'] ?>" style="flex:1">Join</button>
                        <?php else: ?>
                        <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-primary btn-sm" style="flex:1">Join</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="ri-building-4-line empty-state-icon"></i><h3>No clubs yet</h3><p>Be the first to create a club!</p></div>
        <?php endif; ?>
        <div class="text-center mt-8">
            <a href="<?= BASE_URL ?>/pages/clubs.php" class="btn btn-primary btn-lg"><i class="ri-arrow-right-line"></i> View All Clubs</a>
        </div>
    </div>
</section>

<!-- ============================================================
     UPCOMING EVENTS SECTION
     ============================================================ -->
<section class="section">
    <div class="container">
        <div class="section-header animate-on-scroll">
            <span class="section-label">Events</span>
            <h2 class="section-title">Upcoming Events</h2>
            <p class="section-subtitle">Don't miss out on exciting events happening at your university.</p>
        </div>
        <?php if ($upcomingEvents): ?>
        <div class="grid-auto">
            <?php foreach ($upcomingEvents as $i => $event): ?>
            <div class="event-card animate-on-scroll" style="animation-delay: <?= $i * 0.08 ?>s">
                <div class="event-card-banner">
                    <img src="<?= eventBannerUrl($event['banner']) ?>" alt="<?= htmlspecialchars($event['title']) ?>" />
                    <div class="event-card-status">
                        <?php
                        $now = time();
                        $start = strtotime($event['start_date']);
                        $end   = strtotime($event['end_date']);
                        if ($now < $start) echo '<span class="badge badge-info">Upcoming</span>';
                        elseif ($now >= $start && $now <= $end) echo '<span class="badge badge-success">Ongoing</span>';
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
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= min(100, round($event['current_participants'] / $event['max_participants'] * 100)) ?>%"></div>
                    </div>
                    <div class="event-spots"><?= $event['current_participants'] ?>/<?= $event['max_participants'] ?> registered</div>
                    <?php endif; ?>
                    <div class="event-card-footer mt-4">
                        <a href="<?= BASE_URL ?>/pages/event-detail.php?id=<?= $event['id'] ?>" class="btn btn-outline-primary btn-sm">Details</a>
                        <?php if (isLoggedIn()): ?>
                        <button class="btn btn-primary btn-sm register-event-btn" data-event-id="<?= $event['id'] ?>">Register Now</button>
                        <?php else: ?>
                        <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-primary btn-sm">Register Now</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="ri-calendar-event-line empty-state-icon"></i><h3>No upcoming events</h3><p>Check back soon!</p></div>
        <?php endif; ?>
        <div class="text-center mt-8">
            <a href="<?= BASE_URL ?>/pages/events.php" class="btn btn-primary btn-lg"><i class="ri-arrow-right-line"></i> View All Events</a>
        </div>
    </div>
</section>

<!-- ============================================================
     CTA SECTION
     ============================================================ -->
<?php if (!isLoggedIn()): ?>
<section class="section" style="background: linear-gradient(135deg, #0f172a, #1a56db);">
    <div class="container text-center" style="color:#fff; max-width: 640px;">
        <div class="animate-on-scroll">
            <h2 style="font-size: var(--font-size-4xl); font-weight:900; color:#fff; margin-bottom:var(--sp-4);">Ready to Get Started?</h2>
            <p style="font-size:var(--font-size-lg); color:rgba(255,255,255,0.75); margin-bottom:var(--sp-8);">Join thousands of students already managing their club life on UniHub.</p>
            <div class="hero-actions" style="justify-content:center">
                <a href="<?= BASE_URL ?>/pages/register.php" class="btn btn-white btn-xl"><i class="ri-user-add-line"></i> Create Account</a>
                <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-xl" style="border:2px solid rgba(255,255,255,0.4);color:#fff">Sign In</a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
// Join club buttons
document.querySelectorAll('.join-club-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const clubId = btn.dataset.clubId;
        btn.disabled = true;
        btn.textContent = 'Sending...';
        try {
            const data = await apiPost(BASE_URL + '/api/clubs.php?action=join', { action:'join', club_id: parseInt(clubId) });
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) { btn.textContent = 'Requested'; btn.className = 'btn btn-ghost btn-sm'; }
            else btn.disabled = false;
        } catch { showToast('Network error.', 'error'); btn.disabled = false; btn.textContent = 'Join'; }
    });
});

// Register event buttons
document.querySelectorAll('.register-event-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const eventId = btn.dataset.eventId;
        btn.disabled = true; btn.textContent = 'Registering...';
        try {
            const data = await apiPost(BASE_URL + '/api/events.php', { action:'register', event_id: parseInt(eventId) });
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) { btn.textContent = 'Registered ✓'; btn.className = 'btn btn-success btn-sm'; }
            else { btn.disabled = false; btn.textContent = 'Register Now'; }
        } catch { showToast('Network error.', 'error'); btn.disabled = false; btn.textContent = 'Register Now'; }
    });
});

// Scroll reveal init
document.querySelectorAll('.animate-on-scroll').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(24px)';
    el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
});
const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            e.target.style.opacity = '1';
            e.target.style.transform = 'none';
            io.unobserve(e.target);
        }
    });
}, { threshold: 0.12 });
document.querySelectorAll('.animate-on-scroll').forEach(el => io.observe(el));
</script>
