<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] != "student") {
    header("Location: ../login.php");
    exit();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$fullname = $_SESSION['fullname'] ?? 'Student';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Dashboard - Lost &amp; Found</title>

    <style>


        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family:Arial, Helvetica, sans-serif;
        }

        :root {
            --primary: #4da3ff;
            --bg-surface: #ffffff;
            --border-color: #e3e8ee;
            --text-main: #172033;
            --text-muted: #8793a5;
            --text-light: #9aa5b4;
            --background: #f3f6fa;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 15px;
            --transition: .2s ease;
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
        #menu-toggle {
            display: none;
        }

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

    
        .main-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
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

        .user-nav-actions {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }

        .badge-btn {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 14px;
        }

        .content-body {
            padding: 32px 36px;
            display: flex;
            flex-direction: column;
            gap: 28px;
            width: 100%;
        }

        .section-title-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .section-title-wrapper h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
        }

        .section-title-wrapper span {
            font-size: 11px;
            color: var(--text-light);
        }

        .welcome-panel {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            align-items: center;
            gap: 28px;
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 34px;
            box-shadow: 0 2px 8px rgba(17, 26, 45, .04);
            position: relative;
            overflow: hidden;
        }

        .welcome-copy {
            min-width: 0;
        }

        .welcome-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            color: #2468a9;
            font-size: 10px;
            font-weight: 750;
            letter-spacing: 1.3px;
            text-transform: uppercase;
        }

        .welcome-label::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #4da3ff;
        }

        .welcome-panel h2 {
            color: var(--text-main);
            font-size: clamp(21px, 2.2vw, 27px);
            line-height: 1.35;
            margin-bottom: 12px;
            letter-spacing: -.4px;
        }

        .welcome-panel p {
            max-width: 620px;
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.8;
        }

        .dashboard-note {
            display: flex;
            align-items: flex-start;
            gap: 13px;
            padding: 17px 19px;
            border: 1px solid #dce9f8;
            border-radius: 12px;
            background: #f7faff;
            color: #34445d;
            font-size: 12px;
            line-height: 1.7;
        }

        .note-mark {
            width: 28px;
            height: 28px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #e2efff;
            color: #2468a9;
            font-size: 14px;
            font-weight: 700;
        }

        .dashboard-note strong {
            display: block;
            color: var(--text-main);
            margin-bottom: 2px;
        }

        .dashboard-footer {
            margin-top: auto;
            padding: 18px 36px 22px;
            color: #8a96a8;
            font-size: 11px;
            border-top: 1px solid #e7ebf1;
        }

        /* ==========================================
           TABLET
        ========================================== */

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

            .welcome-panel {
                padding: 25px;
                gap: 20px;
            }

            .dashboard-footer {
                padding: 18px 23px 20px;
            }
        }

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

            #menu-toggle:checked
            ~ .dashboard-wrapper .sidebar {
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

            .user-nav-actions {
                gap: 8px;
            }

            .content-body {
                padding: 20px 17px 30px;
                gap: 22px;
            }

            .welcome-panel {
                padding: 22px;
                gap: 20px;
            }

            .welcome-panel p {
                font-size: 13px;
            }

            .dashboard-footer {
                padding: 16px 17px 20px;
                text-align: center;
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

            .badge-btn {
                width: 33px;
                height: 33px;
            }

            .content-body {
                padding: 17px 13px 25px;
            }

            .section-title-wrapper h3 {
                font-size: 14px;
            }

            .section-title-wrapper span {
                font-size: 10px;
            }

            .welcome-panel {
                padding: 19px;
            }

            .welcome-panel h2 {
                font-size: 18px;
            }

            .dashboard-note {
                padding: 13px;
                font-size: 12px;
            }
        }

        @media screen and (max-width: 350px) {
            .user-nav-actions {
                gap: 5px;
            }

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

            .welcome-panel {
                padding: 16px;
            }

            .header-title h1 {
                max-width: 145px;
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
                <a href="student_dashboard.php" class="nav-item active">
                    <span class="nav-icon">⌂</span>
                    <span>Dashboard</span>
                </a>

                <a href="report_lost.php" class="nav-item nav-lost">
                    <span class="nav-icon">⌕</span>
                    <span>Report Lost Item</span>
                </a>

                <a href="report_found.php" class="nav-item nav-found">
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
                <a href="../logout.php" class="nav-item"
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
                        <h1>
                            Welcome back,
                            <?php echo htmlspecialchars($fullname); ?>!
                        </h1>

                        <p>
                            Manage and monitor your lost &amp; found
                            activities on campus.
                        </p>
                    </div>
                </div>

            </header>

            <div class="content-body">
                <section class="welcome-panel">
                    <div class="welcome-copy">
                        <span class="welcome-label">Student Portal</span>
                        <h2>A simpler way to reconnect with lost items.</h2>
                        <p>
                            Welcome to the SLF campus community. Report an item,
                            check your submissions, and keep your profile details
                            up to date using the navigation menu.
                        </p>
                    </div>

                </section>

                <div class="dashboard-note">
                    <span class="note-mark" aria-hidden="true">i</span>
                    <div>
                        <strong>Helpful reminder</strong>
                        Include a clear description, accurate location, and date
                        when submitting a report. These details can help the
                        campus community identify an item more easily.
                    </div>
                </div>
            </div>

            <footer class="dashboard-footer">
                Student Lost &amp; Found <span aria-hidden="true">·</span> Student Portal
            </footer>
        </main>
    </div>
<script>
window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
        window.location.reload();
    }
});

function confirmLogout() {
    return confirm("Are you sure you want to log out?");
}
</script>
</body>
</html>
