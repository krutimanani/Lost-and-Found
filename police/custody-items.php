<?php
/**
 * Custody Items
 * View all items in police custody
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if police is logged in
if (!isPoliceLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('police.custody_items.page_title');

// Get all custody items (found items with no user_id or with police custody reference)
try {
    $stmt = $pdo->query("
        SELECT f.*, c.category_name, loc.location_name
        FROM found_items f
        LEFT JOIN categories c ON f.category_id = c.category_id
        LEFT JOIN locations loc ON f.location_id = loc.location_id
        WHERE f.user_id IS NULL OR f.contact_info LIKE '%Police Custody%'
        ORDER BY f.created_at DESC
    ");
    $custody_items = $stmt->fetchAll();
} catch (PDOException $e) {
    $custody_items = [];
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">
        <i class="fas fa-box"></i> <?php echo t('police.custody_items.title'); ?>
    </h2>
    <a href="<?php echo SITE_URL; ?>police/upload-custody-item.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?php echo t('police.custody_items.upload_new'); ?>
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <p class="mb-0 text-muted">
            <i class="fas fa-info-circle"></i>
            <?php echo t('police.custody_items.description'); ?>
            <?php echo t('police.custody_items.total_items'); ?>: <strong><?php echo count($custody_items); ?></strong>
        </p>
    </div>
</div>

<?php if (count($custody_items) > 0): ?>
    <div class="row g-4">
        <?php foreach ($custody_items as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card item-card h-100 border-primary">
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
                            <span class="badge bg-primary"><?php echo t('police.custody_items.custody'); ?></span>
                        </div>
                        <p class="card-text text-muted small mb-2">
                            <i class="fas fa-tag"></i> <?php echo getLocalizedCategoryName($item['category_name'] ?? 'N/A'); ?><br>
                            <i class="fas fa-map-marker-alt"></i> <?php echo getLocalizedLocationName($item['location_name'] ?? 'N/A'); ?><br>
                            <i class="fas fa-calendar"></i> <?php echo formatDate($item['found_date']); ?><br>
                            <?php if (!empty($item['contact_info'])): ?>
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['contact_info']); ?>
                            <?php endif; ?>
                        </p>
                        <p class="card-text text-truncate-2"><?php echo htmlspecialchars($item['description']); ?></p>
                        <a href="<?php echo SITE_URL; ?>police/report-details.php?type=found&id=<?php echo $item['found_item_id']; ?>"
                           class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-eye"></i> <?php echo t('police.custody_items.view_details'); ?>
                        </a>
                    </div>
                    <div class="card-footer bg-transparent">
                        <small class="text-muted">
                            <i class="fas fa-clock"></i> <?php echo t('police.custody_items.added_ago', ['time' => timeAgo($item['created_at'])]); ?>
                        </small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
            <h5 class="text-muted"><?php echo t('police.custody_items.no_items'); ?></h5>
            <p class="text-muted"><?php echo t('police.custody_items.no_items_message'); ?></p>
            <a href="<?php echo SITE_URL; ?>police/upload-custody-item.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> <?php echo t('police.custody_items.upload_first'); ?>
            </a>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>