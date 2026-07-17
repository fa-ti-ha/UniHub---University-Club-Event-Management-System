<?php
// pages/club-detail.php added
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$slug = sanitize($_GET['slug'] ?? '');
if (!$slug) { header('Location: ' . BASE_URL . '/pages/clubs.php'); exit; }

$stmt = $pdo->prepare("SELECT c.*, ts.name AS supervisor_name, ts.email AS supervisor_email, ts.designation AS supervisor_designation, u.full_name AS admin_name FROM clubs c LEFT JOIN teacher_supervisors ts ON ts.id = c.supervisor_id LEFT JOIN users u ON u.id = c.admin_id WHERE c.slug = ? AND c.status = 'active'");
$stmt->execute([$slug]);
$club = $stmt->fetch();
if (!$club) { header('Location: ' . BASE_URL . '/pages/clubs.php'); exit; }

// Members
$membersStmt = $pdo->prepare("SELECT u.full_name, u.profile_picture, u.student_id, cm.role, cm.joined_at FROM club_members cm JOIN users u ON u.id = cm.user_id WHERE cm.club_id = ? ORDER BY cm.joined_at ASC LIMIT 12");
$membersStmt->execute([$club['id']]);
$members = $membersStmt->fetchAll();

// Real member count from club_members (not the stale total_members column)
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM club_members WHERE club_id = ?");
$countStmt->execute([$club['id']]);
$actualMemberCount = (int)$countStmt->fetchColumn();


// Events
$eventsStmt = $pdo->prepare("SELECT * FROM events WHERE club_id = ? AND status = 'approved' ORDER BY start_date DESC LIMIT 6");
$eventsStmt->execute([$club['id']]);
$clubEvents = $eventsStmt->fetchAll();

// Check membership / request status
$isMember = false; $hasPending = false;
if (isLoggedIn()) {
    $uid = currentUser()['id'];
    $m = $pdo->prepare("SELECT id FROM club_members WHERE club_id = ? AND user_id = ?");
    $m->execute([$club['id'], $uid]); $isMember = (bool)$m->fetch();
    if (!$isMember) {
        $r = $pdo->prepare("SELECT id FROM club_join_requests WHERE club_id = ? AND user_id = ? AND status = 'pending'");
        $r->execute([$club['id'], $uid]); $hasPending = (bool)$r->fetch();
    }
}

define('PAGE_TITLE', $club['name']);
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Banner -->
<div style="height:320px;background:linear-gradient(135deg,#0f172a,#1a56db);position:relative;overflow:hidden;margin-top:0">
    <img src="<?= clubBannerUrl($club['banner']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;opacity:0.4;position:absolute;inset:0" />
    <div style="position:absolute;inset:0;display:flex;align-items:flex-end;padding:2rem" class="container">
        <div style="display:flex;align-items:flex-end;gap:1.5rem">
            <img src="<?= clubLogoUrl($club['logo']) ?>" alt="<?= htmlspecialchars($club['name']) ?>" style="width:100px;height:100px;border-radius:16px;border:4px solid #fff;object-fit:cover;box-shadow:0 8px 32px rgba(0,0,0,0.3)" />
            <div style="color:#fff;padding-bottom:0.5rem">
                <span class="badge badge-info" style="margin-bottom:0.5rem"><?= htmlspecialchars($club['category']) ?></span>
                <h1 style="font-size:2rem;font-weight:900;color:#fff;line-height:1.1"><?= htmlspecialchars($club['name']) ?></h1>
                <p style="color:rgba(255,255,255,0.8);margin-top:0.25rem"><i class="ri-group-line"></i> <?= $actualMemberCount ?> Members</p>
            </div>
        </div>
    </div>
</div>

<section class="section-sm" style="background:var(--color-bg)">
<div class="container">
<div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem;margin-top:-2rem;position:relative;z-index:2">

<!-- Left Column -->
<div>
    <!-- About -->
    <div class="card mb-6">
        <div class="card-header"><h3 class="card-title"><i class="ri-information-line" style="color:var(--color-primary)"></i> About</h3></div>
        <div class="card-body">
            <p style="line-height:1.8;color:var(--color-text-2)"><?= nl2br(htmlspecialchars($club['full_description'] ?? $club['short_description'] ?? '')) ?></p>
        </div>
    </div>

    <!-- Mission & Vision -->
    <?php if ($club['mission'] || $club['vision']): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
        <?php if ($club['mission']): ?>
        <div class="card">
            <div class="card-body">
                <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem">
                    <div class="feature-icon icon-blue" style="width:40px;height:40px;font-size:1.1rem"><i class="ri-focus-3-line"></i></div>
                    <h4>Mission</h4>
                </div>
                <p style="color:var(--color-text-2);font-size:0.875rem;line-height:1.7"><?= htmlspecialchars($club['mission']) ?></p>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($club['vision']): ?>
        <div class="card">
            <div class="card-body">
                <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem">
                    <div class="feature-icon icon-purple" style="width:40px;height:40px;font-size:1.1rem"><i class="ri-eye-line"></i></div>
                    <h4>Vision</h4>
                </div>
                <p style="color:var(--color-text-2);font-size:0.875rem;line-height:1.7"><?= htmlspecialchars($club['vision']) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Activities -->
    <?php if ($club['activities']): ?>
    <div class="card mb-6">
        <div class="card-header"><h3 class="card-title"><i class="ri-calendar-check-line" style="color:var(--color-primary)"></i> Activities</h3></div>
        <div class="card-body">
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem">
                <?php foreach (explode(',', $club['activities']) as $act): ?>
                <span class="badge badge-primary" style="padding:0.4rem 0.75rem;font-size:0.8rem"><?= htmlspecialchars(trim($act)) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Club Events -->
    <?php if ($clubEvents): ?>
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title"><i class="ri-calendar-event-line" style="color:var(--color-primary)"></i> Club Events</h3>
            <a href="<?= BASE_URL ?>/pages/events.php" class="btn btn-ghost btn-sm">View All</a>
        </div>
        <div class="card-body" style="padding:0">
            <?php foreach ($clubEvents as $ev): ?>
            <div style="display:flex;align-items:center;gap:1rem;padding:1rem 1.5rem;border-bottom:1px solid var(--color-border)">
                <div style="text-align:center;background:var(--color-primary-light);border-radius:10px;padding:0.5rem 0.75rem;min-width:52px">
                    <div style="font-size:1.2rem;font-weight:800;color:var(--color-primary);line-height:1"><?= date('d', strtotime($ev['start_date'])) ?></div>
                    <div style="font-size:10px;text-transform:uppercase;color:var(--color-text-2);font-weight:600"><?= date('M', strtotime($ev['start_date'])) ?></div>
                </div>
                <div style="flex:1">
                    <strong style="font-size:0.9rem"><?= htmlspecialchars($ev['title']) ?></strong><br>
                    <span style="font-size:0.8rem;color:var(--color-text-3)"><i class="ri-map-pin-line"></i> <?= htmlspecialchars($ev['venue'] ?? 'TBA') ?></span>
                </div>
                <a href="<?= BASE_URL ?>/pages/event-detail.php?id=<?= $ev['id'] ?>" class="btn btn-outline-primary btn-sm">Details</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Members -->
    <?php if ($members): ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title"><i class="ri-group-line" style="color:var(--color-primary)"></i> Members (<?= $actualMemberCount ?>)</h3></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:1rem">
                <?php foreach ($members as $mem): ?>
                <div style="text-align:center">
                    <img src="<?= profilePicUrl($mem['profile_picture']) ?>" alt="" style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid var(--color-border);margin:0 auto 0.5rem" />
                    <div style="font-size:0.8rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($mem['full_name']) ?></div>
                    <span class="badge badge-<?= $mem['role'] === 'president' ? 'warning' : ($mem['role'] === 'club_admin' ? 'primary' : 'secondary') ?>" style="font-size:10px"><?= ucfirst(str_replace('_',' ',$mem['role'])) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Right Sidebar -->
<div>
    <!-- Join Card -->
    <div class="card mb-4" style="position:sticky;top:calc(var(--navbar-h) + 1rem)">
        <div class="card-body text-center">
            <?php if ($isMember): ?>
            <div class="badge badge-success" style="padding:0.5rem 1rem;font-size:0.875rem;margin-bottom:1rem">✓ You are a member</div>
            <?php elseif ($hasPending): ?>
            <div class="badge badge-warning" style="padding:0.5rem 1rem;font-size:0.875rem;margin-bottom:1rem">⏳ Request Pending</div>
            <?php elseif (isLoggedIn()): ?>
            <button class="btn btn-primary btn-block btn-lg mb-3" id="joinClubBtn" data-club-id="<?= $club['id'] ?>">
                <i class="ri-user-add-line"></i> Join Club
            </button>
            <?php else: ?>
            <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-primary btn-block btn-lg mb-3"><i class="ri-login-box-line"></i> Login to Join</a>
            <?php endif; ?>
            <p class="text-muted text-sm">Membership is subject to admin approval</p>
        </div>
    </div>

    <!-- Club Info -->
    <div class="card mb-4">
        <div class="card-header"><h4 class="card-title">Club Info</h4></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:1rem">
            <div style="display:flex;gap:0.75rem;align-items:center">
                <div class="stat-card-icon icon-blue" style="width:36px;height:36px;font-size:0.9rem;flex-shrink:0"><i class="ri-group-line"></i></div>
                <div><div style="font-size:0.75rem;color:var(--color-text-3)">Total Members</div><strong><?= $actualMemberCount ?></strong></div>
            </div>
            <?php if ($club['supervisor_name']): ?>
            <div style="display:flex;gap:0.75rem;align-items:center">
                <div class="stat-card-icon icon-green" style="width:36px;height:36px;font-size:0.9rem;flex-shrink:0"><i class="ri-user-star-line"></i></div>
                <div><div style="font-size:0.75rem;color:var(--color-text-3)">Supervisor</div><strong><?= htmlspecialchars($club['supervisor_name']) ?></strong><div style="font-size:0.75rem;color:var(--color-text-3)"><?= htmlspecialchars($club['supervisor_designation'] ?? '') ?></div></div>
            </div>
            <?php endif; ?>
            <?php if ($club['president_name']): ?>
            <div style="display:flex;gap:0.75rem;align-items:center">
                <div class="stat-card-icon icon-purple" style="width:36px;height:36px;font-size:0.9rem;flex-shrink:0"><i class="ri-vip-crown-line"></i></div>
                <div><div style="font-size:0.75rem;color:var(--color-text-3)">President</div><strong><?= htmlspecialchars($club['president_name']) ?></strong></div>
            </div>
            <?php endif; ?>
            <?php if ($club['vice_president_name']): ?>
            <div style="display:flex;gap:0.75rem;align-items:center">
                <div class="stat-card-icon icon-orange" style="width:36px;height:36px;font-size:0.9rem;flex-shrink:0"><i class="ri-award-line"></i></div>
                <div><div style="font-size:0.75rem;color:var(--color-text-3)">Vice President</div><strong><?= htmlspecialchars($club['vice_president_name']) ?></strong></div>
            </div>
            <?php endif; ?>
            <div style="display:flex;gap:0.75rem;align-items:center">
                <div class="stat-card-icon icon-teal" style="width:36px;height:36px;font-size:0.9rem;flex-shrink:0"><i class="ri-calendar-line"></i></div>
                <div><div style="font-size:0.75rem;color:var(--color-text-3)">Founded</div><strong><?= formatDate($club['created_at'], 'M Y') ?></strong></div>
            </div>
        </div>
    </div>
</div>

</div><!-- /grid -->
</div><!-- /container -->
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
document.getElementById('joinClubBtn')?.addEventListener('click', async function() {
    this.disabled = true; this.innerHTML = '<i class="ri-loader-4-line spin"></i> Sending...';
    const data = await apiPost(BASE_URL + '/api/clubs.php?action=join', { action:'join', club_id: <?= $club['id'] ?> });
    showToast(data.message, data.success ? 'success' : 'error');
    if (data.success) { this.innerHTML = '✓ Request Sent'; this.className = 'btn btn-ghost btn-block btn-lg mb-3'; }
    else this.disabled = false;
});
</script>
