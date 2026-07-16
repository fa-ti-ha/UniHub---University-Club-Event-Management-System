<?php
// dashboard/club-admin/edit-club.php
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
if (!$club) { setFlash('error','No club found.'); header('Location: index.php'); exit; }

$supervisors = $pdo->query("SELECT * FROM teacher_supervisors ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['name', 'short_description', 'full_description', 'mission', 'vision', 'activities', 'category', 'president_name', 'vice_president_name', 'supervisor_id'];
    $updates = []; $vals = [];
    foreach ($fields as $f) { $updates[] = "$f=?"; $vals[] = $f === 'supervisor_id' ? (int)($_POST[$f]??0) : sanitize($_POST[$f]??''); }

    if (!empty($_FILES['logo']['name'])) { $logo = uploadImage($_FILES['logo'], 'clubs/logos'); if ($logo) { $updates[] = 'logo=?'; $vals[] = $logo; } }
    if (!empty($_FILES['banner']['name'])) { $banner = uploadImage($_FILES['banner'], 'clubs/banners'); if ($banner) { $updates[] = 'banner=?'; $vals[] = $banner; } }

    $vals[] = $club['id'];
    $pdo->prepare("UPDATE clubs SET " . implode(',', $updates) . " WHERE id=?")->execute($vals);
    setFlash('success', 'Club updated successfully!');
    header('Location: edit-club.php'); exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell('club_admin', $user, 'Edit Club', 'Edit Club Info', $pdo); ?>

<div class="card" style="max-width:900px">
<div class="card-header"><h3 class="card-title"><i class="ri-edit-line" style="color:var(--color-primary)"></i> Edit <?= htmlspecialchars($club['name']) ?></h3></div>
<div class="card-body">
<form method="POST" enctype="multipart/form-data">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
    <div class="form-group">
        <label class="form-label">Club Name</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($club['name']) ?>" required />
    </div>
    <div class="form-group">
        <label class="form-label">Category</label>
        <select name="category" class="form-control">
            <?php foreach(['General','Technology','Arts & Culture','Business','Sports','Academic','Social'] as $c): ?>
            <option <?= $club['category']===$c?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group" style="grid-column:1/-1">
        <label class="form-label">Short Description</label>
        <textarea name="short_description" class="form-control" rows="2"><?= htmlspecialchars($club['short_description']??'') ?></textarea>
    </div>
    <div class="form-group" style="grid-column:1/-1">
        <label class="form-label">Full Description</label>
        <textarea name="full_description" class="form-control" rows="5"><?= htmlspecialchars($club['full_description']??'') ?></textarea>
    </div>
    <div class="form-group">
        <label class="form-label">Mission</label>
        <textarea name="mission" class="form-control" rows="3"><?= htmlspecialchars($club['mission']??'') ?></textarea>
    </div>
    <div class="form-group">
        <label class="form-label">Vision</label>
        <textarea name="vision" class="form-control" rows="3"><?= htmlspecialchars($club['vision']??'') ?></textarea>
    </div>
    <div class="form-group" style="grid-column:1/-1">
        <label class="form-label">Activities <span class="text-muted text-sm">(comma-separated)</span></label>
        <textarea name="activities" class="form-control" rows="2"><?= htmlspecialchars($club['activities']??'') ?></textarea>
    </div>
    <div class="form-group">
        <label class="form-label">President Name</label>
        <input type="text" name="president_name" class="form-control" value="<?= htmlspecialchars($club['president_name']??'') ?>" />
    </div>
    <div class="form-group">
        <label class="form-label">Vice President Name</label>
        <input type="text" name="vice_president_name" class="form-control" value="<?= htmlspecialchars($club['vice_president_name']??'') ?>" />
    </div>
    <div class="form-group" style="grid-column:1/-1">
        <label class="form-label">Teacher Supervisor</label>
        <select name="supervisor_id" class="form-control">
            <option value="">Select Supervisor</option>
            <?php foreach ($supervisors as $sup): ?>
            <option value="<?= $sup['id'] ?>" <?= $club['supervisor_id']==$sup['id']?'selected':'' ?>><?= htmlspecialchars($sup['name']) ?> (<?= htmlspecialchars($sup['designation']??'') ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Club Logo <?php if($club['logo']): ?><span class="text-muted text-sm">(current: <?= $club['logo'] ?>)</span><?php endif; ?></label>
        <label class="file-upload" for="logoInput">
            <input type="file" id="logoInput" name="logo" accept="image/*" onchange="previewImage(this,'logoP')" />
            <?php if($club['logo']): ?><img src="<?= clubLogoUrl($club['logo']) ?>" id="logoP" class="file-preview" style="display:block" alt="Logo" /><?php else: ?>
            <div class="file-upload-icon"><i class="ri-image-add-line"></i></div><p>Upload new logo</p><img id="logoP" class="file-preview" alt="" /><?php endif; ?>
        </label>
    </div>
    <div class="form-group">
        <label class="form-label">Club Banner</label>
        <label class="file-upload" for="bannerInput">
            <input type="file" id="bannerInput" name="banner" accept="image/*" onchange="previewImage(this,'bannerP')" />
            <?php if($club['banner']): ?><img src="<?= clubBannerUrl($club['banner']) ?>" id="bannerP" class="file-preview" style="display:block;width:100%;height:80px;object-fit:cover" alt="Banner" /><?php else: ?>
            <div class="file-upload-icon"><i class="ri-image-line"></i></div><p>Upload new banner</p><img id="bannerP" class="file-preview" alt="" /><?php endif; ?>
        </label>
    </div>
</div>
<div style="display:flex;gap:1rem;margin-top:1rem">
    <button type="submit" class="btn btn-primary btn-lg"><i class="ri-save-line"></i> Save Changes</button>
    <a href="index.php" class="btn btn-ghost btn-lg">Cancel</a>
</div>
</form>
</div>
</div>

<?php renderDashboardEnd(); ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?= toggleSidebarScript(); ?>
