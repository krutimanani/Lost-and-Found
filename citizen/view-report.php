<?php
/**
 * View Report Details
 * View detailed information about a lost or found item
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('citizen.view_report.page_title');
$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (!in_array($type, ['lost', 'found']) || $id === 0) {
    $_SESSION['error'] = t('citizen.view_report.error_invalid_request');
    redirect(SITE_URL . 'citizen/my-reports.php');
}

// Get item details
try {
    if ($type === 'lost') {
        $stmt = $pdo->prepare("
            SELECT l.*, c.category_name, loc.location_name,
                   COALESCE(p.name, u.name) as reporter_name,
                   COALESCE(p.email, u.email) as reporter_email,
                   COALESCE(p.phone, u.phone) as reporter_phone,
                   CASE WHEN l.police_id IS NOT NULL THEN 'police' ELSE 'citizen' END as reporter_type,
                   p.badge_number, ps.station_name
            FROM lost_items l
            LEFT JOIN categories c ON l.category_id = c.category_id
            LEFT JOIN locations loc ON l.location_id = loc.location_id
            LEFT JOIN users u ON l.user_id = u.user_id
            LEFT JOIN police p ON l.police_id = p.police_id
            LEFT JOIN police_stations ps ON p.station_id = ps.station_id
            WHERE l.lost_item_id = ?
        ");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("
            SELECT f.*, c.category_name, loc.location_name,
                   COALESCE(p.name, u.name) as reporter_name,
                   COALESCE(p.email, u.email) as reporter_email,
                   COALESCE(p.phone, u.phone) as reporter_phone,
                   CASE WHEN f.police_id IS NOT NULL THEN 'police' ELSE 'citizen' END as reporter_type,
                   p.badge_number, ps.station_name
            FROM found_items f
            LEFT JOIN categories c ON f.category_id = c.category_id
            LEFT JOIN locations loc ON f.location_id = loc.location_id
            LEFT JOIN users u ON f.user_id = u.user_id
            LEFT JOIN police p ON f.police_id = p.police_id
            LEFT JOIN police_stations ps ON p.station_id = ps.station_id
            WHERE f.found_item_id = ?
        ");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
    }

    if (!$item) {
        $_SESSION['error'] = t('citizen.view_report.error_not_found');
        redirect(SITE_URL . 'citizen/my-reports.php');
    }
} catch (PDOException $e) {
    $_SESSION['error'] = t('citizen.view_report.error_load_failed');
    redirect(SITE_URL . 'citizen/my-reports.php');
}

// Check if there's a match for this item
$match = null;
try {
    if ($type === 'lost') {
        $match_stmt = $pdo->prepare("
            SELECT mr.*, f.item_name as matched_item_name, f.description as matched_description,
                   f.image_path, p.name as police_name, ps.station_name
            FROM matched_reports mr
            LEFT JOIN found_items f ON mr.found_item_id = f.found_item_id
            LEFT JOIN police p ON mr.matched_by_police = p.police_id
            LEFT JOIN police_stations ps ON p.station_id = ps.station_id
            WHERE mr.lost_item_id = ?
            ORDER BY mr.matched_at DESC
            LIMIT 1
        ");
        $match_stmt->execute([$id]);
    } else {
        $match_stmt = $pdo->prepare("
            SELECT mr.*, l.item_name as matched_item_name, l.description as matched_description,
                   l.image_path, p.name as police_name, ps.station_name
            FROM matched_reports mr
            LEFT JOIN lost_items l ON mr.lost_item_id = l.lost_item_id
            LEFT JOIN police p ON mr.matched_by_police = p.police_id
            LEFT JOIN police_stations ps ON p.station_id = ps.station_id
            WHERE mr.found_item_id = ?
            ORDER BY mr.matched_at DESC
            LIMIT 1
        ");
        $match_stmt->execute([$id]);
    }
    $match = $match_stmt->fetch();
} catch (PDOException $e) {
    $match = null;
}

// For found items, check if user already claimed this item
$user_id = $_SESSION['user_id'];
$already_claimed = false;
if ($type === 'found' && $item['status'] === 'Approved') {
    try {
        $check_claim_stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM item_claims
            WHERE found_item_id = ? AND user_id = ?
        ");
        $check_claim_stmt->execute([$id, $user_id]);
        $claim_result = $check_claim_stmt->fetch();
        $already_claimed = ($claim_result['count'] > 0);
    } catch (PDOException $e) {
        $already_claimed = false;
    }
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header <?php echo $type === 'lost' ? 'bg-danger' : 'bg-success'; ?> text-white">
                <h4 class="mb-0">
                    <i class="fas fa-<?php echo $type === 'lost' ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                    <?php echo t('citizen.view_report.report_title', ['type' => ucfirst($type)]); ?>
                </h4>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <?php if ($item['image_path']): ?>
                            <img src="<?php echo SITE_URL; ?>uploads/<?php echo $type; ?>/<?php echo htmlspecialchars($item['image_path']); ?>"
                                 class="img-fluid rounded shadow-sm" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                        <?php else: ?>
                            <div class="bg-secondary rounded d-flex align-items-center justify-content-center"
                                 style="height: 300px;">
                                <i class="fas fa-image fa-4x text-white opacity-50"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-7">
                        <div class="mb-3">
                            <h3 class="fw-bold"><?php echo htmlspecialchars($item['item_name']); ?></h3>
                            <span class="badge badge-<?php echo strtolower($item['status']); ?> fs-6">
                                <?php echo $item['status']; ?>
                            </span>
                        </div>

                        <div class="mb-3">
                            <h6 class="text-muted mb-2"><?php echo t('citizen.view_report.label_category'); ?></h6>
                            <p class="mb-0">
                                <i class="fas fa-tag text-primary"></i>
                                <?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?>
                            </p>
                        </div>

                        <div class="mb-3">
                            <h6 class="text-muted mb-2"><?php echo t('citizen.view_report.label_date', ['type' => ucfirst($type)]); ?></h6>
                            <p class="mb-0">
                                <i class="fas fa-calendar text-primary"></i>
                                <?php echo formatDate($type === 'lost' ? $item['lost_date'] : $item['found_date'], 'd M Y'); ?>
                            </p>
                        </div>

                        <div class="mb-3">
                            <h6 class="text-muted mb-2"><?php echo t('citizen.view_report.label_location'); ?></h6>
                            <p class="mb-0">
                                <i class="fas fa-map-marker-alt text-primary"></i>
                                <?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?>
                            </p>
                        </div>

                        <div class="mb-3">
                            <h6 class="text-muted mb-2"><?php echo t('citizen.view_report.label_description'); ?></h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                        </div>

                        <?php if (!empty($item['contact_info'])): ?>
                            <div class="mb-3">
                                <h6 class="text-muted mb-2"><?php echo t('citizen.view_report.label_additional_contact'); ?></h6>
                                <p class="mb-0">
                                    <i class="fas fa-phone text-primary"></i>
                                    <?php echo htmlspecialchars($item['contact_info']); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <div>
                            <h6 class="text-muted mb-2"><?php echo t('citizen.view_report.label_reported_on'); ?></h6>
                            <p class="mb-0">
                                <i class="fas fa-clock text-primary"></i>
                                <?php echo formatDate($item['created_at'], 'd M Y, h:i A'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Match Information -->
        <?php if ($match): ?>
            <div class="card border-0 shadow-sm border-info mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-handshake"></i> <?php echo t('citizen.view_report.match_found_title'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success mb-3">
                        <i class="fas fa-check-circle"></i>
                        <?php echo t('citizen.view_report.match_found_message'); ?>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <?php if ($match['image_path']): ?>
                                <img src="<?php echo SITE_URL; ?>uploads/<?php echo $type === 'lost' ? 'found' : 'lost'; ?>/<?php echo htmlspecialchars($match['image_path']); ?>"
                                     class="img-fluid rounded" alt="Matched Item">
                            <?php else: ?>
                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center"
                                     style="height: 150px;">
                                    <i class="fas fa-image fa-2x text-white opacity-50"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <h6 class="fw-bold"><?php echo htmlspecialchars($match['matched_item_name']); ?></h6>
                            <p class="text-muted small mb-2"><?php echo htmlspecialchars($match['matched_description']); ?></p>

                            <p class="mb-1">
                                <strong><?php echo t('citizen.view_report.match_status'); ?>:</strong>
                                <span class="badge badge-<?php echo strtolower($match['status']); ?>">
                                    <?php echo $match['status']; ?>
                                </span>
                            </p>
                            <p class="mb-1">
                                <strong><?php echo t('citizen.view_report.match_matched_by'); ?>:</strong> <?php echo htmlspecialchars($match['police_name']); ?>
                                (<?php echo htmlspecialchars($match['station_name']); ?>)
                            </p>
                            <p class="mb-1">
                                <strong><?php echo t('citizen.view_report.match_matched_on'); ?>:</strong> <?php echo formatDate($match['matched_at'], 'd M Y, h:i A'); ?>
                            </p>
                            <?php if (!empty($match['notes'])): ?>
                                <p class="mb-0">
                                    <strong><?php echo t('citizen.view_report.match_notes'); ?>:</strong> <?php echo htmlspecialchars($match['notes']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-2">
            <a href="<?php echo SITE_URL; ?>citizen/my-reports.php?tab=<?php echo $type; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> <?php echo t('citizen.view_report.btn_back'); ?>
            </a>

            <?php if ($type === 'found' && $item['status'] === 'Approved'): ?>
                <?php if ($already_claimed): ?>
                    <button class="btn btn-info" disabled>
                        <i class="fas fa-check"></i> <?php echo t('citizen.view_report.already_claimed'); ?>
                    </button>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>citizen/claim-item.php?id=<?php echo $id; ?>" class="btn btn-info">
                        <i class="fas fa-hand-holding"></i> <?php echo t('citizen.view_report.btn_claim'); ?>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="fas fa-user"></i> <?php echo t('citizen.view_report.reporter_info_title'); ?>
                </h6>
            </div>
            <div class="card-body">
                <?php if ($item['reporter_type'] === 'police'): ?>
                    <p class="mb-2">
                        <span class="badge bg-info text-dark mb-2">
                            <i class="fas fa-shield-alt"></i> <?php echo t('common.user_type.police'); ?>
                        </span>
                    </p>
                    <p class="mb-2">
                        <strong><?php echo t('citizen.view_report.reporter_name'); ?>:</strong><br>
                        <?php echo htmlspecialchars($item['reporter_name'] ?? 'N/A'); ?>
                    </p>
                    <?php if (!empty($item['badge_number'])): ?>
                    <p class="mb-2">
                        <strong><?php echo t('common.label.badge_number'); ?>:</strong><br>
                        <?php echo htmlspecialchars($item['badge_number']); ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($item['station_name'])): ?>
                    <p class="mb-2">
                        <strong><?php echo t('common.label.station'); ?>:</strong><br>
                        <?php echo htmlspecialchars($item['station_name']); ?>
                    </p>
                    <?php endif; ?>
                    <p class="mb-2">
                        <strong><?php echo t('citizen.view_report.reporter_email'); ?>:</strong><br>
                        <?php echo htmlspecialchars($item['reporter_email'] ?? 'N/A'); ?>
                    </p>
                    <p class="mb-0">
                        <strong><?php echo t('citizen.view_report.reporter_phone'); ?>:</strong><br>
                        <?php echo htmlspecialchars($item['reporter_phone'] ?? 'N/A'); ?>
                    </p>
                <?php else: ?>
                    <p class="mb-2">
                        <span class="badge bg-secondary mb-2">
                            <i class="fas fa-user"></i> <?php echo t('common.user_type.citizen'); ?>
                        </span>
                    </p>
                    <p class="mb-2">
                        <strong><?php echo t('citizen.view_report.reporter_name'); ?>:</strong><br>
                        <?php echo htmlspecialchars($item['reporter_name'] ?? 'N/A'); ?>
                    </p>
                    <p class="mb-2">
                        <strong><?php echo t('citizen.view_report.reporter_email'); ?>:</strong><br>
                        <?php echo htmlspecialchars($item['reporter_email'] ?? 'N/A'); ?>
                    </p>
                    <p class="mb-0">
                        <strong><?php echo t('citizen.view_report.reporter_phone'); ?>:</strong><br>
                        <?php echo htmlspecialchars($item['reporter_phone'] ?? 'N/A'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle"></i> <?php echo t('citizen.view_report.status_guide_title'); ?>
                </h6>
            </div>
            <div class="card-body">
                <p class="small mb-2">
                    <span class="badge badge-pending"><?php echo t('citizen.view_report.status_pending'); ?></span> - <?php echo t('citizen.view_report.status_pending_desc'); ?>
                </p>
                <p class="small mb-2">
                    <span class="badge badge-approved"><?php echo t('citizen.view_report.status_approved'); ?></span> - <?php echo t('citizen.view_report.status_approved_desc'); ?>
                </p>
                <p class="small mb-2">
                    <span class="badge badge-rejected"><?php echo t('citizen.view_report.status_rejected'); ?></span> - <?php echo t('citizen.view_report.status_rejected_desc'); ?>
                </p>
                <p class="small mb-0">
                    <span class="badge badge-matched"><?php echo t('citizen.view_report.status_matched'); ?></span> - <?php echo t('citizen.view_report.status_matched_desc'); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>