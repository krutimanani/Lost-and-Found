<?php
/**
 * Main Configuration File
 * Rajkot E Milaap - Lost & Found Portal
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site Configuration
define('SITE_NAME', 'Rajkot E Milaap');
define('SITE_URL', 'http://localhost/lostfound/');
define('SITE_EMAIL', 'contact@rajkotemilaap.com');

// File Upload Configuration
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Pagination
define('ITEMS_PER_PAGE', 12);

// Date/Time
date_default_timezone_set('Asia/Kolkata');

// Include database connection
require_once __DIR__ . '/database.php';

// Include and initialize i18n (Internationalization) system
require_once __DIR__ . '/i18n/i18n.php';
initI18n();

// Load user's saved language preference if logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    loadUserLanguagePreference();
}
?>