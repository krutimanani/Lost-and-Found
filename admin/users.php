<?php
/**
 * Manage Users
 * Admin can view and manage all registered citizens
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('admin.users.title');
$admin_id = $_SESSION['user_id'];

// Handle user status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status']) && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $new_status = $_POST['new_status'];

    if (in_array($new_status, ['Active', 'Inactive']) && $user_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");

            if ($stmt->execute([$new_status, $user_id])) {
                logActivity($pdo, $admin_id, 'admin', 'Change User Status', "User ID: $user_id, Status: $new_status");
                $_SESSION['success'] = t('admin.users.success_updated');
            } else {
                $_SESSION['error'] = t('admin.users.error_update_failed');
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = t('admin.users.error_update_failed');
        }
        redirect(SITE_URL . 'admin/users.php');
    }
}

// Get search query
$search = sanitize($_GET['search'] ?? '');

// Get all users
try {
    if (!empty($search)) {
        $stmt = $pdo->prepare("
            SELECT * FROM users
            WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?
            ORDER BY created_at DESC
        ");
        $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    }
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">
        <i class="fas fa-users"></i> <?php echo t('admin.users.title'); ?>
        <span class="text-muted fs-6">(<?php echo t('admin.users.total_count', ['count' => count($users)]); ?>)</span>
    </h2>
</div>

<!-- Search -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="">
            <div class="input-group">
                <input type="text" class="form-control" name="search"
                       value="<?php echo htmlspecialchars($search); ?>"
                       placeholder="<?php echo t('admin.users.search_placeholder'); ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> <?php echo t('admin.users.search'); ?>
                </button>
                <?php if (!empty($search)): ?>
                    <a href="<?php echo SITE_URL; ?>admin/users.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> <?php echo t('admin.users.clear'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<?php if (count($users) > 0): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo t('admin.users.id'); ?></th>
                            <th><?php echo t('admin.users.name'); ?></th>
                            <th><?php echo t('admin.users.email'); ?></th>
                            <th><?php echo t('admin.users.phone'); ?></th>
                            <th><?php echo t('admin.users.address'); ?></th>
                            <th><?php echo t('admin.users.status'); ?></th>
                            <th><?php echo t('admin.users.registered'); ?></th>
                            <th><?php echo t('admin.users.actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td class="text-truncate" style="max-width: 200px;">
                                    <?php echo htmlspecialchars($user['address']); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $user['status'] === 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $user['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo formatDate($user['created_at'], 'd M Y'); ?></small>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="openUserModal(<?php echo htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8'); ?>)">
                                        <i class="fas fa-user-cog"></i> <?php echo t('admin.users.manage'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-users fa-4x text-muted mb-3"></i>
            <h5 class="text-muted"><?php echo t('admin.users.no_users'); ?></h5>
            <?php if (!empty($search)): ?>
                <p class="text-muted"><?php echo t('admin.users.no_match'); ?></p>
                <a href="<?php echo SITE_URL; ?>admin/users.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> <?php echo t('admin.users.view_all'); ?>
                </a>
            <?php else: ?>
                <p class="text-muted"><?php echo t('admin.users.no_registered'); ?></p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Manage User Status Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo t('admin.users.manage_status'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong><?php echo t('admin.users.name'); ?>:</strong> <span id="modal_name"></span>
                </div>
                <div class="mb-3">
                    <strong><?php echo t('admin.users.email'); ?>:</strong> <span id="modal_email"></span>
                </div>
                <div class="mb-3">
                    <strong><?php echo t('admin.users.phone'); ?>:</strong> <span id="modal_phone"></span>
                </div>
                <div class="mb-3">
                    <strong><?php echo t('admin.users.address'); ?>:</strong> <span id="modal_address"></span>
                </div>
                <div class="mb-3">
                    <strong><?php echo t('admin.users.registered'); ?>:</strong> <span id="modal_created"></span>
                </div>
                <hr>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label"><strong><?php echo t('admin.users.change_status'); ?>:</strong></label>
                        <select class="form-select" name="new_status" id="modal_status" required>
                            <option value="Active"><?php echo t('admin.users.status_active'); ?></option>
                            <option value="Inactive"><?php echo t('admin.users.status_inactive'); ?></option>
                        </select>
                        <small class="text-muted"><?php echo t('admin.users.status_note'); ?></small>
                    </div>
                    <input type="hidden" name="user_id" id="modal_user_id">
                    <input type="hidden" name="change_status" value="1">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save"></i> <?php echo t('admin.users.update_status'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openUserModal(user) {
    document.getElementById('modal_name').textContent = user.name;
    document.getElementById('modal_email').textContent = user.email;
    document.getElementById('modal_phone').textContent = user.phone;
    document.getElementById('modal_address').textContent = user.address || 'N/A';
    document.getElementById('modal_created').textContent = new Date(user.created_at).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
    document.getElementById('modal_status').value = user.status;
    document.getElementById('modal_user_id').value = user.user_id;

    var modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}
</script>

<?php include '../includes/footer.php'; ?>