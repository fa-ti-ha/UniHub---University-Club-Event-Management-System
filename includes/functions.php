<?php
// ============================================================
// includes/functions.php — COMPLETE & BUG-FIXED VERSION
// ============================================================
//fixed some issues
function sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash_' . $type] = $message;
}

function getFlash(string $type): ?string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-') . '-' . substr(uniqid(), -5);
}

function timeAgo(?string $datetime): string {
    if (!$datetime) return 'N/A';
    try {
        $now  = new DateTime();
        $past = new DateTime($datetime);
        $diff = $now->diff($past);
        if ($diff->y > 0) return $diff->y . 'y ago';
        if ($diff->m > 0) return $diff->m . 'mo ago';
        if ($diff->d > 0) return $diff->d . 'd ago';
        if ($diff->h > 0) return $diff->h . 'h ago';
        if ($diff->i > 0) return $diff->i . 'm ago';
        return 'Just now';
    } catch (\Exception $e) {
        return $datetime;
    }
}

function formatDate(?string $date, string $format = 'M d, Y'): string {
    if (!$date) return 'N/A';
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : 'N/A';
}

function formatDateTime(?string $date): string {
    if (!$date) return 'N/A';
    $ts = strtotime($date);
    return $ts ? date('M d, Y · h:i A', $ts) : 'N/A';
}

function isUpcoming(string $date): bool {
    return strtotime($date) > time();
}

function profilePicUrl(?string $path): string {
    if ($path) {
        $full = UPLOAD_DIR . 'profiles/' . basename($path);
        if (file_exists($full)) return UPLOAD_URL . 'profiles/' . basename($path);
    }
    return BASE_URL . '/assets/images/default-avatar.svg';
}

function clubLogoUrl(?string $path): string {
    if ($path) {
        $full = UPLOAD_DIR . 'clubs/logos/' . basename($path);
        if (file_exists($full)) return UPLOAD_URL . 'clubs/logos/' . basename($path);
    }
    return BASE_URL . '/assets/images/default-club.svg';
}

function clubBannerUrl(?string $path): string {
    if ($path) {
        $full = UPLOAD_DIR . 'clubs/banners/' . basename($path);
        if (file_exists($full)) return UPLOAD_URL . 'clubs/banners/' . basename($path);
    }
    return BASE_URL . '/assets/images/default-banner.svg';
}

function eventBannerUrl(?string $path): string {
    if ($path) {
        $full = UPLOAD_DIR . 'events/' . basename($path);
        if (file_exists($full)) return UPLOAD_URL . 'events/' . basename($path);
    }
    return BASE_URL . '/assets/images/default-event.svg';
}

function uploadImage(array $file, string $folder): ?string {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    // Use finfo for reliable MIME detection
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null;

    $ext      = match($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        default      => 'jpg',
    };
    $filename = uniqid('img_', true) . '.' . $ext;
    $dir      = UPLOAD_DIR . $folder;
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $dest = $dir . '/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $filename;
    }
    return null;
}

function paginate(int $total, int $perPage, int $current): array {
    $totalPages = max(1, (int)ceil($total / $perPage));
    $current    = max(1, min($current, $totalPages));
    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $current,
        'total_pages' => $totalPages,
        'offset'      => ($current - 1) * $perPage,
        'has_prev'    => $current > 1,
        'has_next'    => $current < $totalPages,
    ];
}

function sendNotification(PDO $pdo, int $userId, string $type, string $title, string $message, ?int $relatedId = null, ?string $relatedType = null): void {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_id, related_type) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$userId, $type, $title, $message, $relatedId, $relatedType]);
    } catch (\PDOException $e) {
        // Silently fail — notification shouldn't break main flow
        error_log('Notification error: ' . $e->getMessage());
    }
}

function getUnreadNotificationCount(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function logActivity(PDO $pdo, ?int $userId, string $action, string $description = ''): void {
    try {
        $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?,?,?,?)");
        $stmt->execute([$userId, $action, $description, $ip]);
    } catch (\PDOException $e) {
        error_log('Activity log error: ' . $e->getMessage());
    }
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function csrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    if (session_status() === PHP_SESSION_NONE) return false;
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getSetting(PDO $pdo, string $key, string $default = ''): string {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? (string)$val : $default;
}

function getStatusBadge(?string $status): string {
    if (!$status) return '<span class="badge badge-secondary">N/A</span>';
    $badges = [
        'active'       => 'badge-success',
        'student'      => 'badge-info',
        'club_admin'   => 'badge-primary',
        'super_admin'  => 'badge-danger',
        'pending'      => 'badge-warning',
        'approved'     => 'badge-success',
        'rejected'     => 'badge-danger',
        'suspended'    => 'badge-danger',
        'blocked'      => 'badge-danger',
        'completed'    => 'badge-secondary',
        'ongoing'      => 'badge-success',
        'confirmed'    => 'badge-success',
        'cancelled'    => 'badge-danger',
        'president'    => 'badge-warning',
        'vice_president' => 'badge-info',
        'member'       => 'badge-secondary',
        'attended'     => 'badge-success',
    ];
    $class = $badges[$status] ?? 'badge-secondary';
    return '<span class="badge ' . $class . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
}

// Helper used on create-club, profile pages
function previewImageScript(): string {
    return '<script>
function previewImage(input, previewId) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) { if(window.showToast) showToast("Image must be under 5MB.", "warning"); return; }
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById(previewId);
        if (img) { img.src = e.target.result; img.style.display = "block"; }
    };
    reader.readAsDataURL(file);
}
</script>';
}
