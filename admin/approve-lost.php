<?php
/**
 * Approve Lost Items
 * Admin can approve or reject lost item reports
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('admin.approve_lost.title');
$admin_id = $_SESSION['user_id'];

// Handle approve/reject action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['item_id'])) {
    $item_id = intval($_POST['item_id']);
    $action = $_POST['action'];
    $reason = sanitize($_POST['reason'] ?? '');

    if (in_array($action, ['approve', 'reject']) && $item_id > 0) {
        try {
            $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';
            $stmt = $pdo->prepare("UPDATE lost_items SET status = ? WHERE lost_item_id = ?");

            if ($stmt->execute([$new_status, $item_id])) {
                // Get user_id for notification
                $user_stmt = $pdo->prepare("SELECT user_id, item_name FROM lost_items WHERE lost_item_id = ?");
                $user_stmt->execute([$item_id]);
                $item = $user_stmt->fetch();

                if ($item) {
                    // Send notification
                    $title = ($action === 'approve') ? t('admin.approve_lost.notification_approved_title') : t('admin.approve_lost.notification_rejected_title');
                    $message = ($action === 'approve')
                        ? t('admin.approve_lost.notification_approved_message', ['item_name' => $item['item_name']])
                        : t('admin.approve_lost.notification_rejected_message', ['item_name' => $item['item_name'], 'reason' => ($reason ?: t('admin.approve_lost.no_reason_provided'))]);

                    sendNotification($pdo, $item['user_id'], 'Citizen', $title, $message, 'Admin');
                }

                // Log activity
                logActivity($pdo, $admin_id, 'admin', ucfirst($action) . ' Lost Item', "Item ID: $item_id");

                $_SESSION['success'] = ($action === 'approve') ? t('admin.approve_lost.success_approved') : t('admin.approve_lost.success_rejected');
            } else {
                $_SESSION['error'] = t('admin.approve_lost.error_update_failed');
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = t('admin.approve_lost.error_update_failed');
        }
        redirect(SITE_URL . 'admin/approve-lost.php');
    }
}

// Get pending lost items
try {
    $pending_items = $pdo->query("
        SELECT l.*, c.category_name, loc.location_name,
               COALESCE(p.name, u.name) as reporter_name,
               COALESCE(p.email, u.email) as email,
               COALESCE(p.phone, u.phone) as phone,
               CASE WHEN l.police_id IS NOT NULL THEN 'police' ELSE 'citizen' END as reporter_type
        FROM lost_items l
        LEFT JOIN categories c ON l.category_id = c.category_id
        LEFT JOIN locations loc ON l.location_id = loc.location_id
        LEFT JOIN users u ON l.user_id = u.user_id
        LEFT JOIN police p ON l.police_id = p.police_id
        WHERE l.status = 'Pending'
        ORDER BY l.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $pending_items = [];
}

include '../includes/header.php';
?>

<h2 class="fw-bold mb-4">
    <i class="fas fa-check"></i> <?php echo t('admin.approve_lost.title'); ?>
    <?php if (count($pending_items) > 0): ?>
        <span class="badge bg-warning text-dark"><?php echo t('admin.approve_lost.pending_count', ['count' => count($pending_items)]); ?></span>
    <?php endif; ?>
</h2>

<?php if (count($pending_items) > 0): ?>
    <div class="row g-4">
        <?php foreach ($pending_items as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-warning h-100">
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
                        <p class="card-text text-muted small mb-2">
                            <strong><?php echo t('admin.approve_lost.reporter'); ?>:</strong> <?php echo htmlspecialchars($item['reporter_name']); ?><br>
                            <strong><?php echo t('admin.approve_lost.email'); ?>:</strong> <?php echo htmlspecialchars($item['email']); ?><br>
                            <strong><?php echo t('admin.approve_lost.phone'); ?>:</strong> <?php echo htmlspecialchars($item['phone']); ?><br>
                            <strong><?php echo t('admin.approve_lost.category'); ?>:</strong> <?php echo htmlspecialchars(getLocalizedCategoryName($item['category_name'] ?? 'N/A')); ?><br>
                            <strong><?php echo t('admin.approve_lost.location'); ?>:</strong> <?php echo htmlspecialchars(getLocalizedLocationName($item['location_name'] ?? 'N/A')); ?><br>
                            <strong><?php echo t('admin.approve_lost.lost_date'); ?>:</strong> <?php echo formatDate($item['lost_date']); ?>
                        </p>
                        <p class="card-text"><strong><?php echo t('admin.approve_lost.description'); ?>:</strong><br><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                        <?php if (!empty($item['contact_info'])): ?>
                            <p class="card-text text-muted small">
                                <strong><?php echo t('admin.approve_lost.contact_info'); ?>:</strong> <?php echo htmlspecialchars($item['contact_info']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <small class="text-muted d-block mb-2">
                            <i class="fas fa-clock"></i> <?php echo t('admin.approve_lost.submitted'); ?> <?php echo timeAgo($item['created_at']); ?>
                        </small>
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#approveModal<?php echo $item['lost_item_id']; ?>">
                                <i class="fas fa-check"></i> <?php echo t('admin.approve_lost.approve'); ?>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#rejectModal<?php echo $item['lost_item_id']; ?>">
                                <i class="fas fa-times"></i> <?php echo t('admin.approve_lost.reject'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Modals placed outside the card loop to prevent z-index issues -->
    <?php foreach ($pending_items as $item): ?>
        <!-- Approve Modal -->
        <div class="modal fade" id="approveModal<?php echo $item['lost_item_id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title"><?php echo t('admin.approve_lost.approve_modal_title'); ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p><?php echo t('admin.approve_lost.approve_confirm'); ?></p>
                            <p class="mb-0"><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></p>
                            <input type="hidden" name="item_id" value="<?php echo $item['lost_item_id']; ?>">
                            <input type="hidden" name="action" value="approve">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('admin.approve_lost.cancel'); ?></button>
                            <button type="submit" class="btn btn-success"><?php echo t('admin.approve_lost.approve'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div class="modal fade" id="rejectModal<?php echo $item['lost_item_id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title"><?php echo t('admin.approve_lost.reject_modal_title'); ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p><?php echo t('admin.approve_lost.reject_confirm'); ?></p>
                            <p><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></p>
                            <div class="mb-3">
                                <label class="form-label"><?php echo t('admin.approve_lost.rejection_reason'); ?></label>
                                <textarea class="form-control" name="reason" rows="3"
                                          placeholder="<?php echo t('admin.approve_lost.rejection_placeholder'); ?>"></textarea>
                            </div>
                            <input type="hidden" name="item_id" value="<?php echo $item['lost_item_id']; ?>">
                            <input type="hidden" name="action" value="reject">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('admin.approve_lost.cancel'); ?></button>
                            <button type="submit" class="btn btn-danger"><?php echo t('admin.approve_lost.reject'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
            <h5 class="text-muted"><?php echo t('admin.approve_lost.all_reviewed'); ?></h5>
            <p class="text-muted"><?php echo t('admin.approve_lost.no_pending'); ?></p>
            <a href="<?php echo SITE_URL; ?>admin/dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> <?php echo t('admin.approve_lost.back_to_dashboard'); ?>
            </a>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>