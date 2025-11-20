<?php
/**
 * Review Claims
 * Police can review and approve/reject citizen claims
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if police is logged in
if (!isPoliceLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('police.review_claims.page_title');
$police_id = $_SESSION['user_id'];

// Handle approve/reject/collect action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['claim_id'])) {
    $claim_id = intval($_POST['claim_id']);
    $action = $_POST['action'];
    $notes = sanitize($_POST['notes'] ?? '');

    if (in_array($action, ['approve', 'reject', 'collected']) && $claim_id > 0) {
        try {
            if ($action === 'collected') {
                // Mark item as collected
                $stmt = $pdo->prepare("
                    UPDATE item_claims
                    SET collected = TRUE, collected_at = NOW(), collected_by = ?
                    WHERE claim_id = ?
                ");

                if ($stmt->execute([$police_id, $claim_id])) {
                    // Get claim details for notification
                    $claim_stmt = $pdo->prepare("
                        SELECT ic.user_id, fi.item_name
                        FROM item_claims ic
                        INNER JOIN found_items fi ON ic.found_item_id = fi.found_item_id
                        WHERE ic.claim_id = ?
                    ");
                    $claim_stmt->execute([$claim_id]);
                    $claim = $claim_stmt->fetch();

                    if ($claim) {
                        // Send notification to citizen
                        sendNotification(
                            $pdo,
                            $claim['user_id'],
                            'Citizen',
                            t('police.review_claims.item_collected_title'),
                            t('police.review_claims.item_collected_message', ['item' => $claim['item_name']]),
                            'Claim'
                        );
                    }

                    // Log activity
                    logActivity($pdo, $police_id, 'police', 'Item Collected', "Claim ID: $claim_id");

                    $_SESSION['success'] = t('police.review_claims.marked_collected');
                } else {
                    $_SESSION['error'] = t('police.review_claims.failed_mark_collected');
                }
            } else {
                // Approve or reject claim
                $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';
                $stmt = $pdo->prepare("
                    UPDATE item_claims
                    SET status = ?, reviewed_by = ?, reviewed_at = NOW(), notes = ?
                    WHERE claim_id = ?
                ");

                if ($stmt->execute([$new_status, $police_id, $notes, $claim_id])) {
                    // Get claim details for notification
                    $claim_stmt = $pdo->prepare("
                        SELECT ic.user_id, fi.item_name
                        FROM item_claims ic
                        INNER JOIN found_items fi ON ic.found_item_id = fi.found_item_id
                        WHERE ic.claim_id = ?
                    ");
                    $claim_stmt->execute([$claim_id]);
                    $claim = $claim_stmt->fetch();

                    if ($claim) {
                        // Send notification to citizen
                        $title = ($action === 'approve') ? t('police.review_claims.claim_approved_title') : t('police.review_claims.claim_rejected_title');
                        $message = ($action === 'approve')
                            ? t('police.review_claims.claim_approved_message', ['item' => $claim['item_name']])
                            : t('police.review_claims.claim_rejected_message', ['item' => $claim['item_name'], 'reason' => ($notes ? t('police.review_claims.reason') . ': ' . $notes : '')]);

                        sendNotification($pdo, $claim['user_id'], 'Citizen', $title, $message, 'Claim');
                    }

                    // Log activity
                    logActivity($pdo, $police_id, 'police', ucfirst($action) . ' Claim', "Claim ID: $claim_id");

                    $_SESSION['success'] = t('police.review_claims.claim_' . $action . '_success');
                } else {
                    $_SESSION['error'] = t('police.review_claims.failed_update');
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = t('police.review_claims.failed_update');
        }
        redirect(SITE_URL . 'police/review-claims.php');
    }
}

// Get filter
$status_filter = $_GET['status'] ?? 'Pending';

// Get claims
try {
    $sql = "
        SELECT
            ic.*,
            fi.item_name as found_item_name,
            fi.image_path as found_image,
            fi.description as found_description,
            fi.found_date,
            c.category_name,
            loc.location_name,
            u.name as claimant_name,
            u.email as claimant_email,
            u.phone as claimant_phone,
            p.name as reviewed_by_name,
            p2.name as collected_by_name
        FROM item_claims ic
        INNER JOIN found_items fi ON ic.found_item_id = fi.found_item_id
        INNER JOIN users u ON ic.user_id = u.user_id
        LEFT JOIN categories c ON fi.category_id = c.category_id
        LEFT JOIN locations loc ON fi.location_id = loc.location_id
        LEFT JOIN police p ON ic.reviewed_by = p.police_id
        LEFT JOIN police p2 ON ic.collected_by = p2.police_id
        WHERE ic.status = ?
        ORDER BY ic.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status_filter]);
    $claims = $stmt->fetchAll();
} catch (PDOException $e) {
    $claims = [];
}

// Get counts for tabs
try {
    $counts_stmt = $pdo->query("
        SELECT status, COUNT(*) as count
        FROM item_claims
        GROUP BY status
    ");
    $counts = [];
    while ($row = $counts_stmt->fetch()) {
        $counts[$row['status']] = $row['count'];
    }
} catch (PDOException $e) {
    $counts = [];
}

include '../includes/header.php';
?>

<h2 class="fw-bold mb-4">
    <i class="fas fa-clipboard-check"></i> <?php echo t('police.review_claims.title'); ?>
</h2>

<!-- Status Filter Tabs -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?php echo $status_filter === 'Pending' ? 'active' : ''; ?>"
           href="?status=Pending">
            <?php echo t('police.review_claims.pending'); ?>
            <?php if (isset($counts['Pending']) && $counts['Pending'] > 0): ?>
                <span class="badge bg-warning text-dark"><?php echo $counts['Pending']; ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $status_filter === 'Approved' ? 'active' : ''; ?>"
           href="?status=Approved">
            <?php echo t('police.review_claims.approved'); ?>
            <?php if (isset($counts['Approved']) && $counts['Approved'] > 0): ?>
                <span class="badge bg-success"><?php echo $counts['Approved']; ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $status_filter === 'Rejected' ? 'active' : ''; ?>"
           href="?status=Rejected">
            <?php echo t('police.review_claims.rejected'); ?>
            <?php if (isset($counts['Rejected']) && $counts['Rejected'] > 0): ?>
                <span class="badge bg-danger"><?php echo $counts['Rejected']; ?></span>
            <?php endif; ?>
        </a>
    </li>
</ul>

<?php if (count($claims) > 0): ?>
    <div class="row g-4">
        <?php foreach ($claims as $claim): ?>
            <div class="col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row">
                            <!-- Item Image -->
                            <div class="col-md-3">
                                <?php if ($claim['found_image']): ?>
                                    <img src="<?php echo SITE_URL; ?>uploads/found/<?php echo htmlspecialchars($claim['found_image']); ?>"
                                         class="img-fluid rounded" alt="<?php echo htmlspecialchars($claim['found_item_name']); ?>">
                                <?php else: ?>
                                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center"
                                         style="height: 200px;">
                                        <i class="fas fa-image fa-3x text-white opacity-50"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Claim Details -->
                            <div class="col-md-9">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($claim['found_item_name']); ?></h5>
                                        <p class="text-muted small mb-0">
                                            <i class="fas fa-tag"></i> <?php echo getLocalizedCategoryName($claim['category_name']); ?>
                                            | <i class="fas fa-map-marker-alt"></i> <?php echo getLocalizedLocationName($claim['location_name']); ?>
                                            | <i class="fas fa-calendar"></i> <?php echo t('police.review_claims.found'); ?>: <?php echo formatDate($claim['found_date']); ?>
                                        </p>
                                    </div>
                                    <?php
                                    $badge_class = $claim['status'] === 'Pending' ? 'bg-warning text-dark' :
                                                  ($claim['status'] === 'Approved' ? 'bg-success' : 'bg-danger');
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?> fs-6">
                                        <?php echo $claim['status']; ?>
                                    </span>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-2"><i class="fas fa-user"></i> <?php echo t('police.review_claims.claimant_info'); ?></h6>
                                        <p class="small mb-1"><strong><?php echo t('police.review_claims.name'); ?>:</strong> <?php echo htmlspecialchars($claim['claimant_name']); ?></p>
                                        <p class="small mb-1"><strong><?php echo t('police.review_claims.email'); ?>:</strong> <?php echo htmlspecialchars($claim['claimant_email']); ?></p>
                                        <p class="small mb-0"><strong><?php echo t('police.review_claims.phone'); ?>:</strong> <?php echo htmlspecialchars($claim['claimant_phone']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-2"><i class="fas fa-info-circle"></i> <?php echo t('police.review_claims.item_description'); ?></h6>
                                        <p class="small mb-0"><?php echo nl2br(htmlspecialchars($claim['found_description'])); ?></p>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="text-primary mb-2"><i class="fas fa-comment-dots"></i> <?php echo t('police.review_claims.claim_reason'); ?></h6>
                                    <div class="bg-light p-3 rounded">
                                        <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($claim['claim_reason'])); ?></p>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="text-primary mb-2"><i class="fas fa-shield-alt"></i> <?php echo t('police.review_claims.proof'); ?></h6>
                                    <div class="bg-warning bg-opacity-10 p-3 rounded border border-warning">
                                        <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($claim['proof_description'])); ?></p>
                                    </div>
                                </div>

                                <?php if ($claim['status'] !== 'Pending'): ?>
                                    <div class="alert alert-<?php echo $claim['status'] === 'Approved' ? 'success' : 'danger'; ?> mb-3">
                                        <strong><?php echo t('police.review_claims.reviewed_by'); ?>:</strong> <?php echo htmlspecialchars($claim['reviewed_by_name'] ?? 'Police'); ?><br>
                                        <strong><?php echo t('police.review_claims.reviewed_at'); ?>:</strong> <?php echo formatDate($claim['reviewed_at'], 'd M Y, h:i A'); ?>
                                        <?php if (!empty($claim['notes'])): ?>
                                            <br><strong><?php echo t('police.review_claims.notes'); ?>:</strong> <?php echo htmlspecialchars($claim['notes']); ?>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($claim['status'] === 'Approved'): ?>
                                        <?php if ($claim['collected']): ?>
                                            <div class="alert alert-info mb-0">
                                                <i class="fas fa-check-circle"></i> <strong><?php echo t('police.review_claims.item_collected'); ?></strong><br>
                                                <small>
                                                    <strong><?php echo t('police.review_claims.collected_at'); ?>:</strong> <?php echo formatDate($claim['collected_at'], 'd M Y, h:i A'); ?><br>
                                                    <strong><?php echo t('police.review_claims.collected_by'); ?>:</strong> <?php echo htmlspecialchars($claim['collected_by_name'] ?? 'Police'); ?>
                                                </small>
                                            </div>
                                        <?php else: ?>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="claim_id" value="<?php echo $claim['claim_id']; ?>">
                                                <input type="hidden" name="action" value="collected">
                                                <button type="submit" class="btn btn-primary" onclick="return confirm('<?php echo t('police.review_claims.confirm_collected'); ?>')">
                                                    <i class="fas fa-hand-holding"></i> <?php echo t('police.review_claims.mark_collected'); ?>
                                                </button>
                                            </form>
                                            <small class="text-muted d-block mt-2">
                                                <i class="fas fa-info-circle"></i> <?php echo t('police.review_claims.mark_collected_hint'); ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                                data-bs-target="#approveModal<?php echo $claim['claim_id']; ?>">
                                            <i class="fas fa-check"></i> <?php echo t('police.review_claims.approve_claim'); ?>
                                        </button>
                                        <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                                                data-bs-target="#rejectModal<?php echo $claim['claim_id']; ?>">
                                            <i class="fas fa-times"></i> <?php echo t('police.review_claims.reject_claim'); ?>
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <div class="text-muted small mt-2">
                                    <i class="fas fa-clock"></i> <?php echo t('police.review_claims.submitted_ago', ['time' => timeAgo($claim['created_at'])]); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Modals for Approve/Reject -->
    <?php foreach ($claims as $claim): ?>
        <?php if ($claim['status'] === 'Pending'): ?>
            <!-- Approve Modal -->
            <div class="modal fade" id="approveModal<?php echo $claim['claim_id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title"><?php echo t('police.review_claims.approve_claim'); ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p><?php echo t('police.review_claims.approve_confirm'); ?></p>
                                <p class="mb-0"><strong><?php echo t('police.review_claims.item'); ?>:</strong> <?php echo htmlspecialchars($claim['found_item_name']); ?></p>
                                <p class="mb-3"><strong><?php echo t('police.review_claims.claimant'); ?>:</strong> <?php echo htmlspecialchars($claim['claimant_name']); ?></p>

                                <div class="mb-3">
                                    <label class="form-label"><?php echo t('police.review_claims.notes_optional'); ?></label>
                                    <textarea class="form-control" name="notes" rows="3"
                                              placeholder="<?php echo t('police.review_claims.notes_placeholder'); ?>"></textarea>
                                </div>

                                <input type="hidden" name="claim_id" value="<?php echo $claim['claim_id']; ?>">
                                <input type="hidden" name="action" value="approve">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('police.review_claims.cancel'); ?></button>
                                <button type="submit" class="btn btn-success"><?php echo t('police.review_claims.approve_claim'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Reject Modal -->
            <div class="modal fade" id="rejectModal<?php echo $claim['claim_id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title"><?php echo t('police.review_claims.reject_claim'); ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p><?php echo t('police.review_claims.reject_confirm'); ?></p>
                                <p class="mb-3"><strong><?php echo t('police.review_claims.item'); ?>:</strong> <?php echo htmlspecialchars($claim['found_item_name']); ?></p>

                                <div class="mb-3">
                                    <label class="form-label"><?php echo t('police.review_claims.rejection_reason'); ?> <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="notes" rows="3" required
                                              placeholder="<?php echo t('police.review_claims.rejection_placeholder'); ?>"></textarea>
                                    <small class="text-muted"><?php echo t('police.review_claims.rejection_hint'); ?></small>
                                </div>

                                <input type="hidden" name="claim_id" value="<?php echo $claim['claim_id']; ?>">
                                <input type="hidden" name="action" value="reject">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('police.review_claims.cancel'); ?></button>
                                <button type="submit" class="btn btn-danger"><?php echo t('police.review_claims.reject_claim'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
            <h5 class="text-muted"><?php echo t('police.review_claims.no_claims', ['status' => $status_filter]); ?></h5>
            <p class="text-muted"><?php echo t('police.review_claims.no_claims_message', ['status' => strtolower($status_filter)]); ?></p>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
