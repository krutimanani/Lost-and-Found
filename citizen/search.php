<?php
/**
 * Search Page
 * Search for lost and found items
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('citizen.search.page_title');

// Get search parameters
$search_type = $_GET['type'] ?? 'lost';
$search_query = sanitize($_GET['q'] ?? '');
$category_filter = intval($_GET['category'] ?? 0);
$location_filter = intval($_GET['location'] ?? 0);

// Get categories and locations for filters
try {
    $categories = $pdo->query("SELECT * FROM categories WHERE status = 'Active' ORDER BY category_name")->fetchAll();
    $locations = $pdo->query("SELECT * FROM locations WHERE status = 'Active' ORDER BY location_name")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    $locations = [];
}

// Build search query
$results = [];
try {
    if ($search_type === 'lost') {
        $sql = "
            SELECT l.*, c.category_name, loc.location_name
            FROM lost_items l
            LEFT JOIN categories c ON l.category_id = c.category_id
            LEFT JOIN locations loc ON l.location_id = loc.location_id
            WHERE l.status = 'Approved'
        ";
    } else {
        $sql = "
            SELECT f.*, c.category_name, loc.location_name
            FROM found_items f
            LEFT JOIN categories c ON f.category_id = c.category_id
            LEFT JOIN locations loc ON f.location_id = loc.location_id
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

    if ($location_filter > 0) {
        $sql .= " AND location_id = ?";
        $params[] = $location_filter;
    }

    $sql .= $search_type === 'lost' ? " ORDER BY l.lost_date DESC" : " ORDER BY f.found_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
} catch (PDOException $e) {
    $results = [];
}

// For found items, check which ones user already claimed
$user_id = $_SESSION['user_id'];
$user_claimed_items = [];

if ($search_type === 'found') {
    // Get items user already claimed
    try {
        $claimed_stmt = $pdo->prepare("
            SELECT found_item_id
            FROM item_claims
            WHERE user_id = ?
        ");
        $claimed_stmt->execute([$user_id]);
        $user_claimed_items = $claimed_stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $user_claimed_items = [];
    }
}

include '../includes/header.php';
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h4 class="fw-bold mb-3">
            <i class="fas fa-search"></i> <?php echo t('citizen.search.heading', ['type' => ucfirst($search_type)]); ?>
        </h4>

        <form method="GET" action="">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="type" class="form-label"><?php echo t('citizen.search.label_search_in'); ?></label>
                    <select class="form-select" id="type" name="type">
                        <option value="lost" <?php echo $search_type === 'lost' ? 'selected' : ''; ?>><?php echo t('citizen.search.option_lost_items'); ?></option>
                        <option value="found" <?php echo $search_type === 'found' ? 'selected' : ''; ?>><?php echo t('citizen.search.option_found_items'); ?></option>
                    </select>
                </div>

                <div class="col-md-5">
                    <label for="q" class="form-label"><?php echo t('citizen.search.label_keywords'); ?></label>
                    <input type="text" class="form-control" id="q" name="q"
                           value="<?php echo htmlspecialchars($search_query); ?>"
                           placeholder="<?php echo t('citizen.search.placeholder_keywords'); ?>">
                </div>

                <div class="col-md-2">
                    <label for="category" class="form-label"><?php echo t('citizen.search.label_category'); ?></label>
                    <select class="form-select" id="category" name="category">
                        <option value="0"><?php echo t('citizen.search.all_categories'); ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>"
                                <?php echo $category_filter === $cat['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(getLocalizedCategoryName($cat)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="location" class="form-label"><?php echo t('citizen.search.label_location'); ?></label>
                    <select class="form-select" id="location" name="location">
                        <option value="0"><?php echo t('citizen.search.all_locations'); ?></option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo $loc['location_id']; ?>"
                                <?php echo $location_filter === $loc['location_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(getLocalizedLocationName($loc)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> <?php echo t('citizen.search.btn_search'); ?>
                    </button>
                    <a href="<?php echo SITE_URL; ?>citizen/search.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> <?php echo t('citizen.search.btn_reset'); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Search Results -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <?php echo t('citizen.search.results_title'); ?>
        <?php if (!empty($search_query) || $category_filter > 0 || $location_filter > 0): ?>
            <span class="text-muted">(<?php echo t('citizen.search.items_found', ['count' => count($results)]); ?>)</span>
        <?php endif; ?>
    </h5>
</div>

<?php if (count($results) > 0): ?>
    <div class="row g-4">
        <?php foreach ($results as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card item-card h-100">
                    <?php if ($item['image_path']): ?>
                        <img src="<?php echo SITE_URL; ?>uploads/<?php echo $search_type; ?>/<?php echo htmlspecialchars($item['image_path']); ?>"
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
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?><br>
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?><br>
                            <i class="fas fa-calendar"></i>
                            <?php echo formatDate($search_type === 'lost' ? $item['lost_date'] : $item['found_date']); ?>
                        </p>
                        <p class="card-text text-truncate-2"><?php echo htmlspecialchars($item['description']); ?></p>
                        <?php if ($search_type === 'found'): ?>
                            <?php $already_claimed = in_array($item['found_item_id'], $user_claimed_items); ?>
                            <div class="d-grid gap-2">
                                <a href="<?php echo SITE_URL; ?>citizen/view-report.php?type=<?php echo $search_type; ?>&id=<?php echo $item['found_item_id']; ?>"
                                   class="btn btn-success btn-sm">
                                    <i class="fas fa-eye"></i> <?php echo t('citizen.search.btn_view_details'); ?>
                                </a>
                                <?php if ($already_claimed): ?>
                                    <button class="btn btn-info btn-sm" disabled>
                                        <i class="fas fa-check"></i> <?php echo t('citizen.search.already_claimed'); ?>
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo SITE_URL; ?>citizen/claim-item.php?id=<?php echo $item['found_item_id']; ?>"
                                       class="btn btn-info btn-sm">
                                        <i class="fas fa-hand-holding"></i> <?php echo t('citizen.search.btn_claim_item'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>citizen/view-report.php?type=<?php echo $search_type; ?>&id=<?php echo $item['lost_item_id']; ?>"
                               class="btn btn-danger btn-sm w-100">
                                <i class="fas fa-eye"></i> <?php echo t('citizen.search.btn_view_details'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <small class="text-muted">
                            <i class="fas fa-clock"></i> <?php echo timeAgo($item['created_at']); ?>
                        </small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-search fa-4x text-muted mb-3"></i>
            <h5 class="text-muted"><?php echo t('citizen.search.no_results_title'); ?></h5>
            <?php if (!empty($search_query) || $category_filter > 0 || $location_filter > 0): ?>
                <p class="text-muted"><?php echo t('citizen.search.no_results_adjust'); ?></p>
            <?php else: ?>
                <p class="text-muted"><?php echo t('citizen.search.no_results_use_form'); ?></p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>