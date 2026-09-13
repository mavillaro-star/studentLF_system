<?php
session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Redirect if role is not student
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
        /* Base Reset */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: #eaf0f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 30px 20px;
        }

        /* Outer Dashboard Container */
        .dashboard-container {
            display: flex;
            width: 100%;
            max-width: 1100px;
            background-color: #ffffff;
            border-radius: 20px;
            padding: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            gap: 20px;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 220px;
            background-color: #172033;
            border-radius: 16px;
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            color: #ffffff;
            flex-shrink: 0;
        }

        .logo-box {
            background-color: #26334d;
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px auto;
        }

        .logo-icon {
            width: 24px;
            height: 24px;
            fill: #8a99ad;
        }

        .brand-title {
            font-size: 13px;
            font-weight: 700;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-align: center;
            padding-bottom: 16px;
            margin-bottom: 16px;
            border-bottom: 1px solid #26334d;
        }

        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex-grow: 1;
            list-style: none;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #a3b1c2;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            padding: 10px 14px;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .nav-item:hover {
            color: #ffffff;
            background-color: #212c42;
        }

        .nav-item.active {
            color: #ffffff;
            background-color: #34435e;
        }

        .nav-item.nav-lost { color: #ff6b6b; }
        .nav-item.nav-found { color: #20c997; }

        .logout {
            margin-top: auto;
            border-top: 1px solid #26334d;
            padding-top: 16px;
        }

        /* Main Content Styling */
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 8px;
        }

        .page-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background-color: #3b82f6;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
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
            line-height: 1.2;
        }

        .user-role {
            font-size: 11px;
            color: #8a99ad;
        }

        /* Inner Content Panel */
        .content-display {
            width: 100%;
            border-radius: 16px;
            background-color: #f7fafc;
            padding: 20px;
            border: 1px solid #edf2f7;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .welcome-banner {
            background-color: #172033;
            color: #ffffff;
            padding: 24px 28px;
            border-radius: 14px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .banner-text h2 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .banner-text p {
            font-size: 12px;
            color: #a3b1c2;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            background-color: #ff5252;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-action-alt {
            display: inline-flex;
            align-items: center;
            background-color: #10b981;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 260px;
            gap: 20px;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 10px;
        }

        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 16px 10px;
            text-align: center;
            border: 1px solid #e5e7eb;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .stat-card.card-red::before { background-color: #ff5252; }
        .stat-card.card-orange::before { background-color: #f59e0b; }
        .stat-card.card-green::before { background-color: #10b981; }

        .stat-num {
            font-size: 20px;
            font-weight: 800;
            color: #111827;
        }

        .stat-label {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* Item Cards */
        .recent-items-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .item-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }

        .item-icon {
            font-size: 24px;
            background-color: #f3f4f6;
            height: 64px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .item-title {
            font-size: 12px;
            font-weight: 700;
            color: #111827;
        }

        .item-location {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
        }

        .badge-found {
            display: inline-block;
            background-color: #d1fae5;
            color: #10b981;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 10px;
            border-radius: 4px;
            margin-top: 6px;
        }

        /* Feed Side Panel */
        .feed-box {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .feed-item {
            padding-bottom: 12px;
            border-bottom: 1px solid #f3f4f6;
        }

        .feed-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .feed-item-title {
            font-size: 12px;
            font-weight: 700;
            color: #111827;
        }

        .feed-item-desc {
            font-size: 11px;
            color: #6b7280;
            margin-top: 2px;
            line-height: 1.3;
        }

        .feed-time {
            font-size: 10px;
            color: #9ca3af;
            margin-top: 4px;
        }

        @media screen and (max-width: 850px) {
            .dashboard-container { flex-direction: column; }
            .sidebar { width: 100%; }
            .dashboard-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <aside class="sidebar">
        <a href="student_dashboard.php" style="text-decoration: none;">
            <div class="logo-box">
                <svg class="logo-icon" viewBox="0 0 24 24">
                    <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14zM10 7h-1v2H7v1h2v2h1v-2h2V9h-2V7z"/>
                </svg>
            </div>
        </a>

        <div class="brand-title">Lost &amp; Found</div>

        <ul class="nav-menu">
            <li>
                <a href="student_dashboard.php" class="nav-item active">
                    <span class="icon">🏫</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="report_lost.php" class="nav-item nav-lost">
                    <span class="icon">🔍</span>
                    <span>Report Lost Item</span>
                </a>
            </li>
            <li>
                <a href="report_found.php" class="nav-item nav-found">
                    <span class="icon">📦</span>
                    <span>Report Found Item</span>
                </a>
            </li>
            <li>
                <a href="my_reports.php" class="nav-item">
                    <span class="icon">📊</span>
                    <span>My Reports</span>
                </a>
            </li>
            <li>
                <a href="profile.php" class="nav-item">
                    <span class="icon">👤</span>
                    <span>Profile</span>
                </a>
            </li>
        </ul>

        <div class="logout">
            <a href="st_logout_con.php" class="nav-item">
                <span class="icon">↪</span>
                <span>Log Out</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="header">
            <h1 class="page-title">Student Portal</h1>
            <div class="user-profile">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($fullname); ?></span>
                    <span class="user-role">Student</span>
                </div>
                <div class="avatar">
                    <?php echo strtoupper(substr($fullname, 0, 1)); ?>
                </div>
            </div>
        </header>

        <section class="content-display">
            <div class="welcome-banner">
                <div class="banner-text">
                    <h2>Welcome back, <?php echo htmlspecialchars($fullname); ?>!</h2>
                    <p>Track your submitted reports or log a new lost/found item below.</p>
                </div>
                <div class="action-buttons">
                    <a href="report_lost.php" class="btn-action">+ Report Lost</a>
                    <a href="report_found.php" class="btn-action-alt">+ Report Found</a>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="left-col">
                    <div class="section-title">My Activity Overview</div>
                    <div class="stats-row">
                        <div class="stat-card card-red">
                            <div class="stat-num">0</div>
                            <div class="stat-label">Active Reports</div>
                        </div>
                        <div class="stat-card card-orange">
                            <div class="stat-num">0</div>
                            <div class="stat-label">Potential Matches</div>
                        </div>
                        <div class="stat-card card-green">
                            <div class="stat-num">0</div>
                            <div class="stat-label">Items Recovered</div>
                        </div>
                    </div>

                    <div class="section-title">Recently Found Items On Campus</div>
                    <div class="recent-items-grid">
                        <div class="item-card">
                            <div class="item-icon">💧</div>
                            <div class="item-title">Water Bottle</div>
                            <div class="item-location">Library • Today</div>
                            <span class="badge-found">Found</span>
                        </div>
                        <div class="item-card">
                            <div class="item-icon">🧮</div>
                            <div class="item-title">Calculator</div>
                            <div class="item-location">Rm 101 • Yesterday</div>
                            <span class="badge-found">Found</span>
                        </div>
                        <div class="item-card">
                            <div class="item-icon">👛</div>
                            <div class="item-title">Wallet</div>
                            <div class="item-location">Cafeteria • Oct 24</div>
                            <span class="badge-found">Found</span>
                        </div>
                    </div>
                </div>

                <div class="right-col">
                    <div class="section-title">System Updates</div>
                    <div class="feed-box">
                        <div class="feed-item">
                            <div class="feed-item-title">System Alert</div>
                            <div class="feed-item-desc">Welcome to the new Lost &amp; Found portal. Submit a claim if you lost an item.</div>
                            <div class="feed-time">Just now</div>
                        </div>
                        <div class="feed-item">
                            <div class="feed-item-title">Office Notice</div>
                            <div class="feed-item-desc">Items can be retrieved at the Student Affairs Office (Mon–Fri).</div>
                            <div class="feed-time">1 day ago</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>