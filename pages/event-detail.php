<?php
// pages/event-detail.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . '/pages/events.php'); exit; }

$stmt = $pdo->prepare("SELECT e.*, c.name AS club_name, c.slug AS club_slug, c.logo AS club_logo FROM events e JOIN clubs c ON c.id = e.club_id WHERE e.id = ? AND e.status = 'approved'");
$stmt->execute([$id]);
$event = $stmt->fetch();
if (!$event) { header('Location: ' . BASE_URL . '/pages/events.php'); exit; }

// Gallery images
$galleryStmt = $pdo->prepare("SELECT * FROM event_images WHERE event_id = ? ORDER BY uploaded_at ASC");
$galleryStmt->execute([$id]);
$gallery = $galleryStmt->fetchAll();

// Check registration
$registered = false; $regStatus = null;
if (isLoggedIn()) {
    $uid = currentUser()['id'];
    $r = $pdo->prepare("SELECT status FROM event_registrations WHERE event_id = ? AND user_id = ?");
    $r->execute([$id, $uid]);
    $reg = $r->fetch();
    if ($reg) { $registered = true; $regStatus = $reg['status']; }
}

$isFull = $event['max_participants'] > 0 && $event['current_participants'] >= $event['max_participants'];
$deadlinePassed = $event['registration_deadline'] && strtotime($event['registration_deadline']) < time();
$pct = $event['max_participants'] > 0 ? min(100, round($event['current_participants'] / $event['max_participants'] * 100)) : 0;

define('PAGE_TITLE', $event['title']);
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Event Banner -->
<div style="height:380px;position:relative;overflow:hidden;background:linear-gradient(135deg,#0f172a,#1a56db)">
    <img src="<?= eventBannerUrl($event['banner']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;opacity:0.5;position:absolute;inset:0" />
    <div style="position:absolute;inset:0;display:flex;align-items:flex-end;padding:2.5rem" class="container">
        <div style="color:#fff">
            <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem">
                <img src="<?= clubLogoUrl($event['club_logo']) ?>" alt="" style="width:36px;height:36px;border-radius:8px;object-fit:cover;border:2px solid rgba(255,255,255,0.3)" />
                <span style="font-weight:600;color:rgba(255,255,255,0.9)"><?= htmlspecialchars($event['club_name']) ?></span>
                <span class="badge badge-info"><?= htmlspecialchars($event['category'] ?? 'General') ?></span>
            </div>
            <h1 style="font-size:2.5rem;font-weight:900;color:#fff;line-height:1.1;max-width:700px"><?= htmlspecialchars($event['title']) ?></h1>
        </div>
    </div>
</div>

<section class="section-sm" style="background:var(--color-bg)">
<div class="container">
<div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem">

<!-- Left -->
<div>
    <!-- Description -->
    <div class="card mb-6">
        <div class="card-header"><h3 class="card-title"><i class="ri-file-text-line" style="color:var(--color-primary)"></i> About This Event</h3></div>
        <div class="card-body"><p style="line-height:1.9;color:var(--color-text-2)"><?= nl2br(htmlspecialchars($event['description'] ?? '')) ?></p></div>
    </div>

    <!-- Gallery -->
    <?php if ($gallery): ?>
    <div class="card mb-6">
        <div class="card-header"><h3 class="card-title"><i class="ri-image-line" style="color:var(--color-primary)"></i> Gallery</h3></div>
        <div class="card-body">
            <div class="gallery-grid">
                <?php foreach ($gallery as $img): ?>
                <div class="gallery-item" onclick="openLightbox('<?= UPLOAD_URL ?>events/<?= $img['image_path'] ?>', '<?= htmlspecialchars($img['caption'] ?? '') ?>')">
                    <img src="<?= UPLOAD_URL ?>events/<?= $img['image_path'] ?>" alt="<?= htmlspecialchars($img['caption'] ?? '') ?>" loading="lazy" />
                    <div class="gallery-overlay"><i class="ri-zoom-in-line"></i></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Right Sidebar -->
<div>
    <!-- Register Card -->
    <div class="card mb-4" style="position:sticky;top:calc(var(--navbar-h) + 1rem)">
        <div class="card-body">
            <?php if ($event['max_participants'] > 0): ?>
            <div style="margin-bottom:1rem">
                <div class="flex-between mb-1"><span class="text-sm text-muted">Seats filled</span><strong><?= $pct ?>%</strong></div>
                <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
                <div class="text-xs text-muted mt-1"><?= $event['current_participants'] ?> / <?= $event['max_participants'] ?> registered</div>
            </div>
            <?php endif; ?>

            <?php if ($registered): ?>
            <div class="alert alert-success mb-3"><i class="ri-checkbox-circle-line"></i> You're registered! Status: <strong><?= ucfirst($regStatus) ?></strong></div>
            <button class="btn btn-ghost btn-block btn-sm cancel-reg-btn" data-event-id="<?= $event['id'] ?>"><i class="ri-close-line"></i> Cancel Registration</button>
            <?php elseif ($isFull): ?>
            <div class="alert alert-danger"><i class="ri-error-warning-line"></i> This event is full.</div>
            <?php elseif ($deadlinePassed): ?>
            <div class="alert alert-warning"><i class="ri-time-line"></i> Registration deadline has passed.</div>
            <?php elseif (isLoggedIn()): ?>
            <button class="btn btn-primary btn-block btn-lg mb-2 reg-btn" data-event-id="<?= $event['id'] ?>">
                <i class="ri-calendar-check-line"></i> Register Now
            </button>
            <?php else: ?>
            <a href="<?= BASE_URL ?>/pages/login.php?redirect=event-detail&id=<?= $event['id'] ?>" class="btn btn-primary btn-block btn-lg mb-2"><i class="ri-login-box-line"></i> Login to Register</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Event Details -->
    <div class="card">
        <div class="card-header"><h4 class="card-title">Event Details</h4></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:1rem">
            <?php $details = [
                ['icon'=>'ri-calendar-line','color'=>'icon-blue','label'=>'Start Date','value'=> formatDateTime($event['start_date'])],
                ['icon'=>'ri-calendar-check-line','color'=>'icon-green','label'=>'End Date','value'=> formatDateTime($event['end_date'])],
                ['icon'=>'ri-map-pin-line','color'=>'icon-purple','label'=>'Venue','value'=> $event['venue'] ?? 'TBA'],
                ['icon'=>'ri-building-4-line','color'=>'icon-orange','label'=>'Organizer','value'=> $event['club_name']],
            ];
            if ($event['registration_deadline']) $details[] = ['icon'=>'ri-timer-line','color'=>'icon-red','label'=>'Reg. Deadline','value'=> formatDateTime($event['registration_deadline'])];
            if ($event['max_participants'] > 0) $details[] = ['icon'=>'ri-group-line','color'=>'icon-teal','label'=>'Max Participants','value'=> $event['max_participants']];
            foreach ($details as $d): ?>
            <div style="display:flex;gap:0.75rem;align-items:flex-start">
                <div class="stat-card-icon <?= $d['color'] ?>" style="width:36px;height:36px;font-size:0.9rem;flex-shrink:0"><i class="<?= $d['icon'] ?>"></i></div>
                <div><div style="font-size:0.75rem;color:var(--color-text-3)"><?= $d['label'] ?></div><strong style="font-size:0.875rem"><?= htmlspecialchars($d['value']) ?></strong></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

</div>
</div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
document.querySelector('.reg-btn')?.addEventListener('click', async function() {
    this.disabled=true; this.innerHTML='<i class="ri-loader-4-line spin"></i> Registering...';
    const data = await apiPost(BASE_URL+'/api/events.php', {action:'register',event_id:<?= $event['id'] ?>});
    showToast(data.message, data.success?'success':'error');
    if (data.success) location.reload();
    else { this.disabled=false; this.innerHTML='<i class="ri-calendar-check-line"></i> Register Now'; }
});
document.querySelector('.cancel-reg-btn')?.addEventListener('click', async function() {
    if (!confirm('Cancel your registration?')) return;
    const data = await apiPost(BASE_URL+'/api/events.php', {action:'cancel_registration',event_id:<?= $event['id'] ?>});
    showToast(data.message, data.success?'success':'error');
    if (data.success) location.reload();
});
</script>
