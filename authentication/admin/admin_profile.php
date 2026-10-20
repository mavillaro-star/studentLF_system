<?php
session_start();
include '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$user_id = $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT id, fullname, contact_number, email, profile_picture, role FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    $_SESSION['error_message'] = "Admin profile not found.";
    header("Location: admin_dashboard.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? $user['fullname'] ?? 'Admin';
$adminInitial = strtoupper(substr(trim($fullname), 0, 1));
$profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : '';
$relativeFolder = "../../uploads/profile_pics/";
$serverPath = __DIR__ . "/" . $relativeFolder . $profilePic;

if (!empty($profilePic) && file_exists($serverPath)) {
    $profileImage = $relativeFolder . rawurlencode($profilePic) . "?v=" . time();
} else {
    $profileImage = "../../uploads/default.png";
}

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
    <title>My Profile - Admin Console</title>
    <link rel="stylesheet" href="admin.css">

    <style>
        .main-content {
            margin-left: 260px; width: calc(100% - 260px); min-height: 100vh;
            padding: 28px 30px; display: block;
        }

        .profile-page-header {
            width: 100%; display: flex; justify-content: space-between;
            align-items: center; gap: 20px; margin-bottom: 24px;
        }

        .profile-heading h1 {
            margin: 0; color: var(--text); font-size: 24px; font-weight: 700;
        }

        .profile-heading p {
            margin-top: 5px; color: var(--muted); font-size: 13px;
        }

        .profile-page-body { width: 100%; }

        .profile-alert {
            width: 100%; padding: 12px 15px; margin-bottom: 15px;
            border-radius: 8px; background: #dcfce7; color: #166534;
            border: 1px solid #bbf7d0; font-size: 13px; font-weight: 600;
        }

        .profile-card {
            width: 100%; background: var(--surface); border: 1px solid var(--border);
            border-radius: 14px; box-shadow: var(--shadow); overflow: hidden;
        }

        .profile-card-header {
            display: flex; justify-content: space-between; align-items: center;
            gap: 15px; padding: 20px 24px; border-bottom: 1px solid var(--border);
        }

        .profile-card-header h2 {
            margin: 0; color: var(--text); font-size: 16px; font-weight: 700;
        }

        .profile-card-id { color: var(--muted); font-size: 11px; }
        .profile-card-body { padding: 24px; }

        .profile-identity {
            display: flex; align-items: center; gap: 20px; padding-bottom: 24px;
            margin-bottom: 8px; border-bottom: 1px solid #f1f5f9;
        }

        .profile-image-wrapper {
            width: 95px; height: 95px; flex-shrink: 0; border-radius: 50%;
            overflow: hidden; border: 2px solid var(--border); background: #f1f5f9;
        }

        .profile-image-wrapper img {
            width: 100%; height: 100%; object-fit: cover; display: block;
        }

        .profile-identity-info { min-width: 0; }

        .profile-identity-info h3 {
            margin: 0; color: var(--text); font-size: 21px; font-weight: 700;
            line-height: 1.35; word-break: break-word;
        }

        .profile-identity-info p {
            margin-top: 5px; color: var(--muted); font-size: 13px; word-break: break-word;
        }

        .profile-role {
            display: inline-flex; align-items: center; padding: 5px 11px;
            margin-top: 10px; border-radius: 20px; background: #fee2e2;
            color: #b91c1c; font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .4px;
        }

        .profile-details { display: flex; flex-direction: column; }

        .profile-detail-row {
            display: grid; grid-template-columns: 190px minmax(0, 1fr);
            align-items: center; min-height: 56px; border-bottom: 1px solid #f1f5f9;
        }

        .profile-detail-row:last-child { border-bottom: none; }

        .profile-detail-label {
            color: var(--muted); font-size: 12px; font-weight: 600;
        }

        .profile-detail-value {
            color: var(--text); font-size: 13px; font-weight: 500; word-break: break-word;
        }

        .profile-detail-value strong { font-weight: 700; }

        .profile-actions {
            display: flex; align-items: center; justify-content: space-between;
            gap: 15px; padding: 18px 24px; border-top: 1px solid var(--border);
            background: #f8fafc;
        }

        .btn-back {
            color: var(--muted); text-decoration: none; font-size: 12px;
            font-weight: 600; transition: .2s;
        }

        .btn-back:hover { color: var(--text); }

        .action-group {
            display: flex; align-items: center; gap: 10px; margin-left: auto;
        }

        .btn-edit, .btn-delete {
            min-height: 38px; padding: 0 17px; border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            text-decoration: none; font-size: 12px; font-weight: 600; transition: .2s ease;
        }

        .btn-edit { background: var(--primary); color: #fff; border: 1px solid var(--primary); }

        .btn-edit:hover {
            background: var(--primary-dark); border-color: var(--primary-dark);
        }

        .btn-delete { background: #ef4444; color: #fff; border: 1px solid #ef4444; }

        .btn-delete:hover { background: #dc2626; border-color: #dc2626; }

        @media (max-width: 1000px) {
            .main-content { padding: 24px 20px; }
            .profile-detail-row { grid-template-columns: 150px minmax(0, 1fr); }
        }

        @media (max-width: 760px) {
            .main-content { margin-left: 0; width: 100%; padding: 20px 16px; }
            .profile-page-header { align-items: flex-start; }
            .profile-heading h1 { font-size: 20px; }
            .profile-heading p { font-size: 12px; }
            .profile-card-body { padding: 20px; }
            .profile-identity { align-items: flex-start; }
            .profile-actions { flex-wrap: wrap; }
        }

        @media (max-width: 560px) {
            .profile-identity {
                flex-direction: column; align-items: center; text-align: center;
            }

            .profile-identity-info { width: 100%; }

            .profile-detail-row {
                grid-template-columns: 1fr; gap: 5px; padding: 13px 0; min-height: auto;
            }

            .profile-actions { flex-direction: column; align-items: stretch; }
            .btn-back { text-align: left; }

            .action-group {
                width: 100%; margin-left: 0; display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .btn-edit, .btn-delete { width: 100%; }
        }

        @media (max-width: 400px) {
            .main-content { padding: 16px 12px; }
            .profile-card-header { padding: 16px; }
            .profile-card-body { padding: 16px; }
            .profile-card-header h2 { font-size: 14px; }
            .profile-identity-info h3 { font-size: 18px; }
            .profile-actions { padding: 15px 16px; }
            .action-group { grid-template-columns: 1fr; }
        }
    </style>
    <link rel="stylesheet" href="../../responsive.css">
</head>

<body class="admin-sidebar-page">
<input type="checkbox" id="admin-menu-toggle" aria-label="Toggle navigation menu">
<label class="mobile-sidebar-toggle" for="admin-menu-toggle" aria-label="Toggle navigation menu">☰</label>
<label class="mobile-sidebar-overlay" for="admin-menu-toggle" aria-hidden="true"></label>
<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">SLF</div>
            <div>
                <h2>Lost &amp; Found</h2>
                <span>Administrator</span>
            </div>
        </div>

        <nav class="navigation">
            <a href="admin_dashboard.php" class="nav-item">
                <span class="nav-icon">⌂</span><span>Dashboard</span>
            </a>

            <a href="manage_users.php" class="nav-item">
                <span class="nav-icon">♙</span><span>Manage Users</span>
            </a>

            <a href="view_lost.php" class="nav-item">
                <span class="nav-icon">▣</span><span>Lost Item Reports</span>
                <?php if ($lost_count > 0): ?>
                    <span class="nav-count"><?php echo $lost_count; ?></span>
                <?php endif; ?>
            </a>

            <a href="view_found.php" class="nav-item">
                <span class="nav-icon">⌕</span><span>Found Item Reports</span>
                <?php if ($found_count > 0): ?>
                    <span class="nav-count"><?php echo $found_count; ?></span>
                <?php endif; ?>
            </a>

            <a href="admin_profile.php" class="nav-item active">
                <span class="nav-icon">○</span><span>My Profile</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <a href="../../authentication/logout.php" class="nav-item" onclick="return confirmLogout();">
                <span class="nav-icon">↪</span><span>Log Out</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="profile-page-header">
            <div class="profile-heading">
                <h1>My Profile</h1>
                <p>View your administrator account information.</p>
            </div>
        </header>

        <section class="profile-page-body">
            <?php if (isset($_GET['success']) && $_GET['success'] === 'profile_updated'): ?>
                <div class="profile-alert">Profile successfully updated!</div>
            <?php endif; ?>

            <div class="profile-card">
                <div class="profile-card-header">
                    <h2>Account Information</h2>
                    <span class="profile-card-id">ID <?php echo htmlspecialchars($user['id']); ?></span>
                </div>

                <div class="profile-card-body">
                    <div class="profile-identity">
                        <div class="profile-image-wrapper">
                            <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Admin Profile Picture" onerror="this.onerror=null;this.src='../../uploads/default.png';">
                        </div>

                        <div class="profile-identity-info">
                            <h3><?php echo htmlspecialchars($user['fullname']); ?></h3>
                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                            <span class="profile-role"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                        </div>
                    </div>

                    <div class="profile-details">
                        <div class="profile-detail-row">
                            <span class="profile-detail-label">Full Name</span>
                            <span class="profile-detail-value"><strong><?php echo htmlspecialchars($user['fullname']); ?></strong></span>
                        </div>

                        <div class="profile-detail-row">
                            <span class="profile-detail-label">Contact Number</span>
                            <span class="profile-detail-value"><?php echo !empty($user['contact_number']) ? htmlspecialchars($user['contact_number']) : 'No contact number'; ?></span>
                        </div>

                        <div class="profile-detail-row">
                            <span class="profile-detail-label">Email Address</span>
                            <span class="profile-detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>

                        <div class="profile-detail-row">
                            <span class="profile-detail-label">System Role</span>
                            <span class="profile-detail-value"><span class="profile-role"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span></span>
                        </div>
                    </div>
                </div>

                <div class="profile-actions">
                    <a href="admin_dashboard.php" class="btn-back">Back to Dashboard</a>

                    <div class="action-group">
                        <a href="admin_edit_profile.php" class="btn-edit">Edit Profile</a>
                        <a href="delete_admin_profile.php" class="btn-delete" onclick="return confirm('Are you sure you want to delete your admin account? This action cannot be undone.');">Delete Account</a>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<script>
   
    function confirmLogout() {
        return confirm("Are you sure you want to log out?");
    }
</script>
</body>
</html>