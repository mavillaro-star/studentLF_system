```php
<?php
session_start();
include '../../db.php';

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
   FETCH LOST REPORTS
========================= */

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

$first_letter = strtoupper(
    substr(trim($admin_fullname), 0, 1)
);
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Lost Item Reports - Admin</title>

<link rel="stylesheet" href="admin.css">

<style>

/* =========================
   PAGE
========================= */

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
    min-height: calc(100vh - 16px);
    display: flex;
    flex-direction: column;
    gap: 10px;
    overflow: hidden;
}

/* =========================
   HEADER
========================= */

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

/* =========================
   CONTENT
========================= */

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

/* =========================
   ALERT
========================= */

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

/* =========================
   TABLE
========================= */

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
    overflow: auto;
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
    color: #1f2937;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.reports-table tbody tr:hover {
    background: #f8fafc;
}

.reports-table tbody tr:last-child td {
    border-bottom: none;
}

/* =========================
   COLUMNS
========================= */

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
    width: 250px;
}

.col-action {
    width: 85px;
}

/* =========================
   REPORTER
========================= */

.reporter-name {
    font-weight: 700;
    color: #111827;
    margin-bottom: 3px;
}

.reporter-contact {
    font-size: 11px;
    color: #6b7280;
}

/* =========================
   ITEM
========================= */

.item-name {
    font-weight: 700;
    color: #111827;
}

/* =========================
   IMAGE
========================= */

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

/* =========================
   STATUS
========================= */

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

/* =========================
   VIEW BUTTON
========================= */

.view-btn {
    border: 1px solid #dbe3ec;
    background: #f8fafc;
    color: #334155;
    padding: 7px 11px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: 0.2s;
}

.view-btn:hover {
    background: #1e293b;
    border-color: #1e293b;
    color: #ffffff;
}

/* =========================
   EMPTY
========================= */

.empty-state {
    text-align: center;
    padding: 50px 20px !important;
    color: #6b7280;
    font-size: 13px;
}

/* =========================
   MODAL
========================= */

.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.62);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 9999;
}

.modal-overlay.show {
    display: flex;
}

.report-modal {
    width: min(850px, 100%);
    max-height: 92vh;
    background: #ffffff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25);
    display: flex;
    flex-direction: column;
}

/* =========================
   MODAL HEADER
========================= */

.modal-header {
    padding: 17px 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-heading {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.modal-title {
    margin: 0;
    color: #111827;
    font-size: 17px;
    font-weight: 800;
}

.modal-subtitle {
    color: #6b7280;
    font-size: 11px;
}

.modal-close {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 7px;
    background: #f1f5f9;
    color: #475569;
    font-size: 20px;
    cursor: pointer;
}

.modal-close:hover {
    background: #e2e8f0;
}

/* =========================
   MODAL BODY
========================= */

.modal-body {
    padding: 20px;
    overflow-y: auto;
}

.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.detail-card {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #ffffff;
    overflow: hidden;
}

.detail-card.full {
    grid-column: 1 / -1;
}

.detail-card-title {
    padding: 11px 14px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    color: #334155;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.detail-card-content {
    padding: 14px;
}

/* =========================
   REPORTER DETAILS
========================= */

.reporter-detail {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 13px 18px;
}

.detail-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-label {
    font-size: 10px;
    color: #94a3b8;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.detail-value {
    color: #1f2937;
    font-size: 13px;
    font-weight: 600;
    word-break: break-word;
}

/* =========================
   ITEM DETAILS
========================= */

.item-detail-layout {
    display: grid;
    grid-template-columns: 190px 1fr;
    gap: 20px;
}

.modal-image-box {
    width: 190px;
    height: 190px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-item-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.modal-no-image {
    color: #94a3b8;
    font-size: 12px;
    text-align: center;
    padding: 15px;
}

.item-information {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.item-main-name {
    font-size: 19px;
    color: #111827;
    font-weight: 800;
    margin: 4px 0 0;
}

.description-box {
    background: #f8fafc;
    border: 1px solid #edf2f7;
    border-radius: 8px;
    padding: 11px 12px;
}

.description-box p {
    margin: 5px 0 0;
    color: #475569;
    font-size: 13px;
    line-height: 1.55;
    white-space: pre-wrap;
    word-break: break-word;
}

/* =========================
   MODAL STATUS
========================= */

.status-detail-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}

.modal-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 11px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 800;
}

.modal-status.pending {
    background: #fef3c7;
    color: #b45309;
}

.modal-status.matched {
    background: #dbeafe;
    color: #2563eb;
}

.modal-status.claimed {
    background: #d1fae5;
    color: #047857;
}

/* =========================
   MODAL FOOTER
========================= */

.modal-footer {
    padding: 13px 20px;
    border-top: 1px solid #e5e7eb;
    background: #f8fafc;
    display: flex;
    justify-content: flex-end;
}

.close-modal-btn {
    border: 1px solid #d1d5db;
    background: #ffffff;
    color: #374151;
    padding: 8px 15px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}

.close-modal-btn:hover {
    background: #f1f5f9;
}

/* =========================
   MOBILE
========================= */

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

    .detail-grid {
        grid-template-columns: 1fr;
    }

    .detail-card.full {
        grid-column: auto;
    }

    .item-detail-layout {
        grid-template-columns: 1fr;
    }

    .modal-image-box {
        width: 100%;
        max-width: 260px;
        height: 220px;
        margin: 0 auto;
    }
}

@media (max-width: 500px) {

    .content-display {
        padding: 6px;
    }

    .reports-table {
        min-width: 1000px;
    }

    .modal-overlay {
        padding: 8px;
    }

    .report-modal {
        max-height: 96vh;
        border-radius: 10px;
    }

    .modal-header,
    .modal-body,
    .modal-footer {
        padding-left: 14px;
        padding-right: 14px;
    }

    .reporter-detail {
        grid-template-columns: 1fr;
    }

    .item-main-name {
        font-size: 17px;
    }
}

</style>

</head>

<body>

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
        </a>

        <a href="view_found.php" class="nav-item">
            <span class="nav-icon">⌕</span>
            <span>Found Item Reports</span>
        </a>

        <a href="admin_profile.php" class="nav-item">
            <span class="nav-icon">◎</span>
            <span>My Profile</span>
        </a>

    </nav>


    <div class="sidebar-bottom">

        <a href="ad_logout_con.php" class="logout-link">
            <span>↪</span>
            <span>Log Out</span>
        </a>

    </div>

</aside>


<!-- =========================
     MAIN CONTENT
========================= -->

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


    <div class="user-profile">

        <div class="user-info">

            <span class="user-name">
                <?php echo htmlspecialchars($admin_fullname); ?>
            </span>

            <span class="user-role">
                Administrator
            </span>

        </div>

        <div class="avatar">
            <?php echo htmlspecialchars($first_letter); ?>
        </div>

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

    <th class="col-id">
        ID
    </th>

    <th class="col-reporter">
        Reporter
    </th>

    <th class="col-item">
        Item
    </th>

    <th class="col-image">
        Image
    </th>

    <th class="col-date">
        Date Lost
    </th>

    <th class="col-location">
        Location
    </th>

    <th class="col-status">
        Status
    </th>

    <th class="col-action">
        Details
    </th>

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

$email = !empty($row['email'])
    ? $row['email']
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

    <div class="reporter-name">

        <?php echo htmlspecialchars($reporter_name); ?>

    </div>


    <?php if (!empty($row['contact_number'])): ?>

        <div class="reporter-contact">

            <?php echo htmlspecialchars(
                $row['contact_number']
            ); ?>

        </div>

    <?php endif; ?>

</td>


<!-- ITEM -->

<td>

    <div class="item-name">

        <?php echo htmlspecialchars(
            $row['item_name']
        ); ?>

    </div>

</td>


<!-- IMAGE -->

<td>

<?php if (!empty($row['image'])): ?>

    <img
        src="../../uploads/<?php echo htmlspecialchars(
            $row['image']
        ); ?>"
        class="item-thumb"
        alt="Lost Item"
    >

<?php else: ?>

    <span class="no-image">
        No Image
    </span>

<?php endif; ?>

</td>


<!-- DATE -->

<td>

    <?php echo htmlspecialchars(
        $row['date_lost']
    ); ?>

</td>


<!-- LOCATION -->

<td>

    <?php echo htmlspecialchars(
        $row['location_lost']
    ); ?>

</td>


<!-- STATUS -->

<td>

<div class="status-wrapper">


    <span class="status-badge <?php echo $status_class; ?>">

        <?php echo $status_label; ?>

    </span>


    <form
        method="POST"
        action="view_lost.php"
        class="status-form"
    >

        <input
            type="hidden"
            name="report_id"
            value="<?php echo (int)$row['id']; ?>"
        >


        <select
            name="status"
            class="status-select"
            required
        >

            <option
                value="Pending"
                <?php echo $current_status === 'Pending'
                    ? 'selected'
                    : ''; ?>
            >
                Pending
            </option>


            <option
                value="Matched"
                <?php echo $current_status === 'Matched'
                    ? 'selected'
                    : ''; ?>
            >
                Matched
            </option>


            <option
                value="Claimed"
                <?php echo $current_status === 'Claimed'
                    ? 'selected'
                    : ''; ?>
            >
                Claimed
            </option>

        </select>


        <button
            type="submit"
            name="update_status"
            class="update-btn"
        >
            Update
        </button>

    </form>

</div>

</td>

<td>

<button
    type="button"
    class="view-btn"
    onclick="openReportModal(this)"

    data-id="<?php echo (int)$row['id']; ?>"

    data-fullname="<?php echo htmlspecialchars(
        $reporter_name,
        ENT_QUOTES,
        'UTF-8'
    ); ?>"

    data-course="<?php echo htmlspecialchars(
        $course,
        ENT_QUOTES,
        'UTF-8'
    ); ?>"

    data-year="<?php echo htmlspecialchars(
        $year_level,
        ENT_QUOTES,
        'UTF-8'
    ); ?>"

    data-contact="<?php echo htmlspecialchars(
        $contact,
        ENT_QUOTES,
        'UTF-8'
    ); ?>"

    data-email="<?php echo htmlspecialchars(
        $email,
        ENT_QUOTES,
        'UTF-8'
    ); ?>"

    data-item="<?php echo htmlspecialchars(
        $row['item_name'],
        ENT_QUOTES,
        'UTF-8'
    ); ?>"

    data-description="<?php echo htmlspecialchars(
        $row['description'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    ); ?>"

    data-date="<?php echo htmlspecialchars(
        $row['date_lost'],
        ENT_QUOTES,
        'UTF-8'
    ); ?>"

    data-location="<?php echo htmlspecialchars(
        $row['location_lost'],
        ENT_QUOTES,
        'UTF-8'
    ); ?>"

    data-status="<?php echo htmlspecialchars(
        $current_status,
        ENT_QUOTES,
        'UTF-8'
    ); ?>"

    data-image="<?php
        echo !empty($row['image'])
            ? '../../uploads/' . htmlspecialchars(
                $row['image'],
                ENT_QUOTES,
                'UTF-8'
            )
            : '';
    ?>"
>
    View
</button>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

    <td
        colspan="8"
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

<div
    class="modal-overlay"
    id="reportModal"
    onclick="closeReportModal(event)"
>

<div
    class="report-modal"
    onclick="event.stopPropagation()"
>

<div class="modal-header">

    <div class="modal-heading">

        <h2 class="modal-title">
            Lost Report Details
        </h2>

        <span class="modal-subtitle">
            Complete information submitted by the reporter
        </span>

    </div>


    <button
        type="button"
        class="modal-close"
        onclick="closeReportModal()"
        aria-label="Close"
    >
        ×
    </button>

</div>


<!-- MODAL BODY -->

<div class="modal-body">

<div class="detail-grid">

<div class="detail-card">

    <div class="detail-card-title">
        Reporter Information
    </div>


    <div class="detail-card-content">

        <div class="reporter-detail">


            <div class="detail-field">

                <span class="detail-label">
                    Full Name
                </span>

                <span
                    class="detail-value"
                    id="detailFullname"
                >
                </span>

            </div>


            <div class="detail-field">

                <span class="detail-label">
                    Course
                </span>

                <span
                    class="detail-value"
                    id="detailCourse"
                >
                </span>

            </div>


            <div class="detail-field">

                <span class="detail-label">
                    Year Level
                </span>

                <span
                    class="detail-value"
                    id="detailYear"
                >
                </span>

            </div>


            <div class="detail-field">

                <span class="detail-label">
                    Contact Number
                </span>

                <span
                    class="detail-value"
                    id="detailContact"
                >
                </span>

            </div>


            <div class="detail-field">

                <span class="detail-label">
                    Email Address
                </span>

                <span
                    class="detail-value"
                    id="detailEmail"
                >
                </span>

            </div>


        </div>

    </div>

</div>

<div class="detail-card">

    <div class="detail-card-title">
        Report Information
    </div>


    <div class="detail-card-content">

        <div class="reporter-detail">


            <div class="detail-field">

                <span class="detail-label">
                    Report Number
                </span>

                <span
                    class="detail-value"
                    id="detailId"
                >
                </span>

            </div>


            <div class="detail-field">

                <span class="detail-label">
                    Date Lost
                </span>

                <span
                    class="detail-value"
                    id="detailDate"
                >
                </span>

            </div>


            <div class="detail-field">

                <span class="detail-label">
                    Location Lost
                </span>

                <span
                    class="detail-value"
                    id="detailLocation"
                >
                </span>

            </div>


            <div class="detail-field">

                <span class="detail-label">
                    Current Status
                </span>

                <span
                    class="detail-value"
                    id="detailStatusText"
                >
                </span>

            </div>


        </div>

    </div>

</div>

<div class="detail-card full">

    <div class="detail-card-title">
        Lost Item Details
    </div>


    <div class="detail-card-content">

        <div class="item-detail-layout">


            <!-- IMAGE -->

            <div class="modal-image-box">

                <img
                    id="detailImage"
                    class="modal-item-image"
                    src=""
                    alt="Lost Item Image"
                    style="display:none;"
                >


                <span
                    id="noDetailImage"
                    class="modal-no-image"
                >
                    No image available
                </span>

            </div>


            <!-- INFORMATION -->

            <div class="item-information">


                <div>

                    <span class="detail-label">
                        Item Name
                    </span>

                    <h3
                        class="item-main-name"
                        id="detailItem"
                    >
                    </h3>

                </div>


                <div class="description-box">

                    <span class="detail-label">
                        Description
                    </span>

                    <p id="detailDescription">
                    </p>

                </div>


                <div class="status-detail-box">

                    <div>

                        <span class="detail-label">
                            Report Status
                        </span>

                    </div>


                    <span
                        id="modalStatus"
                        class="modal-status"
                    >
                    </span>

                </div>


            </div>

        </div>

    </div>

</div>


</div>

</div>

<div class="modal-footer">

    <button
        type="button"
        class="close-modal-btn"
        onclick="closeReportModal()"
    >
        Close
    </button>

</div>


</div>

</div>


<script>

function openReportModal(button) {

    const modal =
        document.getElementById('reportModal');

    document.getElementById('detailFullname').textContent =
        button.dataset.fullname || 'Not provided';

    document.getElementById('detailCourse').textContent =
        button.dataset.course || 'Not provided';

    document.getElementById('detailYear').textContent =
        button.dataset.year || 'Not provided';

    document.getElementById('detailContact').textContent =
        button.dataset.contact || 'Not provided';

    document.getElementById('detailEmail').textContent =
        button.dataset.email || 'Not provided';

    document.getElementById('detailId').textContent =
        '#' + button.dataset.id;

    document.getElementById('detailDate').textContent =
        button.dataset.date || 'Not provided';

    document.getElementById('detailLocation').textContent =
        button.dataset.location || 'Not provided';

    document.getElementById('detailStatusText').textContent =
        button.dataset.status || 'Pending';

    document.getElementById('detailItem').textContent =
        button.dataset.item || 'Unnamed Item';

    document.getElementById('detailDescription').textContent =
        button.dataset.description ||
        'No description provided.';


    /* IMAGE */

    const image =
        document.getElementById('detailImage');

    const noImage =
        document.getElementById('noDetailImage');

    if (button.dataset.image) {

        image.src = button.dataset.image;

        image.style.display = 'block';

        noImage.style.display = 'none';

    } else {

        image.src = '';

        image.style.display = 'none';

        noImage.style.display = 'block';
    }

    const status =
        document.getElementById('modalStatus');

    status.className = 'modal-status';


    if (button.dataset.status === 'Pending') {

        status.classList.add('pending');

        status.textContent =
            '🟠 Pending';

    } else if (
        button.dataset.status === 'Matched'
    ) {

        status.classList.add('matched');

        status.textContent =
            '🔵 Matched';

    } else {

        status.classList.add('claimed');

        status.textContent =
            '🟢 Claimed';
    }

    modal.classList.add('show');

    document.body.style.overflow = 'hidden';
}

function closeReportModal(event) {

    if (
        event &&
        event.target &&
        event.target.id !== 'reportModal'
    ) {
        return;
    }

    const modal =
        document.getElementById('reportModal');

    modal.classList.remove('show');

    document.body.style.overflow = '';
}


/* =========================
   ESCAPE KEY
========================= */

document.addEventListener(
    'keydown',
    function(event) {

        if (event.key === 'Escape') {
            closeReportModal();
        }

    }
);

</script>

</body>
</html>