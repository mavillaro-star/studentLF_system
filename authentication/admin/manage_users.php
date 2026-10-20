<?php
session_start();
include '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

$users = mysqli_query(
    $conn,
    "SELECT id, fullname, course, year_level, contact_number, email, role
     FROM users
     ORDER BY
        CASE
            WHEN role = 'admin' THEN 1
            WHEN role = 'student' THEN 2
            ELSE 3
        END,
        id ASC"
);

$lost_count = 0;
$found_count = 0;
$modals = [];

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
    <title>Manage Users - Admin Console</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            overflow-x: hidden;
            background: #f5f7fb;
        }

        /* MAIN CONTENT */
        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            min-width: 0;
            min-height: 100vh;
        }

        .content-display {
            width: 100%;
            min-width: 0;
            padding: 30px;
        }

        /* TOP BAR */
        .top-bar {
            width: 100%;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 20px;
        }

        .add-user {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 11px 16px;
            background: #2563eb;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            box-shadow: 0 3px 7px rgba(37, 99, 235, 0.15);
            transition: 0.2s ease;
        }

        .add-user:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .add-user-icon {
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid rgba(255, 255, 255, 0.9);
            border-radius: 5px;
            font-size: 17px;
            line-height: 1;
        }

        /* ALERT MESSAGES */
        .alert-message {
            width: 100%;
            padding: 13px 16px;
            margin-bottom: 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .alert-message.success {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .alert-message.error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        /* TABLE SECTION */
        .table-section {
            width: 100%;
            min-width: 0;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 3px 10px rgba(15, 23, 42, 0.04);
        }

        .table-section h2 {
            margin: 0 0 22px;
            color: #111827;
            font-size: 20px;
            font-weight: 700;
        }

        /* RESPONSIVE TABLE */
        .table-responsive {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }

        .table-responsive::-webkit-scrollbar {
            height: 7px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .users-table {
            width: 100%;
            min-width: 1050px;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: auto;
        }

        .users-table thead th {
            padding: 15px 14px;
            background: #f3f6fa;
            color: #475569;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .users-table thead th:first-child {
            border-left: 1px solid #e5e7eb;
            border-radius: 7px 0 0 0;
        }

        .users-table thead th:last-child {
            border-right: 1px solid #e5e7eb;
            border-radius: 0 7px 0 0;
            text-align: center;
        }

        .users-table tbody td {
            padding: 15px 14px;
            color: #374151;
            font-size: 13px;
            border-bottom: 1px solid #edf0f4;
            vertical-align: middle;
            white-space: nowrap;
        }

        .users-table tbody tr {
            transition: background 0.2s ease;
        }

        .users-table tbody tr:hover {
            background: #f8fafc;
        }

        .users-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .users-table td:first-child {
            color: #64748b;
            font-weight: 600;
        }

        .users-table td:nth-child(2) {
            min-width: 175px;
        }

        .users-table td:nth-child(6) {
            min-width: 220px;
        }

        .users-table th:last-child,
        .users-table td:last-child {
            min-width: 130px;
            text-align: center;
        }

        .users-table strong {
            color: #111827;
            font-weight: 600;
        }

        /* ROLE BADGES */
        .role-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .role-admin {
            background: #eef2ff;
            color: #4338ca;
        }

        .role-student {
            background: #ecfdf5;
            color: #047857;
        }

        .empty-value {
            color: #9ca3af;
        }

        /* ACTION ICONS */
        .action-links {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .action-links a {
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.2s ease,
                        border-color 0.2s ease,
                        transform 0.2s ease;
        }

        .action-links a:hover {
            transform: translateY(-1px);
        }

        .action-links a:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 3px;
        }

        /* VIEW */
        .view-btn {
            color: #2563eb;
            background: #eff6ff;
            border-color: #dbeafe !important;
        }

        .view-btn:hover {
            background: #dbeafe;
        }

        /* EDIT */
        .edit-btn {
            color: #b7791f;
            background: #fffbeb;
            border-color: #fde68a !important;
        }

        .edit-btn:hover {
            background: #fef3c7;
        }

        /* DELETE */
        .delete-btn {
            color: #dc2626;
            background: #fef2f2;
            border-color: #fecaca !important;
        }

        .delete-btn:hover {
            background: #fee2e2;
        }

        /* EYE ICON */
        .view-icon {
            position: relative;
            width: 17px;
            height: 11px;
            border: 1.8px solid currentColor;
            border-radius: 80% 20%;
            transform: rotate(45deg);
        }

        .view-icon::after {
            content: "";
            position: absolute;
            width: 4px;
            height: 4px;
            border: 1.5px solid currentColor;
            border-radius: 50%;
            top: 1.5px;
            left: 4.5px;
        }

        /* PENCIL ICON */
        .edit-icon {
            position: relative;
            width: 15px;
            height: 5px;
            border: 2px solid currentColor;
            border-radius: 1px;
            transform: rotate(-45deg);
        }

        /* TRASH ICON */
        .delete-icon {
            position: relative;
            width: 11px;
            height: 13px;
            border: 1.7px solid currentColor;
            border-top: 0;
            border-radius: 0 0 2px 2px;
        }

        .delete-icon::before {
            content: "";
            position: absolute;
            width: 15px;
            height: 2px;
            background: currentColor;
            top: -4px;
            left: -3.7px;
            border-radius: 2px;
        }

        .delete-icon::after {
            content: "";
            position: absolute;
            width: 5px;
            height: 2px;
            background: currentColor;
            top: -7px;
            left: 1px;
            border-radius: 2px 2px 0 0;
        }

        /* EMPTY TABLE */
        .empty-table {
            text-align: center;
            color: #6b7280 !important;
            padding: 35px 20px !important;
            white-space: normal !important;
        }

        /* DELETE CONFIRMATION MODAL */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.58);
            padding: 20px;
        }

        .modal:target {
            display: flex;
        }

        .modal-content {
            width: 100%;
            max-width: 400px;
            padding: 25px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.18);
        }

        .modal-content p {
            margin: 0 0 22px;
            color: #374151;
            font-size: 15px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .modal-content strong {
            color: #111827;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .btn-confirm,
        .btn-cancel {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            border: 0;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-confirm {
            background: #dc2626;
            color: #fff;
        }

        .btn-confirm:hover {
            background: #b91c1c;
        }

        .btn-cancel {
            background: #e5e7eb;
            color: #374151;
        }

        .btn-cancel:hover {
            background: #d1d5db;
        }

        /* TABLET */
        @media (max-width: 1100px) {
            .main-content {
                margin-left: 230px;
                width: calc(100% - 230px);
            }

            .content-display {
                padding: 24px;
            }

            .table-section {
                padding: 20px;
            }
        }

        @media (max-width: 900px) {
            .main-content {
                margin-left: 210px;
                width: calc(100% - 210px);
            }

            .content-display {
                padding: 18px;
            }

            .table-section {
                padding: 18px;
            }

            .table-section h2 {
                font-size: 19px;
            }
        }

        /* MOBILE */
        @media (max-width: 700px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                min-height: 100vh;
            }

            .content-display {
                width: 100%;
                padding: 15px;
            }

            .top-bar {
                width: 100%;
                margin-bottom: 15px;
            }

            .add-user {
                padding: 10px 13px;
                font-size: 12px;
            }

            .table-section {
                width: 100%;
                padding: 15px;
                border-radius: 10px;
            }

            .table-section h2 {
                font-size: 18px;
                margin-bottom: 17px;
            }

            .users-table {
                min-width: 1000px;
            }

            .users-table th {
                padding: 13px 12px;
                font-size: 10px;
            }

            .users-table td {
                padding: 13px 12px;
                font-size: 12px;
            }

            .action-links {
                gap: 7px;
            }

            .action-links a {
                width: 33px;
                height: 33px;
            }

            .modal {
                padding: 15px;
            }

            .modal-content {
                max-width: 100%;
                padding: 21px;
            }
        }

        @media (max-width: 480px) {
            .content-display {
                padding: 12px;
            }

            .top-bar {
                margin-bottom: 13px;
            }

            .add-user {
                width: 100%;
                justify-content: center;
                padding: 12px;
            }

            .table-section {
                padding: 12px;
                border-radius: 9px;
            }

            .table-section h2 {
                font-size: 17px;
            }

            .alert-message {
                font-size: 12px;
                padding: 12px;
            }

            .users-table {
                min-width: 980px;
            }

            .modal {
                padding: 12px;
            }

            .modal-content {
                padding: 18px;
                border-radius: 10px;
            }

            .modal-content p {
                font-size: 14px;
            }

            .modal-actions {
                flex-direction: column-reverse;
            }

            .btn-confirm,
            .btn-cancel {
                width: 100%;
                text-align: center;
            }
        }
    </style>
    <link rel="stylesheet" href="../../responsive.css">
</head>

<body class="admin-sidebar-page">
<input type="checkbox" id="admin-menu-toggle" aria-label="Toggle navigation menu">
<label class="mobile-sidebar-toggle" for="admin-menu-toggle" aria-label="Toggle navigation menu">☰</label>
<label class="mobile-sidebar-overlay" for="admin-menu-toggle" aria-hidden="true"></label>

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

                <a href="manage_users.php" class="nav-item active">
                    <span class="nav-icon">♙</span>
                    <span>Manage Users</span>
                </a>

                <a href="view_lost.php" class="nav-item">
                    <span class="nav-icon">▣</span>
                    <span>Lost Item Reports</span>
                    <?php if ($lost_count > 0): ?>
                        <span class="nav-count"><?php echo $lost_count; ?></span>
                    <?php endif; ?>
                </a>

                <a href="view_found.php" class="nav-item">
                    <span class="nav-icon">⌕</span>
                    <span>Found Item Reports</span>
                    <?php if ($found_count > 0): ?>
                        <span class="nav-count"><?php echo $found_count; ?></span>
                    <?php endif; ?>
                </a>

                <a href="admin_profile.php" class="nav-item">
                    <span class="nav-icon">○</span>
                    <span>My Profile</span>
                </a>
            </nav>

            <div class="sidebar-bottom">
                <a href="../../authentication/logout.php" class="nav-item" onclick="return confirmLogout();">
                    <span class="nav-icon">↪</span>
                    <span>Log Out</span>
                </a>
            </div>
        </aside>

        <main class="main-content">
            <section class="content-display">

                <div class="top-bar">
                    <a href="add_new_user.php" class="add-user">
                        <span class="add-user-icon">+</span>
                        <span>Add New User</span>
                    </a>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert-message success">
                        <?php
                        echo htmlspecialchars($_SESSION['success_message']);
                        unset($_SESSION['success_message']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert-message error">
                        <?php
                        echo htmlspecialchars($_SESSION['error_message']);
                        unset($_SESSION['error_message']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="table-section">
                    <h2>User Accounts</h2>

                    <div class="table-responsive">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Course</th>
                                    <th>Year Level</th>
                                    <th>Contact Number</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if ($users && mysqli_num_rows($users) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($users)): ?>
                                        <?php
                                        $raw_id = (int)($row['id'] ?? 0);
                                        $id = htmlspecialchars((string)$raw_id);
                                        $name = htmlspecialchars($row['fullname'] ?? '');
                                        $course = htmlspecialchars($row['course'] ?? '');
                                        $year = htmlspecialchars($row['year_level'] ?? '');
                                        $contact = htmlspecialchars($row['contact_number'] ?? '');
                                        $email = htmlspecialchars($row['email'] ?? '');
                                        $role = strtolower($row['role'] ?? '');

                                        if ($role === 'admin') {
                                            $role_display = '<span class="role-badge role-admin">Admin</span>';
                                            $course_display = '<span class="empty-value">—</span>';
                                            $year_display = '<span class="empty-value">—</span>';
                                        } else {
                                            $role_display = '<span class="role-badge role-student">Student</span>';
                                            $course_display = $course !== '' ? $course : '<span class="empty-value">—</span>';
                                            $year_display = $year !== '' ? $year : '<span class="empty-value">—</span>';
                                        }
                                        ?>

                                        <tr>
                                            <td><?php echo $id; ?></td>
                                            <td><strong><?php echo $name; ?></strong></td>
                                            <td><?php echo $course_display; ?></td>
                                            <td><?php echo $year_display; ?></td>
                                            <td>
                                                <?php echo $contact !== '' ? $contact : '<span class="empty-value">—</span>'; ?>
                                            </td>
                                            <td><?php echo $email; ?></td>
                                            <td><?php echo $role_display; ?></td>
                                            <td>
                                                <div class="action-links">
                                                    <a href="view.php?id=<?php echo $id; ?>" class="view-btn" title="View User" aria-label="View User">
                                                        <span class="view-icon"></span>
                                                    </a>
                                                    <a href="edit_user.php?id=<?php echo $id; ?>" class="edit-btn" title="Edit User" aria-label="Edit User">
                                                        <span class="edit-icon"></span>
                                                    </a>
                                                    <a href="#deleteModal<?php echo $id; ?>" class="delete-btn" title="Delete User" aria-label="Delete User">
                                                        <span class="delete-icon"></span>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>

                                        <?php
                                        $modals[] = '
                                        <div id="deleteModal'.$id.'" class="modal">
                                            <div class="modal-content">
                                                <p>
                                                    Are you sure you want to delete
                                                    <strong>'.$name.'</strong>?
                                                </p>
                                                <form action="delete_user.php" method="POST">
                                                    <input type="hidden" name="id" value="'.$id.'">
                                                    <div class="modal-actions">
                                                        <button type="submit" class="btn-confirm">Yes, Delete</button>
                                                        <a href="#" class="btn-cancel">Cancel</a>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        ';
                                        ?>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="empty-table">No user accounts found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>
        </main>

    </div>

    <?php
    foreach ($modals as $modal) {
        echo $modal;
    }
    ?>

    <script>
        function confirmLogout() {
            return confirm("Are you sure you want to log out?");
        }
    </script>

</body>
</html>