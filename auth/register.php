<?php
/**
 * Registration Page
 * Citizens can register here
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . 'citizen/dashboard.php');
}
if (isPoliceLoggedIn()) {
    redirect(SITE_URL . 'police/dashboard.php');
}
if (isAdminLoggedIn()) {
    redirect(SITE_URL . 'admin/dashboard.php');
}

$page_title = t('auth.register.title');
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($password)) {
        $error = t('validation.required');
    } elseif (!validateEmail($email)) {
        $error = t('validation.email_invalid');
    } elseif (!validatePhone($phone)) {
        $error = t('validation.phone_invalid');
    } elseif (strlen($password) < 6) {
        $error = t('validation.password_short');
    } elseif ($password !== $confirm_password) {
        $error = t('validation.password_mismatch');
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = t('validation.email_exists');
            } else {
                // Insert new user
                $hashed_password = hashPassword($password);
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, phone, address, password, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'Active', NOW())
                ");

                if ($stmt->execute([$name, $email, $phone, $address, $hashed_password])) {
                    $user_id = $pdo->lastInsertId();

                    // Log activity
                    logActivity($pdo, $user_id, 'citizen', 'Registration', 'New user registered');

                    // Send welcome notification
                    sendNotification(
                        $pdo,
                        $user_id,
                        'Citizen',
                        t('auth.register.notification_title'),
                        t('auth.register.notification_message'),
                        'System'
                    );

                    $_SESSION['success'] = t('auth.register.success');
                    redirect(SITE_URL . 'auth/login.php');
                } else {
                    $error = t('auth.register.error_failed');
                }
            }
        } catch (PDOException $e) {
            $error = t('auth.register.error_failed');
        }
    }
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                        <h2 class="fw-bold"><?php echo t('auth.register.title'); ?></h2>
                        <p class="text-muted"><?php echo t('auth.register.subtitle'); ?></p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label"><?php echo t('common.label.name'); ?></label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                   placeholder="<?php echo t('auth.register.name_placeholder'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label"><?php echo t('common.label.email'); ?></label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   placeholder="<?php echo t('auth.register.email_placeholder'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label"><?php echo t('common.label.phone'); ?></label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                   placeholder="<?php echo t('auth.register.phone_placeholder'); ?>" maxlength="10" required>
                            <small class="text-muted"><?php echo t('auth.register.phone_help'); ?></small>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label"><?php echo t('common.label.address'); ?></label>
                            <textarea class="form-control" id="address" name="address" rows="2"
                                      placeholder="<?php echo t('auth.register.address_placeholder'); ?>" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label"><?php echo t('common.label.password'); ?></label>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="<?php echo t('auth.register.password_placeholder'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label"><?php echo t('auth.register.confirm_password'); ?></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   placeholder="<?php echo t('auth.register.confirm_password_placeholder'); ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                            <i class="fas fa-user-plus"></i> <?php echo t('auth.register.button'); ?>
                        </button>

                        <div class="text-center">
                            <p class="text-muted mb-0">
                                <?php echo t('auth.register.have_account'); ?>
                                <a href="<?php echo SITE_URL; ?>auth/login.php" class="text-primary fw-bold"><?php echo t('auth.register.login_link'); ?></a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>