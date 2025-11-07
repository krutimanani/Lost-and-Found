<?php
/**
 * Utility Functions
 * Rajkot E Milaap - Lost & Found Portal
 */

// Sanitize input data
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone number (10 digits)
function validatePhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Upload file
function uploadFile($file, $destination) {
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => t('validation.file_no_upload')];
    }

    $filename = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    // Validate file extension
    if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => t('validation.file_invalid')];
    }

    // Validate file size
    if ($fileSize > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => t('validation.file_too_large')];
    }

    // Generate unique filename
    $newFilename = uniqid('', true) . '.' . $fileExt;
    $filePath = $destination . $newFilename;

    // Move uploaded file
    if (move_uploaded_file($fileTmpName, $filePath)) {
        return ['success' => true, 'filename' => $newFilename];
    }

    return ['success' => false, 'message' => t('validation.file_upload_failed')];
}

// Format date
function formatDate($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

// Time ago function
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return t('common.time.just_now');
    if ($diff < 3600) return t('common.time.minutes_ago', ['count' => floor($diff / 60)]);
    if ($diff < 86400) return t('common.time.hours_ago', ['count' => floor($diff / 3600)]);
    if ($diff < 604800) return t('common.time.days_ago', ['count' => floor($diff / 86400)]);

    return formatDate($datetime);
}

// Redirect function
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'citizen';
}

// Check if police is logged in
function isPoliceLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'police';
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Get current user role
function getUserRole() {
    if (isAdminLoggedIn()) return 'admin';
    if (isPoliceLoggedIn()) return 'police';
    if (isLoggedIn()) return 'citizen';
    return null;
}

// Get user name
function getUserName() {
    return $_SESSION['user_name'] ?? 'User';
}

// Send notification
function sendNotification($pdo, $user_id, $user_type, $title, $message, $type = 'System') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, user_type, title, message, notification_type, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$user_id, $user_type, $title, $message, $type]);
    } catch (PDOException $e) {
        return false;
    }
}

// Send email notification
function sendEmail($to, $subject, $message) {
    $headers = "From: " . SITE_EMAIL . "\r\n";
    $headers .= "Reply-To: " . SITE_EMAIL . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    return mail($to, $subject, $message, $headers);
}

// Get unread notifications count
function getUnreadNotificationsCount($pdo) {
    if (!isset($_SESSION['user_id'])) return 0;

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM notifications
            WHERE user_id = ? AND user_type = ? AND is_read = 0
        ");
        $stmt->execute([$_SESSION['user_id'], ucfirst($_SESSION['user_type'])]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// Log activity
function logActivity($pdo, $user_id, $user_type, $action, $description = '') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, user_type, action, description, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return $stmt->execute([$user_id, ucfirst($user_type), $action, $description, $ip]);
    } catch (PDOException $e) {
        return false;
    }
}

// Get setting value
function getSetting($pdo, $key, $default = '') {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

// Show success message
function showSuccess($message) {
    return '<div class="alert alert-success alert-dismissible fade show" role="alert">'
         . htmlspecialchars($message)
         . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

// Show error message
function showError($message) {
    return '<div class="alert alert-danger alert-dismissible fade show" role="alert">'
         . htmlspecialchars($message)
         . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

// Show info message
function showInfo($message) {
    return '<div class="alert alert-info alert-dismissible fade show" role="alert">'
         . htmlspecialchars($message)
         . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}
?>