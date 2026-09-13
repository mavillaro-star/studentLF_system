<?php
session_start();
include '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    $profile_picture = null;

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/profile_pics/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        $file_name = time() . '_' . basename($_FILES['profile_picture']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($file_tmp, $target_file)) {
            $profile_picture = $file_name;
        }
    }

    if ($profile_picture && $password) {
        $stmt = mysqli_prepare($conn, "UPDATE users SET fullname=?, contact_number=?, email=?, password=?, profile_picture=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssssi", $fullname, $contact_number, $email, $password, $profile_picture, $user_id);
    } elseif ($profile_picture) {
        $stmt = mysqli_prepare($conn, "UPDATE users SET fullname=?, contact_number=?, email=?, profile_picture=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssssi", $fullname, $contact_number, $email, $profile_picture, $user_id);
    } elseif ($password) {
        $stmt = mysqli_prepare($conn, "UPDATE users SET fullname=?, contact_number=?, email=?, password=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssssi", $fullname, $contact_number, $email, $password, $user_id);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET fullname=?, contact_number=?, email=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssi", $fullname, $contact_number, $email, $user_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        header("Location: admin_profile.php?success=profile_updated");
        exit(); 
    } else {
        $error_msg = "Error updating profile: " . mysqli_error($conn);
    }
}

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

$admin_name = $_SESSION['fullname'] ?? $user['fullname'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Admin Console</title>
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

        /* Outer Container */
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

        .logout {
            margin-top: auto;
            border-top: 1px solid #26334d;
            padding-top: 16px;
        }

        /* Main Content Layout */
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
            background-color: #ef4444;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            overflow: hidden;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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

        /* Form Area */
        .content-display {
            width: 100%;
            border-radius: 16px;
            background-color: #f7fafc;
            padding: 24px;
            border: 1px solid #edf2f7;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid #fca5a5;
        }

        .form-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e5e7eb;
        }

        .profile-preview-box {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #f3f4f6;
        }

        .preview-img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e5e7eb;
        }

        .placeholder-img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background-color: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            font-size: 12px;
            font-weight: 700;
            color: #374151;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 13px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background-color: #ffffff;
            color: #111827;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        input[type="file"].form-control {
            padding: 7px 12px;
        }

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #f3f4f6;
        }

        .btn-submit {
            background-color: #3b82f6;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .btn-submit:hover {
            background-color: #2563eb;
        }

        .btn-cancel {
            color: #6b7280;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .btn-cancel:hover {
            color: #111827;
        }

        @media screen and (max-width: 850px) {
            .dashboard-container { flex-direction: column; }
            .sidebar { width: 100%; }
            .form-grid { grid-template-columns: 1fr; }
            .form-group.full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <aside class="sidebar">
        <a href="admin_dashboard.php" style="text-decoration: none;">
            <div class="logo-box">
                <svg class="logo-icon" viewBox="0 0 24 24">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                </svg>
            </div>
        </a>

        <div class="brand-title">Admin Console</div>

        <ul class="nav-menu">
            <li>
                <a href="admin_dashboard.php" class="nav-item">
                    <span class="icon">⚙️</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="manage_users.php" class="nav-item">
                    <span class="icon">👥</span>
                    <span>Manage Users</span>
                </a>
            </li>
            <li>
                <a href="view_reports.php" class="nav-item">
                    <span class="icon">📋</span>
                    <span>View Reports</span>
                </a>
            </li>
            <li>
                <a href="admin_profile.php" class="nav-item active">
                    <span class="icon">👤</span>
                    <span>My Profile</span>
                </a>
            </li>
        </ul>

        <div class="logout">
            <a href="ad_logout_con.php" class="nav-item" style="color: #ff6b6b;">
                <span class="icon">↪</span>
                <span>Log Out</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="header">
            <h1 class="page-title">Edit Profile</h1>
            <div class="user-profile">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($admin_name); ?></span>
                    <span class="user-role">Administrator</span>
                </div>
                <div class="avatar">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="../../uploads/profile_pics/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <section class="content-display">
            <?php if (isset($error_msg)): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <div class="form-card">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">

                    <div class="profile-preview-box">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="../../uploads/profile_pics/<?php echo htmlspecialchars($user['profile_picture']); ?>" class="preview-img" alt="Current Profile Picture">
                        <?php else: ?>
                            <div class="placeholder-img">No Image</div>
                        <?php endif; ?>
                        <div>
                            <span style="font-size: 13px; font-weight: 700; color: #111827;">Current Profile Picture</span>
                            <p style="font-size: 11px; color: #6b7280; margin-top: 2px;">Upload a new image file below to replace this image.</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="fullname">Full Name *</label>
                            <input type="text" id="fullname" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="contact_number">Contact Number</label>
                            <input type="text" id="contact_number" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($user['contact_number']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current" autocomplete="new-password">
                        </div>

                        <div class="form-group full-width">
                            <label for="profile_picture">Upload New Profile Picture</label>
                            <input type="file" id="profile_picture" name="profile_picture" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="admin_profile.php" class="btn-cancel">← Cancel</a>
                        <button type="submit" class="btn-submit">Save Changes</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>

</body>
</html>