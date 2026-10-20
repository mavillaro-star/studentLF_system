<?php
session_start();
include '../../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if user is a student
if ($_SESSION['role'] != "student") {
    header("Location: ../login.php");
    exit();
}

$fullname = $_SESSION['fullname'];
$message = "";

if (isset($_POST['submit'])) {

    $user_id = $_SESSION['user_id'];

    $item_name = mysqli_real_escape_string(
        $conn,
        trim($_POST['item_name'])
    );

    $description = mysqli_real_escape_string(
        $conn,
        trim($_POST['description'])
    );

    $date_found = $_POST['date_found'];

    $location_found = mysqli_real_escape_string(
        $conn,
        trim($_POST['location_found'])
    );

    $status = "Found";
    $image = "";

    /* SERVER-SIDE DATE VALIDATION */

    if ($date_found > date('Y-m-d')) {
        $message = "Invalid date. The date found cannot be a future date.";
    }

    /* IMAGE UPLOAD */

    if (empty($message)) {

        if (
            isset($_FILES['image']) &&
            $_FILES['image']['error'] == 0
        ) {

            $allowed_types = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];

            $file_name = $_FILES['image']['name'];

            $file_extension = strtolower(
                pathinfo(
                    $file_name,
                    PATHINFO_EXTENSION
                )
            );

            // Check file type
            if (!in_array($file_extension, $allowed_types)) {
                $message = "Invalid image format. Please upload JPG, JPEG, PNG, or WEBP.";
            }

            // Check file size
            elseif (
                $_FILES['image']['size'] >
                2 * 1024 * 1024
            ) {
                $message = "Image size must not exceed 2MB.";
            }

            else {

                $image =
                    time() . "_" .
                    basename($file_name);

                $upload_folder = "../../uploads/";

                if (!file_exists($upload_folder)) {
                    mkdir(
                        $upload_folder,
                        0777,
                        true
                    );
                }

                if (
                    !move_uploaded_file(
                        $_FILES['image']['tmp_name'],
                        $upload_folder . $image
                    )
                ) {
                    $message = "Image upload failed!";
                }
            }
        }
    }

    /* INSERT FOUND ITEM */

    if (empty($message)) {

        $sql = "INSERT INTO found_items
                (
                    user_id,
                    item_name,
                    description,
                    date_found,
                    location_found,
                    image,
                    status
                )
                VALUES
                (
                    '$user_id',
                    '$item_name',
                    '$description',
                    '$date_found',
                    '$location_found',
                    '$image',
                    '$status'
                )";

        if (mysqli_query($conn, $sql)) {

            /* NOTIFY ALL ADMINS */

            $admins = mysqli_query(
                $conn,
                "SELECT id FROM users WHERE role = 'admin'"
            );

            while ($admin = mysqli_fetch_assoc($admins)) {

                $admin_id = $admin['id'];
                $notif_msg = "New Found Item reported: '$item_name'";

                $notif_stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO notifications
                    (user_id, message)
                    VALUES (?, ?)"
                );

                mysqli_stmt_bind_param(
                    $notif_stmt,
                    "is",
                    $admin_id,
                    $notif_msg
                );

                mysqli_stmt_execute($notif_stmt);
                mysqli_stmt_close($notif_stmt);
            }

            /* REDIRECT TO MY REPORTS */

            header("Location: my_reports.php");
            exit();

        } else {
            $message = "Database Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Report Found Item - Lost &amp; Found</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        :root {
            --primary: #1d6fc5;
            --bg-surface: #ffffff;
            --border-color: #e3e8ee;
            --text-main: #172033;
            --text-muted: #8793a5;
            --background: #f3f6fa;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
        }

        body {
            background: var(--background);
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* MOBILE MENU */

        #menu-toggle {
            display: none;
        }

        /* SIDEBAR - SAME AS STUDENT DASHBOARD */

        .dashboard-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 230px;
            height: 100vh;
            padding: 24px 16px;
            background: #111a2d;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #1f2b43;
            overflow-y: auto;
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
            color: #ffffff;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            border-radius: 12px;
            background: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
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
            color: #ffffff;
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
            color: #ffffff;
            background: #18243a;
        }

        .nav-item:hover .nav-icon {
            background: #223352;
        }

        .nav-item.active {
            color: #ffffff;
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
            color: #ffffff;
        }

        .nav-lost .nav-icon {
            color: #ff7676;
        }

        .nav-found .nav-icon {
            color: #35d39a;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #26334c;
        }

        .sidebar-bottom .nav-item:hover {
            background: #241d2a;
            color: #ff7f7f;
        }

        /* MOBILE MENU BUTTON AND OVERLAY */

        .mobile-menu-btn {
            display: none;
            width: 38px;
            height: 38px;
            border: 1px solid #e2e7ed;
            border-radius: 10px;
            background: #ffffff;
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
            position: fixed;
            inset: 0;
            background: rgba(8, 15, 28, .45);
            opacity: 0;
            visibility: hidden;
            transition: .25s ease;
            z-index: 150;
            cursor: pointer;
        }

        /* MAIN WORKSPACE */

        .main-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            width: calc(100% - 230px);
            min-height: 100vh;
            margin-left: 230px;
        }

        /* TOP HEADER - SAME AS STUDENT DASHBOARD */

        .top-header {
            min-height: 74px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 12px 30px;
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border-color);
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
            font-weight: 750;
            color: var(--text-main);
            line-height: 1.4;
        }

        .header-title p {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 3px;
        }

        .content-body {
            padding: 28px 30px 35px;
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
        }

        .page-intro {
            background: linear-gradient(to right, #1e3a8a, #0f172a);
            border-radius: 12px;
            padding: 24px;
            color: #ffffff;
            margin-bottom: 24px;
            box-shadow: 0 3px 8px rgba(15, 23, 42, .1);
        }

        .page-intro h2 {
            font-size: 19px;
            font-weight: 750;
        }

        .page-intro p {
            font-size: 11px;
            color: #cbd5e1;
            margin-top: 6px;
            line-height: 1.7;
            max-width: 650px;
        }

        .report-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
        }

        .report-header {
            padding: 20px 22px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .report-header-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .report-header-text h3 {
            font-size: 14px;
            color: #0f172a;
            font-weight: 750;
        }

        .report-header-text p {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
        }

        .report-form {
            padding: 24px 22px 22px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 11px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 7px;
        }

        .required {
            color: #f43f5e;
        }

        .optional {
            color: #94a3b8;
            font-weight: 400;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            color: #172033;
            font-family: inherit;
            font-size: 12px;
            outline: none;
            transition: .2s ease;
        }

        .form-group input {
            min-height: 42px;
        }

        .form-group textarea {
            min-height: 110px;
            resize: vertical;
            line-height: 1.6;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #94a3b8;
        }

        .file-upload {
            border: 2px dashed #cbd5e1;
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            transition: .2s ease;
        }

        .file-upload:hover {
            background: #f1f5f9;
        }

        .file-upload input {
            width: 100%;
            border: none;
            padding: 0;
            min-height: auto;
            background: transparent;
            font-size: 11px;
        }

        .file-note {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 9px;
            line-height: 1.5;
        }

        .form-info {
            margin-top: 22px;
            padding: 14px;
            border-radius: 8px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            font-size: 11px;
            line-height: 1.7;
        }

        .form-info strong {
            color: #78350f;
        }

        .error-message {
            margin-bottom: 20px;
            padding: 13px 14px;
            border-radius: 8px;
            background: #fff1f2;
            border-left: 4px solid #f43f5e;
            color: #be123c;
            font-size: 11px;
            line-height: 1.6;
        }

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 22px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
        }

        .form-btn {
            min-height: 40px;
            padding: 0 18px;
            border: 1px solid transparent;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: inherit;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: .2s ease;
        }

        .cancel-btn {
            background: #ffffff;
            color: #475569;
            border-color: #e2e8f0;
        }

        .cancel-btn:hover {
            background: #f8fafc;
        }

        .submit-btn {
            background: #2563eb;
            color: #ffffff;
        }

        .submit-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        /* TABLET */

        @media screen and (max-width: 1000px) {
            .sidebar {
                width: 205px;
            }

            .main-wrapper {
                width: calc(100% - 205px);
                margin-left: 205px;
            }

            .top-header {
                padding: 12px 23px;
            }

            .content-body {
                padding: 23px;
            }
        }

        /* MOBILE - SAME NAVIGATION AS DASHBOARD */

        @media screen and (max-width: 760px) {
            .dashboard-wrapper {
                display: block;
            }

            .sidebar {
                width: 270px;
                max-width: 85vw;
                transform: translateX(-100%);
                transition: transform .25s ease;
                box-shadow: 8px 0 25px rgba(0, 0, 0, .18);
                z-index: 200;
            }

            #menu-toggle:checked ~ .dashboard-wrapper .sidebar {
                transform: translateX(0);
            }

            #menu-toggle:checked ~ .menu-overlay {
                opacity: 1;
                visibility: visible;
            }

            .menu-overlay {
                display: block;
            }

            .mobile-menu-btn {
                display: flex;
            }

            .main-wrapper {
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

            .content-body {
                padding: 20px 17px 30px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .page-intro {
                padding: 20px;
            }

            .report-form {
                padding: 20px;
            }

            .report-header {
                padding: 18px 20px;
            }
        }

        @media screen and (max-width: 430px) {
            .top-header {
                padding: 10px 13px;
                gap: 8px;
            }

            .header-title h1 {
                font-size: 14px;
            }

            .content-body {
                padding: 17px 13px 25px;
            }

            .page-intro {
                padding: 18px;
            }

            .page-intro h2 {
                font-size: 17px;
            }

            .report-form {
                padding: 17px 15px;
            }

            .report-header {
                padding: 16px 15px;
            }

            .form-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .form-btn {
                padding: 0 10px;
                width: 100%;
            }
        }

        @media screen and (max-width: 350px) {
            
            .mobile-menu-btn {
                width: 34px;
                height: 34px;
            }

            .header-left {
                gap: 7px;
            }

            .header-title h1 {
                font-size: 12px;
            }

            .form-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="../../responsive.css">
</head>

<body>

<input type="checkbox" id="menu-toggle">

<div class="dashboard-wrapper">

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

        <div class="sidebar-bottom">

            <a href="../../authentication/logout.php"
               class="nav-item"
               onclick="return confirmLogout();">

                <span class="nav-icon">↪</span>
                <span>Log Out</span>

            </a>

        </div>

    </aside>

    <label for="menu-toggle" class="menu-overlay"></label>

    <main class="main-wrapper">

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
                    <h1>Report Found Item</h1>
                    <p>Submit information about an item you have found on campus.</p>
                </div>
            </div>
        </header>

        <div class="content-body">

            <section class="report-card">

                <div class="report-header">

                    <div class="report-header-icon">+</div>

                    <div class="report-header-text">
                        <h3>Found Item Information</h3>
                        <p>Fill in the required information below.</p>
                    </div>

                </div>

                <form method="POST" enctype="multipart/form-data" class="report-form">

                    <?php if (!empty($message)) { ?>

                        <div class="error-message">
                            <?php echo htmlspecialchars($message); ?>
                        </div>

                    <?php } ?>

                    <div class="form-grid">

                        <div class="form-group">

                            <label>
                                Item Name <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                name="item_name"
                                placeholder="Example: Black Wallet"
                                value="<?php echo isset($_POST['item_name']) ? htmlspecialchars($_POST['item_name']) : ''; ?>"
                                required
                            >

                        </div>

                        <!-- DATE FOUND -->

                        <div class="form-group">

                            <label>
                                Date Found <span class="required">*</span>
                            </label>

                            <input
                                type="date"
                                name="date_found"
                                max="<?php echo date('Y-m-d'); ?>"
                                value="<?php echo isset($_POST['date_found']) ? htmlspecialchars($_POST['date_found']) : ''; ?>"
                                required
                            >

                        </div>

                        <div class="form-group full">

                            <label>
                                Location Found <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                name="location_found"
                                placeholder="Example: Library, Cafeteria, Room 204"
                                value="<?php echo isset($_POST['location_found']) ? htmlspecialchars($_POST['location_found']) : ''; ?>"
                                required
                            >

                        </div>

                        <div class="form-group full">

                            <label>
                                Description <span class="required">*</span>
                            </label>

                            <textarea
                                name="description"
                                rows="3"
                                placeholder="Describe the item's color, brand, size, design, markings, or other identifying details."
                                required
                            ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>

                        </div>

                        <!-- FILE UPLOAD -->

                        <div class="form-group full">

                            <label>
                                Upload Image
                                <span class="optional">(Optional)</span>
                            </label>

                            <div class="file-upload">

                                <input
                                    type="file"
                                    name="image"
                                    accept=".jpg,.jpeg,.png,.webp,image/*"
                                >

                                <p class="file-note">
                                    Upload a clear image of the found item if available.
                                    Maximum file size: 2MB (JPG, JPEG, PNG, WEBP).
                                </p>

                            </div>

                        </div>

                    </div>

                    <div class="form-info">
                        <strong>Important:</strong>
                        Please provide truthful and accurate information.
                    
                    </div>

                    <div class="form-actions">

                        <a href="student_dashboard.php"
                           class="form-btn cancel-btn">
                            Cancel
                        </a>

                        <button type="submit"
                                name="submit"
                                class="form-btn submit-btn">
                            Submit Report
                        </button>

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