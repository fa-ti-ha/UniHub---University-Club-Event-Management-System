<?php
// dashboard/club-admin/create-event.php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $club) {
    $title    = sanitize($_POST['title'] ?? '');
    $desc     = sanitize($_POST['description'] ?? '');
    $category = sanitize($_POST['category'] ?? 'General');
    $venue    = sanitize($_POST['venue'] ?? '');
    $start    = $_POST['start_date'] ?? '';
    $end      = $_POST['end_date'] ?? '';
    $deadline = $_POST['registration_deadline'] ?? null;
    $maxP     = (int)($_POST['max_participants'] ?? 0);
    $regType  = in_array($_POST['registration_type']??'auto',['auto','manual']) ? $_POST['registration_type'] : 'auto';

    if (!$title) { setFlash('error','Event title is required.'); }
    else {
        $banner = null;
        if (!empty($_FILES['banner']['name'])) $banner = uploadImage($_FILES['banner'], 'events');
        $slug = slugify($title);
        $pdo->prepare("INSERT INTO events (club_id,title,slug,description,category,venue,banner,start_date,end_date,registration_deadline,max_participants,registration_type,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'pending',?)")
            ->execute([$club['id'],$title,$slug,$desc,$category,$venue,$banner,$start,$end,$deadline?:null,$maxP,$regType,$uid]);
        sendNotification($pdo, $uid, 'event_pending', 'Event Submitted for Review', "Your event \"$title\" is pending Super Admin approval.");
        setFlash('success','Event submitted for approval!');
        header('Location: events.php'); exit;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell('club_admin', $user, 'Create Event', 'Create New Event', $pdo); ?>

<div class="card" style="max-width:900px">
<div class="card-header"><h3 class="card-title"><i class="ri-add-circle-line" style="color:var(--color-primary)"></i> New Event</h3><span class="badge badge-warning">Requires Super Admin Approval</span></div>
<div class="card-body">
<form method="POST" enctype="multipart/form-data">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
    <div class="form-group" style="grid-column:1/-1">
        <label class="form-label">Event Title <span class="required">*</span></label>
        <div class="form-control-icon"><i class="ri-calendar-event-line input-icon"></i><input type="text" name="title" class="form-control" placeholder="e.g. Annual Hackathon 2026" required /></div>
    </div>
    <div class="form-group">
        <label class="form-label">Category</label>
        <select name="category" class="form-control">
            <?php foreach(['General','Competition','Workshop','Cultural','Sports','Seminar','Other'] as $c): ?>
            <option><?= $c ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Venue <span class="required">*</span></label>
        <div class="form-control-icon"><i class="ri-map-pin-line input-icon"></i><input type="text" name="venue" class="form-control" placeholder="Building / Room / Location" required /></div>
    </div>
    <div class="form-group" style="grid-column:1/-1">
        <label class="form-label">Event Description <span class="required">*</span></label>
        <textarea name="description" class="form-control" rows="5" placeholder="Describe the event in detail..." required></textarea>
    </div>
    <div class="form-group">
        <label class="form-label">Start Date & Time <span class="required">*</span></label>
        <input type="datetime-local" name="start_date" class="form-control" required />
    </div>
    <div class="form-group">
        <label class="form-label">End Date & Time <span class="required">*</span></label>
        <input type="datetime-local" name="end_date" class="form-control" required />
    </div>
    <div class="form-group">
        <label class="form-label">Registration Deadline</label>
        <input type="datetime-local" name="registration_deadline" class="form-control" />
    </div>
    <div class="form-group">
        <label class="form-label">Max Participants <span class="text-muted text-sm">(0 = unlimited)</span></label>
        <div class="form-control-icon"><i class="ri-group-line input-icon"></i><input type="number" name="max_participants" class="form-control" value="0" min="0" /></div>
    </div>
    <div class="form-group">
        <label class="form-label">Registration Type</label>
        <select name="registration_type" class="form-control">
            <option value="auto">Auto Confirm</option>
            <option value="manual">Manual Review</option>
        </select>
    </div>
   
    
    <div class="form-group" style="grid-column:1/-1">
        <label class="form-label">Event Banner</label>
        <label class="file-upload" for="bannerInput">
            <input type="file" id="bannerInput" name="banner" accept="image/*" onchange="previewImage(this,'bannerPrev')" />
            <div class="file-upload-icon"><i class="ri-image-add-line"></i></div>
            <p>Click to upload event banner <span>(JPG, PNG, max 5MB)</span></p>
            <img id="bannerPrev" class="file-preview" alt="Banner preview" />
        </label>
    </div>
</div>
<div style="display:flex;gap:1rem;margin-top:1rem">
    <button type="submit" class="btn btn-primary btn-lg"><i class="ri-send-plane-line"></i> Submit for Approval</button>
    <a href="events.php" class="btn btn-ghost btn-lg">Cancel</a>
</div>
</form>
</div>
</div>

<?php renderDashboardEnd(); ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?= toggleSidebarScript(); ?>
