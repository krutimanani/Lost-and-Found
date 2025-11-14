<?php
/**
 * My Reports
 * View all lost and found reports by the user
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('citizen.my_reports.page_title');
$user_id = $_SESSION['user_id'];
$active_tab = $_GET['tab'] ?? 'lost';

// Get lost items
try {
    $lost_query = $pdo->prepare("
        SELECT l.*, c.category_name, loc.location_name
        FROM lost_items l
        LEFT JOIN categories c ON l.category_id = c.category_id
        LEFT JOIN locations loc ON l.location_id = loc.location_id
        WHERE l.user_id = ?
        ORDER BY l.created_at DESC
    ");
    $lost_query->execute([$user_id]);
    $lost_items = $lost_query->fetchAll();
} catch (PDOException $e) {
    $lost_items = [];
}

// Get found items
try {
    $found_query = $pdo->prepare("
        SELECT f.*, c.category_name, loc.location_name
        FROM found_items f
        LEFT JOIN categories c ON f.category_id = c.category_id
        LEFT JOIN locations loc ON f.location_id = loc.location_id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
    ");
    $found_query->execute([$user_id]);
    $found_items = $found_query->fetchAll();
} catch (PDOException $e) {
    $found_items = [];
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">
        <i class="fas fa-folder-open"></i> <?php echo t('citizen.my_reports.heading'); ?>
    </h2>
    <div>
        <a href="<?php echo SITE_URL; ?>citizen/report-lost.php" class="btn btn-danger btn-sm me-2">
            <i class="fas fa-plus"></i> <?php echo t('citizen.my_reports.btn_report_lost'); ?>
        </a>
        <a href="<?php echo SITE_URL; ?>citizen/report-found.php" class="btn btn-success btn-sm">
            <i class="fas fa-plus"></i> <?php echo t('citizen.my_reports.btn_report_found'); ?>
        </a>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link <?php echo $active_tab === 'lost' ? 'active' : ''; ?>"
           href="?tab=lost">
            <i class="fas fa-exclamation-triangle"></i> <?php echo t('citizen.my_reports.tab_lost', ['count' => count($lost_items)]); ?>
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?php echo $active_tab === 'found' ? 'active' : ''; ?>"
           href="?tab=found">
            <i class="fas fa-check-circle"></i> <?php echo t('citizen.my_reports.tab_found', ['count' => count($found_items)]); ?>
        </a>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content">
    <!-- Lost Items Tab -->
    <?php if ($active_tab === 'lost'): ?>
        <?php if (count($lost_items) > 0): ?>
            <div class="row g-4">
                <?php foreach ($lost_items as $item): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card item-card h-100">
                            <?php if ($item['image_path']): ?>
                                <img src="<?php echo SITE_URL; ?>uploads/lost/<?php echo htmlspecialchars($item['image_path']); ?>"
                                     class="card-img-top" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center"
                                     style="height: 200px;">
                                    <i class="fas fa-image fa-3x text-white opacity-50"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                                    <span class="badge badge-<?php echo strtolower($item['status']); ?>">
                                        <?php echo $item['status']; ?>
                                    </span>
                                </div>
                                <p class="card-text text-muted small mb-2">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?><br>
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?><br>
                                    <i class="fas fa-calendar"></i> <?php echo formatDate($item['lost_date']); ?>
                                </p>
                                <p class="card-text text-truncate-2"><?php echo htmlspecialchars($item['description']); ?></p>
                                <a href="<?php echo SITE_URL; ?>citizen/view-report.php?type=lost&id=<?php echo $item['lost_item_id']; ?>"
                                   class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-eye"></i> <?php echo t('citizen.my_reports.btn_view_details'); ?>
                                </a>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> <?php echo t('citizen.my_reports.reported_ago', ['time' => timeAgo($item['created_at'])]); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted"><?php echo t('citizen.my_reports.no_lost_title'); ?></h5>
                    <p class="text-muted"><?php echo t('citizen.my_reports.no_lost_message'); ?></p>
                    <a href="<?php echo SITE_URL; ?>citizen/report-lost.php" class="btn btn-danger">
                        <i class="fas fa-plus"></i> <?php echo t('citizen.my_reports.btn_report_lost_item'); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Found Items Tab -->
    <?php if ($active_tab === 'found'): ?>
        <?php if (count($found_items) > 0): ?>
            <div class="row g-4">
                <?php foreach ($found_items as $item): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card item-card h-100">
                            <?php if ($item['image_path']): ?>
                                <img src="<?php echo SITE_URL; ?>uploads/found/<?php echo htmlspecialchars($item['image_path']); ?>"
                                     class="card-img-top" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center"
                                     style="height: 200px;">
                                    <i class="fas fa-image fa-3x text-white opacity-50"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                                    <span class="badge badge-<?php echo strtolower($item['status']); ?>">
                                        <?php echo $item['status']; ?>
                                    </span>
                                </div>
                                <p class="card-text text-muted small mb-2">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?><br>
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?><br>
                                    <i class="fas fa-calendar"></i> <?php echo formatDate($item['found_date']); ?>
                                </p>
                                <p class="card-text text-truncate-2"><?php echo htmlspecialchars($item['description']); ?></p>
                                <a href="<?php echo SITE_URL; ?>citizen/view-report.php?type=found&id=<?php echo $item['found_item_id']; ?>"
                                   class="btn btn-success btn-sm w-100">
                                    <i class="fas fa-eye"></i> <?php echo t('citizen.my_reports.btn_view_details'); ?>
                                </a>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> <?php echo t('citizen.my_reports.reported_ago', ['time' => timeAgo($item['created_at'])]); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted"><?php echo t('citizen.my_reports.no_found_title'); ?></h5>
                    <p class="text-muted"><?php echo t('citizen.my_reports.no_found_message'); ?></p>
                    <a href="<?php echo SITE_URL; ?>citizen/report-found.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> <?php echo t('citizen.my_reports.btn_report_found_item'); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>