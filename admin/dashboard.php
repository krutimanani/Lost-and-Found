<?php
/**
 * Admin Dashboard
 * Overview and statistics for admin
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('admin.dashboard.title');

// Get comprehensive statistics
try {
    $stats = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM users WHERE status = 'Active') as total_users,
            (SELECT COUNT(*) FROM police WHERE status = 'Active') as total_police,
            (SELECT COUNT(*) FROM lost_items) as total_lost_items,
            (SELECT COUNT(*) FROM lost_items WHERE status = 'Pending') as pending_lost,
            (SELECT COUNT(*) FROM found_items) as total_found_items,
            (SELECT COUNT(*) FROM found_items WHERE status = 'Pending') as pending_found,
            (SELECT COUNT(*) FROM matched_reports WHERE status = 'Matched') as total_matched,
            (SELECT COUNT(*) FROM matched_reports WHERE status = 'Resolved') as total_resolved
    ")->fetch();
} catch (PDOException $e) {
    $stats = [
        'total_users' => 0, 'total_police' => 0, 'total_lost_items' => 0, 'pending_lost' => 0,
        'total_found_items' => 0, 'pending_found' => 0, 'total_matched' => 0, 'total_resolved' => 0
    ];
}

// Get recent pending lost items
try {
    $pending_lost = $pdo->query("
        SELECT l.*, c.category_name,
               COALESCE(p.name, u.name) as reporter_name,
               CASE WHEN l.police_id IS NOT NULL THEN 'police' ELSE 'citizen' END as reporter_type
        FROM lost_items l
        LEFT JOIN categories c ON l.category_id = c.category_id
        LEFT JOIN users u ON l.user_id = u.user_id
        LEFT JOIN police p ON l.police_id = p.police_id
        WHERE l.status = 'Pending'
        ORDER BY l.created_at DESC
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    $pending_lost = [];
}

// Get recent pending found items
try {
    $pending_found = $pdo->query("
        SELECT f.*, c.category_name,
               COALESCE(p.name, u.name) as reporter_name,
               CASE WHEN f.police_id IS NOT NULL THEN 'police' ELSE 'citizen' END as reporter_type
        FROM found_items f
        LEFT JOIN categories c ON f.category_id = c.category_id
        LEFT JOIN users u ON f.user_id = u.user_id
        LEFT JOIN police p ON f.police_id = p.police_id
        WHERE f.status = 'Pending' AND (f.user_id IS NOT NULL OR f.police_id IS NOT NULL)
        ORDER BY f.created_at DESC
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    $pending_found = [];
}

// Get recent activity
try {
    $recent_activity = $pdo->query("
        SELECT * FROM activity_log
        ORDER BY created_at DESC
        LIMIT 10
    ")->fetchAll();
} catch (PDOException $e) {
    $recent_activity = [];
}

include '../includes/header.php';
?>

<!-- Welcome Banner -->
<div class="bg-primary text-white py-4 mb-4 rounded">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2 class="fw-bold mb-2">
                <i class="fas fa-user-shield"></i> <?php echo t('admin.dashboard.title'); ?>
            </h2>
            <p class="mb-0 opacity-75">
                <?php echo t('admin.dashboard.welcome', ['name' => htmlspecialchars(getUserName())]); ?>
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="<?php echo SITE_URL; ?>admin/approve-lost.php" class="btn btn-light btn-sm me-2">
                <i class="fas fa-check"></i> <?php echo t('admin.dashboard.pending_reports'); ?>
            </a>
            <a href="<?php echo SITE_URL; ?>admin/users.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-users"></i> <?php echo t('admin.dashboard.manage_users'); ?>
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="icon-box bg-primary-light mx-auto">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo number_format($stats['total_users']); ?></h3>
                <p class="text-muted mb-0"><?php echo t('admin.dashboard.total_citizens'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="icon-box bg-info-light mx-auto">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo number_format($stats['total_police']); ?></h3>
                <p class="text-muted mb-0"><?php echo t('admin.dashboard.police_officers'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="icon-box bg-danger-light mx-auto">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo number_format($stats['total_lost_items']); ?></h3>
                <p class="text-muted mb-0"><?php echo t('admin.dashboard.lost_items'); ?></p>
                <?php if ($stats['pending_lost'] > 0): ?>
                    <span class="badge bg-warning text-dark"><?php echo t('admin.dashboard.pending_count', ['count' => $stats['pending_lost']]); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="icon-box bg-success-light mx-auto">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo number_format($stats['total_found_items']); ?></h3>
                <p class="text-muted mb-0"><?php echo t('admin.dashboard.found_items'); ?></p>
                <?php if ($stats['pending_found'] > 0): ?>
                    <span class="badge bg-warning text-dark"><?php echo t('admin.dashboard.pending_count', ['count' => $stats['pending_found']]); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Additional Stats -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="icon-box bg-info-light mx-auto mb-3">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3 class="fw-bold"><?php echo number_format($stats['total_matched']); ?></h3>
                <p class="text-muted mb-0"><?php echo t('admin.dashboard.items_matched'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="icon-box bg-success-light mx-auto mb-3">
                    <i class="fas fa-check-double"></i>
                </div>
                <h3 class="fw-bold"><?php echo number_format($stats['total_resolved']); ?></h3>
                <p class="text-muted mb-0"><?php echo t('admin.dashboard.cases_resolved'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <a href="<?php echo SITE_URL; ?>admin/approve-lost.php" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-card">
                <div class="card-body text-center py-4">
                    <i class="fas fa-check fa-3x text-warning mb-3"></i>
                    <h6 class="fw-bold mb-2"><?php echo t('admin.dashboard.approve_reports'); ?></h6>
                    <p class="text-muted small mb-0"><?php echo t('admin.dashboard.review_submissions'); ?></p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="<?php echo SITE_URL; ?>admin/users.php" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-card">
                <div class="card-body text-center py-4">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h6 class="fw-bold mb-2"><?php echo t('admin.dashboard.manage_users'); ?></h6>
                    <p class="text-muted small mb-0"><?php echo t('admin.dashboard.view_manage_citizens'); ?></p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="<?php echo SITE_URL; ?>admin/police-management.php" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-card">
                <div class="card-body text-center py-4">
                    <i class="fas fa-user-shield fa-3x text-info mb-3"></i>
                    <h6 class="fw-bold mb-2"><?php echo t('admin.dashboard.police_management'); ?></h6>
                    <p class="text-muted small mb-0"><?php echo t('admin.dashboard.manage_police_officers'); ?></p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="<?php echo SITE_URL; ?>admin/categories.php" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-card">
                <div class="card-body text-center py-4">
                    <i class="fas fa-tags fa-3x text-success mb-3"></i>
                    <h6 class="fw-bold mb-2"><?php echo t('admin.dashboard.categories'); ?></h6>
                    <p class="text-muted small mb-0"><?php echo t('admin.dashboard.manage_categories'); ?></p>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Pending Lost Items -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-clock"></i> <?php echo t('admin.dashboard.pending_lost_items'); ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (count($pending_lost) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($pending_lost as $item): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($item['item_name']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($item['reporter_name']); ?><br>
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?>
                                        </small>
                                    </div>
                                    <small class="text-muted"><?php echo timeAgo($item['created_at']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="<?php echo SITE_URL; ?>admin/approve-lost.php" class="btn btn-outline-warning btn-sm">
                            <?php echo t('admin.dashboard.view_all'); ?> <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted mb-0"><?php echo t('admin.dashboard.all_lost_reviewed'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pending Found Items -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-clock"></i> <?php echo t('admin.dashboard.pending_found_items'); ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (count($pending_found) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($pending_found as $item): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($item['item_name']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($item['reporter_name']); ?><br>
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?>
                                        </small>
                                    </div>
                                    <small class="text-muted"><?php echo timeAgo($item['created_at']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="<?php echo SITE_URL; ?>admin/approve-found.php" class="btn btn-outline-warning btn-sm">
                            <?php echo t('admin.dashboard.view_all'); ?> <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted mb-0"><?php echo t('admin.dashboard.all_found_reviewed'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<?php if (count($recent_activity) > 0): ?>
<div class="row g-4 mt-2">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-history"></i> <?php echo t('admin.dashboard.recent_activity'); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th><?php echo t('admin.dashboard.user_type'); ?></th>
                                <th><?php echo t('admin.dashboard.action'); ?></th>
                                <th><?php echo t('admin.dashboard.description'); ?></th>
                                <th><?php echo t('admin.dashboard.ip_address'); ?></th>
                                <th><?php echo t('admin.dashboard.time'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activity as $activity): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($activity['user_type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                    <td class="text-truncate" style="max-width: 300px;"><?php echo htmlspecialchars($activity['description']); ?></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($activity['ip_address']); ?></small></td>
                                    <td><small class="text-muted"><?php echo timeAgo($activity['created_at']); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>