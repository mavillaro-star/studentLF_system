<?php
session_start();
include '../../db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "admin") {
    header("Location: ../login.php");
    exit();
}

// Check if ID exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid user ID.";
    header("Location: manage_users.php");
    exit();
}

$id = intval($_GET['id']);

// Get user
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: manage_users.php");
    exit();
}

$user = mysqli_fetch_assoc($result);

// Profile picture
$profilePic = !empty($user['profile_picture'])
    ? $user['profile_picture']
    : '';

$relative_folder = "../../uploads/profile_pics/";
$server_path = __DIR__ . "/" . $relative_folder . $profilePic;

if (!empty($profilePic) && file_exists($server_path)) {
    $image_url = $relative_folder . rawurlencode($profilePic);
} else {
    $image_url = "../../uploads/default.png";
}

// User initials
$user_fullname = trim($user['fullname'] ?? 'User');
$name_parts = preg_split('/\s+/', $user_fullname);

if (count($name_parts) >= 2) {
    $user_initials = strtoupper(
        substr($name_parts[0], 0, 1) .
        substr($name_parts[count($name_parts) - 1], 0, 1)
    );
} else {
    $user_initials = strtoupper(substr($user_fullname, 0, 2));
}

$role = strtolower($user['role'] ?? 'student');

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

    <title>User Profile Details - Admin Console</title>

    <link rel="stylesheet" href="admin.css">

    <style>
        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            min-height: 100vh;
            padding: 28px 30px;
        }

        .user-page-header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
        }

        .user-page-heading h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 5px;
        }

        .user-page-heading p {
            font-size: 13px;
            color: var(--muted);
        }
        

        /* =====================================================
           BACK LINK
        ===================================================== */

        .user-page-toolbar {
            width: 100%;
            margin-bottom: 16px;
        }

        .user-back-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            color: var(--muted);
            font-size: 13px;
            font-weight: 600;

            transition: 0.2s ease;
        }

        .user-back-link:hover {
            color: var(--text);
        }


        /* =====================================================
           MAIN PROFILE LAYOUT
        ===================================================== */

        .user-profile-layout {
            width: 100%;

            display: grid;
            grid-template-columns: 300px minmax(0, 1fr);

            gap: 20px;
            align-items: stretch;
        }


        /* =====================================================
           LEFT PROFILE CARD
        ===================================================== */

        .user-profile-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;

            padding: 28px 24px;

            box-shadow: var(--shadow);

            display: flex;
            flex-direction: column;
            align-items: center;
        }


        /* PROFILE IMAGE */

        .user-photo {
            width: 145px;
            height: 145px;

            border-radius: 14px;
            overflow: hidden;

            border: 1px solid var(--border);

            background: #f1f5f9;

            margin-bottom: 18px;
        }

        .user-photo img {
            width: 100%;
            height: 100%;

            object-fit: cover;

            display: block;
        }


        /* INITIALS FALLBACK */

        .user-initials {
            width: 100%;
            height: 100%;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #dbeafe;
            color: var(--primary);

            font-size: 40px;
            font-weight: 700;
        }


        /* USER NAME */

        .user-profile-name {
            width: 100%;

            text-align: center;

            font-size: 19px;
            font-weight: 700;

            color: var(--text);

            line-height: 1.35;

            margin-bottom: 6px;
        }

        .user-profile-email {
            width: 100%;

            text-align: center;

            font-size: 12px;
            color: var(--muted);

            word-break: break-word;

            margin-bottom: 15px;
        }


        /* ROLE */

        .user-role-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 10px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .user-role-admin {
            background: #fee2e2;
            color: var(--red);
        }

        .user-role-student {
            background: #dbeafe;
            color: var(--primary-dark);
        }


        /* SUMMARY */

        .user-summary-divider {
            width: 100%;
            height: 1px;

            background: var(--border);

            margin: 22px 0 18px;
        }

        .user-summary-row {
            width: 100%;

            display: flex;
            justify-content: space-between;
            align-items: flex-start;

            gap: 15px;

            margin-bottom: 14px;
        }

        .user-summary-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--muted);
        }

        .user-summary-value {
            font-size: 12px;
            font-weight: 600;
            color: var(--text);

            text-align: right;
            word-break: break-word;
        }


        /* =====================================================
           RIGHT INFORMATION CARD
        ===================================================== */

        .user-information-card {
            background: var(--surface);

            border: 1px solid var(--border);
            border-radius: 14px;

            box-shadow: var(--shadow);

            overflow: hidden;
        }


        /* INFORMATION HEADER */

        .user-information-header {
            display: flex;
            justify-content: space-between;
            align-items: center;

            padding: 20px 24px;

            border-bottom: 1px solid var(--border);
        }

        .user-information-title {
            font-size: 16px;
            font-weight: 700;

            color: var(--text);
        }

        .user-information-id {
            font-size: 11px;
            color: var(--muted);
        }


        /* INFORMATION BODY */

        .user-information-body {
            padding: 4px 24px 14px;
        }

        .user-information-row {
            display: grid;

            grid-template-columns: 180px minmax(0, 1fr);

            align-items: center;

            min-height: 56px;

            border-bottom: 1px solid #f1f5f9;
        }

        .user-information-row:last-child {
            border-bottom: none;
        }

        .user-information-label {
            font-size: 12px;
            font-weight: 600;

            color: var(--muted);
        }

        .user-information-value {
            font-size: 13px;
            font-weight: 500;

            color: var(--text);

            word-break: break-word;
        }


        /* =====================================================
           ACTIONS
        ===================================================== */

        .user-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;

            gap: 10px;

            padding: 17px 24px;

            background: #f8fafc;

            border-top: 1px solid var(--border);
        }

        .user-action-btn {
            min-height: 37px;

            padding: 0 16px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            border-radius: 8px;

            font-size: 12px;
            font-weight: 600;

            transition: 0.2s ease;
        }

        .user-btn-back {
            background: #ffffff;

            color: var(--text);

            border: 1px solid var(--border);
        }

        .user-btn-back:hover {
            background: #f1f5f9;
        }

        .user-btn-edit {
            background: var(--primary);

            color: #ffffff;

            border: 1px solid var(--primary);
        }

        .user-btn-edit:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 1000px) {

            .main-content {
                padding: 24px 20px;
            }

            .user-profile-layout {
                grid-template-columns: 240px minmax(0, 1fr);
            }

            .user-information-row {
                grid-template-columns: 140px minmax(0, 1fr);
            }

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 760px) {

            /*
               On mobile the sidebar may be handled
               by your existing admin.css.
               Main content loses desktop offset.
            */

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px 16px;
            }

            .user-page-header {
                align-items: flex-start;
            }

            .user-page-heading h1 {
                font-size: 20px;
            }

            .user-profile-layout {
                grid-template-columns: 1fr;
            }

            .user-profile-card {
                padding: 24px 20px;
            }

            .user-information-row {
                grid-template-columns: 1fr;

                gap: 5px;

                padding: 13px 0;

                min-height: auto;
            }

            .user-actions {
                flex-direction: column;
            }

            .user-action-btn {
                width: 100%;
            }

        }


        /* =====================================================
           SMALL MOBILE
        ===================================================== */

        @media (max-width: 450px) {

            .main-content {
                padding: 16px 12px;
            }

            .user-page-header {
                margin-bottom: 18px;
            }

            .user-page-heading p {
                font-size: 11px;
            }

            .user-photo {
                width: 125px;
                height: 125px;
            }

            .user-profile-name {
                font-size: 18px;
            }

            .user-information-header {
                padding: 17px 18px;
            }

            .user-information-body {
                padding: 3px 18px 12px;
            }

            .user-actions {
                padding: 15px 18px;
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

    <!-- =====================================================
         SIDEBAR
         YOUR EXISTING NAVIGATION — NOT REDESIGNED
    ====================================================== -->

    <aside class="sidebar">

        <div class="sidebar-brand">

            <div class="brand-icon">
                SLF
            </div>

            <div>
                <h2>Lost & Found</h2>
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


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">

        <div class="user-page-header">

            <div class="user-page-heading">

                <h1>
                    User Profile Details
                </h1>

                <p>
                    View account information and user details.
                </p>

            </div>


        </div>


        <!-- PROFILE -->
        <div class="user-profile-layout">

            <div class="user-profile-card">

                <div class="user-photo">

                    <?php if (!empty($profilePic) && file_exists($server_path)): ?>

                        <img
                            src="<?php echo htmlspecialchars($image_url); ?>"
                            alt="Profile Picture"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        >

                        <div
                            class="user-initials"
                            style="display:none;"
                        >
                            <?php echo htmlspecialchars($user_initials); ?>
                        </div>

                    <?php else: ?>

                        <div class="user-initials">
                            <?php echo htmlspecialchars($user_initials); ?>
                        </div>

                    <?php endif; ?>

                </div>


                <h2 class="user-profile-name">
                    <?php echo htmlspecialchars($user_fullname); ?>
                </h2>


                <p class="user-profile-email">
                    <?php echo htmlspecialchars($user['email'] ?? 'No email address'); ?>
                </p>


                <span
                    class="user-role-badge
                    <?php echo ($role === 'admin')
                        ? 'user-role-admin'
                        : 'user-role-student'; ?>"
                >
                    <?php echo htmlspecialchars(ucfirst($role)); ?>
                </span>


                <div class="user-summary-divider"></div>


                <div class="user-summary-row">

                    <span class="user-summary-label">
                        User ID
                    </span>

                    <span class="user-summary-value">
                        #<?php echo htmlspecialchars($user['id']); ?>
                    </span>

                </div>


                <?php if ($role === 'student'): ?>

                    <div class="user-summary-row">

                        <span class="user-summary-label">
                            Course
                        </span>

                        <span class="user-summary-value">
                            <?php
                            echo !empty($user['course'])
                                ? htmlspecialchars($user['course'])
                                : 'N/A';
                            ?>
                        </span>

                    </div>


                    <div class="user-summary-row">

                        <span class="user-summary-label">
                            Year Level
                        </span>

                        <span class="user-summary-value">
                            <?php
                            echo !empty($user['year_level'])
                                ? htmlspecialchars($user['year_level'])
                                : 'N/A';
                            ?>
                        </span>

                    </div>

                <?php endif; ?>


                <div class="user-summary-row">

                    <span class="user-summary-label">
                        Contact
                    </span>

                    <span class="user-summary-value">
                        <?php
                        echo !empty($user['contact_number'])
                            ? htmlspecialchars($user['contact_number'])
                            : 'N/A';
                        ?>
                    </span>

                </div>

            </div>

            <div class="user-information-card">


                <div class="user-information-header">

                    <h2 class="user-information-title">
                        Account Information
                    </h2>

                    <span class="user-information-id">
                        ID #<?php echo htmlspecialchars($user['id']); ?>
                    </span>

                </div>


                <div class="user-information-body">



                    <div class="user-information-row">

                        <div class="user-information-label">
                            Full Name
                        </div>

                        <div class="user-information-value">

                            <strong>
                                <?php echo htmlspecialchars($user['fullname'] ?? 'N/A'); ?>
                            </strong>

                        </div>

                    </div>


                    <!-- COURSE -->

                    <?php if ($role === 'student'): ?>

                        <div class="user-information-row">

                            <div class="user-information-label">
                                Course
                            </div>

                            <div class="user-information-value">

                                <?php
                                echo !empty($user['course'])
                                    ? htmlspecialchars($user['course'])
                                    : 'Not provided';
                                ?>

                            </div>

                        </div>


                        <!-- YEAR -->

                        <div class="user-information-row">

                            <div class="user-information-label">
                                Year Level
                            </div>

                            <div class="user-information-value">

                                <?php
                                echo !empty($user['year_level'])
                                    ? htmlspecialchars($user['year_level'])
                                    : 'Not provided';
                                ?>

                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- CONTACT -->

                    <div class="user-information-row">

                        <div class="user-information-label">
                            Contact Number
                        </div>

                        <div class="user-information-value">

                            <?php
                            echo !empty($user['contact_number'])
                                ? htmlspecialchars($user['contact_number'])
                                : 'Not provided';
                            ?>

                        </div>

                    </div>


                    <!-- EMAIL -->

                    <div class="user-information-row">

                        <div class="user-information-label">
                            Email Address
                        </div>

                        <div class="user-information-value">

                            <?php
                            echo !empty($user['email'])
                                ? htmlspecialchars($user['email'])
                                : 'Not provided';
                            ?>

                        </div>

                    </div>


                    <!-- ROLE -->

                    <div class="user-information-row">

                        <div class="user-information-label">
                            Account Role
                        </div>

                        <div class="user-information-value">

                            <span
                                class="user-role-badge
                                <?php echo ($role === 'admin')
                                    ? 'user-role-admin'
                                    : 'user-role-student'; ?>"
                            >
                                <?php echo htmlspecialchars(ucfirst($role)); ?>
                            </span>

                        </div>

                    </div>


                    <!-- REGISTERED DATE -->

                    <?php if (isset($user['created_at'])): ?>

                        <div class="user-information-row">

                            <div class="user-information-label">
                                Registered On
                            </div>

                            <div class="user-information-value">

                                <?php
                                echo !empty($user['created_at'])
                                    ? date('F d, Y', strtotime($user['created_at']))
                                    : 'Not available';
                                ?>

                            </div>

                        </div>

                    <?php endif; ?>


                </div>


                <!-- ACTIONS -->

                <div class="user-actions">

                    <a
                        href="manage_users.php"
                        class="user-action-btn user-btn-back"
                    >
                        Back
                    </a>


                    <a
                        href="edit_user.php?id=<?php echo (int)$user['id']; ?>"
                        class="user-action-btn user-btn-edit"
                    >
                        Edit User
                    </a>

                </div>


            </div>

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
