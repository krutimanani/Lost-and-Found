<?php
/**
 * Citizen Dashboard
 * Overview of user's reports and statistics
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('citizen.dashboard.page_title');
$user_id = $_SESSION['user_id'];

// Get user statistics
try {
    $stats_query = $pdo->prepare("
        SELECT
            (SELECT COUNT(*) FROM lost_items WHERE user_id = ?) as my_lost,
            (SELECT COUNT(*) FROM found_items WHERE user_id = ?) as my_found,
            (SELECT COUNT(*) FROM matched_reports mr
             LEFT JOIN lost_items l ON mr.lost_item_id = l.lost_item_id
             LEFT JOIN found_items f ON mr.found_item_id = f.found_item_id
             WHERE (l.user_id = ? OR f.user_id = ?) AND mr.status = 'Matched') as my_matched,
            (SELECT COUNT(*) FROM notifications WHERE user_id = ? AND user_type = 'Citizen' AND is_read = 0) as unread_notifications
    ");
    $stats_query->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
    $stats = $stats_query->fetch();
} catch (PDOException $e) {
    $stats = ['my_lost' => 0, 'my_found' => 0, 'my_matched' => 0, 'unread_notifications' => 0];
}

// Get recent lost items
try {
    $recent_lost_query = $pdo->prepare("
        SELECT l.*, c.category_name, loc.location_name
        FROM lost_items l
        LEFT JOIN categories c ON l.category_id = c.category_id
        LEFT JOIN locations loc ON l.location_id = loc.location_id
        WHERE l.user_id = ?
        ORDER BY l.created_at DESC
        LIMIT 3
    ");
    $recent_lost_query->execute([$user_id]);
    $recent_lost = $recent_lost_query->fetchAll();
} catch (PDOException $e) {
    $recent_lost = [];
}

// Get recent found items
try {
    $recent_found_query = $pdo->prepare("
        SELECT f.*, c.category_name, loc.location_name
        FROM found_items f
        LEFT JOIN categories c ON f.category_id = c.category_id
        LEFT JOIN locations loc ON f.location_id = loc.location_id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
        LIMIT 3
    ");
    $recent_found_query->execute([$user_id]);
    $recent_found = $recent_found_query->fetchAll();
} catch (PDOException $e) {
    $recent_found = [];
}

// Get recent notifications
try {
    $notifications_query = $pdo->prepare("
        SELECT * FROM notifications
        WHERE user_id = ? AND user_type = 'Citizen'
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $notifications_query->execute([$user_id]);
    $notifications = $notifications_query->fetchAll();
} catch (PDOException $e) {
    $notifications = [];
}

include '../includes/header.php';
?>

<!-- Welcome Banner -->
<div class="bg-primary text-white py-4 mb-4 rounded">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2 class="fw-bold mb-2">
                <i class="fas fa-home"></i> <?php echo t('citizen.dashboard.welcome', ['name' => htmlspecialchars(getUserName())]); ?>
            </h2>
            <p class="mb-0 opacity-75">
                <?php echo t('citizen.dashboard.welcome_subtitle'); ?>
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="<?php echo SITE_URL; ?>citizen/report-lost.php" class="btn btn-light btn-sm me-2">
                <i class="fas fa-exclamation-triangle"></i> <?php echo t('citizen.dashboard.btn_report_lost'); ?>
            </a>
            <a href="<?php echo SITE_URL; ?>citizen/report-found.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-check-circle"></i> <?php echo t('citizen.dashboard.btn_report_found'); ?>
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="icon-box bg-danger-light mx-auto">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo number_format($stats['my_lost']); ?></h3>
                <p class="text-muted mb-0"><?php echo t('citizen.dashboard.stat_my_lost'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="icon-box bg-success-light mx-auto">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo number_format($stats['my_found']); ?></h3>
                <p class="text-muted mb-0"><?php echo t('citizen.dashboard.stat_my_found'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="icon-box bg-info-light mx-auto">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo number_format($stats['my_matched']); ?></h3>
                <p class="text-muted mb-0"><?php echo t('citizen.dashboard.stat_matched'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="icon-box bg-warning-light mx-auto">
                    <i class="fas fa-bell"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo number_format($stats['unread_notifications']); ?></h3>
                <p class="text-muted mb-0"><?php echo t('citizen.dashboard.stat_notifications'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <a href="<?php echo SITE_URL; ?>citizen/report-lost.php" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-card">
                <div class="card-body text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5 class="fw-bold mb-2"><?php echo t('citizen.dashboard.quick_report_lost_title'); ?></h5>
                    <p class="text-muted small mb-0"><?php echo t('citizen.dashboard.quick_report_lost_desc'); ?></p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?php echo SITE_URL; ?>citizen/report-found.php" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-card">
                <div class="card-body text-center py-4">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5 class="fw-bold mb-2"><?php echo t('citizen.dashboard.quick_report_found_title'); ?></h5>
                    <p class="text-muted small mb-0"><?php echo t('citizen.dashboard.quick_report_found_desc'); ?></p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?php echo SITE_URL; ?>citizen/search.php" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-card">
                <div class="card-body text-center py-4">
                    <i class="fas fa-search fa-3x text-primary mb-3"></i>
                    <h5 class="fw-bold mb-2"><?php echo t('citizen.dashboard.quick_search_title'); ?></h5>
                    <p class="text-muted small mb-0"><?php echo t('citizen.dashboard.quick_search_desc'); ?></p>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Lost Items -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo t('citizen.dashboard.recent_lost_title'); ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (count($recent_lost) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_lost as $item): ?>
                            <a href="<?php echo SITE_URL; ?>citizen/view-report.php?type=lost&id=<?php echo $item['lost_item_id']; ?>"
                               class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($item['item_name']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?> •
                                            <i class="fas fa-calendar"></i> <?php echo timeAgo($item['lost_date']); ?>
                                        </small>
                                    </div>
                                    <span class="badge badge-<?php echo strtolower($item['status']); ?>">
                                        <?php echo $item['status']; ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="<?php echo SITE_URL; ?>citizen/my-reports.php?tab=lost" class="btn btn-outline-danger btn-sm">
                            <?php echo t('citizen.dashboard.view_all_lost'); ?> <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted"><?php echo t('citizen.dashboard.no_lost_items'); ?></p>
                        <a href="<?php echo SITE_URL; ?>citizen/report-lost.php" class="btn btn-danger btn-sm">
                            <i class="fas fa-plus"></i> <?php echo t('citizen.dashboard.btn_report_lost_item'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Found Items -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-check-circle"></i> <?php echo t('citizen.dashboard.recent_found_title'); ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (count($recent_found) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_found as $item): ?>
                            <a href="<?php echo SITE_URL; ?>citizen/view-report.php?type=found&id=<?php echo $item['found_item_id']; ?>"
                               class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($item['item_name']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?> •
                                            <i class="fas fa-calendar"></i> <?php echo timeAgo($item['found_date']); ?>
                                        </small>
                                    </div>
                                    <span class="badge badge-<?php echo strtolower($item['status']); ?>">
                                        <?php echo $item['status']; ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="<?php echo SITE_URL; ?>citizen/my-reports.php?tab=found" class="btn btn-outline-success btn-sm">
                            <?php echo t('citizen.dashboard.view_all_found'); ?> <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted"><?php echo t('citizen.dashboard.no_found_items'); ?></p>
                        <a href="<?php echo SITE_URL; ?>citizen/report-found.php" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> <?php echo t('citizen.dashboard.btn_report_found_item'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Notifications -->
<?php if (count($notifications) > 0): ?>
<div class="row g-4 mt-2">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-bell"></i> <?php echo t('citizen.dashboard.notifications_title'); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="list-group-item <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-bold">
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="badge bg-primary badge-sm"><?php echo t('citizen.dashboard.notification_new'); ?></span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                    </h6>
                                    <p class="mb-1 text-muted small"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> <?php echo timeAgo($notification['created_at']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="<?php echo SITE_URL; ?>citizen/notifications.php" class="btn btn-outline-primary btn-sm">
                        <?php echo t('citizen.dashboard.view_all_notifications'); ?> <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>