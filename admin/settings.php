<?php
/**
 * System Settings
 * Admin can manage system settings
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('admin.settings.title');

include '../includes/header.php';
?>

<h2 class="fw-bold mb-4">
    <i class="fas fa-cog"></i> <?php echo t('admin.settings.title'); ?>
</h2>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><?php echo t('admin.settings.site_info'); ?></h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th><?php echo t('admin.settings.site_name'); ?>:</th>
                        <td><?php echo SITE_NAME; ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('admin.settings.site_url'); ?>:</th>
                        <td><?php echo SITE_URL; ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('admin.settings.contact_email'); ?>:</th>
                        <td><?php echo SITE_EMAIL; ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('admin.settings.max_file_size'); ?>:</th>
                        <td><?php echo (MAX_FILE_SIZE / 1048576); ?> MB</td>
                    </tr>
                    <tr>
                        <th><?php echo t('admin.settings.items_per_page'); ?>:</th>
                        <td><?php echo ITEMS_PER_PAGE; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><?php echo t('admin.settings.database_info'); ?></h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th><?php echo t('admin.settings.db_host'); ?>:</th>
                        <td><?php echo DB_HOST; ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('admin.settings.db_name'); ?>:</th>
                        <td><?php echo DB_NAME; ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('admin.settings.db_user'); ?>:</th>
                        <td><?php echo DB_USER; ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('admin.settings.connection_status'); ?>:</th>
                        <td><span class="badge bg-success"><?php echo t('admin.settings.connected'); ?></span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><?php echo t('admin.settings.system_status'); ?></h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle"></i>
                    <strong><?php echo t('admin.settings.note'); ?>:</strong> <?php echo t('admin.settings.config_message'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>