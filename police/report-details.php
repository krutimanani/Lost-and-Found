<?php
/**
 * Report Details (Police)
 * Police can view report details and create matches
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if police is logged in
if (!isPoliceLoggedIn()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = t('police.report_details.page_title');
$police_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (!in_array($type, ['lost', 'found']) || $id === 0) {
    $_SESSION['error'] = t('police.report_details.invalid_request');
    redirect(SITE_URL . 'police/view-reports.php');
}

// Get item details
try {
    if ($type === 'lost') {
        $stmt = $pdo->prepare("
            SELECT l.*, c.category_name, loc.location_name,
                   COALESCE(p.name, u.name) as reporter_name,
                   COALESCE(p.email, u.email) as reporter_email,
                   COALESCE(p.phone, u.phone) as reporter_phone,
                   COALESCE(ps.station_address, u.address) as address,
                   CASE WHEN l.police_id IS NOT NULL THEN 'police' ELSE 'citizen' END as reporter_type,
                   p.badge_number, ps.station_name, ps.station_address, ps.contact_no as station_contact
            FROM lost_items l
            LEFT JOIN categories c ON l.category_id = c.category_id
            LEFT JOIN locations loc ON l.location_id = loc.location_id
            LEFT JOIN users u ON l.user_id = u.user_id
            LEFT JOIN police p ON l.police_id = p.police_id
            LEFT JOIN police_stations ps ON p.station_id = ps.station_id
            WHERE l.lost_item_id = ? AND l.status = 'Approved'
        ");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT f.*, c.category_name, loc.location_name,
                   COALESCE(p.name, u.name) as reporter_name,
                   COALESCE(p.email, u.email) as reporter_email,
                   COALESCE(p.phone, u.phone) as reporter_phone,
                   COALESCE(ps.station_address, u.address) as address,
                   CASE WHEN f.police_id IS NOT NULL THEN 'police' ELSE 'citizen' END as reporter_type,
                   p.badge_number, ps.station_name, ps.station_address, ps.contact_no as station_contact
            FROM found_items f
            LEFT JOIN categories c ON f.category_id = c.category_id
            LEFT JOIN locations loc ON f.location_id = loc.location_id
            LEFT JOIN users u ON f.user_id = u.user_id
            LEFT JOIN police p ON f.police_id = p.police_id
            LEFT JOIN police_stations ps ON p.station_id = ps.station_id
            WHERE f.found_item_id = ? AND f.status = 'Approved'
        ");
        $stmt->execute([$id]);
    }

    $item = $stmt->fetch();

    if (!$item) {
        $_SESSION['error'] = t('police.report_details.item_not_found');
        redirect(SITE_URL . 'police/view-reports.php');
    }
} catch (PDOException $e) {
    $_SESSION['error'] = t('police.report_details.failed_to_load');
    redirect(SITE_URL . 'police/view-reports.php');
}

// Handle match creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_match'])) {
    $match_id = intval($_POST['match_id'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');

    if ($match_id > 0) {
        try {
            // Check if match already exists
            if ($type === 'lost') {
                $check_stmt = $pdo->prepare("SELECT match_id FROM matched_reports WHERE lost_item_id = ? AND found_item_id = ?");
                $check_stmt->execute([$id, $match_id]);
            } else {
                $check_stmt = $pdo->prepare("SELECT match_id FROM matched_reports WHERE lost_item_id = ? AND found_item_id = ?");
                $check_stmt->execute([$match_id, $id]);
            }

            if ($check_stmt->fetch()) {
                $_SESSION['error'] = t('police.report_details.match_exists');
            } else {
                // Create match
                if ($type === 'lost') {
                    $insert_stmt = $pdo->prepare("
                        INSERT INTO matched_reports (lost_item_id, found_item_id, matched_by_police, status, notes, matched_at)
                        VALUES (?, ?, ?, 'Matched', ?, NOW())
                    ");
                    $insert_stmt->execute([$id, $match_id, $police_id, $notes]);
                    $lost_id = $id;
                    $found_id = $match_id;
                } else {
                    $insert_stmt = $pdo->prepare("
                        INSERT INTO matched_reports (lost_item_id, found_item_id, matched_by_police, status, notes, matched_at)
                        VALUES (?, ?, ?, 'Matched', ?, NOW())
                    ");
                    $insert_stmt->execute([$match_id, $id, $police_id, $notes]);
                    $lost_id = $match_id;
                    $found_id = $id;
                }

                // Get reporter IDs for notifications
                $lost_user = $pdo->prepare("SELECT user_id FROM lost_items WHERE lost_item_id = ?");
                $lost_user->execute([$lost_id]);
                $lost_reporter = $lost_user->fetchColumn();

                $found_user = $pdo->prepare("SELECT user_id FROM found_items WHERE found_item_id = ?");
                $found_user->execute([$found_id]);
                $found_reporter = $found_user->fetchColumn();

                // Send notifications
                if ($lost_reporter) {
                    sendNotification($pdo, $lost_reporter, 'Citizen', t('police.report_details.match_found_title'), t('police.report_details.match_found_lost_message'), 'Match');
                }
                if ($found_reporter) {
                    sendNotification($pdo, $found_reporter, 'Citizen', t('police.report_details.match_found_title'), t('police.report_details.match_found_found_message'), 'Match');
                }

                logActivity($pdo, $police_id, 'police', 'Create Match', "Created match between lost item #$lost_id and found item #$found_id");

                $_SESSION['success'] = t('police.report_details.match_created');
                redirect(SITE_URL . 'police/report-details.php?type=' . $type . '&id=' . $id);
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = t('police.report_details.match_failed');
        }
    }
}

// Get potential matches
$potential_matches = [];
try {
    if ($type === 'lost') {
        $match_stmt = $pdo->prepare("
            SELECT f.*, c.category_name, loc.location_name
            FROM found_items f
            LEFT JOIN categories c ON f.category_id = c.category_id
            LEFT JOIN locations loc ON f.location_id = loc.location_id
            WHERE f.status = 'Approved'
              AND f.category_id = ?
              AND f.found_item_id NOT IN (SELECT found_item_id FROM matched_reports WHERE lost_item_id = ?)
            ORDER BY f.found_date DESC
            LIMIT 10
        ");
        $match_stmt->execute([$item['category_id'], $id]);
    } else {
        $match_stmt = $pdo->prepare("
            SELECT l.*, c.category_name, loc.location_name
            FROM lost_items l
            LEFT JOIN categories c ON l.category_id = c.category_id
            LEFT JOIN locations loc ON l.location_id = loc.location_id
            WHERE l.status = 'Approved'
              AND l.category_id = ?
              AND l.lost_item_id NOT IN (SELECT lost_item_id FROM matched_reports WHERE found_item_id = ?)
            ORDER BY l.lost_date DESC
            LIMIT 10
        ");
        $match_stmt->execute([$item['category_id'], $id]);
    }
    $potential_matches = $match_stmt->fetchAll();
} catch (PDOException $e) {
    $potential_matches = [];
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header <?php echo $type === 'lost' ? 'bg-danger' : 'bg-success'; ?> text-white">
                <h4 class="mb-0">
                    <i class="fas fa-<?php echo $type === 'lost' ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                    <?php echo t('police.report_details.title_' . $type); ?>
                </h4>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <?php if ($item['image_path']): ?>
                            <img src="<?php echo SITE_URL; ?>uploads/<?php echo $type; ?>/<?php echo htmlspecialchars($item['image_path']); ?>"
                                 class="img-fluid rounded shadow-sm" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                        <?php else: ?>
                            <div class="bg-secondary rounded d-flex align-items-center justify-content-center"
                                 style="height: 300px;">
                                <i class="fas fa-image fa-4x text-white opacity-50"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-7">
                        <h3 class="fw-bold mb-3"><?php echo htmlspecialchars($item['item_name']); ?></h3>

                        <table class="table table-sm">
                            <tr>
                                <th width="40%"><?php echo t('police.report_details.category'); ?>:</th>
                                <td><?php echo getLocalizedCategoryName($item['category_name'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo t('police.report_details.' . $type . '_date'); ?>:</th>
                                <td><?php echo formatDate($type === 'lost' ? $item['lost_date'] : $item['found_date'], 'd M Y'); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo t('police.report_details.location'); ?>:</th>
                                <td><?php echo getLocalizedLocationName($item['location_name'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo t('police.report_details.reporter'); ?>:</th>
                                <td>
                                    <?php
                                    if ($item['reporter_name']) {
                                        echo htmlspecialchars($item['reporter_name']);
                                    } elseif ($item['police_name']) {
                                        echo htmlspecialchars($item['police_name']) . ' (' . t('police.report_details.police_officer') . ')';
                                        if ($item['badge_number']) {
                                            echo '<br><small class="text-muted">' . t('police.report_details.badge') . ': ' . htmlspecialchars($item['badge_number']) . '</small>';
                                        }
                                    } else {
                                        echo t('police.report_details.police_custody_ref') . ': ' . htmlspecialchars($item['contact_info'] ?? 'N/A');
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo t('police.report_details.contact'); ?>:</th>
                                <td>
                                    <?php
                                    if ($item['reporter_phone'] || $item['reporter_email']) {
                                        echo htmlspecialchars($item['reporter_phone'] ?? 'N/A') . '<br>';
                                        echo htmlspecialchars($item['reporter_email'] ?? 'N/A');
                                    } elseif ($item['police_phone'] || $item['police_email']) {
                                        echo htmlspecialchars($item['police_phone'] ?? 'N/A') . '<br>';
                                        echo htmlspecialchars($item['police_email'] ?? 'N/A');
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo t('police.report_details.address'); ?>:</th>
                                <td>
                                    <?php
                                    if ($item['address']) {
                                        echo htmlspecialchars($item['address']);
                                    } elseif ($item['station_address']) {
                                        echo '<strong>' . htmlspecialchars($item['station_name'] ?? t('police.report_details.police_station')) . '</strong><br>';
                                        echo htmlspecialchars($item['station_address']);
                                        if ($item['station_contact']) {
                                            echo '<br><small class="text-muted">' . t('police.report_details.station_contact') . ': ' . htmlspecialchars($item['station_contact']) . '</small>';
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php if (!empty($item['contact_info'])): ?>
                            <tr>
                                <th><?php echo t('police.report_details.additional_contact'); ?>:</th>
                                <td><?php echo htmlspecialchars($item['contact_info']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th><?php echo t('police.report_details.reported_on'); ?>:</th>
                                <td><?php echo formatDate($item['created_at'], 'd M Y, h:i A'); ?></td>
                            </tr>
                        </table>

                        <div class="mt-3">
                            <h6 class="fw-bold"><?php echo t('police.report_details.description'); ?>:</h6>
                            <p><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="<?php echo SITE_URL; ?>police/view-reports.php?type=<?php echo $type; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> <?php echo t('police.report_details.back_to_reports'); ?>
            </a>
        </div>
    </div>

    <!-- Potential Matches Sidebar -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="fas fa-handshake"></i> <?php echo t('police.report_details.potential_matches'); ?>
                </h6>
            </div>
            <div class="card-body">
                <?php if (count($potential_matches) > 0): ?>
                    <?php foreach ($potential_matches as $match): ?>
                        <div class="border rounded p-2 mb-2">
                            <?php if ($match['image_path']): ?>
                                <img src="<?php echo SITE_URL; ?>uploads/<?php echo $type === 'lost' ? 'found' : 'lost'; ?>/<?php echo htmlspecialchars($match['image_path']); ?>"
                                     class="img-fluid rounded mb-2" alt="Match">
                            <?php endif; ?>
                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($match['item_name']); ?></h6>
                            <p class="small text-muted mb-2">
                                <?php echo getLocalizedCategoryName($match['category_name']); ?> •
                                <?php echo getLocalizedLocationName($match['location_name']); ?>
                            </p>
                            <button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal"
                                    data-bs-target="#matchModal<?php echo $type === 'lost' ? $match['found_item_id'] : $match['lost_item_id']; ?>">
                                <i class="fas fa-link"></i> <?php echo t('police.report_details.create_match'); ?>
                            </button>

                            <!-- Match Modal -->
                            <div class="modal fade" id="matchModal<?php echo $type === 'lost' ? $match['found_item_id'] : $match['lost_item_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><?php echo t('police.report_details.create_match'); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><?php echo t('police.report_details.create_match_confirm'); ?></p>
                                                <div class="mb-3">
                                                    <label class="form-label"><?php echo t('police.report_details.notes_optional'); ?></label>
                                                    <textarea class="form-control" name="notes" rows="3"
                                                              placeholder="<?php echo t('police.report_details.notes_placeholder'); ?>"></textarea>
                                                </div>
                                                <input type="hidden" name="match_id"
                                                       value="<?php echo $type === 'lost' ? $match['found_item_id'] : $match['lost_item_id']; ?>">
                                                <input type="hidden" name="create_match" value="1">
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('police.report_details.cancel'); ?></button>
                                                <button type="submit" class="btn btn-primary"><?php echo t('police.report_details.create_match'); ?></button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small mb-0"><?php echo t('police.report_details.no_matches'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>