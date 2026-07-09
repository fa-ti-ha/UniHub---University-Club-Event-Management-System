<?php
// dashboard/student/my-clubs.php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dashboard-shell.php';
requireRole('student');
define('EXTRA_CSS', 'dashboard.css');
$user = currentUser(); $uid = $user['id'];

$clubs = $pdo->prepare("SELECT c.*, cm.role AS my_role, cm.joined_at FROM club_members cm JOIN clubs c ON c.id = cm.club_id WHERE cm.user_id = ? ORDER BY cm.joined_at DESC");
$clubs->execute([$uid]); $clubs = $clubs->fetchAll();

$pending = $pdo->prepare("SELECT cjr.*, c.name AS club_name, c.logo FROM club_join_requests cjr JOIN clubs c ON c.id = cjr.club_id WHERE cjr.user_id = ? AND cjr.status = 'pending'");
$pending->execute([$uid]); $pending = $pending->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell('student', $user, 'My Clubs', 'My Clubs', $pdo); ?>

<!-- Pending Requests -->
<?php if ($pending): ?>
<div class="card mb-6">
    <div class="card-header"><h3 class="card-title"><i class="ri-time-line" style="color:var(--color-warning)"></i> Pending Join Requests</h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:0.75rem">
        <?php foreach ($pending as $p): ?>
        <div style="display:flex;align-items:center;gap:1rem;padding:0.75rem;background:var(--color-bg-muted);border-radius:var(--radius-md)">
            <img src="<?= clubLogoUrl($p['logo']) ?>" alt="" style="width:40px;height:40px;border-radius:var(--radius-md);object-fit:cover" />
            <strong style="flex:1"><?= htmlspecialchars($p['club_name']) ?></strong>
            <span class="badge badge-warning">Pending</span>
            <span class="text-xs text-muted"><?= timeAgo($p['requested_at']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- My Clubs -->
<?php if ($clubs): ?>
<div class="grid-auto">
    <?php foreach ($clubs as $club): ?>
    <div class="club-card">
        <div class="club-card-header">
            <img src="<?= clubBannerUrl($club['banner']) ?>" alt="" class="club-card-banner" />
            <img src="<?= clubLogoUrl($club['logo']) ?>" alt="" class="club-card-logo" />
        </div>
        <div class="club-card-body">
            <span class="club-card-category"><?= htmlspecialchars($club['category']) ?></span>
            <h3 class="club-card-name"><?= htmlspecialchars($club['name']) ?></h3>
            <p class="club-card-desc"><?= htmlspecialchars(substr($club['short_description'] ?? '', 0, 100)) ?>...</p>
            <div class="club-card-meta">
                <span><i class="ri-group-line"></i> <?= $club['total_members'] ?> members</span>
                <span><i class="ri-calendar-line"></i> Joined <?= formatDate($club['joined_at'], 'M Y') ?></span>
            </div>
            <div style="margin-bottom:0.75rem"><span class="badge badge-primary"><?= ucfirst(str_replace('_',' ',$club['my_role'])) ?></span></div>
            <div class="club-card-actions">
                <a href="<?= BASE_URL ?>/pages/club-detail.php?slug=<?= $club['slug'] ?>" class="btn btn-outline-primary btn-sm" style="flex:1"><i class="ri-eye-line"></i> View Club</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state">
    <i class="ri-building-4-line empty-state-icon"></i><h3>You haven't joined any clubs yet</h3>
    <p>Discover clubs that match your interests!</p>
    <a href="<?= BASE_URL ?>/pages/clubs.php" class="btn btn-primary mt-4">Browse Clubs</a>
</div>
<?php endif; ?>

<?php renderDashboardEnd(); ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?= toggleSidebarScript(); ?>
