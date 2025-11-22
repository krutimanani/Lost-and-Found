<?php
/**
 * Police Management
 * Admin can manage police officers
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('admin.police.title');
$admin_id = $_SESSION['user_id'];

// Handle police account creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_police'])) {
    $name = sanitize($_POST['name']);
    $badge_number = sanitize($_POST['badge_number']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $phone = sanitize($_POST['phone']);
    $station_id = intval($_POST['station_id']);
    $police_rank = sanitize($_POST['police_rank']);
    $status = sanitize($_POST['status']);

    // Validation
    $errors = [];
    if (empty($name)) $errors[] = t('admin.police.error_name_required');
    if (empty($badge_number)) $errors[] = t('admin.police.error_badge_required');
    if (empty($email) || !validateEmail($email)) $errors[] = t('admin.police.error_email_required');
    if (empty($password) || strlen($password) < 6) $errors[] = t('admin.police.error_password_length');
    if (empty($phone) || !validatePhone($phone)) $errors[] = t('admin.police.error_phone_required');
    if ($station_id <= 0) $errors[] = t('admin.police.error_station_required');
    if (empty($police_rank)) $errors[] = t('admin.police.error_rank_required');

    if (empty($errors)) {
        try {
            // Check if badge number or email already exists
            $stmt = $pdo->prepare("SELECT police_id FROM police WHERE badge_number = ? OR email = ?");
            $stmt->execute([$badge_number, $email]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = t('admin.police.error_exists');
            } else {
                // Create police account
                $hashed_password = hashPassword($password);
                $stmt = $pdo->prepare("
                    INSERT INTO police (name, badge_number, email, password, phone, station_id, police_rank, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");

                if ($stmt->execute([$name, $badge_number, $email, $hashed_password, $phone, $station_id, $police_rank, $status])) {
                    $new_police_id = $pdo->lastInsertId();
                    logActivity($pdo, $admin_id, 'admin', 'Create Police Account', "Created police account: $name (Badge: $badge_number)");
                    $_SESSION['success'] = t('admin.police.success_created');
                } else {
                    $_SESSION['error'] = t('admin.police.error_create_failed');
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = t('admin.police.error_database') . $e->getMessage();
        }
        redirect(SITE_URL . 'admin/police-management.php');
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

// Handle police officer update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_police']) && isset($_POST['police_id'])) {
    $police_id = intval($_POST['police_id']);
    $name = sanitize($_POST['name']);
    $badge_number = sanitize($_POST['badge_number']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'] ?? '';
    $phone = sanitize($_POST['phone']);
    $station_id = intval($_POST['station_id']);
    $police_rank = sanitize($_POST['police_rank']);
    $status = sanitize($_POST['status']);

    // Validation
    $errors = [];
    if (empty($name)) $errors[] = t('admin.police.error_name_required');
    if (empty($badge_number)) $errors[] = t('admin.police.error_badge_required');
    if (empty($email) || !validateEmail($email)) $errors[] = t('admin.police.error_email_required');
    if (!empty($password) && strlen($password) < 6) $errors[] = t('admin.police.error_password_length');
    if (empty($phone) || !validatePhone($phone)) $errors[] = t('admin.police.error_phone_required');
    if ($station_id <= 0) $errors[] = t('admin.police.error_station_required');
    if (empty($police_rank)) $errors[] = t('admin.police.error_rank_required');

    if (empty($errors)) {
        try {
            // Check if badge number or email already exists for other officers
            $stmt = $pdo->prepare("SELECT police_id FROM police WHERE (badge_number = ? OR email = ?) AND police_id != ?");
            $stmt->execute([$badge_number, $email, $police_id]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = t('admin.police.error_exists_other');
            } else {
                // Update police officer
                if (!empty($password)) {
                    // Update with new password
                    $hashed_password = hashPassword($password);
                    $stmt = $pdo->prepare("
                        UPDATE police
                        SET name = ?, badge_number = ?, email = ?, password = ?, phone = ?,
                            station_id = ?, police_rank = ?, status = ?
                        WHERE police_id = ?
                    ");
                    $result = $stmt->execute([$name, $badge_number, $email, $hashed_password, $phone, $station_id, $police_rank, $status, $police_id]);
                } else {
                    // Update without changing password
                    $stmt = $pdo->prepare("
                        UPDATE police
                        SET name = ?, badge_number = ?, email = ?, phone = ?,
                            station_id = ?, police_rank = ?, status = ?
                        WHERE police_id = ?
                    ");
                    $result = $stmt->execute([$name, $badge_number, $email, $phone, $station_id, $police_rank, $status, $police_id]);
                }

                if ($result) {
                    logActivity($pdo, $admin_id, 'admin', 'Update Police Account', "Updated police account: $name (Badge: $badge_number, ID: $police_id)");
                    $_SESSION['success'] = t('admin.police.success_updated');
                } else {
                    $_SESSION['error'] = t('admin.police.error_update_failed');
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = t('admin.police.error_database') . $e->getMessage();
        }
        redirect(SITE_URL . 'admin/police-management.php');
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
        redirect(SITE_URL . 'admin/police-management.php');
    }
}

// Get all police stations for dropdown
try {
    $police_stations = $pdo->query("SELECT station_id, station_name FROM police_stations ORDER BY station_name")->fetchAll();
} catch (PDOException $e) {
    $police_stations = [];
}

// Get all police officers
try {
    $police_officers = $pdo->query("
        SELECT p.*, ps.station_name
        FROM police p
        LEFT JOIN police_stations ps ON p.station_id = ps.station_id
        ORDER BY p.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $police_officers = [];
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">
        <i class="fas fa-user-shield"></i> <?php echo t('admin.police.title'); ?>
        <span class="text-muted fs-6">(<?php echo t('admin.police.officer_count', ['count' => count($police_officers)]); ?>)</span>
    </h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPoliceModal">
        <i class="fas fa-plus"></i> <?php echo t('admin.police.add_officer'); ?>
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

<!-- Add Police Officer Modal -->
<div class="modal fade" id="addPoliceModal" tabindex="-1" aria-labelledby="addPoliceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addPoliceModalLabel">
                    <i class="fas fa-user-plus"></i> <?php echo t('admin.police.modal_add_title'); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label"><?php echo t('admin.police.full_name'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="badge_number" class="form-label"><?php echo t('admin.police.badge_number_label'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="badge_number" name="badge_number" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label"><?php echo t('admin.police.email'); ?> <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label"><?php echo t('admin.police.phone_label'); ?> <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="<?php echo t('admin.police.phone_placeholder'); ?>" pattern="[0-9]{10}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label"><?php echo t('admin.police.password_label'); ?> <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                            <small class="text-muted"><?php echo t('admin.police.password_help'); ?></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="police_rank" class="form-label"><?php echo t('admin.police.rank_label'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="police_rank" name="police_rank" placeholder="<?php echo t('admin.police.rank_placeholder'); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="station_id" class="form-label"><?php echo t('admin.police.station_label'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" id="station_id" name="station_id" required>
                                <option value=""><?php echo t('admin.police.select_station'); ?></option>
                                <?php foreach ($police_stations as $station): ?>
                                    <option value="<?php echo $station['station_id']; ?>">
                                        <?php echo htmlspecialchars($station['station_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label"><?php echo t('admin.police.status_label'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Active" selected><?php echo t('common.status.active'); ?></option>
                                <option value="Inactive"><?php echo t('common.status.inactive'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> <?php echo t('common.button.cancel'); ?>
                    </button>
                    <button type="submit" name="create_police" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo t('admin.police.button_create'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (count($police_officers) > 0): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo t('admin.police.id'); ?></th>
                            <th><?php echo t('admin.police.name'); ?></th>
                            <th><?php echo t('admin.police.email'); ?></th>
                            <th><?php echo t('admin.police.phone'); ?></th>
                            <th><?php echo t('admin.police.badge_number'); ?></th>
                            <th><?php echo t('admin.police.rank'); ?></th>
                            <th><?php echo t('admin.police.station'); ?></th>
                            <th><?php echo t('admin.police.status'); ?></th>
                            <th><?php echo t('admin.police.joined'); ?></th>
                            <th><?php echo t('admin.police.actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($police_officers as $officer): ?>
                            <tr>
                                <td><?php echo $officer['police_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($officer['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($officer['email']); ?></td>
                                <td><?php echo htmlspecialchars($officer['phone']); ?></td>
                                <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($officer['badge_number']); ?></span></td>
                                <td><?php echo htmlspecialchars($officer['police_rank'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($officer['station_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php echo $officer['status'] === 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $officer['status']; ?>
                                    </span>
                                </td>
                                <td><small class="text-muted"><?php echo formatDate($officer['created_at'], 'd M Y'); ?></small></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="openPoliceModal(<?php echo htmlspecialchars(json_encode($officer), ENT_QUOTES, 'UTF-8'); ?>)">
                                        <i class="fas fa-edit"></i> <?php echo t('admin.police.edit'); ?>
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
            <i class="fas fa-user-shield fa-4x text-muted mb-3"></i>
            <h5 class="text-muted"><?php echo t('admin.police.no_officers'); ?></h5>
            <p class="text-muted"><?php echo t('admin.police.no_registered'); ?></p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPoliceModal">
                <i class="fas fa-plus"></i> <?php echo t('admin.police.add_first'); ?>
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- Edit Police Officer Modal -->
<div class="modal fade" id="editPoliceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-edit"></i> <?php echo t('admin.police.modal_edit_title'); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_name" class="form-label"><?php echo t('admin.police.full_name'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_badge_number" class="form-label"><?php echo t('admin.police.badge_number_label'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_badge_number" name="badge_number" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_email" class="form-label"><?php echo t('admin.police.email'); ?> <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_phone" class="form-label"><?php echo t('admin.police.phone_label'); ?> <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone" placeholder="<?php echo t('admin.police.phone_placeholder'); ?>" pattern="[0-9]{10}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_password" class="form-label"><?php echo t('admin.police.new_password_label'); ?></label>
                            <input type="password" class="form-control" id="edit_password" name="password" minlength="6">
                            <small class="text-muted"><?php echo t('admin.police.password_keep'); ?></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_police_rank" class="form-label"><?php echo t('admin.police.rank_label'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_police_rank" name="police_rank" placeholder="<?php echo t('admin.police.rank_placeholder'); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_station_id" class="form-label"><?php echo t('admin.police.station_label'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_station_id" name="station_id" required>
                                <option value=""><?php echo t('admin.police.select_station'); ?></option>
                                <?php foreach ($police_stations as $station): ?>
                                    <option value="<?php echo $station['station_id']; ?>">
                                        <?php echo htmlspecialchars($station['station_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_status" class="form-label"><?php echo t('admin.police.status_label'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="Active"><?php echo t('common.status.active'); ?></option>
                                <option value="Inactive"><?php echo t('common.status.inactive'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> <?php echo t('common.button.cancel'); ?>
                    </button>
                    <input type="hidden" name="police_id" id="edit_police_id">
                    <input type="hidden" name="update_police" value="1">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo t('admin.police.button_update'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openPoliceModal(officer) {
    // Populate form fields with current data
    document.getElementById('edit_name').value = officer.name;
    document.getElementById('edit_badge_number').value = officer.badge_number;
    document.getElementById('edit_email').value = officer.email;
    document.getElementById('edit_phone').value = officer.phone;
    document.getElementById('edit_police_rank').value = officer.police_rank || '';
    document.getElementById('edit_station_id').value = officer.station_id;
    document.getElementById('edit_status').value = officer.status;
    document.getElementById('edit_police_id').value = officer.police_id;
    document.getElementById('edit_password').value = ''; // Clear password field

    // Open the modal
    var modal = new bootstrap.Modal(document.getElementById('editPoliceModal'));
    modal.show();
}
</script>

<?php include '../includes/footer.php'; ?>