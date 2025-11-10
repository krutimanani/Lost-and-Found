<?php
/**
 * Logout Page
 * Logs out the user and destroys session
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Set success message before destroying session
$_SESSION['success'] = t('auth.logout.success');

// Log activity before destroying session
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    logActivity($pdo, $_SESSION['user_id'], $_SESSION['user_type'], t('auth.logout.activity_type'), t('auth.logout.activity_message'));
}

// Destroy session
session_destroy();

// Redirect to home page
header('Location: ' . SITE_URL);
exit();
?>