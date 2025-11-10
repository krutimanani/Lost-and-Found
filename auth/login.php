<?php
/**
 * Login Page
 * Allows users, police, and admins to login
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

$page_title = t('auth.login.title');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user_type = sanitize($_POST['user_type'] ?? 'citizen');

    // Validation
    if (empty($email) || empty($password)) {
        $error = t('validation.required');
    } elseif (!validateEmail($email)) {
        $error = t('validation.email_invalid');
    } else {
        try {
            // Determine which table to query
            $table = '';
            $redirect_url = '';

            switch ($user_type) {
                case 'citizen':
                    $table = 'users';
                    $redirect_url = 'citizen/dashboard.php';
                    break;
                case 'police':
                    $table = 'police';
                    $redirect_url = 'police/dashboard.php';
                    break;
                case 'admin':
                    $table = 'admins';
                    $redirect_url = 'admin/dashboard.php';
                    break;
                default:
                    $error = t('auth.login.error_invalid_type');
            }

            if (empty($error)) {
                // Get user from appropriate table
                if ($user_type === 'citizen') {
                    $stmt = $pdo->prepare("SELECT user_id as id, name, email, password, status FROM users WHERE email = ?");
                } elseif ($user_type === 'police') {
                    $stmt = $pdo->prepare("SELECT police_id as id, name, email, password, status FROM police WHERE email = ?");
                } else {
                    $stmt = $pdo->prepare("SELECT admin_id as id, name, email, password, status FROM admins WHERE email = ?");
                }

                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && verifyPassword($password, $user['password'])) {
                    // Check if account is active
                    if ($user['status'] !== 'Active') {
                        $error = t('auth.login.error_account_inactive');
                    } else {
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_type'] = $user_type;

                        // Load user's language preference
                        loadUserLanguagePreference();

                        // Log activity
                        logActivity($pdo, $user['id'], $user_type, 'Login', 'User logged in successfully');

                        // Redirect
                        $_SESSION['success'] = t('auth.login.success_welcome', ['name' => $user['name']]);
                        redirect(SITE_URL . $redirect_url);
                    }
                } else {
                    $error = t('auth.login.error_invalid_credentials');
                }
            }
        } catch (PDOException $e) {
            $error = t('auth.login.error_login_failed');
        }
    }
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-circle fa-3x text-primary mb-3"></i>
                        <h2 class="fw-bold"><?php echo t('auth.login.title'); ?></h2>
                        <p class="text-muted"><?php echo t('auth.login.subtitle'); ?></p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="user_type" class="form-label"><?php echo t('auth.login.user_type'); ?></label>
                            <select class="form-select" id="user_type" name="user_type" required>
                                <option value="citizen" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'citizen') ? 'selected' : ''; ?>><?php echo t('auth.login.citizen'); ?></option>
                                <option value="police" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'police') ? 'selected' : ''; ?>><?php echo t('auth.login.police'); ?></option>
                                <option value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'admin') ? 'selected' : ''; ?>><?php echo t('auth.login.admin'); ?></option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label"><?php echo t('common.label.email'); ?></label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   placeholder="<?php echo t('auth.login.email_placeholder'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label"><?php echo t('common.label.password'); ?></label>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="<?php echo t('auth.login.password_placeholder'); ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                            <i class="fas fa-sign-in-alt"></i> <?php echo t('auth.login.button'); ?>
                        </button>

                        <div class="text-center">
                            <p class="text-muted mb-0">
                                <?php echo t('auth.login.no_account'); ?>
                                <a href="<?php echo SITE_URL; ?>auth/register.php" class="text-primary fw-bold"><?php echo t('auth.login.register_link'); ?></a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Demo Credentials Info -->
            <div class="card mt-3 border-info">
                <div class="card-body">
                    <h6 class="fw-bold text-info mb-2">
                        <i class="fas fa-info-circle"></i> <?php echo t('auth.login.demo_credentials'); ?>
                    </h6>
                    <small class="text-muted">
                        <strong><?php echo t('auth.login.demo_citizen'); ?>:</strong> citizen@demo.com / password<br>
                        <strong><?php echo t('auth.login.demo_police'); ?>:</strong> police@demo.com / password<br>
                        <strong><?php echo t('auth.login.demo_admin'); ?>:</strong> admin@demo.com / password
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>