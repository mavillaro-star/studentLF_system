<?php
session_start();
include '../../db.php';

// Check if user is logged in and admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

$admin_id = intval($_SESSION['user_id']);
$admin_fullname = $_SESSION['fullname'] ?? 'Admin';

// Get notifications for this admin using prepared statement
$notif_stmt = mysqli_prepare($conn, "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
mysqli_stmt_bind_param($notif_stmt, "i", $admin_id);
mysqli_stmt_execute($notif_stmt);
$notifications_query = mysqli_stmt_get_result($notif_stmt);

// Get Lost Reports with fullname
$lost = mysqli_query($conn, "
    SELECT lost_items.*, users.fullname
    FROM lost_items
    INNER JOIN users ON lost_items.user_id = users.id
    ORDER BY lost_items.id DESC
");

if (!$lost) {
    die("Lost query failed: " . mysqli_error($conn));
}

// Get Found Reports with fullname
$found = mysqli_query($conn, "
    SELECT found_items.*, users.fullname
    FROM found_items
    INNER JOIN users ON found_items.user_id = users.id
    ORDER BY found_items.id DESC
");

if (!$found) {
    die("Found query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports Management - Admin Console</title>

    <style>
        :root {
            --bg-main: #f8fafc;
            --surface: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --accent-orange: #ea580c;
            --accent-green: #059669;
            --sidebar-bg: #0f172a;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 3px 0 rgb(0 0 0 / 0.05), 0 1px 2px -1px rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.07), 0 2px 4px -2px rgb(0 0 0 / 0.07);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg-main);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            line-height: 1.5;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* SIDEBAR */

        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: #f8fafc;
            display: flex;
            flex-direction: column;
            padding: 24px 16px;
            border-right: 1px solid #1e293b;
            flex-shrink: 0;
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            margin-bottom: 32px;
            padding: 4px 8px;
        }

        .logo-box {
            width: 40px;
            height: 40px;
            background: rgba(37, 99, 235, 0.15);
            border: 1px solid rgba(37, 99, 235, 0.3);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .brand-icon {
            width: 22px;
            height: 22px;
            stroke: #3b82f6;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
        }

        .brand-title {
            font-size: 0.9rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            color: #ffffff;
            line-height: 1.2;
        }

        .brand-sub {
            font-size: 0.725rem;
            color: #64748b;
            font-weight: 500;
        }

        .nav-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            color: #94a3b8;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .nav-svg {
            width: 19px;
            height: 19px;
            stroke: #94a3b8;
            transition: stroke 0.2s ease;
            flex-shrink: 0;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #f8fafc;
        }

        .nav-item:hover .nav-svg {
            stroke: #f8fafc;
        }

        .nav-item.active {
            background: var(--primary);
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .nav-item.active .nav-svg {
            stroke: #ffffff;
        }

        .logout {
            margin-top: auto;
            border-top: 1px solid #1e293b;
            padding-top: 16px;
        }

        .logout-btn {
            color: #f87171 !important;
        }

        .logout-btn .nav-svg {
            stroke: #f87171 !important;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.12);
            color: #fca5a5 !important;
        }

        .logout-btn:hover .nav-svg {
            stroke: #fca5a5 !important;
        }

        /* MAIN CONTENT */

        .main-content {
            flex: 1;
            padding: 32px 40px;
            width: 100%;
            min-width: 0;
            overflow-x: hidden;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .page-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            background: var(--primary);
            color: #fff;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-sm);
        }

        .user-info {
            display: flex;
            flex-direction: column;
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .user-role {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        /* REST OF ORIGINAL DESIGN */

        .content-display {
            width: 100%;
            border-radius: 16px;
            background-color: #f7fafc;
            padding: 24px;
            border: 1px solid #edf2f7;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .alert-msg {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .section-card {
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: #1f2937;
        }

        .notif-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .notif-item {
            font-size: 13px;
            color: #374151;
            padding: 8px 12px;
            background-color: #f9fafb;
            border-radius: 8px;
            border-left: 3px solid #3b82f6;
            display: flex;
            justify-content: space-between;
        }

        .notif-time {
            color: #6b7280;
            font-size: 11px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .reports-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .reports-table th {
            background-color: #f3f4f6;
            color: #4b5563;
            font-weight: 700;
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .reports-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f3f4f6;
            color: #1f2937;
            vertical-align: middle;
        }

        .reports-table tr:hover {
            background-color: #f9fafb;
        }

        .item-thumb {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }

        .no-img {
            font-size: 11px;
            color: #9ca3af;
            font-style: italic;
        }

        .status-select {
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background-color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            outline: none;
        }

        .status-select:focus {
            border-color: #3b82f6;
        }

        .nav-quick-actions {
            display: flex;
            gap: 12px;
        }

        .btn-sub-link {
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 8px;
            background-color: #edf2f7;
            color: #374151;
            transition: all 0.2s ease;
        }

        .btn-sub-link:hover {
            background-color: #e2e8f0;
            color: #111827;
        }

        @media (max-width: 1100px) {
            .sidebar {
                width: 230px;
            }

            .main-content {
                padding: 28px 24px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding: 16px;
                border-right: none;
                border-bottom: 1px solid #1e293b;
            }

            .brand-link {
                margin-bottom: 18px;
            }

            .nav-menu {
                flex-direction: row;
                overflow-x: auto;
                gap: 6px;
                padding-bottom: 6px;
            }

            .nav-menu li {
                flex-shrink: 0;
            }

            .nav-item {
                white-space: nowrap;
                padding: 10px 13px;
            }

            .logout {
                margin-top: 14px;
                border-top: 1px solid #1e293b;
                padding-top: 12px;
            }

            .logout .nav-item {
                width: fit-content;
            }

            .main-content {
                padding: 20px 16px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                margin-bottom: 20px;
            }

            .user-profile {
                align-self: stretch;
                justify-content: flex-end;
            }

            .content-display {
                padding: 18px;
            }

            .notif-item {
                flex-direction: column;
                gap: 5px;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                padding: 14px 12px;
            }

            .brand-link {
                padding: 4px;
            }

            .brand-title {
                font-size: 0.82rem;
            }

            .brand-sub {
                font-size: 0.65rem;
            }

            .logo-box {
                width: 38px;
                height: 38px;
            }

            .main-content {
                padding: 18px 12px;
            }

            .page-title {
                font-size: 1.35rem;
            }

            .user-name {
                font-size: 0.82rem;
            }

            .user-role {
                font-size: 0.7rem;
            }

            .avatar {
                width: 36px;
                height: 36px;
                font-size: 0.85rem;
            }

            .content-display {
                padding: 14px;
            }

            .section-card {
                padding: 16px;
            }
        }
    </style>
</head>

<body>

<div class="dashboard-container">

    <aside class="sidebar">

        <a href="admin_dashboard.php" class="brand-link">

            <div class="logo-box">
                <svg class="brand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    <path d="M10 7v3l2 2"/>
                </svg>
            </div>
            <div class="brand-text">
                    <span class="brand-title">STUDENT L&F</span>
                    <span class="brand-sub">View Reports</span>
            </div>

        </a>

        <ul class="nav-menu">

            <li>
                <a href="admin_dashboard.php" class="nav-item">

                    <svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                    </svg>

                    <span>Dashboard</span>

                </a>
            </li>

            <li>
                <a href="manage_users.php" class="nav-item">

                    <svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>

                    <span>Manage Users</span>

                </a>
            </li>

            <li>
                <a href="view_reports.php" class="nav-item active">

                    <svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="8" y1="13" x2="16" y2="13"/>
                        <line x1="8" y1="17" x2="16" y2="17"/>
                    </svg>

                    <span>View Reports</span>

                </a>
            </li>

            <li>
                <a href="admin_profile.php" class="nav-item">

                    <svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 21c0-4 3.5-7 8-7s8 3 8 7"/>
                    </svg>

                    <span>My Profile</span>

                </a>
            </li>

        </ul>

        <div class="logout">

            <a href="ad_logout_con.php" class="nav-item logout-btn">

                <svg class="nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>

                <span>Log Out</span>

            </a>

        </div>

    </aside>

    <main class="main-content">

        <header class="header">

            <h1 class="page-title">Manage Reports & Notifications</h1>

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
                    <?php echo strtoupper(substr($admin_fullname, 0, 1)); ?>
                </div>

            </div>

        </header>

        <section class="content-display">

            <?php if (isset($_SESSION['success_message'])): ?>

                <div class="alert-msg alert-success">
                    <?php
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                </div>

            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>

                <div class="alert-msg alert-error">
                    <?php
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                </div>

            <?php endif; ?>

            <div class="section-card">

                <h2 class="section-title">
                    🔔 Recent Admin Notifications
                </h2>

                <?php if (mysqli_num_rows($notifications_query) > 0): ?>

                    <ul class="notif-list">

                        <?php while ($notif = mysqli_fetch_assoc($notifications_query)): ?>

                            <li class="notif-item">

                                <span>
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                </span>

                                <span class="notif-time">
                                    <?php echo date("M d, Y h:i A", strtotime($notif['created_at'])); ?>
                                </span>

                            </li>

                        <?php endwhile; ?>

                    </ul>

                <?php else: ?>

                    <p style="font-size: 13px; color: #6b7280;">
                        No new notifications found.
                    </p>

                <?php endif; ?>

            </div>

            <div class="section-card">

                <div style="display: flex; justify-content: space-between; align-items: center;">

                    <h2 class="section-title">
                        📦 Lost Item Reports
                    </h2>

                    <div class="nav-quick-actions">

                        <a href="view_lost_reports.php" class="btn-sub-link">
                            View Full Lost List →
                        </a>

                    </div>

                </div>

                <div class="table-responsive">

                    <table class="reports-table">

                        <thead>

                            <tr>
                                <th>ID</th>
                                <th>Reporter</th>
                                <th>Item Name</th>
                                <th>Image</th>
                                <th>Description</th>
                                <th>Date Lost</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Quick Status Action</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if (mysqli_num_rows($lost) > 0): ?>

                                <?php while ($row = mysqli_fetch_assoc($lost)): ?>

                                    <tr>

                                        <td>
                                            <strong>#<?php echo $row['id']; ?></strong>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($row['fullname']); ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?php echo htmlspecialchars($row['item_name']); ?>
                                            </strong>
                                        </td>

                                        <td>

                                            <?php if (!empty($row['image'])): ?>

                                                <img
                                                    src="../../uploads/<?php echo htmlspecialchars($row['image']); ?>"
                                                    class="item-thumb"
                                                    alt="Lost Item"
                                                >

                                            <?php else: ?>

                                                <span class="no-img">
                                                    No Image
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($row['description']); ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($row['date_lost']); ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($row['location_lost']); ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </strong>
                                        </td>

                                        <td>

                                            <form action="update_status.php" method="POST">

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?php echo $row['id']; ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="type"
                                                    value="lost"
                                                >

                                                <select
                                                    name="status"
                                                    class="status-select"
                                                    onchange="this.form.submit()"
                                                >

                                                    <option value="Pending" <?php echo ($row['status'] == "Pending" ? "selected" : ""); ?>>
                                                        🟠 Pending
                                                    </option>

                                                    <option value="Lost" <?php echo ($row['status'] == "Lost" ? "selected" : ""); ?>>
                                                        🔴 Lost
                                                    </option>

                                                    <option value="Matched" <?php echo ($row['status'] == "Matched" ? "selected" : ""); ?>>
                                                        🔵 Matched
                                                    </option>

                                                    <option value="Claimed" <?php echo ($row['status'] == "Claimed" ? "selected" : ""); ?>>
                                                        🟢 Claimed
                                                    </option>

                                                    <option value="Rejected" <?php echo ($row['status'] == "Rejected" ? "selected" : ""); ?>>
                                                        ⚫ Rejected
                                                    </option>

                                                </select>

                                            </form>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="9" style="text-align: center; color: #6b7280; padding: 16px;">
                                        No lost reports found.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

            <div class="section-card">

                <div style="display: flex; justify-content: space-between; align-items: center;">

                    <h2 class="section-title">
                        🔍 Found Item Reports
                    </h2>

                    <div class="nav-quick-actions">

                        <a href="view_found_reports.php" class="btn-sub-link">
                            View Full Found List →
                        </a>

                    </div>

                </div>

                <div class="table-responsive">

                    <table class="reports-table">

                        <thead>

                            <tr>
                                <th>ID</th>
                                <th>Reporter</th>
                                <th>Item Name</th>
                                <th>Image</th>
                                <th>Description</th>
                                <th>Date Found</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Quick Status Action</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if (mysqli_num_rows($found) > 0): ?>

                                <?php while ($row = mysqli_fetch_assoc($found)): ?>

                                    <tr>

                                        <td>
                                            <strong>#<?php echo $row['id']; ?></strong>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($row['fullname']); ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?php echo htmlspecialchars($row['item_name']); ?>
                                            </strong>
                                        </td>

                                        <td>

                                            <?php if (!empty($row['image'])): ?>

                                                <img
                                                    src="../../uploads/<?php echo htmlspecialchars($row['image']); ?>"
                                                    class="item-thumb"
                                                    alt="Found Item"
                                                >

                                            <?php else: ?>

                                                <span class="no-img">
                                                    No Image
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($row['description']); ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($row['date_found']); ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($row['location_found']); ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </strong>
                                        </td>

                                        <td>

                                            <form action="update_status.php" method="POST">

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?php echo $row['id']; ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="type"
                                                    value="found"
                                                >

                                                <select
                                                    name="status"
                                                    class="status-select"
                                                    onchange="this.form.submit()"
                                                >

                                                    <option value="Pending" <?php echo ($row['status'] == "Pending" ? "selected" : ""); ?>>
                                                        🟠 Pending
                                                    </option>

                                                    <option value="Found" <?php echo ($row['status'] == "Found" ? "selected" : ""); ?>>
                                                        🔴 Found
                                                    </option>

                                                    <option value="Matched" <?php echo ($row['status'] == "Matched" ? "selected" : ""); ?>>
                                                        🔵 Matched
                                                    </option>

                                                    <option value="Claimed" <?php echo ($row['status'] == "Claimed" ? "selected" : ""); ?>>
                                                        🟢 Claimed
                                                    </option>

                                                    <option value="Rejected" <?php echo ($row['status'] == "Rejected" ? "selected" : ""); ?>>
                                                        ⚫ Rejected
                                                    </option>

                                                </select>

                                            </form>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="9" style="text-align: center; color: #6b7280; padding: 16px;">
                                        No found reports found.
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