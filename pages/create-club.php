<?php
// pages/create-club.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
define('PAGE_TITLE', 'Create a Club');

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'club_name'      => sanitize($_POST['club_name'] ?? ''),
        'description'    => sanitize($_POST['description'] ?? ''),
        'objectives'     => sanitize($_POST['objectives'] ?? ''),
        'activities'     => sanitize($_POST['activities'] ?? ''),
        'supervisor_name'=> sanitize($_POST['supervisor_name'] ?? ''),
        'supervisor_email'=> sanitize($_POST['supervisor_email'] ?? ''),
        'reason'         => sanitize($_POST['reason'] ?? ''),
        'contact_info'   => sanitize($_POST['contact_info'] ?? ''),
    ];
    if (strlen($data['club_name']) < 3) { setFlash('error', 'Club name must be at least 3 characters.'); }
    else {
        $logo = $banner = null;
        if (!empty($_FILES['logo']['name'])) $logo = uploadImage($_FILES['logo'], 'clubs/logos');
        if (!empty($_FILES['banner']['name'])) $banner = uploadImage($_FILES['banner'], 'clubs/banners');

        $stmt = $pdo->prepare("INSERT INTO club_creation_requests (requested_by, club_name, logo, banner, description, objectives, activities, supervisor_name, supervisor_email, reason, contact_info) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([currentUser()['id'], $data['club_name'], $logo, $banner, $data['description'], $data['objectives'], $data['activities'], $data['supervisor_name'], $data['supervisor_email'], $data['reason'], $data['contact_info']]);
        sendNotification($pdo, currentUser()['id'], 'club_request_submitted', 'Club Request Submitted', "Your request to create \"{$data['club_name']}\" is under review.");
        setFlash('success', 'Club request submitted! We will review it shortly.');
        header('Location: ' . BASE_URL . '/pages/create-club.php'); exit;
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<section class="page-header">
    <div class="container"><h1><i class="ri-add-circle-line"></i> Start a New Club</h1><p>Submit your club proposal and make it happen</p></div>
</section>

<section class="section-sm" style="background:var(--color-bg)">
<div class="container-sm">
<div class="card">
<div class="card-header">
    <h3 class="card-title">Club Creation Request</h3>
    <span class="badge badge-warning">Requires Admin Approval</span>
</div>
<div class="card-body">
<form method="POST" enctype="multipart/form-data" id="createClubForm">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
        <div class="form-group" style="grid-column:1/-1">
            <label class="form-label">Club Name <span class="required">*</span></label>
            <div class="form-control-icon"><i class="ri-building-4-line input-icon"></i><input type="text" name="club_name" class="form-control" placeholder="e.g. Robotics Club" required minlength="3" /></div>
        </div>
        <div class="form-group" style="grid-column:1/-1">
            <label class="form-label">Club Description <span class="required">*</span></label>
            <textarea name="description" class="form-control" rows="4" placeholder="Describe the club's purpose, activities, and target audience..." required></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Objectives</label>
            <textarea name="objectives" class="form-control" rows="3" placeholder="List the key objectives of this club..."></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Planned Activities</label>
            <textarea name="activities" class="form-control" rows="3" placeholder="Workshops, competitions, meetups..."></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Teacher Supervisor Name</label>
            <div class="form-control-icon"><i class="ri-user-star-line input-icon"></i><input type="text" name="supervisor_name" class="form-control" placeholder="Dr. Full Name" /></div>
        </div>
        <div class="form-group">
            <label class="form-label">Supervisor Email</label>
            <div class="form-control-icon"><i class="ri-mail-line input-icon"></i><input type="email" name="supervisor_email" class="form-control" placeholder="supervisor@university.edu" /></div>
        </div>
        <div class="form-group" style="grid-column:1/-1">
            <label class="form-label">Why should this club be created? <span class="required">*</span></label>
            <textarea name="reason" class="form-control" rows="3" placeholder="Explain the need and benefit of this club for students..." required></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Club Logo</label>
            <label class="file-upload" for="logoInput">
                <input type="file" id="logoInput" name="logo" accept="image/*" onchange="previewImage(this,'logoPreview')" />
                <div class="file-upload-icon"><i class="ri-image-add-line"></i></div>
                <p>Click to upload logo <span>(PNG, JPG, max 5MB)</span></p>
                <img id="logoPreview" class="file-preview" alt="Logo preview" />
            </label>
        </div>
        <div class="form-group">
            <label class="form-label">Club Banner</label>
            <label class="file-upload" for="bannerInput">
                <input type="file" id="bannerInput" name="banner" accept="image/*" onchange="previewImage(this,'bannerPreview')" />
                <div class="file-upload-icon"><i class="ri-image-line"></i></div>
                <p>Click to upload banner <span>(PNG, JPG, max 5MB)</span></p>
                <img id="bannerPreview" class="file-preview" alt="Banner preview" />
            </label>
        </div>
        <div class="form-group" style="grid-column:1/-1">
            <label class="form-label">Contact Information</label>
            <div class="form-control-icon"><i class="ri-phone-line input-icon"></i><input type="text" name="contact_info" class="form-control" placeholder="Email or phone number for this club proposal" /></div>
        </div>
    </div>
    <div style="display:flex;gap:1rem;margin-top:1.5rem">
        <button type="submit" class="btn btn-primary btn-lg"><i class="ri-send-plane-line"></i> Submit Request</button>
        <a href="<?= BASE_URL ?>/pages/clubs.php" class="btn btn-ghost btn-lg">Cancel</a>
    </div>
</form>
</div>
</div>
</div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
document.getElementById('createClubForm').addEventListener('submit', function(e) {
    const name = this.querySelector('[name="club_name"]').value.trim();
    const desc = this.querySelector('[name="description"]').value.trim();
    const reason = this.querySelector('[name="reason"]').value.trim();
    if (!name || !desc || !reason) { e.preventDefault(); showToast('Please fill in all required fields.', 'warning'); }
});
</script>
