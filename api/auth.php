<?php
// ============================================================
// api/auth.php — Login, Register, Logout — COMPLETE & BUG-FIXED
// ============================================================
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ---- LOGOUT (GET) ----
if ($action === 'logout') {
    logoutUser();
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$action = $_POST['action'] ?? (json_decode(file_get_contents('php://input'), true)['action'] ?? '');

// ---- LOGIN ----
if ($action === 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);

    if (!$email || !$password) {
        jsonResponse(['success' => false, 'message' => 'Email and password are required.']);
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid email or password.']);
    }

    if ($user['status'] === 'blocked') {
        jsonResponse(['success' => false, 'message' => 'Your account has been blocked. Contact admin.']);
    }

    loginUser($user);

    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $pdo->prepare("UPDATE users SET remember_token=? WHERE id=?")->execute([$token, $user['id']]);
        setcookie('remember_token', $token, time() + 86400 * 30, '/', '', false, true);
    }

    logActivity($pdo, $user['id'], 'login', 'User logged in from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

    $map = [
        'student'     => '/dashboard/student/index.php',
        'club_admin'  => '/dashboard/club-admin/index.php',
        'super_admin' => '/dashboard/super-admin/index.php',
    ];
    jsonResponse(['success' => true, 'redirect' => BASE_URL . ($map[$user['role']] ?? '/index.php'), 'role' => $user['role']]);
}

// ---- REGISTER ----
if ($action === 'register') {
    $full_name  = sanitize($_POST['full_name'] ?? '');
    $student_id = sanitize($_POST['student_id'] ?? '');
    $email      = trim(strtolower($_POST['email'] ?? ''));
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';
    $dept_id    = (int)($_POST['department_id'] ?? 0);
    $batch      = sanitize($_POST['batch'] ?? '');
    $phone      = sanitize($_POST['phone'] ?? '');

    if (strlen($full_name) < 3)   jsonResponse(['success' => false, 'message' => 'Full name must be at least 3 characters.', 'field' => 'full_name']);
    if (strlen($student_id) < 4)  jsonResponse(['success' => false, 'message' => 'Student ID must be at least 4 characters.', 'field' => 'student_id']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(['success' => false, 'message' => 'Enter a valid email address.', 'field' => 'email']);
    if (strlen($password) < 6)    jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.', 'field' => 'password']);
    if ($password !== $confirm)   jsonResponse(['success' => false, 'message' => 'Passwords do not match.', 'field' => 'confirm_password']);
    if (!$dept_id)                jsonResponse(['success' => false, 'message' => 'Please select a department.', 'field' => 'department_id']);
    if (!$batch)                  jsonResponse(['success' => false, 'message' => 'Batch year is required.', 'field' => 'batch']);

    // Unique checks
    $check = $pdo->prepare("SELECT id FROM users WHERE email=? OR student_id=?");
    $check->execute([$email, $student_id]);
    if ($check->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Email or Student ID is already registered.']);
    }

    $pic = null;
    if (!empty($_FILES['profile_picture']['name']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $pic = uploadImage($_FILES['profile_picture'], 'profiles');
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare("INSERT INTO users (full_name, student_id, email, password_hash, department_id, batch, phone, profile_picture) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$full_name, $student_id, $email, $hash, $dept_id, $batch, $phone, $pic]);

    $newId = (int)$pdo->lastInsertId();
    logActivity($pdo, $newId, 'register', 'New student registration');

    jsonResponse(['success' => true, 'message' => 'Account created! Please log in.', 'redirect' => BASE_URL . '/pages/login.php?registered=1']);
}

jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
