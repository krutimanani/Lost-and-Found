<?php
/**
 * My Claims
 * View all claim requests submitted by the citizen
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('citizen.my_claims.page_title');
$user_id = $_SESSION['user_id'];

// Handle citizen confirmation of collection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_collection') {
    $claim_id = intval($_POST['claim_id'] ?? 0);

    if ($claim_id > 0) {
        try {
            // Verify this claim belongs to the user and police has marked it as collected
            $verify_stmt = $pdo->prepare("
                SELECT claim_id, found_item_id, lost_item_id FROM item_claims
                WHERE claim_id = ? AND user_id = ? AND status = 'Approved' AND collected = TRUE
            ");
            $verify_stmt->execute([$claim_id, $user_id]);
            $claim = $verify_stmt->fetch();

            if ($claim) {
                // Mark as citizen confirmed
                $confirm_stmt = $pdo->prepare("
                    UPDATE item_claims
                    SET citizen_confirmed_collection = TRUE, citizen_confirmed_at = NOW()
                    WHERE claim_id = ?
                ");

                if ($confirm_stmt->execute([$claim_id])) {
                    // Get the police officer who marked it as collected
                    $police_stmt = $pdo->prepare("SELECT collected_by FROM item_claims WHERE claim_id = ?");
                    $police_stmt->execute([$claim_id]);
                    $police_data = $police_stmt->fetch();
                    $collected_by_police = $police_data['collected_by'] ?? null;

                    // Both police and citizen have confirmed - create resolved match report
                    // Only create if we have a valid police ID
                    if ($collected_by_police) {
                        // Use lost_item_id if available, otherwise null
                        $lost_item_id = $claim['lost_item_id'] ?? null;

                        // Create match report (with or without lost item)
                        $match_stmt = $pdo->prepare("
                            INSERT INTO matched_reports (lost_item_id, found_item_id, matched_by_police, status, notes, matched_at)
                            VALUES (?, ?, ?, 'Resolved', 'Item claimed and collected by citizen through claim system', NOW())
                        ");
                        $match_stmt->execute([$lost_item_id, $claim['found_item_id'], $collected_by_police]);

                        // Update lost item status if it exists
                        if ($lost_item_id) {
                            $pdo->prepare("UPDATE lost_items SET status = 'Resolved' WHERE lost_item_id = ?")->execute([$lost_item_id]);
                        }
                    }

                    // Update found item status to Returned
                    $pdo->prepare("UPDATE found_items SET status = 'Returned' WHERE found_item_id = ?")->execute([$claim['found_item_id']]);

                    // Log activity
                    logActivity($pdo, $user_id, 'citizen', 'Confirm Collection', "Confirmed collection for claim ID: $claim_id");

                    $_SESSION['success'] = t('citizen.my_claims.success_confirmed');
                } else {
                    $_SESSION['error'] = t('citizen.my_claims.error_confirm_failed');
                }
            } else {
                $_SESSION['error'] = t('citizen.my_claims.error_invalid_claim');
            }
        } catch (PDOException $e) {
            // Log the error for debugging
            error_log("Citizen confirmation error: " . $e->getMessage());
            $_SESSION['error'] = t('citizen.my_claims.error_confirm_exception');
        }
        redirect(SITE_URL . 'citizen/my-claims.php');
    }
}

// Get all claim requests by this user
try {
    $stmt = $pdo->prepare("
        SELECT
            ic.*,
            fi.item_name as found_item_name,
            fi.image_path as found_image,
            fi.found_date,
            li.item_name as lost_item_name,
            c.category_name,
            p.name as reviewed_by_name,
            p2.name as collected_by_name
        FROM item_claims ic
        INNER JOIN found_items fi ON ic.found_item_id = fi.found_item_id
        LEFT JOIN lost_items li ON ic.lost_item_id = li.lost_item_id
        LEFT JOIN categories c ON fi.category_id = c.category_id
        LEFT JOIN police p ON ic.reviewed_by = p.police_id
        LEFT JOIN police p2 ON ic.collected_by = p2.police_id
        WHERE ic.user_id = ?
        ORDER BY ic.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $claims = $stmt->fetchAll();
} catch (PDOException $e) {
    $claims = [];
}

include '../includes/header.php';
?>

<h2 class="fw-bold mb-4">
    <i class="fas fa-hand-holding"></i> <?php echo t('citizen.my_claims.heading'); ?>
    <?php if (count($claims) > 0): ?>
        <span class="badge bg-primary"><?php echo count($claims); ?></span>
    <?php endif; ?>
</h2>

<?php if (count($claims) > 0): ?>
    <div class="row g-4">
        <?php foreach ($claims as $claim): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <?php if ($claim['found_image']): ?>
                        <img src="<?php echo SITE_URL; ?>uploads/found/<?php echo htmlspecialchars($claim['found_image']); ?>"
                             class="card-img-top" style="height: 200px; object-fit: cover;"
                             alt="<?php echo htmlspecialchars($claim['found_item_name']); ?>">
                    <?php else: ?>
                        <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center"
                             style="height: 200px;">
                            <i class="fas fa-image fa-3x text-white opacity-50"></i>
                        </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($claim['found_item_name']); ?></h5>
                            <?php
                            $badge_class = '';
                            $badge_icon = '';
                            switch ($claim['status']) {
                                case 'Pending':
                                    $badge_class = 'bg-warning text-dark';
                                    $badge_icon = 'fa-clock';
                                    break;
                                case 'Approved':
                                    $badge_class = 'bg-success';
                                    $badge_icon = 'fa-check-circle';
                                    break;
                                case 'Rejected':
                                    $badge_class = 'bg-danger';
                                    $badge_icon = 'fa-times-circle';
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?>">
                                <i class="fas <?php echo $badge_icon; ?>"></i> <?php echo $claim['status']; ?>
                            </span>
                        </div>

                        <p class="card-text small text-muted mb-2">
                            <strong><?php echo t('citizen.my_claims.label_category'); ?>:</strong> <?php echo htmlspecialchars($claim['category_name']); ?><br>
                            <strong><?php echo t('citizen.my_claims.label_found_date'); ?>:</strong> <?php echo formatDate($claim['found_date']); ?>
                        </p>

                        <div class="mb-2">
                            <strong class="small"><?php echo t('citizen.my_claims.label_claim_reason'); ?>:</strong>
                            <p class="card-text small text-truncate-3 mb-0"><?php echo nl2br(htmlspecialchars($claim['claim_reason'])); ?></p>
                        </div>

                        <div class="mb-2">
                            <strong class="small"><?php echo t('citizen.my_claims.label_evidence'); ?>:</strong>
                            <p class="card-text small text-muted mb-0"><?php echo nl2br(htmlspecialchars($claim['proof_description'])); ?></p>
                        </div>

                        <?php if ($claim['status'] === 'Approved'): ?>
                            <div class="alert alert-success mb-2 mt-2">
                                <i class="fas fa-check-circle"></i> <strong><?php echo t('citizen.my_claims.claim_approved'); ?></strong><br>
                                <small><?php echo t('citizen.my_claims.reviewed_by', ['name' => htmlspecialchars($claim['reviewed_by_name'] ?? 'Police')]); ?></small>
                                <?php if (!empty($claim['notes'])): ?>
                                    <br><small><?php echo htmlspecialchars($claim['notes']); ?></small>
                                <?php endif; ?>
                            </div>

                            <?php if ($claim['citizen_confirmed_collection']): ?>
                                <!-- Both confirmed - Case Resolved -->
                                <div class="alert alert-success mb-0">
                                    <i class="fas fa-check-double"></i> <strong><?php echo t('citizen.my_claims.case_resolved'); ?></strong><br>
                                    <small><?php echo t('citizen.my_claims.confirmed_on', ['date' => formatDate($claim['citizen_confirmed_at'], 'd M Y')]); ?></small><br>
                                    <small class="text-success"><?php echo t('citizen.my_claims.successfully_resolved'); ?></small>
                                </div>
                            <?php elseif ($claim['collected']): ?>
                                <!-- Police marked collected, waiting for citizen confirmation -->
                                <div class="alert alert-warning mb-2">
                                    <i class="fas fa-hand-holding"></i> <strong><?php echo t('citizen.my_claims.ready_for_confirmation'); ?></strong><br>
                                    <small><?php echo t('citizen.my_claims.police_marked_collected', ['date' => formatDate($claim['collected_at'], 'd M Y')]); ?></small><br>
                                    <small><?php echo t('citizen.my_claims.please_confirm'); ?></small>
                                </div>
                                <form method="POST" action="">
                                    <input type="hidden" name="claim_id" value="<?php echo $claim['claim_id']; ?>">
                                    <input type="hidden" name="action" value="confirm_collection">
                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('<?php echo t('citizen.my_claims.confirm_dialog'); ?>')">
                                        <i class="fas fa-check-circle"></i> <?php echo t('citizen.my_claims.btn_confirm_collection'); ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <!-- Approved but not yet collected by police -->
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-map-marker-alt"></i> <strong><?php echo t('citizen.my_claims.ready_for_collection'); ?></strong><br>
                                    <small><?php echo t('citizen.my_claims.visit_station_message'); ?></small>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($claim['status'] === 'Rejected'): ?>
                            <div class="alert alert-danger mb-0 mt-2">
                                <i class="fas fa-times-circle"></i> <strong><?php echo t('citizen.my_claims.claim_rejected'); ?></strong><br>
                                <?php if (!empty($claim['notes'])): ?>
                                    <small><strong><?php echo t('citizen.my_claims.rejection_reason'); ?>:</strong> <?php echo htmlspecialchars($claim['notes']); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0 mt-2">
                                <i class="fas fa-info-circle"></i> <small><?php echo t('citizen.my_claims.under_review'); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer bg-transparent">
                        <small class="text-muted">
                            <i class="fas fa-clock"></i> <?php echo t('citizen.my_claims.submitted_ago', ['time' => timeAgo($claim['created_at'])]); ?>
                        </small>
                        <?php if ($claim['reviewed_at']): ?>
                            <br><small class="text-muted">
                                <i class="fas fa-check"></i> <?php echo t('citizen.my_claims.reviewed_ago', ['time' => timeAgo($claim['reviewed_at'])]); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-hand-holding fa-4x text-muted mb-3"></i>
            <h5 class="text-muted"><?php echo t('citizen.my_claims.no_claims_title'); ?></h5>
            <p class="text-muted"><?php echo t('citizen.my_claims.no_claims_message'); ?></p>
            <a href="<?php echo SITE_URL; ?>citizen/search.php?type=found" class="btn btn-primary">
                <i class="fas fa-search"></i> <?php echo t('citizen.my_claims.btn_search_found'); ?>
            </a>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>