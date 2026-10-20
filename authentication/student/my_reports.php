<?php
session_start();
include '../../db.php';

function resolve_student_report_image_url($filename, array $folders): string
{
    $filename = basename((string) $filename);
    foreach ($folders as $folder) {
        if ($filename !== '' && is_file(__DIR__ . '/' . $folder . $filename)) {
            return $folder . rawurlencode($filename);
        }
    }
    return '';
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

function get_location(array $row): string
{
    $candidates = [
        'location', 'place', 'lost_location', 'found_location',
        'item_location', 'location_lost', 'location_found',
        'where_lost', 'where_found', 'last_seen_location',
        'last_seen', 'area', 'venue'
    ];

    foreach ($candidates as $col) {
        if (isset($row[$col]) && trim((string) $row[$col]) !== '') {
            return (string) $row[$col];
        }
    }

    foreach ($row as $key => $val) {
        if (preg_match('/loc|place|area|where/i', $key) && trim((string) $val) !== '') {
            return (string) $val;
        }
    }

    return 'N/A';
}

// Fetch user details
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$stmt->close();

// Fetch Lost Reports
$lost_query = "SELECT * FROM lost_items WHERE user_id = ? ORDER BY date_lost DESC";
$stmt_lost = $conn->prepare($lost_query);
$stmt_lost->bind_param("i", $user_id);
$stmt_lost->execute();
$lost_reports = $stmt_lost->get_result();
$stmt_lost->close();

// Fetch Found Reports
$found_query = "SELECT * FROM found_items WHERE user_id = ? ORDER BY date_found DESC";
$stmt_found = $conn->prepare($found_query);
$stmt_found->bind_param("i", $user_id);
$stmt_found->execute();
$found_reports = $stmt_found->get_result();
$stmt_found->close();

// User information
$user_name = $user['fullname'] ?? $_SESSION['fullname'] ?? 'Student';
$first_initial = strtoupper(substr(trim($user_name), 0, 1));
$avatar = $user['profile_picture'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports - Student Portal</title>
    <link rel="stylesheet" href="myreports.css">
    <link rel="stylesheet" href="../../responsive.css">
</head>

<body>

<input type="checkbox" id="menu-toggle">

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <a class="sidebar-brand">
            <div class="brand-icon">SLF</div>

            <div class="brand-text">
                <strong>LOST &amp; FOUND</strong>
                <span>STUDENT PORTAL</span>
            </div>
        </a>

        <div class="nav-title">Main Menu</div>

        <nav class="navigation">

            <a href="student_dashboard.php" class="nav-item">
                <span class="nav-icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <a href="report_lost.php" class="nav-item nav-lost">
                <span class="nav-icon">⌕</span>
                <span>Report Lost Item</span>
            </a>

            <a href="report_found.php" class="nav-item nav-found">
                <span class="nav-icon">□</span>
                <span>Report Found Item</span>
            </a>

            <a href="my_reports.php" class="nav-item active">
                <span class="nav-icon">▤</span>
                <span>My Reports</span>
            </a>

            <a href="profile.php" class="nav-item">
                <span class="nav-icon">○</span>
                <span>Profile</span>
            </a>

        </nav>

        <div class="sidebar-bottom sidebar-footer">
            <a href="../../authentication/logout.php"
               class="nav-item logout-link"
               onclick="return confirmLogout();">

                <span class="nav-icon">↪</span>
                <span>Log Out</span>
            </a>
        </div>

    </aside>

    <label for="menu-toggle" class="menu-overlay"></label>

    <main class="main-wrapper main-content">

        <header class="top-header">

            <div class="header-left">

                <label for="menu-toggle"
                       class="mobile-menu-btn"
                       aria-label="Open navigation menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </label>

                <div class="header-title">
                    <h1>My Reports</h1>
                </div>

            </div>
        </header>

        <div class="page-content">

            <section class="reports-section card-panel">

                <div class="section-header">
                    <h2>Lost Item Reports</h2>
                </div>

                <div class="table-responsive">
                    <table class="reports-table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item Name</th>
                                <th>Description</th>
                                <th>Date Lost</th>
                                <th>Location</th>
                                <th>Image</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if ($lost_reports->num_rows > 0): ?>

                                <?php while ($row = $lost_reports->fetch_assoc()):
                                    $status_class = strtolower(
                                        str_replace(' ', '-', $row['status'] ?? 'pending')
                                    );

                                    $loc_value = get_location($row);
                                ?>

                                    <tr>
                                        <td>#<?php echo (int) $row['id']; ?></td>

                                        <td>
                                            <strong>
                                                <?php echo htmlspecialchars($row['item_name'] ?? ''); ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?php
                                            $description = $row['description'] ?? '';
                                            echo htmlspecialchars(
                                                strlen($description) > 50
                                                    ? substr($description, 0, 50) . '...'
                                                    : $description
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($row['date_lost'] ?? ''); ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($loc_value); ?>
                                        </td>

                                        <td>
                                            <?php $imageUrl = resolve_student_report_image_url($row['image'] ?? '', ['../uploads/', '../../uploads/']); ?>
                                            <?php if ($imageUrl !== ''): ?>

                                                <img
                                                    src="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="report-image"
                                                    alt="Lost item"
                                                    loading="lazy"
                                                >

                                            <?php else: ?>

                                                <span class="no-image">No Image</span>

                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <span class="status-badge <?php echo htmlspecialchars($status_class); ?>">
                                                <?php echo htmlspecialchars($row['status'] ?? 'Pending'); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <div class="action-buttons">

                                                <a
                                                    href="edit_lost.php?id=<?php echo (int) $row['id']; ?>"
                                                    class="action-link edit-action"
                                                    title="Edit Report"
                                                >Edit</a>

                                                <a
                                                    href="delete_lost.php?id=<?php echo (int) $row['id']; ?>"
                                                    class="action-link delete-action"
                                                    title="Delete Report"
                                                    onclick="return confirm('Are you sure you want to delete this report?');"
                                                >Delete</a>

                                            </div>
                                        </td>
                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="8" class="empty-table">
                                        No lost reports found.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>
            </section>

            <!-- FOUND ITEM REPORTS -->
            <section class="reports-section card-panel">

                <div class="section-header">
                    <h2>Found Item Reports</h2>
                </div>

                <div class="table-responsive">
                    <table class="reports-table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item Name</th>
                                <th>Description</th>
                                <th>Date Found</th>
                                <th>Location</th>
                                <th>Image</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if ($found_reports->num_rows > 0): ?>

                                <?php while ($row = $found_reports->fetch_assoc()):
                                    $status_class = strtolower(
                                        str_replace(' ', '-', $row['status'] ?? 'pending')
                                    );

                                    $loc_value = get_location($row);
                                ?>

                                    <tr>
                                        <td>#<?php echo (int) $row['id']; ?></td>

                                        <td>
                                            <strong>
                                                <?php echo htmlspecialchars($row['item_name'] ?? ''); ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?php
                                            $description = $row['description'] ?? '';
                                            echo htmlspecialchars(
                                                strlen($description) > 50
                                                    ? substr($description, 0, 50) . '...'
                                                    : $description
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($row['date_found'] ?? ''); ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($loc_value); ?>
                                        </td>

                                        <td>
                                            <?php $imageUrl = resolve_student_report_image_url($row['image'] ?? '', ['../../uploads/', '../uploads/']); ?>
                                            <?php if ($imageUrl !== ''): ?>

                                                <img
                                                    src="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="report-image"
                                                    alt="Found item"
                                                    loading="lazy"
                                                >

                                            <?php else: ?>

                                                <span class="no-image">No Image</span>

                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <span class="status-badge <?php echo htmlspecialchars($status_class); ?>">
                                                <?php echo htmlspecialchars($row['status'] ?? 'Pending'); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <div class="action-buttons">

                                                <a
                                                    href="edit_found.php?id=<?php echo (int) $row['id']; ?>"
                                                    class="action-link edit-action"
                                                    title="Edit Report"
                                                >Edit</a>

                                                <a
                                                    href="delete_found.php?id=<?php echo (int) $row['id']; ?>"
                                                    class="action-link delete-action"
                                                    title="Delete Report"
                                                    onclick="return confirm('Are you sure you want to delete this report?');"
                                                >Delete</a>

                                            </div>
                                        </td>
                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="8" class="empty-table">
                                        No found reports found.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>
            </section>

        </div>

    </main>

</div>

<script>
function confirmLogout() {
    return confirm("Are you sure you want to log out?");
}
</script>

</body>
</html>
