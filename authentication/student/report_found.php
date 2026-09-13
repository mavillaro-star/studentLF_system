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


    /* ==========================================
       SERVER-SIDE DATE VALIDATION
       Prevent future dates
    ========================================== */

    if ($date_found > date('Y-m-d')) {

        $message = "Invalid date. The date found cannot be a future date.";

    }


    /* ==========================================
       IMAGE UPLOAD
    ========================================== */

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

                $message =
                    "Invalid image format. Please upload JPG, JPEG, PNG, or WEBP.";

            }


            // Check file size
            elseif (
                $_FILES['image']['size'] >
                2 * 1024 * 1024
            ) {

                $message =
                    "Image size must not exceed 2MB.";

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

                    $message =
                        "Image upload failed!";

                }

            }
        }
    }


    /* ==========================================
       INSERT FOUND ITEM
    ========================================== */

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


            /* ==========================================
               NOTIFY ALL ADMINS
            ========================================== */

            $admins = mysqli_query(
                $conn,
                "SELECT id FROM users WHERE role = 'admin'"
            );


            while ($admin = mysqli_fetch_assoc($admins)) {

                $admin_id = $admin['id'];

                $notif_msg =
                    "New Found Item reported: '$item_name'";


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


                mysqli_stmt_execute(
                    $notif_stmt
                );


                mysqli_stmt_close(
                    $notif_stmt
                );
            }


            /* ==========================================
               REDIRECT TO MY REPORTS
            ========================================== */

            header(
                "Location: my_reports.php"
            );

            exit();


        } else {

            $message =
                "Database Error: " .
                mysqli_error($conn);

        }
    }
}
?>

<!DOCTYPE html>

<html lang="en">

<head>
<meta charset="UTF-8">
<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>Report Found Item</title>


<style>

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;

        font-family:
            -apple-system,
            BlinkMacSystemFont,
            "Segoe UI",
            Arial,
            sans-serif;
    }


    html,
    body {
        width: 100%;
        min-height: 100%;
    }


    body {
        background: #eef3f8;
        color: #172033;
    }


    /* ==========================================
       MAIN LAYOUT
    ========================================== */

    .dashboard {
        width: 100%;
        min-height: 100vh;

        display: flex;
    }


    /* ==========================================
       MOBILE MENU CHECKBOX
    ========================================== */

    #menu-toggle {
        display: none;
    }


    /* ==========================================
       SIDEBAR
    ========================================== */

    .sidebar {
        position: fixed;

        left: 0;
        top: 0;

        width: 230px;
        height: 100vh;

        background: #111a2d;
        color: #ffffff;

        display: flex;
        flex-direction: column;

        padding: 24px 16px;

        z-index: 100;

        border-right: 1px solid #1f2b43;

        overflow-y: auto;
    }


    .brand-link {
        text-decoration: none;
        color: inherit;
    }


    .brand {
        display: flex;
        align-items: center;

        gap: 12px;

        padding: 2px 8px 25px;

        border-bottom: 1px solid #26334c;

        margin-bottom: 22px;
    }


    .brand-mark {
        width: 42px;
        height: 42px;

        border-radius: 12px;

        background: #1d2a44;

        display: flex;
        align-items: center;
        justify-content: center;

        position: relative;

        overflow: hidden;
    }


    .brand-mark::before {
        content: "";

        width: 18px;
        height: 18px;

        border: 2px solid #4da3ff;

        border-radius: 50%;

        position: absolute;
    }


    .brand-mark::after {
        content: "";

        width: 24px;
        height: 2px;

        background: #4da3ff;

        transform: rotate(-45deg);

        position: absolute;
    }


    .brand-text {
        display: flex;
        flex-direction: column;
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


    .nav-menu {
        list-style: none;

        display: flex;
        flex-direction: column;

        gap: 5px;
    }


    .nav-item {
        height: 44px;

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

        font-size: 13px;

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


    /* ==========================================
       MOBILE MENU BUTTON
    ========================================== */

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

        transition: .2s ease;
    }


    /* ==========================================
       MAIN CONTENT
    ========================================== */

    .main-content {
        margin-left: 230px;

        width: calc(100% - 230px);

        min-height: 100vh;

        display: flex;
        flex-direction: column;

        background: #f3f6fa;
    }


    /* ==========================================
       TOPBAR
    ========================================== */

    .topbar {
        min-height: 74px;

        background: #ffffff;

        border-bottom: 1px solid #e4e9ef;

        display: flex;
        align-items: center;
        justify-content: space-between;

        padding: 0 30px;

        position: sticky;

        top: 0;

        z-index: 50;
    }


    .topbar-left {
        display: flex;
        align-items: center;

        gap: 12px;

        min-width: 0;
    }


    .topbar-title {
        min-width: 0;
    }


    .topbar-title h1 {
        font-size: 19px;

        color: #172033;

        font-weight: 750;
    }


    .topbar-title p {
        font-size: 11px;

        color: #8793a5;

        margin-top: 3px;
    }


    .user-area {
        display: flex;
        align-items: center;

        gap: 14px;

        flex-shrink: 0;
    }


    .notification {
        width: 35px;
        height: 35px;

        border: 1px solid #e2e7ed;

        border-radius: 10px;

        display: flex;
        align-items: center;
        justify-content: center;

        background: #ffffff;

        color: #64748b;

        font-size: 14px;

        position: relative;
    }


    .notification::after {
        content: "";

        width: 6px;
        height: 6px;

        border-radius: 50%;

        background: #ff5c5c;

        position: absolute;

        top: 7px;
        right: 7px;

        border: 2px solid #ffffff;
    }


    .user-divider {
        height: 32px;
        width: 1px;

        background: #e3e8ee;
    }


    .user-info {
        text-align: right;
    }


    .user-name {
        display: block;

        font-size: 12px;

        font-weight: 700;

        color: #172033;
    }


    .user-role {
        display: block;

        font-size: 10px;

        color: #8b96a7;

        margin-top: 2px;
    }


    .avatar {
        width: 37px;
        height: 37px;

        border-radius: 11px;

        background: #1d6fc5;

        color: #ffffff;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 14px;

        font-weight: 700;

        box-shadow:
            0 4px 10px
            rgba(29, 111, 197, .18);
    }


    /* ==========================================
       PAGE CONTENT
    ========================================== */

    .page-content {
        width: 100%;

        padding: 28px 30px 35px;
    }


    .page-intro {
        margin-bottom: 20px;
    }


    .page-intro h2 {
        font-size: 20px;

        color: #172033;

        font-weight: 750;

        margin-bottom: 5px;
    }


    .page-intro p {
        font-size: 11px;

        color: #8793a5;

        line-height: 1.6;
    }


    /* ==========================================
       REPORT CARD
    ========================================== */

    .report-card {
        width: 100%;

        background: #ffffff;

        border: 1px solid #e5eaf0;

        border-radius: 15px;

        overflow: hidden;

        box-shadow:
            0 4px 14px
            rgba(25, 45, 70, .035);
    }


    .report-header {
        display: flex;
        align-items: center;

        gap: 13px;

        padding: 19px 21px;

        border-bottom: 1px solid #edf0f4;
    }


    .report-header-icon {
        width: 40px;
        height: 40px;

        border-radius: 11px;

        background: #eafaf4;

        color: #13a976;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 17px;

        font-weight: 800;

        flex-shrink: 0;
    }


    .report-header-text h3 {
        font-size: 13px;

        color: #172033;

        font-weight: 750;
    }


    .report-header-text p {
        font-size: 9px;

        color: #8b96a7;

        margin-top: 3px;
    }


    /* ==========================================
       FORM
    ========================================== */

    .report-form {
        padding: 23px;
    }


    .error-message {
        width: 100%;

        padding: 11px 13px;

        margin-bottom: 18px;

        border-radius: 8px;

        background: #fff0f0;

        border: 1px solid #ffd4d4;

        color: #d84949;

        font-size: 10px;

        line-height: 1.5;
    }


    .form-grid {
        display: grid;

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 18px;
    }


    .form-group {
        min-width: 0;

        display: flex;
        flex-direction: column;
    }


    .form-group.full {
        grid-column: 1 / -1;
    }


    .form-group label {
        font-size: 10px;

        font-weight: 700;

        color: #344054;

        margin-bottom: 7px;
    }


    .required {
        color: #ef5350;
    }


    .form-group input,
    .form-group textarea {
        width: 100%;

        border: 1px solid #dfe5ec;

        background: #fbfcfd;

        border-radius: 9px;

        color: #172033;

        font-size: 11px;

        outline: none;

        transition: .2s ease;
    }


    .form-group input {
        height: 43px;

        padding: 0 13px;
    }


    .form-group textarea {
        min-height: 120px;

        padding: 12px 13px;

        resize: vertical;

        line-height: 1.6;
    }


    .form-group input:focus,
    .form-group textarea:focus {
        border-color: #4da3ff;

        background: #ffffff;

        box-shadow:
            0 0 0 3px
            rgba(77, 163, 255, .08);
    }


    .form-group input::placeholder,
    .form-group textarea::placeholder {
        color: #a3adba;
    }


    /* ==========================================
       FILE UPLOAD
    ========================================== */

    .file-upload {
        width: 100%;

        border: 1px dashed #cbd5e1;

        border-radius: 10px;

        background: #f8fafc;

        padding: 14px;
    }


    .file-upload input {
        height: auto;

        border: none;

        background: transparent;

        padding: 0;

        font-size: 10px;

        box-shadow: none;
    }


    .file-upload input:focus {
        box-shadow: none;
    }


    .file-note {
        font-size: 9px;

        color: #8b96a7;

        margin-top: 7px;

        line-height: 1.5;
    }


    /* ==========================================
       INFORMATION BOX
    ========================================== */

    .form-info {
        margin-top: 20px;

        padding: 12px 14px;

        border-radius: 9px;

        background: #f3f8fd;

        border: 1px solid #dcecf9;

        color: #718096;

        font-size: 9px;

        line-height: 1.6;
    }


    .form-info strong {
        color: #37678e;
    }


    /* ==========================================
       BUTTONS
    ========================================== */

    .form-actions {
        display: flex;

        justify-content: flex-end;

        gap: 9px;

        margin-top: 22px;

        padding-top: 18px;

        border-top: 1px solid #edf0f4;
    }


    .form-btn {
        min-height: 39px;

        padding: 0 16px;

        border-radius: 9px;

        display: inline-flex;

        align-items: center;
        justify-content: center;

        gap: 7px;

        font-size: 10px;

        font-weight: 700;

        text-decoration: none;

        border: none;

        cursor: pointer;

        transition: .2s ease;
    }


    .cancel-btn {
        background: #f1f4f7;

        color: #64748b;
    }


    .cancel-btn:hover {
        background: #e7ebef;

        color: #344054;
    }


    .submit-btn {
        background: #19b982;

        color: #ffffff;
    }


    .submit-btn:hover {
        background: #12a774;

        transform: translateY(-1px);

        box-shadow:
            0 5px 12px
            rgba(25, 185, 130, .15);
    }


    /* ==========================================
       TABLET
    ========================================== */

    @media screen and (max-width: 1000px) {

        .sidebar {
            width: 205px;
        }


        .main-content {
            margin-left: 205px;

            width: calc(100% - 205px);
        }


        .page-content {
            padding: 23px;
        }


        .topbar {
            padding: 0 23px;
        }

    }


    /* ==========================================
       MOBILE
    ========================================== */

    @media screen and (max-width: 760px) {

        body {
            overflow-x: hidden;
        }


        .dashboard {
            display: block;

            min-height: 100vh;
        }


        /* Hamburger */

        .mobile-menu-btn {
            display: flex;
        }


        /* Sidebar */

        .sidebar {
            position: fixed;

            top: 0;
            left: 0;

            width: 270px;
            height: 100vh;

            transform:
                translateX(-100%);

            transition:
                transform .25s ease;

            box-shadow:
                8px 0 25px
                rgba(0, 0, 0, .18);

            z-index: 200;

            overflow-y: auto;
        }


        /* Open sidebar */

        #menu-toggle:checked
        ~ .dashboard
        .sidebar {

            transform:
                translateX(0);
        }


        /* Overlay */

        .menu-overlay {
            display: block;

            position: fixed;

            inset: 0;

            background:
                rgba(8, 15, 28, .45);

            opacity: 0;

            visibility: hidden;

            transition: .25s ease;

            z-index: 150;

            cursor: pointer;
        }


        #menu-toggle:checked
        ~ .menu-overlay {

            opacity: 1;

            visibility: visible;
        }


        .main-content {
            margin-left: 0;

            width: 100%;

            min-height: 100vh;
        }


        .topbar {
            min-height: 65px;

            padding: 0 17px;
        }


        .topbar-title {
            min-width: 0;
        }


        .topbar-title h1 {
            font-size: 16px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .topbar-title p {
            display: none;
        }


        .user-info,
        .user-divider {
            display: none;
        }


        .user-area {
            gap: 8px;
        }


        .notification {
            width: 34px;
            height: 34px;
        }


        .avatar {
            width: 35px;
            height: 35px;
        }


        .page-content {
            padding: 18px;
        }


        .page-intro h2 {
            font-size: 18px;
        }


        .form-grid {
            grid-template-columns: 1fr;

            gap: 16px;
        }


        .form-group.full {
            grid-column: auto;
        }


        .report-form {
            padding: 19px;
        }

    }


    /* ==========================================
       SMALL PHONE
    ========================================== */

    @media screen and (max-width: 430px) {

        .sidebar {
            width: 285px;
        }


        .topbar {
            padding: 0 14px;
        }


        .topbar-title h1 {
            font-size: 14px;
        }


        .page-content {
            padding: 14px;
        }


        .page-intro {
            margin-bottom: 16px;
        }


        .page-intro h2 {
            font-size: 17px;
        }


        .page-intro p {
            font-size: 10px;
        }


        .report-header {
            padding: 16px;
        }


        .report-form {
            padding: 16px;
        }


        .report-header-icon {
            width: 36px;
            height: 36px;

            font-size: 15px;
        }


        .form-actions {
            flex-direction: column-reverse;

            width: 100%;
        }


        .form-btn {
            width: 100%;
        }


        .form-group textarea {
            min-height: 110px;
        }

    }

</style>

</head>

<body>

<!-- Mobile Menu Control -->

<input
type="checkbox"
id="menu-toggle"

>

<div class="dashboard">

<aside class="sidebar">


    <a
        href="students.php"
        class="brand-link"
    >

        <div class="brand">


            <div class="brand-mark"></div>


            <div class="brand-text">

                <strong>
                    LOST &amp; FOUND
                </strong>

                <span>
                    STUDENT PORTAL
                </span>

            </div>


        </div>

    </a>


    <div class="nav-title">
        Main Menu
    </div>


    <ul class="nav-menu">


        <li>

            <a
                href="students.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ⌂
                </span>

                <span>
                    Dashboard
                </span>

            </a>

        </li>


        <li>

            <a
                href="report_lost.php"
                class="nav-item nav-lost"
            >

                <span class="nav-icon">
                    ⌕
                </span>

                <span>
                    Report Lost Item
                </span>

            </a>

        </li>


        <li>

            <a
                href="report_found.php"
                class="nav-item nav-found active"
            >

                <span class="nav-icon">
                    □
                </span>

                <span>
                    Report Found Item
                </span>

            </a>

        </li>


        <li>

            <a
                href="my_reports.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▤
                </span>

                <span>
                    My Reports
                </span>

            </a>

        </li>


        <li>

            <a
                href="profile.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ○
                </span>

                <span>
                    Profile
                </span>

            </a>

        </li>


    </ul>


    <div class="sidebar-bottom">


        <a
            href="../../authentication/logout.php"
            class="nav-item"
            onclick="return confirmLogout();"
        >

            <span class="nav-icon">
                ↪
            </span>

            <span>
                Log Out
            </span>

        </a>


    </div>


</aside>


<!-- Mobile Overlay -->

<label
    for="menu-toggle"
    class="menu-overlay"
></label>


<!-- ==========================================
     MAIN CONTENT
========================================== -->

<main class="main-content">


    <header class="topbar">


        <div class="topbar-left">


            <!-- Hamburger -->

            <label
                for="menu-toggle"
                class="mobile-menu-btn"
            >

                <span></span>
                <span></span>
                <span></span>

            </label>


            <div class="topbar-title">

                <h1>
                    Report Found Item
                </h1>

                <p>
                    Submit information about an item you have found.
                </p>

            </div>


        </div>


        <div class="user-area">


            <div class="notification">
                ♢
            </div>


            <div class="user-divider"></div>


            <div class="user-info">

                <span class="user-name">

                    <?php
                    echo htmlspecialchars($fullname);
                    ?>

                </span>


                <span class="user-role">
                    Student Account
                </span>

            </div>


            <div class="avatar">

                <?php
                echo strtoupper(
                    substr($fullname, 0, 1)
                );
                ?>

            </div>


        </div>


    </header>


    <!-- ==========================================
         PAGE CONTENT
    ========================================== -->

    <div class="page-content">


        <div class="page-intro">

            <h2>
                Report a Found Item
            </h2>

            <p>
                Provide accurate information about the item
                you found so its owner can be identified
                and contacted.
            </p>

        </div>


        <section class="report-card">


            <div class="report-header">


                <div class="report-header-icon">
                    +
                </div>


                <div class="report-header-text">

                    <h3>
                        Found Item Information
                    </h3>

                    <p>
                        Fill in the required information below.
                    </p>

                </div>


            </div>


            <form
                method="POST"
                enctype="multipart/form-data"
                class="report-form"
            >


                <?php if (!empty($message)) { ?>

                    <div class="error-message">

                        <?php
                        echo htmlspecialchars($message);
                        ?>

                    </div>

                <?php } ?>


                <div class="form-grid">


                    <!-- ITEM NAME -->

                    <div class="form-group">


                        <label>

                            Item Name

                            <span class="required">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            name="item_name"
                            placeholder="Example: Black Wallet"
                            value="<?php
                                echo isset($_POST['item_name'])
                                    ? htmlspecialchars($_POST['item_name'])
                                    : '';
                            ?>"
                            required
                        >


                    </div>


                    <!-- DATE FOUND -->

                    <div class="form-group">


                        <label>

                            Date Found

                            <span class="required">
                                *
                            </span>

                        </label>


                        <input
                            type="date"
                            name="date_found"
                            max="<?php
                                echo date('Y-m-d');
                            ?>"
                            value="<?php
                                echo isset($_POST['date_found'])
                                    ? htmlspecialchars($_POST['date_found'])
                                    : '';
                            ?>"
                            required
                        >


                    </div>


                    <!-- LOCATION FOUND -->

                    <div class="form-group full">


                        <label>

                            Location Found

                            <span class="required">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            name="location_found"
                            placeholder="Example: Library, Cafeteria, Room 204"
                            value="<?php
                                echo isset($_POST['location_found'])
                                    ? htmlspecialchars($_POST['location_found'])
                                    : '';
                            ?>"
                            required
                        >


                    </div>


                    <!-- DESCRIPTION -->

                    <div class="form-group full">


                        <label>

                            Description

                            <span class="required">
                                *
                            </span>

                        </label>


                        <textarea
                            name="description"
                            placeholder="Describe the item's color, brand, size, design, markings, or other identifying details."
                            required
                        ><?php
                            echo isset($_POST['description'])
                                ? htmlspecialchars($_POST['description'])
                                : '';
                        ?></textarea>


                    </div>


                    <!-- IMAGE -->

                    <div class="form-group full">


                        <label>

                            Upload Image

                            <span
                                style="color:#9aa5b4;"
                            >
                                (Optional)
                            </span>

                        </label>


                        <div class="file-upload">


                            <input
                                type="file"
                                name="image"
                                accept=".jpg,.jpeg,.png,.webp,image/*"
                            >


                            <div class="file-note">

                                Upload a clear image of the
                                found item if available.
                                Maximum file size: 2MB.

                            </div>


                        </div>


                    </div>


                </div>


                <!-- INFORMATION -->

                <div class="form-info">

                    <strong>
                        Important:
                    </strong>

                    Please provide truthful and accurate
                    information. Do not include unnecessary
                    personal information about the person
                    who may own the item.

                </div>


                <!-- BUTTONS -->

                <div class="form-actions">


                    <a
                        href="students.php"
                        class="form-btn cancel-btn"
                    >
                        Cancel

                    </a>


                    <button
                        type="submit"
                        name="submit"
                        class="form-btn submit-btn"
                    >

                        Submit Report

                    </button>


                </div>


            </form>


        </section>


    </div>


</main>
```

</div>

<script>

function confirmLogout() {

    return confirm(
        "Are you sure you want to log out?"
    );

}

</script>

</body>

</html>
