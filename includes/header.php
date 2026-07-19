<?php
// includes/header.php — Public navigation header
if (!isset($pdo)) { require_once __DIR__ . '/../config/db.php'; }
if (!isset($_SESSION)) { session_start(); }
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$uniName   = defined('SITE_NAME') ? SITE_NAME : 'UniHub';
$pageTitle = defined('PAGE_TITLE') ? PAGE_TITLE . ' | UniHub University' : 'UniHub University';
$loggedIn  = isLoggedIn();
$user      = currentUser();
$role      = currentRole();
$unreadCount = $loggedIn ? getUnreadNotificationCount($pdo, $user['id']) : 0;

$navLinks = [
    ['href' => BASE_URL . '/index.php',         'label' => 'Home'],
    ['href' => BASE_URL . '/pages/clubs.php',   'label' => 'Clubs'],
    ['href' => BASE_URL . '/pages/events.php',  'label' => 'Events'],
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="UniHub University — Connect with clubs, discover events, and build your university experience." />
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <!-- Remix Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" />
    <!-- CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css" />
    <?php if (defined('EXTRA_CSS') && EXTRA_CSS): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= EXTRA_CSS ?>" />
    <?php endif; ?>
</head>
<body>

<!-- ============ NAVBAR ============ -->
<header class="navbar" id="mainNavbar">
    <nav class="navbar-inner container">
        <!-- Logo -->
        <a href="<?= BASE_URL ?>/index.php" class="navbar-brand">
            <div class="navbar-logo-icon"><i class="ri-building-4-line"></i></div>
            <span>Uni<strong>Hub</strong></span>
        </a>

        <!-- Desktop nav links -->
        <ul class="navbar-nav" id="navLinks">
            <?php foreach ($navLinks as $link): ?>
            <li><a href="<?= $link['href'] ?>" class="nav-link"><?= $link['label'] ?></a></li>
            <?php endforeach; ?>
        </ul>

        <!-- Right side -->
        <div class="navbar-actions">
            <!-- Dark mode toggle -->
            <button class="btn-icon" id="themeToggle" title="Toggle dark mode" aria-label="Toggle theme">
                <i class="ri-moon-line" id="themeIcon"></i>
            </button>

            <?php if ($loggedIn && $user): ?>
            <!-- Notifications -->
            <div class="dropdown" id="notifDropdown">
                <button class="btn-icon notif-btn" data-dropdown="notifMenu">
                    <i class="ri-notification-3-line"></i>
                    <?php if ($unreadCount > 0): ?>
                    <span class="notif-badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu notif-menu" id="notifMenu">
                    <div class="dropdown-header">
                        <span>Notifications</span>
                        <a href="#" class="mark-all-read" data-action="mark-all-read">Mark all read</a>
                    </div>
                    <div class="notif-list" id="notifList">
                        <div class="notif-loading"><i class="ri-loader-4-line spin"></i></div>
                    </div>
                    <div class="dropdown-footer">
                        <a href="<?= BASE_URL ?>/dashboard/<?= str_replace('_', '-', $role) ?>/notifications.php">View all</a>
                    </div>
                </div>
            </div>

            <!-- User menu -->
            <div class="dropdown" id="userDropdown">
                <button class="user-menu-btn" data-dropdown="userMenu">
                    <img src="<?= profilePicUrl($user['profile_picture']) ?>" alt="Profile" class="user-avatar-sm" />
                    <span class="user-name-short d-none-mobile"><?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?></span>
                    <i class="ri-arrow-down-s-line"></i>
                </button>
                <div class="dropdown-menu" id="userMenu">
                    <div class="dropdown-user-info">
                        <img src="<?= profilePicUrl($user['profile_picture']) ?>" alt="Profile" />
                        <div>
                            <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                            <span class="role-tag"><?= ucfirst(str_replace('_', ' ', $role)) ?></span>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="<?= BASE_URL ?>/dashboard/<?= str_replace('_', '-', $role) ?>/index.php" class="dropdown-item"><i class="ri-dashboard-line"></i> Dashboard</a>
                    <a href="<?= BASE_URL ?>/dashboard/<?= str_replace('_', '-', $role) ?>/profile.php" class="dropdown-item"><i class="ri-user-line"></i> Profile</a>
                    <div class="dropdown-divider"></div>
                    <a href="<?= BASE_URL ?>/api/auth.php?action=logout" class="dropdown-item text-danger"><i class="ri-logout-box-line"></i> Logout</a>
                </div>
            </div>

            <?php else: ?>
            <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-outline-primary btn-sm">Login</a>
            <a href="<?= BASE_URL ?>/pages/register.php" class="btn btn-primary btn-sm">Register</a>
            <?php endif; ?>

            <!-- Mobile hamburger -->
            <button class="btn-icon hamburger" id="hamburger" aria-label="Open menu">
                <i class="ri-menu-line"></i>
            </button>
        </div>
    </nav>
</header>

<!-- Mobile nav drawer -->
<div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
<div class="mobile-nav" id="mobileNav">
    <div class="mobile-nav-header">
        <span class="navbar-brand"><i class="ri-building-4-line"></i> UniHub</span>
        <button class="btn-icon" id="mobileNavClose"><i class="ri-close-line"></i></button>
    </div>
    <ul class="mobile-nav-links">
        <?php foreach ($navLinks as $link): ?>
        <li><a href="<?= $link['href'] ?>"><?= $link['label'] ?></a></li>
        <?php endforeach; ?>
        <?php if ($loggedIn): ?>
        <li><a href="<?= BASE_URL ?>/dashboard/<?= str_replace('_', '-', $role) ?>/index.php"><i class="ri-dashboard-line"></i> Dashboard</a></li>
        <li><a href="<?= BASE_URL ?>/api/auth.php?action=logout" class="text-danger"><i class="ri-logout-box-line"></i> Logout</a></li>
        <?php else: ?>
        <li><a href="<?= BASE_URL ?>/pages/login.php">Login</a></li>
        <li><a href="<?= BASE_URL ?>/pages/register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</div>

<!-- Flash messages -->
<div class="flash-container" id="flashContainer">
<?php
foreach (['success', 'error', 'warning', 'info'] as $type) {
    $msg = getFlash($type);
    if ($msg): ?>
    <div class="toast toast-<?= $type ?> show" data-auto-dismiss>
        <i class="ri-<?= $type === 'success' ? 'checkbox-circle' : ($type === 'error' ? 'error-warning' : ($type === 'warning' ? 'alert-line' : 'information')) ?>-line"></i>
        <span><?= htmlspecialchars($msg) ?></span>
        <button class="toast-close"><i class="ri-close-line"></i></button>
    </div>
    <?php endif;
}
?>
</div>

<main class="main-content">
