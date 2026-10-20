<?php
session_start();
include '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    $profile_picture = null;

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/profile_pics/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        $original_name = basename($_FILES['profile_picture']['name']);
        $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($extension, $allowed_extensions, true) && getimagesize($file_tmp) !== false && $_FILES['profile_picture']['size'] <= 2 * 1024 * 1024) {
            $file_name = uniqid('admin_', true) . '.' . $extension;
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($file_tmp, $target_file)) $profile_picture = $file_name;
        } else {
            $error_msg = "Invalid image. Use JPG, JPEG, PNG, or WEBP up to 2 MB.";
        }
    } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        $error_msg = "Unable to upload the profile picture.";
    }

    if (!isset($error_msg)) {
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
            $_SESSION['fullname'] = $fullname;
            mysqli_stmt_close($stmt);
            header("Location: admin_profile.php?success=profile_updated");
            exit();
        } else {
            $error_msg = "Error updating profile.";
            mysqli_stmt_close($stmt);
        }
    }
}

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
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

$admin_name = $_SESSION['fullname'] ?? $user['fullname'] ?? 'Admin';
$admin_initial = strtoupper(substr(trim($admin_name), 0, 1));
$profile_picture = !empty($user['profile_picture']) ? $user['profile_picture'] : '';
$profile_path = __DIR__ . '/../../uploads/profile_pics/' . $profile_picture;

if ($profile_picture && file_exists($profile_path)) {
    $profile_image = '../../uploads/profile_pics/' . rawurlencode($profile_picture) . '?v=' . time();
} else {
    $profile_image = '../../uploads/default.png';
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
    <title>Edit Profile - Admin Console</title>
    <link rel="stylesheet" href="admin.css">

    <style>
        .main-content {
            margin-left: 260px; width: calc(100% - 260px); min-height: 100vh;
            padding: 28px 30px; display: block;
        }

        .profile-page-header {
            display: flex; justify-content: space-between; align-items: center;
            gap: 20px; margin-bottom: 24px;
        }

        .profile-heading h1 {
            margin: 0; color: var(--text); font-size: 24px; font-weight: 700;
        }

        .profile-heading p {
            margin-top: 5px; color: var(--muted); font-size: 13px;
        }

        .profile-page-body { width: 100%; }

        .profile-alert {
            padding: 12px 15px; margin-bottom: 15px; border-radius: 8px;
            background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;
            font-size: 13px; font-weight: 600;
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

        .profile-preview-box {
            display: flex; align-items: center; gap: 16px; padding-bottom: 20px;
            margin-bottom: 22px; border-bottom: 1px solid var(--border);
        }

        .preview-img, .placeholder-img {
            width: 76px; height: 76px; border-radius: 50%; flex-shrink: 0;
        }

        .preview-img { object-fit: cover; border: 2px solid var(--border); }

        .placeholder-img {
            display: flex; align-items: center; justify-content: center;
            background: #f1f5f9; color: #64748b; font-size: 11px; font-weight: 600;
        }

        .preview-title { color: var(--text); font-size: 13px; font-weight: 700; }

        .preview-description {
            margin-top: 5px; color: var(--muted); font-size: 12px; line-height: 1.5;
        }

        .form-grid {
            display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px;
        }

        .form-group {
            display: flex; flex-direction: column; gap: 7px; min-width: 0;
        }

        .form-group.full-width { grid-column: 1 / -1; }

        .form-group label { color: var(--text); font-size: 12px; font-weight: 700; }

        .form-control {
            width: 100%; min-height: 42px; padding: 10px 12px;
            border: 1px solid var(--border); border-radius: 8px;
            background: var(--surface); color: var(--text); font-family: inherit;
            font-size: 13px; outline: none; transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus {
            border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
        }

        input[type="file"].form-control { padding: 8px; }

        .form-hint { color: var(--muted); font-size: 11px; line-height: 1.5; }

        .form-actions {
            display: flex; justify-content: space-between; align-items: center;
            gap: 12px; margin-top: 25px; padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .btn-cancel, .btn-submit {
            min-height: 39px; padding: 0 17px; border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            font-family: inherit; font-size: 12px; font-weight: 600;
            text-decoration: none; cursor: pointer; transition: .2s ease;
        }

        .btn-cancel {
            color: var(--muted); background: transparent; border: 1px solid var(--border);
        }

        .btn-cancel:hover { color: var(--text); background: #f8fafc; }

        .btn-submit {
            color: #fff; background: var(--primary); border: 1px solid var(--primary);
        }

        .btn-submit:hover { background: var(--primary-dark); }

        @media (max-width: 1000px) {
            .main-content { padding: 24px 20px; }
        }

        @media (max-width: 760px) {
            .main-content { margin-left: 0; width: 100%; padding: 20px 16px; }
            .profile-page-header { align-items: flex-start; }
            .profile-heading h1 { font-size: 20px; }
            .profile-heading p { font-size: 12px; }
            .profile-card-body { padding: 20px; }
        }

        @media (max-width: 560px) {
            .form-grid { grid-template-columns: 1fr; gap: 17px; }
            .form-group.full-width { grid-column: auto; }
            .profile-card-header { padding: 17px 18px; }
            .profile-card-body { padding: 18px; }
            .profile-preview-box { align-items: flex-start; }
            .form-actions { flex-direction: column-reverse; align-items: stretch; }
            .btn-cancel, .btn-submit { width: 100%; min-height: 42px; }
        }

        @media (max-width: 400px) {
            .main-content { padding: 16px 12px; }
            .profile-card-header h2 { font-size: 14px; }
            .profile-card-body { padding: 15px; }
            .preview-img, .placeholder-img { width: 62px; height: 62px; }
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
                <h1>Edit Profile</h1>
                <p>Update your administrator account information.</p>
            </div>
        </header>

        <section class="profile-page-body">
            <?php if (isset($error_msg)): ?>
                <div class="profile-alert"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <div class="profile-card">
                <div class="profile-card-header">
                    <h2>Edit Account Information</h2>
                    <span class="profile-card-id">ID<?php echo htmlspecialchars($user['id']); ?></span>
                </div>

                <div class="profile-card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="profile-preview-box">
                            <?php if ($profile_picture && file_exists($profile_path)): ?>
                                <img src="<?php echo htmlspecialchars($profile_image); ?>" class="preview-img" alt="Current Profile Picture">
                            <?php else: ?>
                                <div class="placeholder-img">No Image</div>
                            <?php endif; ?>

                            <div>
                                <span class="preview-title">Current Profile Picture</span>
                                <p class="preview-description">Upload a new image below if you want to replace your current profile picture.</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="fullname">Full Name *</label>
                                <input type="text" id="fullname" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="contact_number">Contact Number</label>
                                <input type="text" id="contact_number" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="password">New Password</label>
                                <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current" autocomplete="new-password">
                                <span class="form-hint">Leave this field blank if you do not want to change your password.</span>
                            </div>

                            <div class="form-group full-width">
                                <label for="profile_picture">Upload New Profile Picture</label>
                                <input type="file" id="profile_picture" name="profile_picture" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                                <span class="form-hint">Accepted formats: JPG, JPEG, PNG, WEBP. Maximum file size: 2 MB.</span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="admin_profile.php" class="btn-cancel">Cancel</a>
                            <button type="submit" class="btn-submit">Save Changes</button>
                        </div>
                    </form>
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