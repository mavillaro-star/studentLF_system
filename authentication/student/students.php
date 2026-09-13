<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] != "student") {
    header("Location: ../login.php");
    exit();
}

$fullname = $_SESSION['fullname'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
        }

        html,
        body {
            width: 100%;
            height: 100%;
        }

        body {
            background: #eef3f8;
            color: #172033;
            overflow: hidden;
        }

        .dashboard {
            width: 100%;
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* =========================
           MOBILE MENU TOGGLE
        ========================= */

        #menu-toggle {
            display: none;
        }

        .mobile-menu-btn {
            display: none;
            width: 36px;
            height: 36px;
            border: 1px solid #e2e7ed;
            border-radius: 9px;
            background: #ffffff;
            color: #172033;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            cursor: pointer;
            flex-shrink: 0;
        }

        .menu-overlay {
            display: none;
        }

        /* =========================
           SIDEBAR
        ========================= */

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
            display: block;
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

        /* =========================
           NAVIGATION
        ========================= */

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

        /* LOGOUT AT BOTTOM */

        .sidebar-bottom {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #26334c;
        }

        .sidebar-bottom .nav-item:hover {
            background: #241d2a;
            color: #ff7f7f;
        }

        /* =========================
           MAIN CONTENT
        ========================= */

        .main-content {
            margin-left: 230px;
            width: calc(100% - 230px);
            height: 100vh;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            background: #f3f6fa;
        }

        /* =========================
           TOP HEADER
        ========================= */

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
        }

        .topbar-left h1 {
            font-size: 19px;
            color: #172033;
            font-weight: 750;
        }

        .topbar-left p {
            font-size: 11px;
            color: #8793a5;
            margin-top: 3px;
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 14px;
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
            box-shadow: 0 4px 10px rgba(29, 111, 197, .18);
        }

        /* =========================
           PAGE CONTENT
        ========================= */

        .page-content {
            padding: 28px 30px 35px;
            width: 100%;
        }

        /* =========================
           WELCOME HERO
        ========================= */

        .welcome {
            width: 100%;
            min-height: 175px;
            border-radius: 17px;
            padding: 27px 30px;
            background:
                linear-gradient(120deg, #111c31 0%, #172b49 65%, #1c385c 100%);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .welcome::before {
            content: "";
            width: 260px;
            height: 260px;
            border: 1px solid rgba(77, 163, 255, .18);
            border-radius: 50%;
            position: absolute;
            right: -90px;
            top: -105px;
        }

        .welcome::after {
            content: "";
            width: 170px;
            height: 170px;
            border: 1px solid rgba(77, 163, 255, .12);
            border-radius: 50%;
            position: absolute;
            right: 40px;
            bottom: -110px;
        }

        .welcome-content {
            position: relative;
            z-index: 2;
        }

        .welcome-label {
            font-size: 9px;
            color: #67b1ff;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
            margin-bottom: 9px;
        }

        .welcome h2 {
            color: #ffffff;
            font-size: 23px;
            font-weight: 750;
            margin-bottom: 7px;
        }

        .welcome p {
            color: #9caec5;
            font-size: 11px;
            max-width: 470px;
            line-height: 1.6;
        }

        .welcome-actions {
            display: flex;
            gap: 9px;
            position: relative;
            z-index: 2;
        }

        .action-btn {
            min-height: 38px;
            padding: 0 15px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            text-decoration: none;
            font-size: 11px;
            font-weight: 650;
            transition: .2s ease;
        }

        .lost-btn {
            background: #ff5f61;
            color: #ffffff;
        }

        .lost-btn:hover {
            background: #ed4d50;
            transform: translateY(-1px);
        }

        .found-btn {
            background: #19b982;
            color: #ffffff;
        }

        .found-btn:hover {
            background: #12a774;
            transform: translateY(-1px);
        }

        /* =========================
           STATS
        ========================= */

        .section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .section-heading h3 {
            font-size: 13px;
            color: #172033;
            font-weight: 750;
        }

        .section-heading span {
            font-size: 9px;
            color: #9aa5b4;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e5eaf0;
            border-radius: 13px;
            min-height: 105px;
            padding: 17px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 13px;
            transition: .2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(25, 45, 70, .06);
        }

        .stat-icon {
            width: 43px;
            height: 43px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
        }

        .red-icon {
            background: #fff0f0;
            color: #f05252;
        }

        .orange-icon {
            background: #fff7e8;
            color: #ee9b19;
        }

        .green-icon {
            background: #eafaf4;
            color: #13a976;
        }

        .stat-details {
            display: flex;
            flex-direction: column;
        }

        .stat-num {
            font-size: 23px;
            line-height: 1;
            font-weight: 800;
            color: #172033;
        }

        .stat-label {
            font-size: 10px;
            color: #7d899a;
            margin-top: 6px;
        }

        .stat-line {
            position: absolute;
            left: 0;
            bottom: 0;
            height: 3px;
            width: 100%;
        }

        .red-line {
            background: #f05252;
        }

        .orange-line {
            background: #ee9b19;
        }

        .green-line {
            background: #13a976;
        }

        /* =========================
           LOWER CONTENT
        ========================= */

        .dashboard-columns {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(260px, .8fr);
            gap: 20px;
        }

        .panel {
            background: #ffffff;
            border: 1px solid #e5eaf0;
            border-radius: 14px;
            padding: 18px;
        }

        /* Recent Items */

        .items-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .item-card {
            border: 1px solid #e7ebf0;
            border-radius: 11px;
            padding: 10px;
            transition: .2s ease;
        }

        .item-card:hover {
            border-color: #cbd9e8;
            transform: translateY(-2px);
        }

        .item-visual {
            height: 85px;
            border-radius: 9px;
            background: #f4f7fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-bottom: 10px;
        }

        .item-info strong {
            display: block;
            font-size: 11px;
            color: #172033;
        }

        .item-location {
            display: block;
            color: #8a95a5;
            font-size: 9px;
            margin-top: 4px;
        }

        .found-badge {
            display: inline-block;
            margin-top: 8px;
            padding: 3px 8px;
            border-radius: 5px;
            background: #e9faf3;
            color: #10a674;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        /* System Updates */

        .updates {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .update {
            display: flex;
            gap: 11px;
            padding: 13px 0;
            border-bottom: 1px solid #edf0f4;
        }

        .update:first-child {
            padding-top: 3px;
        }

        .update:last-child {
            border-bottom: none;
            padding-bottom: 2px;
        }

        .update-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4da3ff;
            margin-top: 5px;
            flex-shrink: 0;
            box-shadow: 0 0 0 4px #eaf4ff;
        }

        .update-title {
            display: block;
            font-size: 10px;
            font-weight: 700;
            color: #172033;
        }

        .update-text {
            font-size: 9px;
            color: #7d8998;
            line-height: 1.5;
            margin-top: 3px;
        }

        .update-time {
            display: block;
            font-size: 8px;
            color: #a7afba;
            margin-top: 4px;
        }

        /* =========================
           TABLET
        ========================= */

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

            .dashboard-columns {
                grid-template-columns: 1fr;
            }

            .welcome {
                align-items: flex-start;
                flex-direction: column;
                gap: 20px;
            }
        }

        /* =========================
           MOBILE
        ========================= */

        @media screen and (max-width: 760px) {

            body {
                overflow: hidden;
            }

            .dashboard {
                min-height: 100vh;
                height: 100vh;
                display: block;
                overflow: hidden;
            }

            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                width: 270px;
                height: 100vh;
                padding: 24px 16px;
                border-right: 1px solid #1f2b43;
                border-bottom: none;
                transform: translateX(-100%);
                transition: transform .25s ease;
                box-shadow: 10px 0 30px rgba(0, 0, 0, .15);
                overflow-y: auto;
            }

            #menu-toggle:checked ~ .dashboard .sidebar {
                transform: translateX(0);
            }

            .menu-overlay {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(8, 15, 29, .45);
                z-index: 150;
                opacity: 0;
                visibility: hidden;
                transition: .25s ease;
            }

            #menu-toggle:checked ~ .dashboard .menu-overlay {
                opacity: 1;
                visibility: visible;
            }

            .brand {
                padding-bottom: 25px;
                margin-bottom: 22px;
            }

            .nav-title {
                display: block;
            }

            .nav-menu {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }

            .nav-item {
                height: 44px;
            }

            .sidebar-bottom {
                margin-top: auto;
                padding-top: 15px;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                height: 100vh;
                min-height: 100vh;
                overflow-y: auto;
            }

            .topbar {
                padding: 0 17px;
                min-height: 65px;
            }

            .mobile-menu-btn {
                display: flex;
            }

            .topbar-left h1 {
                font-size: 16px;
            }

            .topbar-left p {
                display: none;
            }

            .user-info,
            .user-divider {
                display: none;
            }

            .user-area {
                gap: 8px;
            }

            .page-content {
                padding: 18px;
            }

            .welcome {
                padding: 23px;
            }

            .welcome h2 {
                font-size: 19px;
            }

            .welcome-actions {
                width: 100%;
            }

            .action-btn {
                flex: 1;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .items-grid {
                grid-template-columns: 1fr;
            }
        }

        /* =========================
           SMALL MOBILE
        ========================= */

        @media screen and (max-width: 430px) {

            .sidebar {
                width: 285px;
            }

            .topbar {
                padding: 0 14px;
            }

            .topbar-left {
                gap: 8px;
            }

            .topbar-left h1 {
                font-size: 15px;
            }

            .notification {
                width: 33px;
                height: 33px;
            }

            .avatar {
                width: 34px;
                height: 34px;
            }

            .welcome {
                padding: 19px;
            }

            .welcome-actions {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
            }

            .page-content {
                padding: 14px;
            }

            .panel {
                padding: 14px;
            }
        }
    </style>
</head>

<body>

<input type="checkbox" id="menu-toggle">

<div class="dashboard">

    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar">

        <a href="students.php" class="brand-link">
            <div class="brand">

                <div class="brand-mark"></div>

                <div class="brand-text">
                    <strong>LOST &amp; FOUND</strong>
                    <span>STUDENT PORTAL</span>
                </div>

            </div>
        </a>

        <div class="nav-title">Main Menu</div>

        <ul class="nav-menu">

            <li>
                <a href="students.php" class="nav-item active">
                    <span class="nav-icon">⌂</span>
                    <span>Dashboard</span>
                </a>
            </li>

            <li>
                <a href="report_lost.php" class="nav-item nav-lost">
                    <span class="nav-icon">⌕</span>
                    <span>Report Lost Item</span>
                </a>
            </li>

            <li>
                <a href="report_found.php" class="nav-item nav-found">
                    <span class="nav-icon">□</span>
                    <span>Report Found Item</span>
                </a>
            </li>

            <li>
                <a href="my_reports.php" class="nav-item">
                    <span class="nav-icon">▤</span>
                    <span>My Reports</span>
                </a>
            </li>

            <li>
                <a href="profile.php" class="nav-item">
                    <span class="nav-icon">○</span>
                    <span>Profile</span>
                </a>
            </li>

        </ul>

        <!-- LOGOUT ALWAYS AT BOTTOM -->

        <div class="sidebar-bottom">

            <a href="../../authentication/logout.php"
               class="nav-item"
               onclick="return confirmLogout();">

                <span class="nav-icon">↪</span>

                <span>Log Out</span>

            </a>

        </div>

    </aside>

    <!-- MOBILE OVERLAY -->

    <label for="menu-toggle" class="menu-overlay"></label>


    <main class="main-content">

        <!-- TOP BAR -->

        <header class="topbar">

            <div class="topbar-left">

                <label for="menu-toggle" class="mobile-menu-btn">
                    ☰
                </label>

                <div>
                    <h1>Student Dashboard</h1>
                    <p>Manage and monitor your lost &amp; found activity.</p>
                </div>

            </div>

            <div class="user-area">

                <div class="notification">
                    ♢
                </div>

                <div class="user-divider"></div>

                <div class="user-info">

                    <span class="user-name">
                        <?php echo htmlspecialchars($fullname); ?>
                    </span>

                    <span class="user-role">
                        Student Account
                    </span>

                </div>

                <div class="avatar">
                    <?php echo strtoupper(substr($fullname, 0, 1)); ?>
                </div>

            </div>

        </header>


        <!-- PAGE CONTENT -->

        <div class="page-content">

            <!-- WELCOME -->

            <section class="welcome">

                <div class="welcome-content">

                    <div class="welcome-label">
                        Student Portal
                    </div>

                    <h2>
                        Welcome back, <?php echo htmlspecialchars($fullname); ?>!
                    </h2>

                    <p>
                        Keep track of your reports, check possible matches,
                        and help return lost belongings to their rightful owners.
                    </p>

                </div>

                <div class="welcome-actions">

                    <a href="report_lost.php" class="action-btn lost-btn">
                        + Report Lost
                    </a>

                    <a href="report_found.php" class="action-btn found-btn">
                        + Report Found
                    </a>

                </div>

            </section>


            <!-- ACTIVITY -->

            <div class="section-heading">

                <h3>My Activity Overview</h3>

                <span>Account activity</span>

            </div>


            <section class="stats">

                <div class="stat-card">

                    <div class="stat-icon red-icon">
                        !
                    </div>

                    <div class="stat-details">

                        <div class="stat-num">
                            0
                        </div>

                        <div class="stat-label">
                            Active Reports
                        </div>

                    </div>

                    <div class="stat-line red-line"></div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon orange-icon">
                        ◇
                    </div>

                    <div class="stat-details">

                        <div class="stat-num">
                            0
                        </div>

                        <div class="stat-label">
                            Potential Matches
                        </div>

                    </div>

                    <div class="stat-line orange-line"></div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon green-icon">
                        ✓
                    </div>

                    <div class="stat-details">

                        <div class="stat-num">
                            0
                        </div>

                        <div class="stat-label">
                            Items Recovered
                        </div>

                    </div>

                    <div class="stat-line green-line"></div>

                </div>

            </section>


            <!-- LOWER DASHBOARD -->

            <section class="dashboard-columns">

                <!-- RECENT ITEMS -->

                <div class="panel">

                    <div class="section-heading">

                        <h3>Recently Found Items</h3>

                        <span>Campus activity</span>

                    </div>

                    <div class="items-grid">

                        <div class="item-card">

                            <div class="item-visual">
                                ◇
                            </div>

                            <div class="item-info">

                                <strong>Water Bottle</strong>

                                <span class="item-location">
                                    Library • Today
                                </span>

                                <span class="found-badge">
                                    Found
                                </span>

                            </div>

                        </div>


                        <div class="item-card">

                            <div class="item-visual">
                                □
                            </div>

                            <div class="item-info">

                                <strong>Calculator</strong>

                                <span class="item-location">
                                    Room 101 • Yesterday
                                </span>

                                <span class="found-badge">
                                    Found
                                </span>

                            </div>

                        </div>


                        <div class="item-card">

                            <div class="item-visual">
                                ◌
                            </div>

                            <div class="item-info">

                                <strong>Wallet</strong>

                                <span class="item-location">
                                    Cafeteria • Recent
                                </span>

                                <span class="found-badge">
                                    Found
                                </span>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- SYSTEM UPDATES -->

                <div class="panel">

                    <div class="section-heading">

                        <h3>System Updates</h3>

                        <span>Latest</span>

                    </div>

                    <div class="updates">

                        <div class="update">

                            <div class="update-dot"></div>

                            <div>

                                <span class="update-title">
                                    System Alert
                                </span>

                                <div class="update-text">
                                    Welcome to the Lost &amp; Found portal.
                                    Submit a report when an item is lost or found.
                                </div>

                                <span class="update-time">
                                    Just now
                                </span>

                            </div>

                        </div>


                        <div class="update">

                            <div class="update-dot"></div>

                            <div>

                                <span class="update-title">
                                    Office Notice
                                </span>

                                <div class="update-text">
                                    Items may be retrieved through the
                                    Student Affairs Office during office hours.
                                </div>

                                <span class="update-time">
                                    1 day ago
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

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