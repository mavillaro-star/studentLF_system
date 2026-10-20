<?php
session_start();
include '../../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

$user_name = $user['fullname'] ?? $_SESSION['fullname'] ?? 'Student';
$first_initial = strtoupper(substr(trim($user_name), 0, 1));
$avatar = $user['profile_picture'] ?? '';

// Check report ID
if (!isset($_GET['id'])) {
    header("Location: my_reports.php");
    exit();
}

$id = $_GET['id'];

// Get found report
$result = mysqli_query(
    $conn,
    "SELECT * FROM found_items
     WHERE id='$id' AND user_id='$user_id'"
);

if (mysqli_num_rows($result) == 0) {
    echo "Report not found.";
    exit();
}

$row = mysqli_fetch_assoc($result);

if (isset($_POST['update'])) {

    $item_name = $_POST['item_name'];
    $description = $_POST['description'];
    $date_found = $_POST['date_found'];
    $location_found = $_POST['location_found'];

    if (!empty($_FILES['image']['name'])) {

        $image = $_FILES['image']['name'];
        $temp = $_FILES['image']['tmp_name'];

        $upload_path = "../../uploads/" . $image;

        move_uploaded_file($temp, $upload_path);

        $update = mysqli_query(
            $conn,
            "UPDATE found_items SET
                item_name='$item_name',
                description='$description',
                date_found='$date_found',
                location_found='$location_found',
                image='$image'
             WHERE id='$id' AND user_id='$user_id'"
        );

    } else {

        $update = mysqli_query(
            $conn,
            "UPDATE found_items SET
                item_name='$item_name',
                description='$description',
                date_found='$date_found',
                location_found='$location_found'
             WHERE id='$id' AND user_id='$user_id'"
        );
    }

    if ($update) {
        echo "
        <h3>Found report updated successfully!</h3>
        <a href='my_reports.php'>Back to My Reports</a>
        ";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Found Report - Student Portal</title>

    <!-- Existing external CSS -->
    <link rel="stylesheet" href="myreports.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont,
                         "Segoe UI", Arial, sans-serif;
            background: #f3f6fa;
            color: #172033;
            min-height: 100vh;
            overflow-x: hidden;
        }

        #menu-toggle {
            display: none;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR - SAME AS MY REPORTS */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 230px;
            height: 100vh;
            padding: 24px 16px;
            background: #111a2d;
            color: #fff;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #1f2b43;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 200;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 2px 8px 25px;
            margin-bottom: 22px;
            border-bottom: 1px solid #26334c;
            text-decoration: none;
            color: #fff;
            flex-shrink: 0;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            border-radius: 12px;
            background: #1d2a44;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4da3ff;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .brand-text strong {
            font-size: 13px;
            letter-spacing: 1px;
            color: #fff;
        }

        .brand-text span {
            font-size: 10px;
            color: #75839b;
            margin-top: 3px;
            letter-spacing: .5px;
        }

        .nav-title {
            font-size: 9px;
            text-transform: uppercase;
            color: #64728a;
            letter-spacing: 1.3px;
            padding: 0 10px;
            margin-bottom: 9px;
        }

        .navigation {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .nav-item {
            min-height: 44px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 12px;
            border-radius: 10px;
            text-decoration: none;
            color: #91a0b8;
            font-size: 12px;
            font-weight: 500;
            transition: .2s ease;
            position: relative;
            flex-shrink: 0;
        }

        .nav-icon {
            width: 27px;
            height: 27px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #19253c;
            font-size: 14px;
            flex-shrink: 0;
        }

        .nav-item:hover {
            color: #fff;
            background: #18243a;
        }

        .nav-item:hover .nav-icon {
            background: #223352;
        }

        .nav-item.active {
            color: #fff;
            background: #1c2b47;
        }

        .nav-item.active::before {
            content: "";
            position: absolute;
            left: 0;
            width: 3px;
            height: 22px;
            border-radius: 0 5px 5px 0;
            background: #4da3ff;
        }

        .nav-item.active .nav-icon {
            background: #2468a9;
            color: #fff;
        }

        .nav-lost .nav-icon {
            color: #ff7676;
        }

        .nav-found .nav-icon {
            color: #35d39a;
        }

        .sidebar-footer {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #26334c;
            flex-shrink: 0;
        }

        .sidebar-footer .nav-item:hover {
            background: #241d2a;
            color: #ff7f7f;
        }

        /* MOBILE MENU */

        .mobile-menu-btn {
            display: none;
            width: 38px;
            height: 38px;
            border: 1px solid #e2e7ed;
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 4px;
            flex-shrink: 0;
        }

        .mobile-menu-btn span {
            width: 17px;
            height: 2px;
            background: #172033;
            border-radius: 5px;
        }

        .menu-overlay {
            display: none;
        }

        /* HEADER */

        .main-content {
            flex: 1;
            min-width: 0;
            width: calc(100% - 230px);
            min-height: 100vh;
            margin-left: 230px;
        }

        .top-header {
            min-height: 74px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 12px 30px;
            background: #fff;
            border-bottom: 1px solid #e3e8ee;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .header-title {
            min-width: 0;
        }

        .header-title h1 {
            font-size: 19px;
            font-weight: 700;
            color: #172033;
            line-height: 1.4;
        }

        .header-title p {
            font-size: 11px;
            color: #8793a5;
            margin-top: 3px;
        }

        .user-profile-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .user-meta {
            text-align: right;
        }

        .username {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #172033;
        }

        .user-role {
            display: block;
            font-size: 10px;
            color: #8793a5;
            margin-top: 2px;
        }

        .profile-avatar-link {
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            flex-shrink: 0;
        }

        .avatar-img,
        .avatar-initials {
            width: 37px;
            height: 37px;
            border-radius: 11px;
            object-fit: cover;
        }

        .avatar-initials {
            background: #1d6fc5;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(29, 111, 197, .18);
        }

        /* EDIT FORM AREA */

        .page-content {
            width: 100%;
            padding: 32px 36px;
        }

        .form-panel {
            width: 100%;
            max-width: 850px;
            margin: 0 auto;
            padding: 28px;
            background: #fff;
            border: 1px solid #e3e8ee;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
        }

        .form-panel h2 {
            font-size: 20px;
            margin-bottom: 8px;
        }

        .form-description {
            color: #8793a5;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #172033;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group textarea,
        .form-group input[type="file"] {
            display: block;
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #dce3ec;
            border-radius: 8px;
            background: #fff;
            color: #172033;
            font: inherit;
            font-size: 13px;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1d6fc5;
            box-shadow: 0 0 0 3px rgba(29, 111, 197, .1);
        }

        .current-image {
            display: block;
            width: 120px;
            height: 120px;
            object-fit: cover;
            border: 1px solid #e3e8ee;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .no-image-text {
            display: inline-block;
            color: #8793a5;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 25px;
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 42px;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s ease;
        }

        .btn-primary {
            background: #1d6fc5;
            color: #fff;
        }

        .btn-primary:hover {
            background: #155ba5;
        }

        .btn-secondary {
            background: #eef2f7;
            color: #344054;
        }

        .btn-secondary:hover {
            background: #e0e7ef;
        }

        /* RESPONSIVE */

        @media screen and (max-width: 1000px) {
            .top-header {
                padding: 12px 23px;
            }

            .page-content {
                padding: 23px;
            }
        }

        @media screen and (max-width: 760px) {
            .dashboard-container {
                display: block;
            }

            .sidebar {
                width: 270px;
                max-width: 85vw;
                height: 100vh;
                transform: translateX(-100%);
                transition: transform .25s ease;
                box-shadow: 8px 0 25px rgba(0, 0, 0, .18);
                z-index: 200;
            }

            #menu-toggle:checked ~ .dashboard-container .sidebar {
                transform: translateX(0);
            }

            .menu-overlay {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(8, 15, 28, .45);
                opacity: 0;
                visibility: hidden;
                transition: .25s ease;
                z-index: 150;
                cursor: pointer;
            }

            #menu-toggle:checked ~ .menu-overlay {
                opacity: 1;
                visibility: visible;
            }

            .mobile-menu-btn {
                display: flex;
            }

            .main-content {
                width: 100%;
                margin-left: 0;
            }

            .top-header {
                min-height: 65px;
                padding: 10px 17px;
            }

            .header-title h1 {
                font-size: 16px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .header-title p {
                display: none;
            }

            .user-profile-info {
                gap: 8px;
            }

            .user-meta {
                display: none;
            }

            .page-content {
                padding: 20px 17px 30px;
            }

            .form-panel {
                padding: 20px;
            }
        }

        @media screen and (max-width: 430px) {
            .top-header {
                padding: 10px 13px;
                gap: 8px;
            }

            .header-left {
                gap: 8px;
            }

            .header-title h1 {
                font-size: 14px;
            }

            .mobile-menu-btn {
                width: 35px;
                height: 35px;
            }

            .page-content {
                padding: 17px 13px 25px;
            }

            .form-panel {
                padding: 17px;
            }

            .form-panel h2 {
                font-size: 18px;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
            }
        }
    </style>
    <link rel="stylesheet" href="../../responsive.css">
</head>

<body>

<input type="checkbox" id="menu-toggle">

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <a href="student_dashboard.php" class="sidebar-brand">
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

            <!-- ACTIVE NAVIGATION -->
            <a href="report_found.php" class="nav-item nav-found active">
                <span class="nav-icon">□</span>
                <span>Report Found Item</span>
            </a>

            <a href="my_reports.php" class="nav-item">
                <span class="nav-icon">▤</span>
                <span>My Reports</span>
            </a>

            <a href="profile.php" class="nav-item">
                <span class="nav-icon">○</span>
                <span>Profile</span>
            </a>

        </nav>

        <div class="sidebar-footer">
            <a href="../../authentication/logout.php"
               class="nav-item logout-link"
               onclick="return confirmLogout();">

                <span class="nav-icon">↪</span>
                <span>Log Out</span>
            </a>
        </div>

    </aside>

    <label for="menu-toggle"
           class="menu-overlay"
           aria-label="Close navigation menu"></label>

    
    <main class="main-content">

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
                    <h1>Edit Found Report</h1>
                    <p>Update the information about your found item.</p>
                </div>

            </div>

            <div class="user-profile-info">

                <div class="user-meta">
                    <span class="username">
                        <?php echo htmlspecialchars($user_name); ?>
                    </span>

                    <span class="user-role">Student Account</span>
                </div>

                <a href="profile.php" class="profile-avatar-link">

                    <?php if (!empty($avatar)): ?>

                        <img
                            src="../../uploads/profile_pics/<?php echo htmlspecialchars($avatar); ?>"
                            alt="Profile Avatar"
                            class="avatar-img"
                        >

                    <?php else: ?>

                        <div class="avatar-initials">
                            <?php echo htmlspecialchars($first_initial); ?>
                        </div>

                    <?php endif; ?>

                </a>

            </div>

        </header>

        <div class="page-content">

            <section class="form-panel">

                <h2>Edit Found Report</h2>

                <p class="form-description">
                    Update your found item details below. Review the information
                    before saving your changes.
                </p>

                <form method="POST" enctype="multipart/form-data">

                    <div class="form-group">
                        <label for="item_name">Item Name</label>

                        <input
                            type="text"
                            id="item_name"
                            name="item_name"
                            value="<?php echo htmlspecialchars($row['item_name'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>

                        <textarea
                            id="description"
                            name="description"
                            required
                        ><?php echo htmlspecialchars($row['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="date_found">Date Found</label>

                        <input
                            type="date"
                            id="date_found"
                            name="date_found"
                            value="<?php echo htmlspecialchars($row['date_found'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="location_found">Location Found</label>

                        <input
                            type="text"
                            id="location_found"
                            name="location_found"
                            value="<?php echo htmlspecialchars($row['location_found'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Current Image</label>

                        <?php if (!empty($row['image'])): ?>

                            <img
                                src="../../uploads/<?php echo rawurlencode(basename((string) $row['image'])); ?>"
                                class="current-image"
                                alt="Current found item image"
                            >

                        <?php else: ?>

                            <span class="no-image-text">No Image</span>

                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="image">Change Image</label>

                        <input
                            type="file"
                            id="image"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp"
                        >
                    </div>

                    <div class="form-actions">

                        <button
                            type="submit"
                            name="update"
                            class="btn btn-primary"
                        >
                            Update Report
                        </button>

                        <a href="my_reports.php" class="btn btn-secondary">
                            Back to My Reports
                        </a>

                    </div>

                </form>

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
