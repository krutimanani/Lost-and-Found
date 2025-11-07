<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo t('common.brand'); ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-hands-helping"></i> <?php echo t('common.brand'); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <!-- Citizen Menu -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>citizen/dashboard.php">
                                <i class="fas fa-home"></i> <?php echo t('common.nav.dashboard'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>citizen/report-lost.php">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo t('common.nav.report_lost'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>citizen/report-found.php">
                                <i class="fas fa-check-circle"></i> <?php echo t('common.nav.report_found'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>citizen/search.php">
                                <i class="fas fa-search"></i> <?php echo t('common.nav.search'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>citizen/notifications.php">
                                <i class="fas fa-bell"></i> <?php echo t('common.nav.notifications'); ?>
                                <?php
                                $unread = getUnreadNotificationsCount($pdo);
                                if ($unread > 0):
                                ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $unread; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo getUserName(); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>citizen/my-reports.php"><i class="fas fa-file-alt"></i> <?php echo t('common.nav.my_reports'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>citizen/my-claims.php"><i class="fas fa-hand-holding"></i> <?php echo t('common.nav.my_claims'); ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>auth/logout.php"><i class="fas fa-sign-out-alt"></i> <?php echo t('common.nav.logout'); ?></a></li>
                            </ul>
                        </li>
                    <?php elseif (isPoliceLoggedIn()): ?>
                        <!-- Police Menu -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>police/dashboard.php">
                                <i class="fas fa-home"></i> <?php echo t('common.nav.dashboard'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>police/view-reports.php">
                                <i class="fas fa-list"></i> <?php echo t('common.nav.view_reports'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>police/review-claims.php">
                                <i class="fas fa-clipboard-check"></i> <?php echo t('common.nav.review_claims'); ?>
                                <?php
                                try {
                                    $pending_claims_stmt = $pdo->query("SELECT COUNT(*) as count FROM item_claims WHERE status = 'Pending'");
                                    $pending_claims = $pending_claims_stmt->fetch()['count'];
                                    if ($pending_claims > 0):
                                ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $pending_claims; ?>
                                    </span>
                                <?php
                                    endif;
                                } catch (PDOException $e) {
                                    // Ignore error
                                }
                                ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>police/upload-custody-item.php">
                                <i class="fas fa-upload"></i> <?php echo t('common.nav.upload_item'); ?>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="policeDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-shield"></i> <?php echo getUserName(); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>police/custody-items.php"><i class="fas fa-box"></i> <?php echo t('common.nav.custody_items'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>police/notifications.php"><i class="fas fa-bell"></i> <?php echo t('common.nav.notifications'); ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>auth/logout.php"><i class="fas fa-sign-out-alt"></i> <?php echo t('common.nav.logout'); ?></a></li>
                            </ul>
                        </li>
                    <?php elseif (isAdminLoggedIn()): ?>
                        <!-- Admin Menu -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>admin/dashboard.php">
                                <i class="fas fa-home"></i> <?php echo t('common.nav.dashboard'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>admin/approve-lost.php">
                                <i class="fas fa-check"></i> <?php echo t('common.nav.approve_reports'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>admin/users.php">
                                <i class="fas fa-users"></i> <?php echo t('common.nav.users'); ?>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-cog"></i> <?php echo getUserName(); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/police-management.php"><i class="fas fa-user-shield"></i> <?php echo t('common.nav.police_management'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/categories.php"><i class="fas fa-tags"></i> <?php echo t('common.nav.categories'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/settings.php"><i class="fas fa-cog"></i> <?php echo t('common.nav.settings'); ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>auth/logout.php"><i class="fas fa-sign-out-alt"></i> <?php echo t('common.nav.logout'); ?></a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Guest Menu -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>"><?php echo t('common.nav.home'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>auth/login.php"><?php echo t('common.nav.login'); ?></a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="btn btn-light text-primary px-3 py-2" href="<?php echo SITE_URL; ?>auth/register.php">
                                <i class="fas fa-user-plus"></i> <?php echo t('common.nav.register'); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Language Switcher (visible to all users) -->
                    <li class="nav-item dropdown ms-2">
                        <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-globe"></i> <?php echo getSupportedLanguages()[getCurrentLanguage()]; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                            <?php
                            // Build language switch URLs preserving existing parameters
                            $current_params = $_GET;
                            $current_params['lang'] = 'en';
                            $en_url = '?' . http_build_query($current_params);

                            $current_params['lang'] = 'hi';
                            $hi_url = '?' . http_build_query($current_params);

                            $current_params['lang'] = 'gu';
                            $gu_url = '?' . http_build_query($current_params);
                            ?>
                            <li><a class="dropdown-item <?php echo getCurrentLanguage() == 'en' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($en_url); ?>"><i class="fas fa-check <?php echo getCurrentLanguage() == 'en' ? '' : 'invisible'; ?>"></i> English</a></li>
                            <li><a class="dropdown-item <?php echo getCurrentLanguage() == 'hi' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($hi_url); ?>"><i class="fas fa-check <?php echo getCurrentLanguage() == 'hi' ? '' : 'invisible'; ?>"></i> हिंदी</a></li>
                            <li><a class="dropdown-item <?php echo getCurrentLanguage() == 'gu' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($gu_url); ?>"><i class="fas fa-check <?php echo getCurrentLanguage() == 'gu' ? '' : 'invisible'; ?>"></i> ગુજરાતી</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-4">
        <div class="container">
            <?php
            // Display flash messages
            if (isset($_SESSION['success'])) {
                echo showSuccess($_SESSION['success']);
                unset($_SESSION['success']);
            }
            if (isset($_SESSION['error'])) {
                echo showError($_SESSION['error']);
                unset($_SESSION['error']);
            }
            if (isset($_SESSION['info'])) {
                echo showInfo($_SESSION['info']);
                unset($_SESSION['info']);
            }
            ?>