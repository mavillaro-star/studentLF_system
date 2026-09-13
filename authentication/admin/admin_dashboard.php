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

$fullname = $_SESSION['fullname'];
$first_letter = strtoupper(substr(trim($fullname), 0, 1));


include '../../db.php';


$lost_count = 0;
$found_count = 0;

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

$total_notifications = $lost_count + $found_count;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="dashboards.css">
</head>

<body>

<div class="dashboard-wrapper">

   <aside class="sidebar">

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
        </a>

        <a href="view_found.php" class="nav-item">
            <span class="nav-icon">⌕</span>
            <span>Found Item Reports</span>
        </a>

        <a href="admin_profile.php" class="nav-item">
            <span class="nav-icon">👤</span>
            <span>My Profile</span>
        </a>

    </nav>

    <div class="sidebar-bottom">
        <a href="ad_logout_con.php" class="logout-link">
            <span>↪</span>
            <span>Log Out</span>
        </a>
    </div>

</aside>

   
    <main class="main-content">

        
        <header class="topbar">

            <div class="welcome-area">
                <h1>Welcome back, <?php echo htmlspecialchars($fullname); ?> !</h1>
                <p>Here's what's happening in your Lost & Found system.</p>
            </div>


            <div class="topbar-actions">

                
                <div class="dropdown-wrapper">

                    <button
                        type="button"
                        class="icon-button"
                        id="notificationButton"
                        aria-label="Notifications"
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            class="bell-icon"
                        >
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>

                        <?php if ($total_notifications > 0): ?>
                            <span class="notification-badge">
                                <?php echo $total_notifications > 99 ? '99+' : $total_notifications; ?>
                            </span>
                        <?php endif; ?>

                    </button>


                   
                    <div class="dropdown notification-dropdown" id="notificationDropdown">

                        <div class="dropdown-header">
                            <div>
                                <h3>Notifications</h3>
                                <span>Latest system activity</span>
                            </div>

                            <span class="notification-total">
                                <?php echo $total_notifications; ?>
                            </span>
                        </div>


                        <div class="notification-list">

                            <?php if ($lost_count > 0): ?>

                                <a href="view_lost.php" class="notification-item">

                                    <div class="notification-icon lost">
                                        !
                                    </div>

                                    <div class="notification-text">
                                        <strong>Lost Item Reports</strong>
                                        <p>
                                            <?php echo $lost_count; ?>
                                            lost report<?php echo $lost_count != 1 ? 's' : ''; ?>
                                            available.
                                        </p>
                                    </div>

                                </a>

                            <?php endif; ?>


                            <?php if ($found_count > 0): ?>

                                <a href="view_found.php" class="notification-item">

                                    <div class="notification-icon found">
                                        ✓
                                    </div>

                                    <div class="notification-text">
                                        <strong>Found Item Reports</strong>
                                        <p>
                                            <?php echo $found_count; ?>
                                            found report<?php echo $found_count != 1 ? 's' : ''; ?>
                                            available.
                                        </p>
                                    </div>

                                </a>

                            <?php endif; ?>


                            <?php if ($total_notifications == 0): ?>

                                <div class="empty-notification">

                                    <div class="empty-icon">
                                        ✓
                                    </div>

                                    <strong>No notifications</strong>

                                    <p>
                                        You're all caught up.
                                    </p>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


                <!-- PROFILE -->
                <div class="dropdown-wrapper">

                    <button
                        type="button"
                        class="profile-button"
                        id="profileButton"
                    >

                        <div class="profile-circle">
                            <?php echo htmlspecialchars($first_letter); ?>
                        </div>

                        <div class="profile-info">
                            <strong>
                                <?php echo htmlspecialchars($fullname); ?>
                            </strong>

                            <span>Administrator</span>
                        </div>

                        <span class="profile-arrow">⌄</span>

                    </button>


                    <!-- PROFILE DROPDOWN -->
                    <div class="dropdown profile-dropdown" id="profileDropdown">

                        <div class="profile-dropdown-header">

                            <div class="large-profile-circle">
                                <?php echo htmlspecialchars($first_letter); ?>
                            </div>

                            <div>
                                <strong>
                                    <?php echo htmlspecialchars($fullname); ?>
                                </strong>

                                <span>Administrator</span>
                            </div>

                        </div>


                        <div class="profile-menu">

                            <a href="admin_profile.php">
                                <span>👤</span>
                                <div>
                                    <strong>View Profile</strong>
                                    <small>Manage your account</small>
                                </div>
                            </a>

                            <a href="logout.php" class="profile-logout">
                                <span>↪</span>
                                <div>
                                    <strong>Log Out</strong>
                                    <small>Sign out of your account</small>
                                </div>
                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </header>


       
        <section class="dashboard-body">

            <div class="page-title">
                <div>
                    <span class="section-label">ADMINISTRATOR ACCOUNT</span>
                    <h2>Dashboard Overview</h2>
                    <p>Monitor and manage student lost and found reports.</p>
                </div>
            </div>


            <div class="stats-grid">

                <a href="view_lost.php" class="stats-card">

                    <div class="stat-card-top">

                        <div class="stat-icon lost-icon">
                            !
                        </div>

                        <span class="stat-arrow">→</span>

                    </div>

                    <div class="stat-content">
                        <span>Lost Item Reports</span>
                        <strong><?php echo $lost_count; ?></strong>
                        <small>Reported lost items</small>
                    </div>

                </a>


                <a href="view_found.php" class="stat-card">

                    <div class="stat-card-top">

                        <div class="stat-icon found-icon">
                            ✓
                        </div>

                        <span class="stat-arrow">→</span>

                    </div>

                    <div class="stat-content">
                        <span>Found Item Reports</span>
                        <strong><?php echo $found_count; ?></strong>
                        <small>Reported found items</small>
                    </div>

                </a>


                <div class="stat-card">

                    <div class="stat-card-top">

                        <div class="stat-icon notification-icon-card">
                            🔔
                        </div>

                    </div>

                    <div class="stat-content">
                        <span>Total Reports</span>
                        <strong><?php echo $total_notifications; ?></strong>
                        <small>Lost and found reports</small>
                    </div>

                </div>

            </div>


            <div class="content-grid">

                <div class="dashboard-panel">

                    <div class="panel-header">
                        <div>
                            <h3>Quick Actions</h3>
                            <p>Quickly access important admin functions.</p>
                        </div>
                    </div>


                    <div class="quick-actions">

                        <a href="lost_item_report.php" class="quick-action">

                            <div class="quick-icon">
                                📄
                            </div>

                            <div>
                                <strong>Lost Reports</strong>
                                <span>View reported lost items</span>
                            </div>

                            <b>→</b>

                        </a>


                        <a href="found_item_report.php" class="quick-action">

                            <div class="quick-icon">
                                🔎
                            </div>

                            <div>
                                <strong>Found Reports</strong>
                                <span>View reported found items</span>
                            </div>

                            <b>→</b>

                        </a>


                        <a href="admin_profile.php" class="quick-action">

                            <div class="quick-icon">
                                👤
                            </div>

                            <div>
                                <strong>My Profile</strong>
                                <span>View your administrator profile</span>
                            </div>

                            <b>→</b>

                        </a>

                    </div>

                </div>


                <div class="dashboard-panel system-panel">

                    <div class="panel-header">
                        <div>
                            <h3>System Status</h3>
                            <p>Current dashboard status.</p>
                        </div>
                    </div>


                    <div class="system-status">

                        <div class="status-row">
                            <span>System</span>
                            <div class="status-value">
                                <i class="status-dot"></i>
                                Operational
                            </div>
                        </div>

                        <div class="status-row">
                            <span>Lost Reports</span>
                            <strong><?php echo $lost_count; ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Found Reports</span>
                            <strong><?php echo $found_count; ?></strong>
                        </div>

                    </div>

                </div>

            </div>

        </section>

    </main>

</div>


<script>
document.addEventListener("DOMContentLoaded", function () {

    const notificationButton = document.getElementById("notificationButton");
    const notificationDropdown = document.getElementById("notificationDropdown");

    const profileButton = document.getElementById("profileButton");
    const profileDropdown = document.getElementById("profileDropdown");


    notificationButton.addEventListener("click", function (event) {

        event.stopPropagation();

        notificationDropdown.classList.toggle("show");

        profileDropdown.classList.remove("show");

    });


    profileButton.addEventListener("click", function (event) {

        event.stopPropagation();

        profileDropdown.classList.toggle("show");

        notificationDropdown.classList.remove("show");

    });


    document.addEventListener("click", function () {

        notificationDropdown.classList.remove("show");
        profileDropdown.classList.remove("show");

    });


    notificationDropdown.addEventListener("click", function (event) {
        event.stopPropagation();
    });


    profileDropdown.addEventListener("click", function (event) {
        event.stopPropagation();
    });

});
</script>

</body>
</html>