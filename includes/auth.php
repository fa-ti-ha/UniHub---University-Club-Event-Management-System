<?php
// ============================================================
// Auth & Session Helpers
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user']) && !empty($_SESSION['role']);
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function currentRole(): string {
    return $_SESSION['role'] ?? 'guest';
}

function requireLogin(string $redirect = '/unihubt/pages/login.php'): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in to continue.';
        header('Location: ' . BASE_URL . '/pages/login.php');
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    if (currentRole() !== $role) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function requireAnyRole(array $roles): void {
    requireLogin();
    if (!in_array(currentRole(), $roles)) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function loginUser(array $user): void {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['user']    = [
        'id'              => $user['id'],
        'full_name'       => $user['full_name'],
        'email'           => $user['email'],
        'student_id'      => $user['student_id'] ?? null,
        'role'            => $user['role'],
        'profile_picture' => $user['profile_picture'] ?? null,
        'department_id'   => $user['department_id'] ?? null,
        'batch'           => $user['batch'] ?? null,
        'phone'           => $user['phone'] ?? null,
    ];
}
//fixed roll access issues
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function dashboardRedirect(): void {
    $role = currentRole();
    $map  = [
        'student'     => BASE_URL . '/dashboard/student/index.php',
        'club_admin'  => BASE_URL . '/dashboard/club-admin/index.php',
        'super_admin' => BASE_URL . '/dashboard/super-admin/index.php',
    ];
    header('Location: ' . ($map[$role] ?? BASE_URL . '/index.php'));
    exit;
}
// check & fixed bugs