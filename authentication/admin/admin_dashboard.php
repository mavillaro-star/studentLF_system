<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit();
}

if ($_SESSION['role'] != "admin") {
    header("Location: ../authentication/login.php");
    exit();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");

$fullname = $_SESSION['fullname'];

include '../../db.php';

$lost_count = 0;
$found_count = 0;
$user_count = 0;

$lost_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM lost_items");
if ($lost_query) {
    $lost_data = mysqli_fetch_assoc($lost_query);
    $lost_count = (int)$lost_data['total'];
}

$found_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM found_items");
if ($found_query) {
    $found_data = mysqli_fetch_assoc($found_query);
    $found_count = (int)$found_data['total'];
}

$user_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
if ($user_query) {
    $user_data = mysqli_fetch_assoc($user_query);
    $user_count = (int)$user_data['total'];
}

$total_notifications = $lost_count + $found_count;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="dashboards.css">
    <style>
    
        :root {
            --sidebar-width: 260px;
        }

        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
            position: relative;
            background-color: #f8fafc;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: #0f172a;
            color: #fff;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: margin-left 0.3s ease-in-out;
        }

        /* Hamburger Toggle Button para sa Mobile/Tablet */
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: #1e293b;
            cursor: pointer;
            margin-right: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Mobile & Tablet Responsiveness */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .sidebar-toggle {
                display: block;
            }
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

        /* Overlay para sa Mobile kung naka-open ang sidebar */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        .sidebar-overlay.active {
            display: block;
        }

        /* Simple dashboard display only */
        .simple-dashboard {
            max-width: 1100px;
            margin: 0 auto;
        }

        .simple-dashboard-heading {
            margin-bottom: 22px;
        }

        .simple-dashboard-heading h2 {
            margin: 6px 0;
            color: #1e293b;
            font-size: 25px;
        }

        .simple-dashboard-heading p {
            margin: 0;  
            font-size: 14px;
        }

        .overview-panel {
            padding: 26px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.03);
        }

        .overview-message {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .overview-symbol {
            width: 52px;
            height: 52px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #dbeafe;
            color: #2563eb;
            font-size: 14px;
            font-weight: 800;
        }

        .overview-message h3 {
            margin-bottom: 6px;
            color: #1e293b;
            font-size: 17px;
        }

        .overview-message p {
            max-width: 700px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }

        .overview-divider {
            height: 1px;
            margin: 24px 0;
            background: #e2e8f0;
        }

        .overview-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .overview-stat {
            min-width: 0;
            padding: 18px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
        }

        .overview-stat > span {
            display: block;
            color: #64748b;
            font-size: 12px;
        }

        .overview-stat strong {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            color: #0f172a;
            font-size: 27px;
        }

        .overview-stat small {
            display: block;
            margin-top: 5px;
            color: #94a3b8;
            font-size: 11px;
            line-height: 1.5;
        }

        .overview-stat .system-operational {
            color: #16a34a;
            font-size: 16px;
        }

        .overview-stat .status-dot {
            display: inline-block;
            flex-shrink: 0;
        }

        @media (max-width: 700px) {
            .dashboard-body {
                padding: 22px 16px !important;
            }

            .overview-panel {
                padding: 18px;
            }

            .overview-stats {
                grid-template-columns: 1fr;
            }

            .simple-dashboard-heading h2 {
                font-size: 22px;
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

   <aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <div class="brand-icon">SLF</div>
        <div>
            <h2>Lost & Found</h2>
            <span>Administrator</span>
        </div>
    </div>

    <nav class="navigation">
        <a href="admin_dashboard.php" class="nav-item active">
            <span class="nav-icon">⌂</span>
            <span>Dashboard</span>
        </a>

        <a href="manage_users.php" class="nav-item">
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
        
        
        <section class="dashboard-body">
            <div class="simple-dashboard">
                <div class="simple-dashboard-heading">
                    <span class="section-label">ADMINISTRATOR ACCOUNT</span>
                    <h2>Dashboard Overview</h2>
                    <p>Welcome to your Student Lost & Found Management System.</p>
                </div>

                <div class="overview-panel">
                    <div class="overview-message">
                        <div class="overview-symbol">SLF</div>
                        <div>
                            <h3>System Overview</h3>
                            <p>Manage lost and found reports using the navigation menu. Your report counts are also available beside Lost Item Reports and Found Item Reports.</p>
                        </div>
                    </div>

                    <div class="overview-divider"></div>

                    <div class="overview-stats">
                        <div class="overview-stat">
                            <span>Total Reports</span>
                            <strong><?php echo $total_notifications; ?></strong>
                            <small>Lost and found reports combined</small>
                        </div>
                        <div class="overview-stat">
                            <span>Registered Users</span>
                            <strong><?php echo $user_count; ?></strong>
                            <small>Accounts in the system</small>
                        </div>
                        <div class="overview-stat">
                            <span>System Status</span>
                            <strong class="system-operational"><span class="status-dot"></span> Operational</strong>
                            <small>Dashboard is ready to use</small>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

</div>

<script>
window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
        window.location.reload();
    }
});

function confirmLogout(){
    return confirm("Are you sure you want to log out?");
}
</script>

</body>
</html>