```php
<?php
session_start();
include '../../db.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "student") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get student information
$stmt = $conn->prepare("
    SELECT id, fullname, profile_picture, course, year_level,
           contact_number, email, role
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

if (!$user) {
    header("Location: students.php");
    exit();
}

$fullname = $user['fullname'] ?? 'Student';
$studentInitial = strtoupper(substr(trim($fullname), 0, 1));

/* ==========================================
   PROFILE IMAGE
========================================== */

$profilePic = !empty($user['profile_picture'])
    ? $user['profile_picture']
    : '';

$profileFolder = "../../uploads/profile_pics/";
$mainUploadFolder = "../../uploads/";

$profileServerPath = __DIR__ . "/../../uploads/profile_pics/" . $profilePic;
$mainServerPath = __DIR__ . "/../../uploads/" . $profilePic;

if (!empty($profilePic) && file_exists($profileServerPath)) {

    $profileImage = $profileFolder .
        rawurlencode($profilePic) .
        "?v=" . time();

} elseif (!empty($profilePic) && file_exists($mainServerPath)) {

    $profileImage = $mainUploadFolder .
        rawurlencode($profilePic) .
        "?v=" . time();

} else {

    $profileImage = "../../uploads/default.png";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Profile - Student Portal</title>

    <style>

        /* ==========================================
           RESET
        ========================================== */

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
            background: #f3f6fa;
            color: #172033;
            overflow-x: hidden;
        }


        /* ==========================================
           MOBILE MENU CHECKBOX
        ========================================== */

        #menu-toggle {
            display: none;
        }


        /* ==========================================
           MAIN LAYOUT
        ========================================== */

        .dashboard {
            width: 100%;
            min-height: 100vh;
            display: flex;
        }

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

            z-index: 200;

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
           OVERLAY
        ========================================== */

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


        /* ==========================================
           MAIN CONTENT
        ========================================== */

        .main-content {

            margin-left: 230px;

            width: calc(100% - 230px);

            min-height: 100vh;

            display: flex;
            flex-direction: column;
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

            overflow: hidden;

            box-shadow:
                0 4px 10px
                rgba(29, 111, 197, .18);
        }


        .avatar img {

            width: 100%;
            height: 100%;

            object-fit: cover;
        }


        /* ==========================================
           PAGE CONTENT
        ========================================== */

        .page-content {

            width: 100%;

            padding: 28px 30px 40px;
        }


        .profile-page-header {

            width: 100%;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 22px;
        }


        .profile-heading h2 {

            color: #172033;

            font-size: 21px;

            font-weight: 750;
        }


        .profile-heading p {

            margin-top: 5px;

            color: #8793a5;

            font-size: 11px;

            line-height: 1.5;
        }


        .account-label {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 7px 11px;

            border-radius: 20px;

            background: #edf6ff;

            color: #2874b8;

            border: 1px solid #d8ebfb;

            font-size: 9px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .5px;
        }


        .account-dot {

            width: 6px;
            height: 6px;

            border-radius: 50%;

            background: #25b982;
        }


        /* ==========================================
           PROFILE CARD
        ========================================== */

        .profile-card {

            width: 100%;

            background: #ffffff;

            border: 1px solid #e3e8ee;

            border-radius: 15px;

            box-shadow:
                0 5px 18px
                rgba(25, 45, 70, .045);

            overflow: hidden;
        }


        /* ==========================================
           PROFILE HERO
        ========================================== */

        .profile-hero {

            position: relative;

            min-height: 185px;

            padding: 25px 28px;

            background:
                linear-gradient(
                    135deg,
                    #17263d 0%,
                    #1c3557 55%,
                    #205a86 100%
                );

            display: flex;

            align-items: center;

            gap: 22px;

            overflow: hidden;
        }


        .profile-hero::before {

            content: "";

            position: absolute;

            width: 240px;
            height: 240px;

            border-radius: 50%;

            right: -80px;
            top: -110px;

            border: 1px solid rgba(255,255,255,.08);
        }


        .profile-hero::after {

            content: "";

            position: absolute;

            width: 170px;
            height: 170px;

            border-radius: 50%;

            right: 55px;
            bottom: -125px;

            border: 1px solid rgba(255,255,255,.06);
        }


        .profile-photo {

            position: relative;

            width: 105px;
            height: 105px;

            border-radius: 50%;

            flex-shrink: 0;

            background: #ffffff;

            padding: 4px;

            box-shadow:
                0 8px 20px
                rgba(0,0,0,.18);

            z-index: 2;
        }


        .profile-photo img {

            width: 100%;
            height: 100%;

            border-radius: 50%;

            object-fit: cover;

            display: block;
        }


        .profile-photo-fallback {

            width: 100%;
            height: 100%;

            border-radius: 50%;

            background: #e8f2fc;

            color: #2468a9;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 34px;

            font-weight: 750;
        }


        .profile-hero-info {

            min-width: 0;

            position: relative;

            z-index: 2;
        }


        .profile-hero-info h3 {

            color: #ffffff;

            font-size: 23px;

            font-weight: 750;

            line-height: 1.3;

            word-break: break-word;
        }


        .profile-email {

            color: #b9c9db;

            font-size: 11px;

            margin-top: 5px;

            word-break: break-word;
        }


        .student-badge {

            display: inline-flex;

            align-items: center;

            margin-top: 12px;

            padding: 5px 11px;

            border-radius: 20px;

            background: rgba(255,255,255,.10);

            border: 1px solid rgba(255,255,255,.15);

            color: #d9ebfc;

            font-size: 9px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .5px;
        }


        /* ==========================================
           INFORMATION HEADER
        ========================================== */

        .profile-section-header {

            padding: 19px 25px;

            border-bottom: 1px solid #edf0f4;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;
        }


        .profile-section-header h3 {

            color: #172033;

            font-size: 14px;

            font-weight: 750;
        }


        .profile-section-header span {

            color: #8b96a7;

            font-size: 9px;
        }


        /* ==========================================
           DETAILS
        ========================================== */

        .profile-details {

            padding: 0 25px;
        }


        .detail-row {

            display: grid;

            grid-template-columns:
                190px minmax(0, 1fr);

            align-items: center;

            min-height: 58px;

            border-bottom: 1px solid #f0f3f6;
        }


        .detail-row:last-child {

            border-bottom: none;
        }


        .detail-label {

            color: #8b96a7;

            font-size: 10px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .4px;
        }


        .detail-value {

            color: #172033;

            font-size: 12px;

            font-weight: 600;

            word-break: break-word;
        }


        .detail-value.muted {

            color: #9aa5b4;

            font-weight: 500;
        }


        .role-badge {

            display: inline-flex;

            align-items: center;

            padding: 5px 10px;

            border-radius: 20px;

            background: #eafaf4;

            color: #12966c;

            font-size: 9px;

            font-weight: 750;

            text-transform: uppercase;

            letter-spacing: .4px;
        }


        /* ==========================================
           ACTION FOOTER
        ========================================== */

        .profile-actions {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding: 18px 25px;

            border-top: 1px solid #e5eaf0;

            background: #f8fafc;
        }


        .profile-back {

            color: #718096;

            text-decoration: none;

            font-size: 10px;

            font-weight: 650;

            transition: .2s ease;
        }


        .profile-back:hover {

            color: #172033;
        }


        .action-buttons {

            display: flex;

            align-items: center;

            gap: 9px;

            margin-left: auto;
        }


        .profile-btn {

            min-height: 38px;

            padding: 0 16px;

            border-radius: 8px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            text-decoration: none;

            font-size: 10px;

            font-weight: 700;

            transition: .2s ease;
        }


        .edit-btn {

            background: #1d6fc5;

            color: #ffffff;

            border: 1px solid #1d6fc5;
        }


        .edit-btn:hover {

            background: #155da7;

            border-color: #155da7;

            transform: translateY(-1px);
        }


        .delete-btn {

            background: #ffffff;

            color: #dc4b4b;

            border: 1px solid #f0caca;
        }


        .delete-btn:hover {

            background: #fff3f3;

            border-color: #eaa6a6;

            transform: translateY(-1px);
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


            .detail-row {

                grid-template-columns:
                    155px minmax(0, 1fr);
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


            .mobile-menu-btn {

                display: flex;
            }


            .sidebar {

                position: fixed;

                top: 0;
                left: 0;

                width: 270px;
                height: 100vh;

                transform: translateX(-100%);

                transition:
                    transform .25s ease;

                box-shadow:
                    8px 0 25px
                    rgba(0, 0, 0, .18);

                z-index: 200;
            }


            #menu-toggle:checked
            ~ .dashboard
            .sidebar {

                transform: translateX(0);
            }


            #menu-toggle:checked
            ~ .menu-overlay {

                opacity: 1;

                visibility: visible;
            }


            .menu-overlay {

                display: block;
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

                padding: 19px 17px 30px;
            }


            .profile-page-header {

                align-items: flex-start;

                margin-bottom: 17px;
            }


            .profile-heading h2 {

                font-size: 18px;
            }


            .profile-heading p {

                font-size: 10px;
            }


            .account-label {

                display: none;
            }


            .profile-hero {

                min-height: auto;

                padding: 24px 20px;

                flex-direction: column;

                text-align: center;

                gap: 13px;
            }


            .profile-photo {

                width: 95px;
                height: 95px;
            }


            .profile-hero-info {

                width: 100%;
            }


            .profile-hero-info h3 {

                font-size: 19px;
            }


            .profile-email {

                font-size: 10px;
            }


            .profile-section-header {

                padding: 17px 19px;
            }


            .profile-details {

                padding: 0 19px;
            }


            .detail-row {

                grid-template-columns: 1fr;

                gap: 5px;

                min-height: auto;

                padding: 13px 0;
            }


            .detail-label {

                font-size: 9px;
            }


            .detail-value {

                font-size: 11px;
            }


            .profile-actions {

                padding: 17px 19px;

                flex-wrap: wrap;
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

                padding: 15px 13px 25px;
            }


            .profile-page-header {

                margin-bottom: 15px;
            }


            .profile-heading h2 {

                font-size: 17px;
            }


            .profile-heading p {

                font-size: 9px;
            }


            .profile-card {

                border-radius: 13px;
            }


            .profile-hero {

                padding: 22px 16px;
            }


            .profile-photo {

                width: 88px;
                height: 88px;
            }


            .profile-hero-info h3 {

                font-size: 18px;
            }


            .profile-section-header {

                padding: 15px 16px;
            }


            .profile-section-header h3 {

                font-size: 13px;
            }


            .profile-details {

                padding: 0 16px;
            }


            .profile-actions {

                padding: 15px 16px;

                flex-direction: column;

                align-items: stretch;
            }


            .profile-back {

                text-align: center;

                padding-bottom: 2px;
            }


            .action-buttons {

                width: 100%;

                margin-left: 0;

                display: grid;

                grid-template-columns: 1fr 1fr;
            }


            .profile-btn {

                width: 100%;
            }

        }


        /* ==========================================
           EXTRA SMALL PHONE
        ========================================== */

        @media screen and (max-width: 350px) {

            .action-buttons {

                grid-template-columns: 1fr;
            }


            .profile-hero-info h3 {

                font-size: 17px;
            }


            .profile-email {

                font-size: 9px;
            }

        }

    </style>

</head>


<body>

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
                    class="nav-item nav-found"
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
                    class="nav-item active"
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


    <!-- ==========================================
         MOBILE OVERLAY
    ========================================== -->

    <label
        for="menu-toggle"
        class="menu-overlay"
    ></label>


    <!-- ==========================================
         MAIN CONTENT
    ========================================== -->

    <main class="main-content">


        <!-- ==========================================
             TOPBAR
        ========================================== -->

        <header class="topbar">


            <div class="topbar-left">


                <!-- MOBILE HAMBURGER -->

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
                        My Profile
                    </h1>

                    <p>
                        View and manage your student account information.
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


                    <?php if (
                        !empty($profilePic) &&
                        file_exists($profileServerPath)
                    ): ?>

                        <img
                            src="<?php echo htmlspecialchars($profileImage); ?>"
                            alt="Student Profile"
                            onerror="
                                this.onerror=null;
                                this.style.display='none';
                            "
                        >

                    <?php elseif (
                        !empty($profilePic) &&
                        file_exists($mainServerPath)
                    ): ?>

                        <img
                            src="<?php echo htmlspecialchars($profileImage); ?>"
                            alt="Student Profile"
                            onerror="
                                this.onerror=null;
                                this.style.display='none';
                            "
                        >

                    <?php else: ?>

                        <?php
                        echo htmlspecialchars($studentInitial);
                        ?>

                    <?php endif; ?>


                </div>


            </div>


        </header>


        <!-- ==========================================
             PAGE CONTENT
        ========================================== -->

        <div class="page-content">


            <div class="profile-page-header">


                <div class="profile-heading">

                    <h2>
                        Account Profile
                    </h2>

                    <p>
                        Review your personal information and account details.
                    </p>

                </div>


                <div class="account-label">

                    <span class="account-dot"></span>

                    Active Student Account

                </div>


            </div>


            <!-- ==========================================
                 PROFILE CARD
            ========================================== -->

            <section class="profile-card">


                <!-- PROFILE HERO -->

                <div class="profile-hero">


                    <div class="profile-photo">


                        <?php if (
                            !empty($profilePic) &&
                            (
                                file_exists($profileServerPath) ||
                                file_exists($mainServerPath)
                            )
                        ): ?>

                            <img
                                src="<?php echo htmlspecialchars($profileImage); ?>"
                                alt="Profile Picture"
                                onerror="
                                    this.onerror=null;
                                    this.style.display='none';
                                    this.nextElementSibling.style.display='flex';
                                "
                            >

                            <div
                                class="profile-photo-fallback"
                                style="display:none;"
                            >
                                <?php
                                echo htmlspecialchars($studentInitial);
                                ?>
                            </div>

                        <?php else: ?>

                            <div class="profile-photo-fallback">

                                <?php
                                echo htmlspecialchars($studentInitial);
                                ?>

                            </div>

                        <?php endif; ?>


                    </div>


                    <div class="profile-hero-info">


                        <h3>

                            <?php
                            echo htmlspecialchars(
                                $user['fullname'] ?? 'Student'
                            );
                            ?>

                        </h3>


                        <p class="profile-email">

                            <?php
                            echo htmlspecialchars(
                                $user['email'] ?? ''
                            );
                            ?>

                        </p>


                        <span class="student-badge">

                            <?php
                            echo htmlspecialchars(
                                ucfirst(
                                    $user['role'] ?? 'student'
                                )
                            );
                            ?>

                        </span>


                    </div>


                </div>


                <!-- INFORMATION HEADER -->

                <div class="profile-section-header">


                    <h3>
                        Personal Information
                    </h3>


                    <span>
                        Account ID #
                        <?php
                        echo htmlspecialchars(
                            $user['id']
                        );
                        ?>
                    </span>


                </div>


                <!-- PROFILE DETAILS -->

                <div class="profile-details">


                    <!-- FULL NAME -->

                    <div class="detail-row">

                        <span class="detail-label">
                            Full Name
                        </span>

                        <span class="detail-value">

                            <?php
                            echo htmlspecialchars(
                                $user['fullname'] ?? ''
                            );
                            ?>

                        </span>

                    </div>


                    <!-- COURSE -->

                    <div class="detail-row">

                        <span class="detail-label">
                            Course
                        </span>

                        <span class="detail-value">

                            <?php
                            echo !empty($user['course'])
                                ? htmlspecialchars($user['course'])
                                : '<span class="muted">Not provided</span>';
                            ?>

                        </span>

                    </div>


                    <!-- YEAR LEVEL -->

                    <div class="detail-row">

                        <span class="detail-label">
                            Year Level
                        </span>

                        <span class="detail-value">

                            <?php
                            echo !empty($user['year_level'])
                                ? htmlspecialchars($user['year_level'])
                                : '<span class="muted">Not provided</span>';
                            ?>

                        </span>

                    </div>


                    <!-- CONTACT -->

                    <div class="detail-row">

                        <span class="detail-label">
                            Contact Number
                        </span>

                        <span class="detail-value">

                            <?php
                            if (!empty($user['contact_number'])) {

                                echo htmlspecialchars(
                                    $user['contact_number']
                                );

                            } else {

                                echo '<span class="detail-value muted">
                                        No contact number
                                      </span>';
                            }
                            ?>

                        </span>

                    </div>


                    <!-- EMAIL -->

                    <div class="detail-row">

                        <span class="detail-label">
                            Email Address
                        </span>

                        <span class="detail-value">

                            <?php
                            echo htmlspecialchars(
                                $user['email'] ?? ''
                            );
                            ?>

                        </span>

                    </div>


                    <!-- ROLE -->

                    <div class="detail-row">

                        <span class="detail-label">
                            System Role
                        </span>

                        <span class="detail-value">

                            <span class="role-badge">

                                <?php
                                echo htmlspecialchars(
                                    ucfirst(
                                        $user['role'] ?? 'student'
                                    )
                                );
                                ?>

                            </span>

                        </span>

                    </div>


                </div>

                    <div class="action-buttons">


                        <a
                            href="editprofile.php"
                            class="profile-btn edit-btn"
                        >
                            Edit Profile
                        </a>


                        <a
                            href="delete_account.php"
                            class="profile-btn delete-btn"
                            onclick=" return confirm('Are you sure you want to delete your account? This action cannot be undone.' );
                            "
                        >
                            Delete Account

                        </a>


                    </div>


                </div>


            </section>


        </div>


    </main>


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