<?php
/**
 * Upload Custody Item
 * Police can upload items in their custody
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if police is logged in
if (!isPoliceLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('police.upload_custody.page_title');
$police_id = $_SESSION['user_id'];
$error = '';

// Get categories and locations
try {
    $categories = $pdo->query("SELECT * FROM categories WHERE status = 'Active' ORDER BY category_name")->fetchAll();
    $locations = $pdo->query("SELECT * FROM locations WHERE status = 'Active' ORDER BY location_name")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    $locations = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = sanitize($_POST['item_name'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');
    $found_date = sanitize($_POST['found_date'] ?? '');
    $location_id = intval($_POST['location_id'] ?? 0);
    $custody_reference = sanitize($_POST['custody_reference'] ?? '');

    // Validation
    if (empty($item_name) || empty($description) || empty($found_date) || $category_id === 0 || $location_id === 0) {
        $error = t('police.upload_custody.validation_error');
    } else {
        // Handle file upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = uploadFile($_FILES['image'], UPLOAD_PATH . 'found/');
            if ($upload_result['success']) {
                $image_path = $upload_result['filename'];
            } else {
                $error = $upload_result['message'];
            }
        }

        if (empty($error)) {
            try {
                // Insert as found item with police as the reporter
                $stmt = $pdo->prepare("
                    INSERT INTO found_items (user_id, police_id, category_id, item_name, description, found_date, location_id, image_path, contact_info, status, created_at)
                    VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, 'Approved', NOW())
                ");

                $contact_info = "Police Custody - Ref: $custody_reference";

                if ($stmt->execute([$police_id, $category_id, $item_name, $description, $found_date, $location_id, $image_path, $contact_info])) {
                    $found_item_id = $pdo->lastInsertId();

                    // Log activity
                    logActivity($pdo, $police_id, 'police', 'Upload Custody Item', "Uploaded custody item: $item_name");

                    $_SESSION['success'] = t('police.upload_custody.upload_success');
                    redirect(SITE_URL . 'police/custody-items.php');
                } else {
                    $error = t('police.upload_custody.upload_failed');
                }
            } catch (PDOException $e) {
                $error = t('police.upload_custody.upload_failed');
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-upload"></i> <?php echo t('police.upload_custody.title'); ?>
                </h4>
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-4">
                    <?php echo t('police.upload_custody.description'); ?>
                </p>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="item_name" class="form-label"><?php echo t('police.upload_custody.item_name'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="item_name" name="item_name"
                                   value="<?php echo htmlspecialchars($_POST['item_name'] ?? ''); ?>"
                                   placeholder="<?php echo t('police.upload_custody.item_name_placeholder'); ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label"><?php echo t('police.upload_custody.category'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value=""><?php echo t('police.upload_custody.select_category'); ?></option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>"
                                        <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo getLocalizedCategoryName($category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 mb-3">
                            <label for="description" class="form-label"><?php echo t('police.upload_custody.description'); ?> <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="4"
                                      placeholder="<?php echo t('police.upload_custody.description_placeholder'); ?>" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            <small class="text-muted"><?php echo t('police.upload_custody.description_hint'); ?></small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="found_date" class="form-label"><?php echo t('police.upload_custody.found_date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="found_date" name="found_date"
                                   value="<?php echo htmlspecialchars($_POST['found_date'] ?? ''); ?>"
                                   max="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="location_id" class="form-label"><?php echo t('police.upload_custody.location'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" id="location_id" name="location_id" required>
                                <option value=""><?php echo t('police.upload_custody.select_location'); ?></option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo $location['location_id']; ?>"
                                        <?php echo (isset($_POST['location_id']) && $_POST['location_id'] == $location['location_id']) ? 'selected' : ''; ?>>
                                        <?php echo getLocalizedLocationName($location['location_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="image" class="form-label"><?php echo t('police.upload_custody.upload_image'); ?></label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <small class="text-muted"><?php echo t('police.upload_custody.image_hint'); ?></small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="custody_reference" class="form-label"><?php echo t('police.upload_custody.custody_reference'); ?></label>
                            <input type="text" class="form-control" id="custody_reference" name="custody_reference"
                                   value="<?php echo htmlspecialchars($_POST['custody_reference'] ?? ''); ?>"
                                   placeholder="<?php echo t('police.upload_custody.custody_reference_placeholder'); ?>">
                            <small class="text-muted"><?php echo t('police.upload_custody.custody_reference_hint'); ?></small>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong><?php echo t('police.upload_custody.note'); ?>:</strong> <?php echo t('police.upload_custody.note_message'); ?>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?php echo SITE_URL; ?>police/dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> <?php echo t('police.upload_custody.cancel'); ?>
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload"></i> <?php echo t('police.upload_custody.upload_item'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>