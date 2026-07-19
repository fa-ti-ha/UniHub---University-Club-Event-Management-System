<?php
// dashboard/student/profile.php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dashboard-shell.php';
requireLogin();
define('EXTRA_CSS', 'dashboard.css');

$user = currentUser();
$uid  = $user['id'];
$role = currentRole();
$deptPath = str_replace('_','-',$role);

// Fetch fresh user data
$freshUser = $pdo->prepare("SELECT u.*, d.name AS dept_name FROM users u LEFT JOIN departments d ON d.id = u.department_id WHERE u.id = ?");
$freshUser->execute([$uid]); $freshUser = $freshUser->fetch();

// Departments
$departments = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $phone     = sanitize($_POST['phone'] ?? '');
    $pic       = $freshUser['profile_picture'];

    if (!empty($_FILES['profile_picture']['name'])) {
        $newPic = uploadImage($_FILES['profile_picture'], 'profiles');
        if ($newPic) $pic = $newPic;
    }

    $pdo->prepare("UPDATE users SET full_name=?, phone=?, profile_picture=? WHERE id=?")->execute([$full_name, $phone, $pic, $uid]);

    // Password change
    if (!empty($_POST['new_password'])) {
        if (strlen($_POST['new_password']) < 6) { setFlash('error', 'Password must be at least 6 characters.'); }
        elseif ($_POST['new_password'] !== $_POST['confirm_password']) { setFlash('error', 'Passwords do not match.'); }
        else {
            $hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $uid]);
            setFlash('success', 'Password updated!');
        }
    }

    // Update session
    $_SESSION['user']['full_name'] = $full_name;
    $_SESSION['user']['phone']     = $phone;
    $_SESSION['user']['profile_picture'] = $pic;

    setFlash('success', 'Profile updated successfully!');
    header('Location: profile.php'); exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dashboard-body">
<?php renderDashboardShell($role, $user, 'Profile', 'My Profile', $pdo); ?>

<!-- Profile Header -->
<div class="profile-header-card mb-6">
    <div class="profile-avatar-wrap">
        <img src="<?= profilePicUrl($freshUser['profile_picture']) ?>" alt="" class="profile-avatar" />
    </div>
    <div class="profile-info">
        <h2><?= htmlspecialchars($freshUser['full_name']) ?></h2>
        <p><i class="ri-mail-line"></i> <?= htmlspecialchars($freshUser['email']) ?></p>
        <p><i class="ri-id-card-line"></i> <?= htmlspecialchars($freshUser['student_id'] ?? 'N/A') ?></p>
        <span class="profile-badge"><i class="ri-shield-check-line"></i> <?= ucfirst(str_replace('_',' ',$role)) ?></span>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem">
<div>
<!-- Edit Profile Form -->
<div class="card mb-4">
    <div class="card-header"><h3 class="card-title"><i class="ri-user-settings-line" style="color:var(--color-primary)"></i> Edit Profile</h3></div>
    <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Profile Picture</label>
                <div class="avatar-upload-wrapper">
                    <img id="profilePreview" src="<?= profilePicUrl($freshUser['profile_picture']) ?>" alt="" class="avatar-preview" />
                    <div class="avatar-upload-btn">
                        <label for="picInput" class="btn btn-ghost btn-sm" style="cursor:pointer"><i class="ri-upload-2-line"></i> Change Photo</label>
                        <span class="text-xs text-muted">Max 5MB</span>
                        <input type="file" id="picInput" name="profile_picture" accept="image/*" style="display:none" onchange="previewImage(this,'profilePreview')" />
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <div class="form-control-icon"><i class="ri-user-line input-icon"></i><input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($freshUser['full_name']) ?>" required /></div>
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <div class="form-control-icon"><i class="ri-phone-line input-icon"></i><input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($freshUser['phone'] ?? '') ?>" /></div>
            </div>
            <div class="form-group">
                <label class="form-label">Student ID <span class="badge badge-secondary">Read-only</span></label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($freshUser['student_id'] ?? '') ?>" disabled />
            </div>
            <div class="form-group">
                <label class="form-label">Email <span class="badge badge-secondary">Read-only</span></label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($freshUser['email']) ?>" disabled />
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Save Changes</button>
    </form>
    </div>
</div>

<!-- Change Password -->
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="ri-lock-password-line" style="color:var(--color-primary)"></i> Change Password</h3></div>
    <div class="card-body">
    <form method="POST">
        <div class="form-group">
            <label class="form-label">New Password</label>
            <div class="form-control-icon"><i class="ri-lock-line input-icon"></i><input type="password" name="new_password" class="form-control has-right-icon" placeholder="Min 6 characters" /><span class="input-icon-right" data-toggle-password="newpass"><i class="ri-eye-line"></i></span></div>
        </div>
        <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <div class="form-control-icon"><i class="ri-lock-password-line input-icon"></i><input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" /></div>
        </div>
        <button type="submit" class="btn btn-warning"><i class="ri-refresh-line"></i> Update Password</button>
    </form>
    </div>
</div>
</div>

<!-- Account Info Card -->
<div>
<div class="card" style="position:sticky;top:calc(var(--navbar-h) + 1rem)">
    <div class="card-header"><h4 class="card-title">Account Info</h4></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:1rem">
        <?php $info = [
            ['icon'=>'ri-calendar-line','color'=>'icon-blue','label'=>'Member Since','value'=> formatDate($freshUser['created_at'], 'M d, Y')],
            ['icon'=>'ri-book-2-line','color'=>'icon-green','label'=>'Department','value'=> $freshUser['dept_name'] ?? 'N/A'],
            ['icon'=>'ri-calendar-2-line','color'=>'icon-purple','label'=>'Batch','value'=> $freshUser['batch'] ?? 'N/A'],
            ['icon'=>'ri-shield-check-line','color'=>'icon-teal','label'=>'Status','value'=> ucfirst($freshUser['status'])],
        ];
        foreach ($info as $i): ?>
        <div style="display:flex;gap:0.75rem;align-items:center">
            <div class="stat-card-icon <?= $i['color'] ?>" style="width:36px;height:36px;font-size:0.9rem;flex-shrink:0"><i class="<?= $i['icon'] ?>"></i></div>
            <div><div style="font-size:0.75rem;color:var(--color-text-3)"><?= $i['label'] ?></div><strong style="font-size:0.875rem"><?= htmlspecialchars($i['value']) ?></strong></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</div>
</div>

<?php renderDashboardEnd(); ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/auth.js"></script>
<?= toggleSidebarScript(); ?>
