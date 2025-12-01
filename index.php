<?php
/**
 * Landing Page - Rajkot E Milaap
 * Home page with hero section, search, and recent items
 */

require_once 'config/config.php';
require_once 'includes/functions.php';

$page_title = t('index.page_title');

// Get statistics
try {
    $stats_query = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM lost_items WHERE status = 'Approved') as total_lost,
            (SELECT COUNT(*) FROM found_items WHERE status = 'Approved') as total_found,
            (SELECT COUNT(*) FROM matched_reports WHERE status = 'Matched') as total_matched,
            (SELECT COUNT(*) FROM users WHERE status = 'Active') as total_users
    ");
    $stats = $stats_query->fetch();
} catch (PDOException $e) {
    $stats = ['total_lost' => 0, 'total_found' => 0, 'total_matched' => 0, 'total_users' => 0];
}

// Get recent lost items
try {
    $lost_items_query = $pdo->query("
        SELECT l.*, c.category_name, loc.location_name
        FROM lost_items l
        LEFT JOIN categories c ON l.category_id = c.category_id
        LEFT JOIN locations loc ON l.location_id = loc.location_id
        WHERE l.status = 'Approved'
        ORDER BY l.created_at DESC
        LIMIT 6
    ");
    $recent_lost = $lost_items_query->fetchAll();
} catch (PDOException $e) {
    $recent_lost = [];
}

// Get recent found items
try {
    $found_items_query = $pdo->query("
        SELECT f.*, c.category_name, loc.location_name
        FROM found_items f
        LEFT JOIN categories c ON f.category_id = c.category_id
        LEFT JOIN locations loc ON f.location_id = loc.location_id
        WHERE f.status = 'Approved'
        ORDER BY f.created_at DESC
        LIMIT 6
    ");
    $recent_found = $found_items_query->fetchAll();
} catch (PDOException $e) {
    $recent_found = [];
}

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="py-5 text-center">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3">
            <i class="fas fa-hands-helping"></i> <?php echo t('common.brand'); ?>
        </h1>
        <p class="lead text-muted mb-3">
            <?php echo t('index.hero_subtitle'); ?>
        </p>
        <p class="text-muted mb-4">
            <?php echo t('index.hero_description'); ?>
        </p>

        <?php if (!isLoggedIn() && !isPoliceLoggedIn() && !isAdminLoggedIn()): ?>
        <div class="d-flex gap-3 justify-content-center">
            <a href="<?php echo SITE_URL; ?>auth/register.php" class="btn btn-primary btn-lg px-4">
                <i class="fas fa-user-plus"></i> <?php echo t('index.get_started'); ?>
            </a>
            <a href="<?php echo SITE_URL; ?>auth/login.php" class="btn btn-outline-primary btn-lg px-4">
                <i class="fas fa-sign-in-alt"></i> <?php echo t('common.nav.login'); ?>
            </a>
        </div>
        <?php else: ?>
        <div class="d-flex gap-3 justify-content-center">
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo SITE_URL; ?>citizen/report-lost.php" class="btn btn-danger btn-lg px-4">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo t('common.nav.report_lost'); ?>
                </a>
                <a href="<?php echo SITE_URL; ?>citizen/report-found.php" class="btn btn-success btn-lg px-4">
                    <i class="fas fa-check-circle"></i> <?php echo t('common.nav.report_found'); ?>
                </a>
            <?php elseif (isPoliceLoggedIn()): ?>
                <a href="<?php echo SITE_URL; ?>police/dashboard.php" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-home"></i> <?php echo t('index.go_to_dashboard'); ?>
                </a>
            <?php elseif (isAdminLoggedIn()): ?>
                <a href="<?php echo SITE_URL; ?>admin/dashboard.php" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-home"></i> <?php echo t('index.go_to_dashboard'); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Statistics Section -->
<section class="py-4">
    <div class="container">
        <div class="row g-3">
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                    <div class="card-body text-center text-white py-4">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h2 class="display-4 fw-bold mb-2"><?php echo number_format($stats['total_lost']); ?></h2>
                        <p class="mb-0"><?php echo t('index.stats_lost_items'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #198754, #146c43);">
                    <div class="card-body text-center text-white py-4">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h2 class="display-4 fw-bold mb-2"><?php echo number_format($stats['total_found']); ?></h2>
                        <p class="mb-0"><?php echo t('index.stats_found_items'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #0dcaf0, #0aa2c0);">
                    <div class="card-body text-center text-white py-4">
                        <i class="fas fa-handshake fa-3x mb-3"></i>
                        <h2 class="display-4 fw-bold mb-2"><?php echo number_format($stats['total_matched']); ?></h2>
                        <p class="mb-0"><?php echo t('index.stats_reunited'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #ffc107, #cc9a06);">
                    <div class="card-body text-center text-dark py-4">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <h2 class="display-4 fw-bold mb-2"><?php echo number_format($stats['total_users']); ?></h2>
                        <p class="mb-0"><?php echo t('index.stats_active_users'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5 fw-bold"><?php echo t('index.how_it_works_title'); ?></h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="icon-box bg-primary-light mx-auto">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h4 class="fw-bold mb-3"><?php echo t('index.step1_title'); ?></h4>
                        <p class="text-muted"><?php echo t('index.step1_description'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="icon-box bg-warning-light mx-auto">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h4 class="fw-bold mb-3"><?php echo t('index.step2_title'); ?></h4>
                        <p class="text-muted"><?php echo t('index.step2_description'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="icon-box bg-success-light mx-auto">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h4 class="fw-bold mb-3"><?php echo t('index.step3_title'); ?></h4>
                        <p class="text-muted"><?php echo t('index.step3_description'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Lost Items -->
<?php if (count($recent_lost) > 0): ?>
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">
                <i class="fas fa-exclamation-triangle text-danger"></i> <?php echo t('index.recent_lost_items'); ?>
            </h2>
            <?php if (isLoggedIn()): ?>
            <a href="<?php echo SITE_URL; ?>citizen/search.php?type=lost" class="btn btn-outline-primary">
                <?php echo t('index.view_all'); ?> <i class="fas fa-arrow-right ms-1"></i>
            </a>
            <?php endif; ?>
        </div>
        <div class="row g-4">
            <?php foreach ($recent_lost as $item): ?>
            <div class="col-md-4 col-sm-6">
                <div class="card item-card h-100 fade-in">
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
                        <h5 class="card-title"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                        <p class="card-text text-muted small">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars(getLocalizedCategoryName($item) ?? 'N/A'); ?><br>
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(getLocalizedLocationName($item) ?? 'N/A'); ?><br>
                            <i class="fas fa-calendar"></i> <?php echo timeAgo($item['lost_date']); ?>
                        </p>
                        <p class="card-text text-truncate-2"><?php echo htmlspecialchars($item['description']); ?></p>
                        <?php if (isLoggedIn()): ?>
                        <a href="<?php echo SITE_URL; ?>citizen/view-report.php?type=lost&id=<?php echo $item['lost_item_id']; ?>"
                           class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-eye"></i> <?php echo t('common.button.view_details'); ?>
                        </a>
                        <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>auth/login.php" class="btn btn-outline-primary btn-sm w-100">
                            <?php echo t('index.login_to_view'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Recent Found Items -->
<?php if (count($recent_found) > 0): ?>
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">
                <i class="fas fa-check-circle text-success"></i> <?php echo t('index.recent_found_items'); ?>
            </h2>
            <?php if (isLoggedIn()): ?>
            <a href="<?php echo SITE_URL; ?>citizen/search.php?type=found" class="btn btn-outline-success">
                <?php echo t('index.view_all'); ?> <i class="fas fa-arrow-right ms-1"></i>
            </a>
            <?php endif; ?>
        </div>
        <div class="row g-4">
            <?php foreach ($recent_found as $item): ?>
            <div class="col-md-4 col-sm-6">
                <div class="card item-card h-100 fade-in">
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
                        <h5 class="card-title"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                        <p class="card-text text-muted small">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars(getLocalizedCategoryName($item) ?? 'N/A'); ?><br>
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(getLocalizedLocationName($item) ?? 'N/A'); ?><br>
                            <i class="fas fa-calendar"></i> <?php echo timeAgo($item['found_date']); ?>
                        </p>
                        <p class="card-text text-truncate-2"><?php echo htmlspecialchars($item['description']); ?></p>
                        <?php if (isLoggedIn()): ?>
                        <a href="<?php echo SITE_URL; ?>citizen/view-report.php?type=found&id=<?php echo $item['found_item_id']; ?>"
                           class="btn btn-success btn-sm w-100">
                            <i class="fas fa-eye"></i> <?php echo t('common.button.view_details'); ?>
                        </a>
                        <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>auth/login.php" class="btn btn-outline-success btn-sm w-100">
                            <?php echo t('index.login_to_view'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Call to Action -->
<?php if (!isLoggedIn() && !isPoliceLoggedIn() && !isAdminLoggedIn()): ?>
<section class="py-5 bg-primary text-white">
    <div class="container text-center">
        <h2 class="fw-bold mb-3"><?php echo t('index.cta_title'); ?></h2>
        <p class="lead mb-4"><?php echo t('index.cta_description'); ?></p>
        <a href="<?php echo SITE_URL; ?>auth/register.php" class="btn btn-light btn-lg px-5">
            <i class="fas fa-user-plus"></i> <?php echo t('index.register_now'); ?>
        </a>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>