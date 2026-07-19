<?php
// dashboard/club-admin/profile.php — same as student but for club admin role
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dashboard-shell.php';
requireRole('club_admin');
define('EXTRA_CSS', 'dashboard.css');
$user = currentUser(); $uid = $user['id'];

$depts = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$dbUser = $pdo->prepare("SELECT u.*, d.name AS dept_name FROM users u LEFT JOIN departments d ON d.id=u.department_id WHERE u.id=?");
$dbUser->execute([$uid]); $dbUser = $dbUser->fetch();

// Handle update
$errors = []; $success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $phone     = sanitize($_POST['phone'] ?? '');
    $batch     = sanitize($_POST['batch'] ?? '');
    $dept_id   = (int)($_POST['department_id'] ?? 0);

    if (strlen($full_name) < 3) { $errors[] = 'Name too short.'; }
    if (empty($errors)) {
        $pic = $dbUser['profile_picture'];
        if (!empty($_FILES['profile_picture']['name']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $newPic = uploadImage($_FILES['profile_picture'], 'profiles');
            if ($newPic) $pic = $newPic;
        }
        $pdo->prepare("UPDATE users SET full_name=?,phone=?,batch=?,department_id=?,profile_picture=? WHERE id=?")
            ->execute([$full_name, $phone, $batch, $dept_id, $pic, $uid]);
        // Refresh session
        $updated = $pdo->prepare("SELECT * FROM users WHERE id=?"); $updated->execute([$uid]); loginUser($updated->fetch());
        setFlash('success','Profile updated!');
        header('Location: profile.php'); exit;
    }

    // Password change
    if (!empty($_POST['current_password'])) {
        $cur = $_POST['current_password']; $new = $_POST['new_password']; $conf = $_POST['confirm_new'];
        if (!password_verify($cur, $dbUser['password_hash'])) { setFlash('error','Current password is incorrect.'); }
        elseif (strlen($new) < 6) { setFlash('error','New password must be at least 6 characters.'); }
        elseif ($new !== $conf)   { setFlash('error','Passwords do not match.'); }
        else {
            $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([password_hash($new, PASSWORD_BCRYPT), $uid]);
            setFlash('success','Password updated!');
        }
        header('Location: profile.php'); exit;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell('club_admin', $user, 'Profile', 'My Profile', $pdo); ?>

<!-- Profile Header -->
<div class="profile-header-card mb-6">
    <div class="profile-avatar-wrap">
        <img src="<?= profilePicUrl($dbUser['profile_picture']) ?>" alt="" class="profile-avatar" id="avatarPreview" />
    </div>
    <div class="profile-info">
        <h2><?= htmlspecialchars($dbUser['full_name']) ?></h2>
        <p><i class="ri-mail-line"></i> <?= htmlspecialchars($dbUser['email']) ?></p>
        <p><i class="ri-building-4-line"></i> <?= htmlspecialchars($dbUser['dept_name'] ?? 'N/A') ?></p>
        <span class="profile-badge"><i class="ri-shield-user-line"></i> Club Admin</span>
    </div>
</div>

<div class="dashboard-grid">
<div>
    <!-- Edit Profile -->
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title"><i class="ri-edit-line" style="color:var(--color-primary)"></i> Edit Profile</h3></div>
        <div class="card-body">
            <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_profile" />
                <div class="form-group">
                    <label class="form-label">Profile Picture</label>
                    <div class="avatar-upload-wrapper">
                        <img src="<?= profilePicUrl($dbUser['profile_picture']) ?>" alt="" class="avatar-preview" id="picPreview" />
                        <div class="avatar-upload-btn">
                            <label for="picInput" class="btn btn-outline-primary btn-sm" style="cursor:pointer"><i class="ri-upload-line"></i> Change Photo</label>
                            <input type="file" id="picInput" name="profile_picture" accept="image/*" style="display:none" onchange="previewImage(this,'picPreview')" />
                            <span class="text-xs text-muted">JPG, PNG, max 5MB</span>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <div class="form-control-icon"><i class="ri-user-line input-icon"></i><input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($dbUser['full_name']) ?>" required /></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <div class="form-control-icon"><i class="ri-phone-line input-icon"></i><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($dbUser['phone'] ?? '') ?>" /></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Batch</label>
                    <input type="text" name="batch" class="form-control" value="<?= htmlspecialchars($dbUser['batch'] ?? '') ?>" placeholder="e.g. 2021" />
                </div>
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-control">
                        <option value="">Select Department</option>
                        <?php foreach ($depts as $d): ?><option value="<?= $d['id'] ?>" <?= $dbUser['department_id']==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Save Changes</button>
            </form>
        </div>
    </div>
</div>

<div>
    <!-- Account Info -->
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title"><i class="ri-information-line" style="color:var(--color-info)"></i> Account Info</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:0.875rem">
            <div><span class="text-xs text-muted">Student ID</span><br><strong><?= htmlspecialchars($dbUser['student_id'] ?? 'N/A') ?></strong></div>
            <div><span class="text-xs text-muted">Email</span><br><strong><?= htmlspecialchars($dbUser['email']) ?></strong></div>
            <div><span class="text-xs text-muted">Role</span><br><?= getStatusBadge($dbUser['role']) ?></div>
            <div><span class="text-xs text-muted">Account Status</span><br><?= getStatusBadge($dbUser['status']) ?></div>
            <div><span class="text-xs text-muted">Member Since</span><br><strong><?= formatDate($dbUser['created_at'], 'M d, Y') ?></strong></div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="card">
        <div class="card-header"><h3 class="card-title"><i class="ri-lock-line" style="color:var(--color-warning)"></i> Change Password</h3></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <div class="form-control-icon"><i class="ri-lock-line input-icon"></i><input type="password" name="current_password" class="form-control" required /></div>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <div class="form-control-icon"><i class="ri-lock-password-line input-icon"></i><input type="password" name="new_password" class="form-control" required /></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <div class="form-control-icon"><i class="ri-lock-password-line input-icon"></i><input type="password" name="confirm_new" class="form-control" required /></div>
                </div>
                <button type="submit" class="btn btn-warning"><i class="ri-key-line"></i> Update Password</button>
            </form>
        </div>
    </div>
</div>
</div>

<?php renderDashboardEnd(); ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?= toggleSidebarScript(); ?>
