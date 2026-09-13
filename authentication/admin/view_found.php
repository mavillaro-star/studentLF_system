```php
<?php
session_start();
include '../../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

$sql = "SELECT 
            found_items.id,
            found_items.item_name,
            found_items.description,
            found_items.location_found,
            found_items.date_found,
            found_items.image,
            found_items.status,
            users.fullname,
            users.course,
            users.year_level,
            users.contact_number
        FROM found_items
        INNER JOIN users
        ON found_items.user_id = users.id
        ORDER BY found_items.id DESC";

$result = mysqli_query($conn, $sql);
$admin_fullname = $_SESSION['fullname'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Found Reports - Admin Console</title>
<link rel="stylesheet" href="admin.css">

<style>

* {
    box-sizing: border-box;
}

body {
    background-color: #eaf0f6;
    min-height: 100vh;
    padding: 8px;
}

.main-content {
    flex-grow: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
    overflow: hidden;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2px 4px;
}

.page-title {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
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
    background-color: #ef4444;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
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

/* CONTENT */

.content-display {
    width: 100%;
    flex: 1;
    min-height: 0;
    border-radius: 12px;
    background-color: #f7fafc;
    padding: 10px;
    border: 1px solid #edf2f7;
    display: flex;
    flex-direction: column;
    gap: 10px;
    overflow: hidden;
}

.top-bar {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    flex-shrink: 0;
}

.btn-back-dash {
    color: #6b7280;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s ease;
}

.btn-back-dash:hover {
    color: #111827;
}

/* TABLE CONTAINER */

.table-section {
    width: 100%;
    flex: 1;
    min-height: 0;
    background-color: #ffffff;
    border-radius: 9px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.table-responsive {
    width: 100%;
    height: 100%;
    overflow: auto;
}

/* TABLE */

.reports-table {
    width: 100%;
    min-width: 1250px;
    border-collapse: collapse;
    font-size: 13px;
}

.reports-table th {
    position: sticky;
    top: 0;
    z-index: 5;
    background-color: #f3f4f6;
    color: #374151;
    font-size: 12px;
    font-weight: 700;
    text-align: left;
    padding: 11px 12px;
    border-bottom: 1px solid #dfe3e8;
    white-space: nowrap;
}

.reports-table td {
    padding: 11px 12px;
    border-bottom: 1px solid #f1f5f9;
    color: #1f2937;
    vertical-align: middle;
}

.reports-table tbody tr {
    transition: background-color 0.15s ease;
}

.reports-table tbody tr:hover {
    background-color: #f8fafc;
}

.reports-table tbody tr:last-child td {
    border-bottom: none;
}

/* COLUMN ORGANIZATION */

.reports-table th:nth-child(1),
.reports-table td:nth-child(1) {
    width: 55px;
}

.reports-table th:nth-child(2),
.reports-table td:nth-child(2) {
    min-width: 145px;
}

.reports-table th:nth-child(3),
.reports-table td:nth-child(3) {
    min-width: 120px;
}

.reports-table th:nth-child(4),
.reports-table td:nth-child(4) {
    width: 70px;
    text-align: center;
}

.reports-table th:nth-child(5),
.reports-table td:nth-child(5) {
    min-width: 125px;
}

.reports-table th:nth-child(6),
.reports-table td:nth-child(6) {
    width: 80px;
}

.reports-table th:nth-child(7),
.reports-table td:nth-child(7) {
    min-width: 135px;
}

.reports-table th:nth-child(8),
.reports-table td:nth-child(8) {
    min-width: 230px;
    max-width: 280px;
}

.reports-table th:nth-child(9),
.reports-table td:nth-child(9) {
    min-width: 150px;
}

.reports-table th:nth-child(10),
.reports-table td:nth-child(10) {
    min-width: 110px;
}

.reports-table th:nth-child(11),
.reports-table td:nth-child(11) {
    min-width: 110px;
}

.reports-table th:nth-child(12),
.reports-table td:nth-child(12) {
    min-width: 170px;
}

/* IMAGE */

.item-thumb {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 7px;
    border: 1px solid #e5e7eb;
    display: block;
}

.no-image {
    font-size: 11px;
    color: #9ca3af;
    font-style: italic;
}

/* TEXT */

.reports-table td:nth-child(2) {
    font-weight: 600;
}

.reports-table td:nth-child(7) {
    font-weight: 600;
    color: #111827;
}

.reports-table td:nth-child(8) {
    color: #4b5563;
    line-height: 1.45;
    white-space: normal;
    word-break: break-word;
}

.reports-table td:nth-child(9) {
    color: #374151;
}

/* STATUS */

.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 5px 9px;
    border-radius: 7px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.status-pending {
    background-color: #fef3c7;
    color: #b45309;
}

.status-matched {
    background-color: #dbeafe;
    color: #2563eb;
}

.status-claimed {
    background-color: #d1fae5;
    color: #047857;
}

.status-returned {
    background-color: #f3e8ff;
    color: #7e22ce;
}

/* ACTIONS */

.action-links {
    display: flex;
    align-items: center;
    gap: 7px;
    white-space: nowrap;
    font-size: 11px;
}

.action-links a {
    text-decoration: none;
    font-weight: 700;
    color: #2563eb;
    transition: 0.2s ease;
}

.action-links a:hover {
    color: #1d4ed8;
    text-decoration: underline;
}

.action-links span {
    font-size: 11px;
    white-space: nowrap;
}

/* EMPTY */

.empty-state {
    text-align: center;
    color: #6b7280;
    padding: 45px 20px !important;
    font-size: 13px;
}

/* SCROLLBAR */

.table-responsive::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* RESPONSIVE */

@media (max-width: 850px) {

    body {
        padding: 6px;
    }

    .main-content {
        gap: 8px;
    }

    .content-display {
        padding: 8px;
        border-radius: 10px;
    }

    .header {
        padding: 2px 4px;
    }

    .page-title {
        font-size: 17px;
    }

    .user-info {
        display: none;
    }

    .avatar {
        width: 34px;
        height: 34px;
    }

    .table-section {
        border-radius: 8px;
    }

    .reports-table {
        min-width: 1250px;
    }

    .reports-table th,
    .reports-table td {
        padding: 10px 11px;
    }
}

@media (max-width: 500px) {

    .content-display {
        padding: 6px;
    }

    .reports-table {
        min-width: 1200px;
    }

    .btn-back-dash {
        font-size: 12px;
    }
}

</style>

</head>

<body>

<div class="dashboard-wrapper">

<aside class="sidebar">

    <div class="sidebar-brand">

        <div class="brand-icon">SLF</div>

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

        <a href="view_lost.php" class="nav-item">
            <span class="nav-icon">▣</span>
            <span>Lost Item Reports</span>
        </a>

        <a href="view_found.php" class="nav-item active">
            <span class="nav-icon">⌕</span>
            <span>Found Item Reports</span>
        </a>

        <a href="admin_profile.php" class="nav-item">
            <span class="nav-icon">👤</span>
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

<main class="main-content">

<header class="header">

    <h1 class="page-title">
        View Found Reports
    </h1>

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
            <?php echo htmlspecialchars(
                strtoupper(substr(trim($admin_fullname), 0, 1))
            ); ?>
        </div>

    </div>

</header>

<section class="content-display">

    <div class="top-bar">

        <a href="view_reports.php" class="btn-back-dash">
            ← Back to Main Reports Overview
        </a>

    </div>

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
                        <th>Date Found</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                <?php if (mysqli_num_rows($result) > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($result)): ?>

                        <tr>

                            <td>
                                <strong>
                                    #<?php echo (int)$row['id']; ?>
                                </strong>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['fullname']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['course']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['year_level']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['contact_number']); ?>
                            </td>

                            <td>

                                <?php if (!empty($row['image'])): ?>

                                    <img
                                        src="../../uploads/<?php echo htmlspecialchars($row['image']); ?>"
                                        class="item-thumb"
                                        alt="Found Item"
                                    >

                                <?php else: ?>

                                    <span class="no-image">
                                        No Image
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($row['item_name']); ?>
                                </strong>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['description']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['location_found']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['date_found']); ?>
                            </td>

                            <td>

                                <?php

                                switch ($row['status']) {

                                    case "Pending":
                                        echo "<span class='status-badge status-pending'>🟠 Pending</span>";
                                        break;

                                    case "Matched":
                                        echo "<span class='status-badge status-matched'>🔵 Matched</span>";
                                        break;

                                    case "Claimed":
                                        echo "<span class='status-badge status-claimed'>🟢 Claimed</span>";
                                        break;

                                    case "Returned":
                                        echo "<span class='status-badge status-returned'>🟣 Returned</span>";
                                        break;

                                    default:
                                        echo htmlspecialchars($row['status']);
                                }

                                ?>

                            </td>

                            <td>

                                <div class="action-links">

                                    <a href="view_found_details.php?id=<?php echo (int)$row['id']; ?>">
                                        View
                                    </a>

                                    <?php if ($row['status'] == "Pending"): ?>

                                        <span>|</span>

                                        <a href="match_item.php?id=<?php echo (int)$row['id']; ?>">
                                            Match
                                        </a>

                                    <?php elseif ($row['status'] == "Matched"): ?>

                                        <span>|</span>

                                        <span style="color:#2563eb; font-weight:500;">
                                            Waiting for Claim
                                        </span>

                                    <?php elseif ($row['status'] == "Claimed"): ?>

                                        <span>|</span>

                                        <span style="color:#059669; font-weight:500;">
                                            Claim Approved
                                        </span>

                                    <?php elseif ($row['status'] == "Returned"): ?>

                                        <span>|</span>

                                        <span style="color:#7e22ce; font-weight:500;">
                                            Completed
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="12" class="empty-state">
                            No Found Reports Available.
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

</body>
</html>