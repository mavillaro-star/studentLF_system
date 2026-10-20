<?php
session_start();
include '../../db.php';

function resolve_lost_item_image_url($filename): string
{
    $filename = basename((string) $filename);
    foreach (['../../authentication/uploads/', '../../uploads/'] as $folder) {
        if ($filename !== '' && is_file(__DIR__ . '/' . $folder . $filename)) {
            return $folder . rawurlencode($filename);
        }
    }
    return '';
}
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../login.php");
    exit();
}

/* =========================
   UPDATE STATUS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $report_id = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
    $new_status = trim($_POST['status'] ?? '');
    $allowed_statuses = ['Pending', 'Matched', 'Claimed'];
    if (!$report_id || !in_array($new_status, $allowed_statuses, true)) {
        $_SESSION['status_message'] = "Unable to update the report status.";
        $_SESSION['status_type'] = "error";
        header("Location: view_lost.php");
        exit();
    }
    $sql_update = "UPDATE lost_items SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql_update);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $new_status, $report_id);
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $_SESSION['status_message'] =
                    "Successfully updated! The report status is now " . $new_status . ".";
                $_SESSION['status_type'] = "success";
            } else {
                $_SESSION['status_message'] =
                    "The report status is already " . $new_status . ".";
                $_SESSION['status_type'] = "info";
            }
        } else {
            $_SESSION['status_message'] = "Failed to update the report status.";
            $_SESSION['status_type'] = "error";
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['status_message'] = "Database error. Please try again.";
        $_SESSION['status_type'] = "error";
    }
    header("Location: view_lost.php");
    exit();
}

/* =========================
   DELETE LOST REPORT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_report'])) {
    $report_id = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
    if (!$report_id) {
        $_SESSION['status_message'] = "Invalid report ID.";
        $_SESSION['status_type'] = "error";
        header("Location: view_lost.php");
        exit();
    }
    $sql_delete = "DELETE FROM lost_items WHERE id = ?";
    $stmt_delete = mysqli_prepare($conn, $sql_delete);
    if ($stmt_delete) {
        mysqli_stmt_bind_param($stmt_delete, "i", $report_id);
        if (mysqli_stmt_execute($stmt_delete)) {
            if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
                $_SESSION['status_message'] = "Lost item report deleted successfully.";
                $_SESSION['status_type'] = "success";
            } else {
                $_SESSION['status_message'] = "Lost item report not found.";
                $_SESSION['status_type'] = "error";
            }
        } else {
            $_SESSION['status_message'] = "Failed to delete the lost item report.";
            $_SESSION['status_type'] = "error";
        }
        mysqli_stmt_close($stmt_delete);
    } else {
        $_SESSION['status_message'] = "Database error. Please try again.";
        $_SESSION['status_type'] = "error";
    }
    header("Location: view_lost.php");
    exit();
}

$sql = "SELECT
            l.id,
            l.user_id,
            l.item_name,
            l.image,
            l.description,
            l.date_lost,
            l.location_lost,
            l.status,
            u.fullname AS reporter_name,
            u.course,
            u.year_level,
            u.contact_number,
            u.email
        FROM lost_items l
        LEFT JOIN users u
            ON l.user_id = u.id
        ORDER BY l.id DESC";
$result = mysqli_query($conn, $sql);
$admin_fullname = $_SESSION['fullname'] ?? 'Admin';
$status_message = $_SESSION['status_message'] ?? '';
$status_type = $_SESSION['status_type'] ?? '';
unset($_SESSION['status_message']);
unset($_SESSION['status_type']);

$lost_count = 0;
$found_count = 0;

$lost_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM lost_items");

if ($lost_result) {
    $lost_data = mysqli_fetch_assoc($lost_result);
    $lost_count = (int)($lost_data['total'] ?? 0);
}

$found_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM found_items");

if ($found_result) {
    $found_data = mysqli_fetch_assoc($found_result);
    $found_count = (int)($found_data['total'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lost Item Reports - Admin</title>
<link rel="stylesheet" href="admin.css">
<style>
body {
    background: #eaf0f6;
    min-height: 100vh;
    padding: 8px;
}
.dashboard-wrapper {
    display: flex;
    width: 100%;
    min-height: calc(100vh - 16px);
    gap: 12px;
}
.main-content {
    flex: 1;
    min-width: 0;
    margin-left:260px;
    min-height: calc(100vh - 16px);
    display: flex;
    flex-direction: column;
    gap: 10px;
    overflow: hidden;
}
.header {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2px 4px;
}
.page-title {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin: 0;
}
.page-description {
    margin: 3px 0 0;
    color: #6b7280;
    font-size: 12px;
}
.user-profile {
    display: flex;
    align-items: center;
    gap: 10px;
}
.avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #1e293b;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
}
.user-info {
    display: flex;
    flex-direction: column;
    text-align: right;
}
.user-name {
    font-size: 13px;
    font-weight: 700;
    color: #111827;
}
.user-role {
    font-size: 11px;
    color: #8a99ad;
}
.content-display {
    width: 100%;
    flex: 1;
    min-height: 0;
    background: #f7fafc;
    border: 1px solid #edf2f7;
    border-radius: 12px;
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    overflow: hidden;
}
.alert {
    width: 100%;
    padding: 11px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    flex-shrink: 0;
}
.alert-success {
    background: #d1fae5;
    border: 1px solid #a7f3d0;
    color: #047857;
}
.alert-error {
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #b91c1c;
}
.alert-info {
    background: #dbeafe;
    border: 1px solid #bfdbfe;
    color: #1d4ed8;
}
.table-section {
    width: 100%;
    flex: 1;
    min-height: 0;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
}
.table-responsive {
    width: 100%;
    height: 100%;
    overflow-x: auto;
    overflow-y: auto;
}
.reports-table {
    width: 100%;
    min-width: 1050px;
    border-collapse: collapse;
    font-size: 13px;
}
.reports-table thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #f3f4f6;
    color: #374151;
    font-size: 12px;
    font-weight: 700;
    text-align: left;
    padding: 11px 12px;
    border-bottom: 1px solid #e5e7eb;
    white-space: nowrap;
}
.reports-table tbody td {
    padding: 11px 12px;
    color: #555b64;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.reports-table tbody tr:hover {
    background: #f8fafc;
}
.reports-table tbody tr:last-child td {
    border-bottom: none;
}
.col-id {
    width: 55px;
}
.col-reporter {
    width: 170px;
}
.col-item {
    width: 140px;
}
.col-image {
    width: 75px;
}
.col-date {
    width: 110px;
}
.col-location {
    width: 155px;
}
.col-status {
    width: 110px;
}
.col-action {
    width: 150px;
}
.reporter-name {
    font-weight: 700;
    color: #111827;
    margin-bottom: 3px;
}
.reporter-contact {
    font-size: 11px;
    color: #6b7280;
}
.item-name {
    font-weight: 700;
    color: #111827;
}

.item-thumb {
    width: 48px;
    height: 48px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    display: block;
}
.no-image {
    display: inline-block;
    font-size: 11px;
    color: #9ca3af;
    font-style: italic;
}
.status-wrapper {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: nowrap;
}
.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 5px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}
.status-pending {
    background: #fef3c7;
    color: #b45309;
}
.status-matched {
    background: #dbeafe;
    color: #2563eb;
}
.status-claimed {
    background: #d1fae5;
    color: #047857;
}
.status-form {
    display: flex;
    align-items: center;
    gap: 5px;
}
.status-select {
    min-width: 92px;
    padding: 6px 7px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #ffffff;
    color: #374151;
    font-size: 11px;
    font-weight: 600;
    outline: none;
    cursor: pointer;
}
.status-select:focus {
    border-color: #2563eb;
}
.update-btn {
    border: none;
    padding: 6px 9px;
    border-radius: 6px;
    background: #1e293b;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
}
.update-btn:hover {
    background: #0f172a;
}
.empty-state {
    text-align: center;
    padding: 50px 20px !important;
    color: #6b7280;
    font-size: 13px;
}
@media (max-width: 850px) {
    body {
        padding: 6px;
    }
    .dashboard-wrapper {
        flex-direction: column;
        min-height: calc(100vh - 12px);
        gap: 8px;
    }
    .main-content {
        min-height: calc(100vh - 12px);
        gap: 8px;
    }
    .content-display {
        padding: 8px;
        border-radius: 10px;
    }
    .reports-table {
        min-width: 1050px;
    }
    .user-info {
        display: none;
    }
}
@media (max-width: 500px) {
    .content-display {
        padding: 6px;
    }
    .reports-table {
        min-width: 1000px;
    }
}
/* ACTION BUTTONS */
.action-buttons {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.action-buttons form {
    margin: 0;
}
.edit-btn,
.delete-btn {
    border: none;
    padding: 7px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: 0.2s;
}
.edit-btn {
    background: #dbeafe;
    color: #1d4ed8;
}
.edit-btn:hover {
    background: #1d4ed8;
    color: #ffffff;
}

.edit-status-details {
    position: relative;
}

.edit-status-details > summary {
    list-style: none;
}

.edit-status-details > summary::-webkit-details-marker {
    display: none;
}

.edit-status-form {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    z-index: 100;
    display: flex;
    flex-direction: column;
    gap: 9px;
    width: 190px;
    padding: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.14);
}

.edit-status-form label {
    color: #334155;
    font-size: 12px;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.edit-status-form .status-select,
.edit-status-form .update-btn {
    width: 100%;
}
.delete-btn {
    background: #fee2e2;
    color: #dc2626;
}
.delete-btn:hover {
    background: #dc2626;
    color: #ffffff;
}

/* TABLE COLUMNS - aligned with view_found.php */
.reports-table {
    min-width: 1250px;
}
.reports-table th:nth-child(1),
.reports-table td:nth-child(1) { width: 55px; }
.reports-table th:nth-child(2),
.reports-table td:nth-child(2) { min-width: 145px; }
.reports-table th:nth-child(3),
.reports-table td:nth-child(3) { min-width: 120px; }
.reports-table th:nth-child(4),
.reports-table td:nth-child(4) { width: 70px; text-align: center; }
.reports-table th:nth-child(5),
.reports-table td:nth-child(5) { min-width: 125px; }
.reports-table th:nth-child(6),
.reports-table td:nth-child(6) { width: 80px; }
.reports-table th:nth-child(7),
.reports-table td:nth-child(7) { min-width: 135px; }
.reports-table th:nth-child(8),
.reports-table td:nth-child(8) { min-width: 230px; max-width: 280px; white-space: normal; overflow-wrap: anywhere; }
.reports-table th:nth-child(9),
.reports-table td:nth-child(9) { min-width: 150px; }
.reports-table th:nth-child(10),
.reports-table td:nth-child(10) { min-width: 110px; }
.reports-table th:nth-child(11),
.reports-table td:nth-child(11) { min-width: 110px; }
.reports-table th:nth-child(12),
.reports-table td:nth-child(12) { min-width: 170px; }
</style>
    <link rel="stylesheet" href="../../responsive.css">
</head>
<body class="admin-sidebar-page">
<input type="checkbox" id="admin-menu-toggle" aria-label="Toggle navigation menu">
<label class="mobile-sidebar-toggle" for="admin-menu-toggle" aria-label="Toggle navigation menu">☰</label>
<label class="mobile-sidebar-overlay" for="admin-menu-toggle" aria-hidden="true"></label>
<div class="dashboard-wrapper">
<!-- =========================
     SIDEBAR
========================= -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            SLF
        </div>
        <div>
            <h2>Lost &amp; Found</h2>
            <span>Administrator</span>
        </div>
    </div>
    <nav class="navigation">
        <a href="admin_dashboard.php" class="nav-item">
            <span class="nav-icon">⌂</span>
            <span>Dashboard</span>
        </a>
        <a href="manage_users.php" class="nav-item">
            <span class="nav-icon">♙</span>
            <span>Manage Users</span>
        </a>
        <a href="view_lost.php" class="nav-item active">
            <span class="nav-icon">▣</span>

            <span>Lost Item Reports</span>
                 <?php if ($lost_count > 0): ?>
                    <span class="nav-count">
                        <?php echo $lost_count; ?>
                    </span>
                <?php endif; ?>
        </a>

        <a href="view_found.php" class="nav-item">
            <span class="nav-icon">⌕</span>
            <span>Found Item Reports</span>

            <?php if ($found_count > 0): ?>
                    <span class="nav-count">
                        <?php echo $found_count; ?>
                    </span>
            <?php endif; ?>
        </a>

        <a href="admin_profile.php" class="nav-item">
            <span class="nav-icon">○</span>
            <span>My Profile</span>
        </a>
    </nav>
      <div class="sidebar-bottom">
            <a href="../../authentication/logout.php" class="nav-item"
             onclick="return confirmLogout();">
                <span class="nav-icon">↪</span>
                <span>Log Out</span>
            </a>
        </div>
</aside>
<main class="main-content">
<header class="header">
    <div>
        <h1 class="page-title">
            Lost Item Reports
        </h1>
        <p class="page-description">
            Review submitted lost item reports and manage their status.
        </p>
    </div>
</header>
<section class="content-display">
<?php if (!empty($status_message)): ?>
    <div class="alert alert-<?php echo htmlspecialchars($status_type); ?>">
        <?php
        if ($status_type === 'success') {
            echo "✓ ";
        } elseif ($status_type === 'error') {
            echo "⚠ ";
        } else {
            echo "ℹ ";
        }
        ?>
        <?php echo htmlspecialchars($status_message); ?>
    </div>
<?php endif; ?>
<div class="table-section">
<div class="table-responsive">
<table class="reports-table">
<thead>
<tr>
    <th>ID</th>
    <th>Reporter</th>
    <th>Course</th>
    <th>Year</th>
    <th>Contact</th>
    <th>Image</th>
    <th>Item Name</th>
    <th>Description</th>
    <th>Location</th>
    <th>Date Lost</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php if ($result && mysqli_num_rows($result) > 0): ?>
<?php while ($row = mysqli_fetch_assoc($result)): ?>
<?php
$current_status = $row['status'] ?? 'Pending';
if (!in_array(
    $current_status,
    ['Pending', 'Matched', 'Claimed'],
    true
)) {
    $current_status = 'Pending';
}
if ($current_status === 'Pending') {
    $status_class = 'status-pending';
    $status_label = '🟠 Pending';
} elseif ($current_status === 'Matched') {
    $status_class = 'status-matched';
    $status_label = '🔵 Matched';
} else {
    $status_class = 'status-claimed';
    $status_label = '🟢 Claimed';
}
/* REPORTER */
$reporter_name = !empty($row['reporter_name'])
    ? $row['reporter_name']
    : 'Unknown Reporter';
$course = !empty($row['course'])
    ? $row['course']
    : 'Not provided';
$year_level = !empty($row['year_level'])
    ? $row['year_level']
    : 'Not provided';
$contact = !empty($row['contact_number'])
    ? $row['contact_number']
    : 'Not provided';
?>
<tr>
<!-- ID -->
<td>
    <strong>
        #<?php echo (int)$row['id']; ?>
    </strong>
</td>
<!-- REPORTER -->
<td>
    <?php echo htmlspecialchars($reporter_name); ?>
</td>
<!-- COURSE -->
<td>
    <?php echo htmlspecialchars($course); ?>
</td>
<!-- YEAR -->
<td>
    <?php echo htmlspecialchars($year_level); ?>
</td>
<!-- CONTACT -->
<td>
    <?php echo htmlspecialchars($contact); ?>
</td>
<!-- IMAGE -->
<td>
<?php $imageUrl = resolve_lost_item_image_url($row['image'] ?? ''); ?>
<?php if ($imageUrl !== ''): ?>
    <img
        src="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>"
        class="item-thumb"
        alt="Lost Item"
        loading="lazy"
    >
<?php else: ?>
    <span class="no-image">No Image</span>
<?php endif; ?>
</td>
<!-- ITEM NAME -->
<td>
    <strong><?php echo htmlspecialchars($row['item_name']); ?></strong>
</td>
<!-- DESCRIPTION -->
<td>
    <?php echo htmlspecialchars($row['description'] ?? ''); ?>
</td>
<!-- LOCATION -->
<td>
    <?php echo htmlspecialchars($row['location_lost']); ?>
</td>
<!-- DATE LOST -->
<td>
    <?php echo htmlspecialchars($row['date_lost']); ?>
</td>
<!-- STATUS -->
<td>
    <div class="status-wrapper">
        <span class="status-badge <?php echo $status_class; ?>">
            <?php echo $status_label; ?>
        </span>
    </div>
</td>
<!-- ACTION -->
<td>
    <div class="action-buttons">
        <details class="edit-status-details">
            <summary class="edit-btn">Edit</summary>

            <form method="POST" action="view_lost.php" class="edit-status-form">
                <input
                    type="hidden"
                    name="report_id"
                    value="<?php echo (int)$row['id']; ?>"
                >

                <label for="status-<?php echo (int)$row['id']; ?>">
                    <?php echo htmlspecialchars($row['item_name'] ?? 'Item', ENT_QUOTES, 'UTF-8'); ?>
                </label>

                <select
                    name="status"
                    id="status-<?php echo (int)$row['id']; ?>"
                    class="status-select"
                    required
                >
                    <option value="Pending" <?php echo $current_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Matched" <?php echo $current_status === 'Matched' ? 'selected' : ''; ?>>Matched</option>
                    <option value="Claimed" <?php echo $current_status === 'Claimed' ? 'selected' : ''; ?>>Claimed</option>
                </select>

                <button type="submit" name="update_status" class="update-btn">
                    Save Status
                </button>
            </form>
        </details>
        <form
            method="POST"
            action="view_lost.php"
            onsubmit="return confirm('Are you sure you want to delete this lost item report?');"
        >
            <input
                type="hidden"
                name="report_id"
                value="<?php echo (int)$row['id']; ?>"
            >
            <button type="submit" name="delete_report" class="delete-btn">
                Delete
            </button>
        </form>
    </div>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
    <td
        colspan="12"
        class="empty-state"
    >
        No lost reports found.
    </td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</section>
</main>
</div>

<script>
function confirmLogout(){
    return confirm("Are you sure you want to log out?");
}
</script>
</body>
</html>
