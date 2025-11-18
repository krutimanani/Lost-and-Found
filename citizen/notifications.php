<?php
/**
 * Notifications Page
 * View all notifications
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('citizen.notifications.page_title');
$user_id = $_SESSION['user_id'];

// Mark notification as read
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $notification_id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
        $_SESSION['success'] = t('citizen.notifications.success_marked_read');
        redirect(SITE_URL . 'citizen/notifications.php');
    } catch (PDOException $e) {
        $_SESSION['error'] = t('citizen.notifications.error_update_failed');
    }
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND user_type = 'Citizen'");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = t('citizen.notifications.success_all_marked_read');
        redirect(SITE_URL . 'citizen/notifications.php');
    } catch (PDOException $e) {
        $_SESSION['error'] = t('citizen.notifications.error_update_failed');
    }
}

// Get all notifications
try {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications
        WHERE user_id = ? AND user_type = 'Citizen'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();

    $unread_count = array_reduce($notifications, function($count, $notification) {
        return $count + ($notification['is_read'] ? 0 : 1);
    }, 0);
} catch (PDOException $e) {
    $notifications = [];
    $unread_count = 0;
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">
        <i class="fas fa-bell"></i> <?php echo t('citizen.notifications.heading'); ?>
        <?php if ($unread_count > 0): ?>
            <span class="badge bg-danger"><?php echo t('citizen.notifications.new_count', ['count' => $unread_count]); ?></span>
        <?php endif; ?>
    </h2>
    <?php if ($unread_count > 0): ?>
        <a href="?mark_all_read=1" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-check-double"></i> <?php echo t('citizen.notifications.btn_mark_all_read'); ?>
        </a>
    <?php endif; ?>
</div>

<?php if (count($notifications) > 0): ?>
    <div class="card border-0 shadow-sm">
        <div class="list-group list-group-flush">
            <?php foreach ($notifications as $notification): ?>
                <div class="list-group-item <?php echo $notification['is_read'] ? '' : 'bg-light border-start border-primary border-4'; ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1 me-3">
                            <div class="d-flex align-items-center mb-2">
                                <?php if (!$notification['is_read']): ?>
                                    <span class="badge bg-primary me-2"><?php echo t('citizen.notifications.badge_new'); ?></span>
                                <?php endif; ?>
                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($notification['title']); ?></h6>
                            </div>
                            <p class="mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <div class="d-flex align-items-center text-muted small">
                                <span class="me-3">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($notification['notification_type']); ?>
                                </span>
                                <span>
                                    <i class="fas fa-clock"></i> <?php echo timeAgo($notification['created_at']); ?>
                                </span>
                            </div>
                        </div>
                        <div>
                            <?php if (!$notification['is_read']): ?>
                                <a href="?mark_read=1&id=<?php echo $notification['notification_id']; ?>"
                                   class="btn btn-sm btn-outline-primary"
                                   title="<?php echo t('citizen.notifications.btn_mark_read'); ?>">
                                    <i class="fas fa-check"></i>
                                </a>
                            <?php else: ?>
                                <i class="fas fa-check-circle text-success" title="<?php echo t('citizen.notifications.read_status'); ?>"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
            <h5 class="text-muted"><?php echo t('citizen.notifications.no_notifications_title'); ?></h5>
            <p class="text-muted"><?php echo t('citizen.notifications.no_notifications_message'); ?></p>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>