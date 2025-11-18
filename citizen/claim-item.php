<?php
/**
 * Claim Found Item
 * Citizen can claim a found item by providing evidence
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('citizen.claim_item.page_title');
$user_id = $_SESSION['user_id'];
$found_item_id = intval($_GET['id'] ?? 0);

if ($found_item_id === 0) {
    $_SESSION['error'] = t('citizen.claim_item.error_invalid_item');
    redirect(SITE_URL . 'citizen/search.php?type=found');
}

// Get found item details
try {
    $stmt = $pdo->prepare("
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
        WHERE f.found_item_id = ? AND f.status = 'Approved'
    ");
    $stmt->execute([$found_item_id]);
    $item = $stmt->fetch();

    if (!$item) {
        $_SESSION['error'] = t('citizen.claim_item.error_not_found');
        redirect(SITE_URL . 'citizen/search.php?type=found');
    }
} catch (PDOException $e) {
    $_SESSION['error'] = t('citizen.claim_item.error_load_failed');
    redirect(SITE_URL . 'citizen/search.php?type=found');
}

// Check if user already claimed this item
try {
    $check_claim = $pdo->prepare("SELECT claim_id FROM item_claims WHERE found_item_id = ? AND user_id = ?");
    $check_claim->execute([$found_item_id, $user_id]);
    if ($check_claim->fetch()) {
        $_SESSION['error'] = t('citizen.claim_item.error_already_claimed');
        redirect(SITE_URL . 'citizen/my-claims.php');
    }
} catch (PDOException $e) {
    // Continue
}

// Handle claim submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $claim_reason = sanitize($_POST['claim_reason'] ?? '');
    $proof_description = sanitize($_POST['proof_description'] ?? '');

    if (empty($claim_reason)) {
        $_SESSION['error'] = t('citizen.claim_item.error_no_reason');
    } elseif (empty($proof_description)) {
        $_SESSION['error'] = t('citizen.claim_item.error_no_proof');
    } else {
        try {
            // Insert claim request
            $insert_stmt = $pdo->prepare("
                INSERT INTO item_claims (found_item_id, lost_item_id, user_id, claim_reason, proof_description, status, created_at)
                VALUES (?, NULL, ?, ?, ?, 'Pending', NOW())
            ");

            if ($insert_stmt->execute([$found_item_id, $user_id, $claim_reason, $proof_description])) {
                // Log activity
                logActivity($pdo, $user_id, 'citizen', 'Claim Item', "Claimed found item ID: $found_item_id");

                // Send notification to user
                sendNotification(
                    $pdo,
                    $user_id,
                    'Citizen',
                    t('citizen.claim_item.notification_title'),
                    t('citizen.claim_item.notification_message', ['item_name' => $item['item_name']]),
                    'Claim'
                );

                $_SESSION['success'] = t('citizen.claim_item.success_message');
                redirect(SITE_URL . 'citizen/my-claims.php');
            } else {
                $_SESSION['error'] = t('citizen.claim_item.error_submit_failed');
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = t('citizen.claim_item.error_submit_failed');
        }
    }
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0">
                    <i class="fas fa-hand-holding"></i> <?php echo t('citizen.claim_item.form_title'); ?>
                </h4>
            </div>
            <div class="card-body p-4">
                <!-- Item Details -->
                <div class="alert alert-info">
                    <h5 class="alert-heading"><i class="fas fa-info-circle"></i> <?php echo t('citizen.claim_item.item_heading'); ?></h5>
                    <div class="row">
                        <div class="col-md-4">
                            <?php if ($item['image_path']): ?>
                                <img src="<?php echo SITE_URL; ?>uploads/found/<?php echo htmlspecialchars($item['image_path']); ?>"
                                     class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                            <?php else: ?>
                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="height: 150px;">
                                    <i class="fas fa-image fa-3x text-white"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <h5 class="fw-bold"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                            <p class="mb-1"><strong><?php echo t('citizen.claim_item.label_category'); ?>:</strong> <?php echo htmlspecialchars($item['category_name']); ?></p>
                            <p class="mb-1"><strong><?php echo t('citizen.claim_item.label_location'); ?>:</strong> <?php echo htmlspecialchars($item['location_name']); ?></p>
                            <p class="mb-1"><strong><?php echo t('citizen.claim_item.label_found_date'); ?>:</strong> <?php echo formatDate($item['found_date']); ?></p>
                            <p class="mb-0"><strong><?php echo t('citizen.claim_item.label_description'); ?>:</strong> <?php echo htmlspecialchars($item['description']); ?></p>
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Claim Form -->
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="claim_reason" class="form-label">
                            <?php echo t('citizen.claim_item.label_claim_reason'); ?> <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="claim_reason" name="claim_reason" rows="4"
                                  placeholder="<?php echo t('citizen.claim_item.placeholder_reason'); ?>" required></textarea>
                        <small class="text-muted"><?php echo t('citizen.claim_item.reason_hint'); ?></small>
                    </div>

                    <div class="mb-3">
                        <label for="proof_description" class="form-label">
                            <?php echo t('citizen.claim_item.label_proof'); ?> <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="proof_description" name="proof_description" rows="5"
                                  placeholder="<?php echo t('citizen.claim_item.placeholder_proof'); ?>" required></textarea>
                        <small class="text-muted"><?php echo t('citizen.claim_item.proof_hint'); ?></small>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-shield-alt"></i>
                        <?php echo t('citizen.claim_item.important_notice'); ?>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo SITE_URL; ?>citizen/search.php?type=found" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo t('citizen.claim_item.btn_cancel'); ?>
                        </a>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-paper-plane"></i> <?php echo t('citizen.claim_item.btn_submit'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>