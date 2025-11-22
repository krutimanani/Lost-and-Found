<?php
/**
 * Manage Categories
 * Admin can view and manage item categories
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('admin.categories.title');
$admin_id = $_SESSION['user_id'];

// Handle category creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $category_name = sanitize($_POST['category_name']);
    $description = sanitize($_POST['description']);
    $status = sanitize($_POST['status']);

    // Validation
    $errors = [];
    if (empty($category_name)) $errors[] = t('admin.categories.error_name_required');
    if (empty($description)) $errors[] = t('admin.categories.error_description_required');

    if (empty($errors)) {
        try {
            // Check if category already exists
            $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE category_name = ?");
            $stmt->execute([$category_name]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = t('admin.categories.error_exists');
            } else {
                // Create category
                $stmt = $pdo->prepare("
                    INSERT INTO categories (category_name, description, status, created_at)
                    VALUES (?, ?, ?, NOW())
                ");

                if ($stmt->execute([$category_name, $description, $status])) {
                    logActivity($pdo, $admin_id, 'admin', 'Create Category', "Created category: $category_name");
                    $_SESSION['success'] = t('admin.categories.success_created');
                } else {
                    $_SESSION['error'] = t('admin.categories.error_create_failed');
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = t('admin.categories.error_database') . $e->getMessage();
        }
        redirect(SITE_URL . 'admin/categories.php');
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

// Handle category update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category']) && isset($_POST['category_id'])) {
    $category_id = intval($_POST['category_id']);
    $category_name = sanitize($_POST['category_name']);
    $description = sanitize($_POST['description']);
    $status = sanitize($_POST['status']);

    // Validation
    $errors = [];
    if (empty($category_name)) $errors[] = t('admin.categories.error_name_required');
    if (empty($description)) $errors[] = t('admin.categories.error_description_required');

    if (empty($errors)) {
        try {
            // Check if category name already exists for other categories
            $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE category_name = ? AND category_id != ?");
            $stmt->execute([$category_name, $category_id]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = t('admin.categories.error_exists');
            } else {
                // Update category
                $stmt = $pdo->prepare("
                    UPDATE categories
                    SET category_name = ?, description = ?, status = ?
                    WHERE category_id = ?
                ");

                if ($stmt->execute([$category_name, $description, $status, $category_id])) {
                    logActivity($pdo, $admin_id, 'admin', 'Update Category', "Updated category: $category_name (ID: $category_id)");
                    $_SESSION['success'] = t('admin.categories.success_updated');
                } else {
                    $_SESSION['error'] = t('admin.categories.error_update_failed');
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = t('admin.categories.error_database') . $e->getMessage();
        }
        redirect(SITE_URL . 'admin/categories.php');
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
        redirect(SITE_URL . 'admin/categories.php');
    }
}

// Handle category deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category']) && isset($_POST['category_id'])) {
    $category_id = intval($_POST['category_id']);

    try {
        // Check if category is being used in items
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM (
                SELECT category_id FROM lost_items WHERE category_id = ?
                UNION ALL
                SELECT category_id FROM found_items WHERE category_id = ?
            ) as items
        ");
        $stmt->execute([$category_id, $category_id]);
        $result = $stmt->fetch();

        if ($result['count'] > 0) {
            $_SESSION['error'] = t('admin.categories.error_in_use', ['count' => $result['count']]);
        } else {
            // Delete category
            $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
            if ($stmt->execute([$category_id])) {
                logActivity($pdo, $admin_id, 'admin', 'Delete Category', "Deleted category ID: $category_id");
                $_SESSION['success'] = t('admin.categories.success_deleted');
            } else {
                $_SESSION['error'] = t('admin.categories.error_delete_failed');
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = t('admin.categories.error_cannot_delete');
    }
    redirect(SITE_URL . 'admin/categories.php');
}

// Get all categories
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">
        <i class="fas fa-tags"></i> <?php echo t('admin.categories.title'); ?>
        <span class="text-muted fs-6">(<?php echo t('admin.categories.total_count', ['count' => count($categories)]); ?>)</span>
    </h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="fas fa-plus"></i> <?php echo t('admin.categories.add_category'); ?>
    </button>
</div>

<!-- Display messages -->
<?php
if (isset($_SESSION['success'])) {
    echo showSuccess($_SESSION['success']);
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo showError($_SESSION['error']);
    unset($_SESSION['error']);
}
?>

<?php if (count($categories) > 0): ?>
    <div class="row g-4">
        <?php foreach ($categories as $category): ?>
            <div class="col-md-4 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <i class="fas fa-tag fa-3x text-primary mb-3"></i>
                        <h5 class="fw-bold"><?php echo htmlspecialchars(getLocalizedCategoryName($category['category_name'])); ?></h5>
                        <p class="text-muted small mb-2 flex-grow-1"><?php echo htmlspecialchars($category['description'] ?? t('admin.categories.no_description')); ?></p>
                        <div class="mb-3">
                            <span class="badge <?php echo $category['status'] === 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo $category['status']; ?>
                            </span>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="openEditModal(<?php echo htmlspecialchars(json_encode($category), ENT_QUOTES, 'UTF-8'); ?>)">
                                <i class="fas fa-edit"></i> <?php echo t('admin.categories.edit'); ?>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="confirmDelete(<?php echo $category['category_id']; ?>, '<?php echo addslashes(htmlspecialchars($category['category_name'])); ?>')">
                                <i class="fas fa-trash"></i> <?php echo t('admin.categories.delete'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-tags fa-4x text-muted mb-3"></i>
            <h5 class="text-muted"><?php echo t('admin.categories.no_categories'); ?></h5>
            <p class="text-muted"><?php echo t('admin.categories.no_configured'); ?></p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-plus"></i> <?php echo t('admin.categories.add_first'); ?>
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addCategoryModalLabel">
                    <i class="fas fa-plus"></i> <?php echo t('admin.categories.modal_add_title'); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="category_name" class="form-label"><?php echo t('admin.categories.name_label'); ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label"><?php echo t('admin.categories.description_label'); ?> <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label"><?php echo t('admin.categories.status_label'); ?> <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Active" selected><?php echo t('common.status.active'); ?></option>
                            <option value="Inactive"><?php echo t('common.status.inactive'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> <?php echo t('common.button.cancel'); ?>
                    </button>
                    <button type="submit" name="create_category" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo t('admin.categories.button_create'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> <?php echo t('admin.categories.modal_edit_title'); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label"><?php echo t('admin.categories.name_label'); ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label"><?php echo t('admin.categories.description_label'); ?> <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label"><?php echo t('admin.categories.status_label'); ?> <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="Active"><?php echo t('common.status.active'); ?></option>
                            <option value="Inactive"><?php echo t('common.status.inactive'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> <?php echo t('common.button.cancel'); ?>
                    </button>
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <input type="hidden" name="update_category" value="1">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo t('admin.categories.button_update'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo t('admin.categories.modal_delete_title'); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <p class="mb-0"><?php echo t('admin.categories.delete_confirm'); ?> <strong id="delete_category_name"></strong>?</p>
                    <p class="text-danger small mt-2"><?php echo t('admin.categories.delete_warning'); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> <?php echo t('common.button.cancel'); ?>
                    </button>
                    <input type="hidden" name="category_id" id="delete_category_id">
                    <input type="hidden" name="delete_category" value="1">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> <?php echo t('admin.categories.button_delete'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(category) {
    document.getElementById('edit_category_name').value = category.category_name;
    document.getElementById('edit_description').value = category.description;
    document.getElementById('edit_status').value = category.status;
    document.getElementById('edit_category_id').value = category.category_id;

    var modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}

function confirmDelete(categoryId, categoryName) {
    document.getElementById('delete_category_name').textContent = categoryName;
    document.getElementById('delete_category_id').value = categoryId;

    var modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
    modal.show();
}
</script>

<?php include '../includes/footer.php'; ?>