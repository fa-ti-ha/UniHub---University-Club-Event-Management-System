<?php
// ============================================================
// pages/clubs.php — Public clubs listing
// ============================================================
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

define('PAGE_TITLE', 'Clubs');

$search   = sanitize($_GET['q'] ?? '');
$category = sanitize($_GET['category'] ?? 'all');
$sort     = sanitize($_GET['sort'] ?? 'newest');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;

// Build query
$where  = ["status = 'active'"];
$params = [];

if ($search) {
    $where[]  = "(name LIKE ? OR short_description LIKE ? OR category LIKE ?)";
    $s        = "%$search%";
    $params[] = $s; $params[] = $s; $params[] = $s;
}
if ($category && $category !== 'all') {
    $where[]  = "category = ?";
    $params[] = $category;
}

$orderBy = match($sort) {
    'popular' => 'total_members DESC',
    'oldest'  => 'created_at ASC',
    default   => 'created_at DESC',
};

$whereSQL = implode(' AND ', $where);
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM clubs WHERE $whereSQL");
$totalStmt->execute($params);
$total   = (int)$totalStmt->fetchColumn();
$pg      = paginate($total, $perPage, $page);

$clubsStmt = $pdo->prepare("SELECT c.*, ts.name AS supervisor_name FROM clubs c LEFT JOIN teacher_supervisors ts ON ts.id = c.supervisor_id WHERE $whereSQL ORDER BY $orderBy LIMIT ? OFFSET ?");
$clubsStmt->execute([...$params, $perPage, $pg['offset']]);
$clubs = $clubsStmt->fetchAll();

// Categories for filter
$categories = $pdo->query("SELECT DISTINCT category FROM clubs WHERE status = 'active' AND category IS NOT NULL ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1><i class="ri-building-4-line"></i> University Clubs</h1>
        <p>Discover communities that share your passion and interests</p>
    </div>
</section>

<!-- Clubs Content -->
<section class="section-sm" style="background:var(--color-bg);">
    <div class="container">
        <!-- Toolbar -->
        <div class="section-toolbar mb-6" style="flex-wrap:wrap;gap:1rem;">
            <!-- Search -->
            <form method="GET" class="search-bar" style="max-width:360px">
                <i class="ri-search-line search-icon"></i>
                <input type="text" name="q" placeholder="Search clubs..." value="<?= htmlspecialchars($search) ?>" />
                <?php if ($category !== 'all'): ?><input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>"><?php endif; ?>
                <?php if ($sort !== 'newest'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
            </form>

            <!-- Sort & Filter -->
            <div class="d-flex gap-3 flex-wrap">
                <div class="filter-chips">
                    <?php
                    $catLinks = array_merge(['all' => 'All'], array_combine($categories, $categories));
                    foreach ($catLinks as $val => $label):
                        $active = ($category === $val || ($val === 'all' && $category === 'all')) ? 'active' : '';
                    ?>
                    <a href="?q=<?= urlencode($search) ?>&category=<?= urlencode($val) ?>&sort=<?= urlencode($sort) ?>" class="filter-chip <?= $active ?>"><?= htmlspecialchars($label) ?></a>
                    <?php endforeach; ?>
                </div>
                <select class="form-control" style="width:auto;min-width:160px" onchange="window.location.href=this.value">
                    <?php foreach (['newest' => 'Newest First', 'popular' => 'Most Popular', 'oldest' => 'Oldest First'] as $val => $label): ?>
                    <option value="?q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&sort=<?= $val ?>" <?= $sort === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Results count -->
        <p class="text-muted mb-4" style="font-size:0.875rem">
            Showing <?= count($clubs) ?> of <?= $total ?> clubs
            <?= $search ? " for \"<strong>" . htmlspecialchars($search) . "</strong>\"" : '' ?>
        </p>

        <!-- Club Grid -->
        <?php if ($clubs): ?>
        <div class="grid-auto">
            <?php foreach ($clubs as $club): ?>
            <div class="club-card">
                <div class="club-card-header">
                    <img src="<?= clubBannerUrl($club['banner']) ?>" alt="" class="club-card-banner" />
                    <img src="<?= clubLogoUrl($club['logo']) ?>" alt="<?= htmlspecialchars($club['name']) ?>" class="club-card-logo" />
                </div>
                <div class="club-card-body">
                    <span class="club-card-category"><?= htmlspecialchars($club['category']) ?></span>
                    <h3 class="club-card-name"><?= htmlspecialchars($club['name']) ?></h3>
                    <p class="club-card-desc"><?= htmlspecialchars(substr($club['short_description'] ?? '', 0, 110)) ?>...</p>
                    <?php if ($club['supervisor_name']): ?>
                    <div class="club-card-meta mb-2">
                        <span><i class="ri-user-star-line"></i> <?= htmlspecialchars($club['supervisor_name']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="club-card-meta">
                        <span><i class="ri-group-line"></i> <?= $club['total_members'] ?> members</span>
                        <span><i class="ri-calendar-line"></i> <?= formatDate($club['created_at'], 'M Y') ?></span>
                    </div>
                    <div class="club-card-actions">
                        <a href="<?= BASE_URL ?>/pages/club-detail.php?slug=<?= urlencode($club['slug']) ?>" class="btn btn-outline-primary btn-sm" style="flex:1">
                            <i class="ri-eye-line"></i> View Details
                        </a>
                        <?php if (isLoggedIn()): ?>
                        <button class="btn btn-primary btn-sm join-btn" data-club-id="<?= $club['id'] ?>" style="flex:1">
                            <i class="ri-user-add-line"></i> Join
                        </button>
                        <?php else: ?>
                        <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-primary btn-sm" style="flex:1">Join</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pg['total_pages'] > 1): ?>
        <div class="pagination">
            <?php if ($pg['has_prev']): ?>
            <a href="?q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&sort=<?= urlencode($sort) ?>&page=<?= $pg['current'] - 1 ?>" class="page-item"><i class="ri-arrow-left-s-line"></i></a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $pg['total_pages']; $i++): ?>
            <a href="?q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&sort=<?= urlencode($sort) ?>&page=<?= $i ?>" class="page-item <?= $i === $pg['current'] ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($pg['has_next']): ?>
            <a href="?q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&sort=<?= urlencode($sort) ?>&page=<?= $pg['current'] + 1 ?>" class="page-item"><i class="ri-arrow-right-s-line"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="empty-state">
            <i class="ri-building-4-line empty-state-icon"></i>
            <h3>No clubs found</h3>
            <p>Try different search terms or filters.</p>
            <a href="<?= BASE_URL ?>/pages/clubs.php" class="btn btn-primary">View All Clubs</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
document.querySelectorAll('.join-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.clubId;
        btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line spin"></i>';
        const data = await apiPost(BASE_URL + '/api/clubs.php?action=join', { action:'join', club_id: parseInt(id) });
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) btn.innerHTML = '✓ Requested';
        else { btn.disabled = false; btn.innerHTML = '<i class="ri-user-add-line"></i> Join'; }
    });
});
</script>
