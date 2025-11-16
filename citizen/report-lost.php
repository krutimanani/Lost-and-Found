<?php
/**
 * Report Lost Item
 * Form to report a lost item
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('citizen.report_lost.page_title');
$user_id = $_SESSION['user_id'];
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
    $lost_date = sanitize($_POST['lost_date'] ?? '');
    $location_id = intval($_POST['location_id'] ?? 0);
    $contact_info = sanitize($_POST['contact_info'] ?? '');

    // Validation
    if (empty($item_name) || empty($description) || empty($lost_date) || $category_id === 0 || $location_id === 0) {
        $error = t('citizen.report_lost.error_required_fields');
    } else {
        // Handle file upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = uploadFile($_FILES['image'], UPLOAD_PATH . 'lost/');
            if ($upload_result['success']) {
                $image_path = $upload_result['filename'];
            } else {
                $error = $upload_result['message'];
            }
        }

        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO lost_items (user_id, category_id, item_name, description, lost_date, location_id, image_path, contact_info, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
                ");

                if ($stmt->execute([$user_id, $category_id, $item_name, $description, $lost_date, $location_id, $image_path, $contact_info])) {
                    $lost_item_id = $pdo->lastInsertId();

                    // Log activity
                    logActivity($pdo, $user_id, 'citizen', 'Report Lost', "Reported lost item: $item_name");

                    // Send notification
                    sendNotification(
                        $pdo,
                        $user_id,
                        'Citizen',
                        t('citizen.report_lost.notification_title'),
                        t('citizen.report_lost.notification_message', ['item_name' => $item_name]),
                        'Report'
                    );

                    $_SESSION['success'] = t('citizen.report_lost.success_message');
                    redirect(SITE_URL . 'citizen/my-reports.php?tab=lost');
                } else {
                    $error = t('citizen.report_lost.error_submit_failed');
                }
            } catch (PDOException $e) {
                $error = t('citizen.report_lost.error_submit_failed');
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo t('citizen.report_lost.form_title'); ?>
                </h4>
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-4">
                    <?php echo t('citizen.report_lost.form_description'); ?>
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
                            <label for="item_name" class="form-label"><?php echo t('citizen.report_lost.label_item_name'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="item_name" name="item_name"
                                   value="<?php echo htmlspecialchars($_POST['item_name'] ?? ''); ?>"
                                   placeholder="<?php echo t('citizen.report_lost.placeholder_item_name'); ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label"><?php echo t('citizen.report_lost.label_category'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value=""><?php echo t('citizen.report_lost.select_category'); ?></option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>"
                                        <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(getLocalizedCategoryName($category)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 mb-3">
                            <label for="description" class="form-label"><?php echo t('citizen.report_lost.label_description'); ?> <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="4"
                                      placeholder="<?php echo t('citizen.report_lost.placeholder_description'); ?>" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            <small class="text-muted"><?php echo t('citizen.report_lost.description_hint'); ?></small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="lost_date" class="form-label"><?php echo t('citizen.report_lost.label_lost_date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="lost_date" name="lost_date"
                                   value="<?php echo htmlspecialchars($_POST['lost_date'] ?? ''); ?>"
                                   max="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="location_id" class="form-label"><?php echo t('citizen.report_lost.label_location'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" id="location_id" name="location_id" required>
                                <option value=""><?php echo t('citizen.report_lost.select_location'); ?></option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo $location['location_id']; ?>"
                                        <?php echo (isset($_POST['location_id']) && $_POST['location_id'] == $location['location_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(getLocalizedLocationName($location)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="image" class="form-label"><?php echo t('citizen.report_lost.label_upload_image'); ?></label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <small class="text-muted"><?php echo t('citizen.report_lost.image_hint'); ?></small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="contact_info" class="form-label"><?php echo t('citizen.report_lost.label_additional_contact'); ?></label>
                            <input type="text" class="form-control" id="contact_info" name="contact_info"
                                   value="<?php echo htmlspecialchars($_POST['contact_info'] ?? ''); ?>"
                                   placeholder="<?php echo t('citizen.report_lost.placeholder_contact'); ?>">
                        </div>

                        <div class="col-12">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?php echo SITE_URL; ?>citizen/dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> <?php echo t('citizen.report_lost.btn_cancel'); ?>
                                </a>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-paper-plane"></i> <?php echo t('citizen.report_lost.btn_submit'); ?>
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