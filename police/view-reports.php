<?php
/**
 * View Reports
 * Police can view all approved lost and found reports
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if police is logged in
if (!isPoliceLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('police.view_reports.page_title');
$report_type = $_GET['type'] ?? 'lost';
$search_query = sanitize($_GET['q'] ?? '');
$category_filter = intval($_GET['category'] ?? 0);

// Get categories for filter
try {
    $categories = $pdo->query("SELECT * FROM categories WHERE status = 'Active' ORDER BY category_name")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Get reports
$reports = [];
try {
    if ($report_type === 'lost') {
        $sql = "
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
        ";
    } else {
        $sql = "
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
        ";
    }

    $params = [];

    if (!empty($search_query)) {
        $sql .= " AND (item_name LIKE ? OR description LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
    }

    if ($category_filter > 0) {
        $sql .= " AND category_id = ?";
        $params[] = $category_filter;
    }

    $sql .= $report_type === 'lost' ? " ORDER BY l.created_at DESC" : " ORDER BY f.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();
} catch (PDOException $e) {
    $reports = [];
}

include '../includes/header.php';
?>

<h2 class="fw-bold mb-4">
    <i class="fas fa-list"></i> <?php echo t('police.view_reports.title_' . $report_type); ?>
</h2>

<!-- Filter and Search -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="type" class="form-label"><?php echo t('police.view_reports.report_type'); ?></label>
                    <select class="form-select" id="type" name="type">
                        <option value="lost" <?php echo $report_type === 'lost' ? 'selected' : ''; ?>><?php echo t('police.view_reports.lost_items'); ?></option>
                        <option value="found" <?php echo $report_type === 'found' ? 'selected' : ''; ?>><?php echo t('police.view_reports.found_items'); ?></option>
                    </select>
                </div>

                <div class="col-md-5">
                    <label for="q" class="form-label"><?php echo t('police.view_reports.search'); ?></label>
                    <input type="text" class="form-control" id="q" name="q"
                           value="<?php echo htmlspecialchars($search_query); ?>"
                           placeholder="<?php echo t('police.view_reports.search_placeholder'); ?>">
                </div>

                <div class="col-md-2">
                    <label for="category" class="form-label"><?php echo t('police.view_reports.category'); ?></label>
                    <select class="form-select" id="category" name="category">
                        <option value="0"><?php echo t('police.view_reports.all_categories'); ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>"
                                <?php echo $category_filter === $cat['category_id'] ? 'selected' : ''; ?>>
                                <?php echo getLocalizedCategoryName($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> <?php echo t('police.view_reports.search_btn'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Results -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <?php echo t('police.view_reports.reports'); ?> <span class="text-muted">(<?php echo t('police.view_reports.items_count', ['count' => count($reports)]); ?>)</span>
    </h5>
</div>

<?php if (count($reports) > 0): ?>
    <div class="row g-4">
        <?php foreach ($reports as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card item-card h-100">
                    <?php if ($item['image_path']): ?>
                        <img src="<?php echo SITE_URL; ?>uploads/<?php echo $report_type; ?>/<?php echo htmlspecialchars($item['image_path']); ?>"
                             class="card-img-top" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                    <?php else: ?>
                        <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center"
                             style="height: 200px;">
                            <i class="fas fa-image fa-3x text-white opacity-50"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                        <p class="card-text text-muted small mb-2">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($item['reporter_name'] ?? 'N/A'); ?><br>
                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($item['reporter_phone'] ?? 'N/A'); ?><br>
                            <i class="fas fa-tag"></i> <?php echo getLocalizedCategoryName($item['category_name'] ?? 'N/A'); ?><br>
                            <i class="fas fa-map-marker-alt"></i> <?php echo getLocalizedLocationName($item['location_name'] ?? 'N/A'); ?>
                        </p>
                        <p class="card-text text-truncate-2"><?php echo htmlspecialchars($item['description']); ?></p>
                        <a href="<?php echo SITE_URL; ?>police/report-details.php?type=<?php echo $report_type; ?>&id=<?php echo $report_type === 'lost' ? $item['lost_item_id'] : $item['found_item_id']; ?>"
                           class="btn btn-<?php echo $report_type === 'lost' ? 'danger' : 'success'; ?> btn-sm w-100">
                            <i class="fas fa-eye"></i> <?php echo t('police.view_reports.view_details'); ?>
                        </a>
                    </div>
                    <div class="card-footer bg-transparent">
                        <small class="text-muted">
                            <i class="fas fa-clock"></i> <?php echo t('police.view_reports.reported_ago', ['time' => timeAgo($item['created_at'])]); ?>
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
            <h5 class="text-muted"><?php echo t('police.view_reports.no_reports_found'); ?></h5>
            <p class="text-muted"><?php echo t('police.view_reports.no_reports_message', ['type' => t('police.view_reports.' . $report_type . '_items_lower')]); ?></p>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>