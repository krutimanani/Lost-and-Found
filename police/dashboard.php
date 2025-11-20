<?php
/**
 * Police Dashboard
 * Overview for police officers
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if police is logged in
if (!isPoliceLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('police.dashboard.page_title');
$police_id = $_SESSION['user_id'];

// Get statistics
try {
    $stats_query = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM lost_items WHERE status = 'Approved') as total_lost,
            (SELECT COUNT(*) FROM found_items WHERE status = 'Approved') as total_found,
            (SELECT COUNT(*) FROM matched_reports WHERE status = 'Matched') as total_matched,
            (SELECT COUNT(*) FROM matched_reports WHERE status = 'Resolved') as total_resolved,
            (SELECT COUNT(*) FROM item_claims WHERE status = 'Pending') as pending_claims
    ");
    $stats = $stats_query->fetch();
} catch (PDOException $e) {
    $stats = ['total_lost' => 0, 'total_found' => 0, 'total_matched' => 0, 'total_resolved' => 0, 'pending_claims' => 0];
}

// Get recent lost items
try {
    $recent_lost = $pdo->query("
        SELECT l.*, c.category_name, loc.location_name,
               COALESCE(p.name, u.name) as reporter_name,
               COALESCE(p.phone, u.phone) as reporter_phone,
               CASE WHEN l.police_id IS NOT NULL THEN 'police' ELSE 'citizen' END as reporter_type,
               p.badge_number, ps.station_name
        FROM lost_items l
        LEFT JOIN categories c ON l.category_id = c.category_id
        LEFT JOIN locations loc ON l.location_id = loc.location_id
        LEFT JOIN users u ON l.user_id = u.user_id
        LEFT JOIN police p ON l.police_id = p.police_id
        LEFT JOIN police_stations ps ON p.station_id = ps.station_id
        WHERE l.status = 'Approved'
        ORDER BY l.created_at DESC
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    $recent_lost = [];
}

// Get recent found items
try {
    $recent_found = $pdo->query("
        SELECT f.*, c.category_name, loc.location_name,
               COALESCE(p.name, u.name) as reporter_name,
               COALESCE(p.phone, u.phone) as reporter_phone,
               CASE WHEN f.police_id IS NOT NULL THEN 'police' ELSE 'citizen' END as reporter_type,
               p.badge_number, ps.station_name
        FROM found_items f
        LEFT JOIN categories c ON f.category_id = c.category_id
        LEFT JOIN locations loc ON f.location_id = loc.location_id
        LEFT JOIN users u ON f.user_id = u.user_id
        LEFT JOIN police p ON f.police_id = p.police_id
        LEFT JOIN police_stations ps ON p.station_id = ps.station_id
        WHERE f.status = 'Approved'
        ORDER BY f.created_at DESC
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    $recent_found = [];
}

// Get recent matches
try {
    $recent_matches = $pdo->query("
        SELECT mr.*, l.item_name as lost_item, f.item_name as found_item,
               p.name as police_name
        FROM matched_reports mr
        LEFT JOIN lost_items l ON mr.lost_item_id = l.lost_item_id
        LEFT JOIN found_items f ON mr.found_item_id = f.found_item_id
        LEFT JOIN police p ON mr.matched_by_police = p.police_id
        ORDER BY mr.matched_at DESC
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    $recent_matches = [];
}

include '../includes/header.php';
?>

<!-- Welcome Banner -->
<div class="bg-primary text-white py-4 mb-4 rounded">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2 class="fw-bold mb-2">
                <i class="fas fa-shield-alt"></i> <?php echo t('police.dashboard.welcome', ['name' => htmlspecialchars(getUserName())]); ?>
            </h2>
            <p class="mb-0 opacity-75">
                <?php echo t('police.dashboard.subtitle'); ?>
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="<?php echo SITE_URL; ?>police/view-reports.php" class="btn btn-light btn-sm me-2">
                <i class="fas fa-list"></i> <?php echo t('police.dashboard.view_reports'); ?>
            </a>
            <a href="<?php echo SITE_URL; ?>police/upload-custody-item.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-upload"></i> <?php echo t('police.dashboard.upload_item'); ?>
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
                <h3 class="fw-bold mb-1"><?php echo number_format($stats['total_lost']); ?></h3>
                <p class="text-muted mb-0"><?php echo t('police.dashboard.lost_items'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="icon-box bg-success-light mx-auto">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo number_format($stats['total_found']); ?></h3>
                <p class="text-muted mb-0"><?php echo t('police.dashboard.found_items'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="icon-box bg-info-light mx-auto">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo number_format($stats['total_matched']); ?></h3>
                <p class="text-muted mb-0"><?php echo t('police.dashboard.matched_items'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="icon-box bg-primary-light mx-auto">
                    <i class="fas fa-check-double"></i>
                </div>
                <h3 class="fw-bold mb-1"><?php echo number_format($stats['total_resolved']); ?></h3>
                <p class="text-muted mb-0"><?php echo t('police.dashboard.resolved_cases'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Pending Claims Alert -->
<?php if ($stats['pending_claims'] > 0): ?>
    <div class="alert alert-warning border-0 shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-clipboard-check fa-3x text-warning"></i>
            </div>
            <div class="flex-grow-1 ms-3">
                <h5 class="alert-heading mb-1">
                    <i class="fas fa-exclamation-circle"></i> <?php echo t('police.dashboard.pending_claims_title'); ?>
                </h5>
                <p class="mb-2">
                    <?php echo t('police.dashboard.pending_claims_message', ['count' => $stats['pending_claims']]); ?>
                </p>
                <a href="<?php echo SITE_URL; ?>police/review-claims.php" class="btn btn-warning btn-sm">
                    <i class="fas fa-clipboard-list"></i> <?php echo t('police.dashboard.review_claims_now'); ?>
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <a href="<?php echo SITE_URL; ?>police/view-reports.php?type=lost" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-card">
                <div class="card-body text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5 class="fw-bold mb-2"><?php echo t('police.dashboard.view_lost_items'); ?></h5>
                    <p class="text-muted small mb-0"><?php echo t('police.dashboard.view_lost_items_desc'); ?></p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?php echo SITE_URL; ?>police/view-reports.php?type=found" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-card">
                <div class="card-body text-center py-4">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5 class="fw-bold mb-2"><?php echo t('police.dashboard.view_found_items'); ?></h5>
                    <p class="text-muted small mb-0"><?php echo t('police.dashboard.view_found_items_desc'); ?></p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?php echo SITE_URL; ?>police/upload-custody-item.php" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-card">
                <div class="card-body text-center py-4">
                    <i class="fas fa-upload fa-3x text-primary mb-3"></i>
                    <h5 class="fw-bold mb-2"><?php echo t('police.dashboard.upload_custody_item'); ?></h5>
                    <p class="text-muted small mb-0"><?php echo t('police.dashboard.upload_custody_item_desc'); ?></p>
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
                    <i class="fas fa-exclamation-triangle"></i> <?php echo t('police.dashboard.recent_lost_items'); ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (count($recent_lost) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_lost as $item): ?>
                            <a href="<?php echo SITE_URL; ?>police/report-details.php?type=lost&id=<?php echo $item['lost_item_id']; ?>"
                               class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($item['item_name']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($item['reporter_name']); ?><br>
                                            <i class="fas fa-map-marker-alt"></i> <?php echo getLocalizedLocationName($item['location_name'] ?? 'N/A'); ?>
                                        </small>
                                    </div>
                                    <small class="text-muted"><?php echo timeAgo($item['created_at']); ?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="<?php echo SITE_URL; ?>police/view-reports.php?type=lost" class="btn btn-outline-danger btn-sm">
                            <?php echo t('police.dashboard.view_all'); ?> <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0"><?php echo t('police.dashboard.no_lost_items'); ?></p>
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
                    <i class="fas fa-check-circle"></i> <?php echo t('police.dashboard.recent_found_items'); ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (count($recent_found) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_found as $item): ?>
                            <a href="<?php echo SITE_URL; ?>police/report-details.php?type=found&id=<?php echo $item['found_item_id']; ?>"
                               class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($item['item_name']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($item['reporter_name']); ?><br>
                                            <i class="fas fa-map-marker-alt"></i> <?php echo getLocalizedLocationName($item['location_name'] ?? 'N/A'); ?>
                                        </small>
                                    </div>
                                    <small class="text-muted"><?php echo timeAgo($item['created_at']); ?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="<?php echo SITE_URL; ?>police/view-reports.php?type=found" class="btn btn-outline-success btn-sm">
                            <?php echo t('police.dashboard.view_all'); ?> <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0"><?php echo t('police.dashboard.no_found_items'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Matches -->
<?php if (count($recent_matches) > 0): ?>
<div class="row g-4 mt-2">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-handshake"></i> <?php echo t('police.dashboard.recent_matches'); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><?php echo t('police.dashboard.lost_item'); ?></th>
                                <th><?php echo t('police.dashboard.found_item'); ?></th>
                                <th><?php echo t('police.dashboard.matched_by'); ?></th>
                                <th><?php echo t('police.dashboard.status'); ?></th>
                                <th><?php echo t('police.dashboard.date'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_matches as $match): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($match['lost_item']); ?></td>
                                    <td><?php echo htmlspecialchars($match['found_item']); ?></td>
                                    <td><?php echo htmlspecialchars($match['police_name']); ?></td>
                                    <td><span class="badge badge-<?php echo strtolower($match['status']); ?>"><?php echo $match['status']; ?></span></td>
                                    <td><?php echo timeAgo($match['matched_at']); ?></td>
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