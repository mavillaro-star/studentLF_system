<?php
session_start();
include '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    $_SESSION['error_message'] = 'Invalid user ID.';
    header('Location: manage_users.php');
    exit();
}

$stmt = $conn->prepare(
    'SELECT id, fullname, course, year_level, contact_number, email, profile_picture, role
     FROM users WHERE id = ? LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['error_message'] = 'User not found.';
    header('Location: manage_users.php');
    exit();
}

$profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : '';
$relativeFolder = "../../uploads/profile_pics/";
$serverPath = __DIR__ . "/" . $relativeFolder . $profilePic;

if (!empty($profilePic) && file_exists($serverPath)) {
    $currentImgSrc = $relativeFolder . rawurlencode($profilePic) . "?v=" . time();
} else {
    $currentImgSrc = "../../uploads/default.png";
}

$courses = [
    'BEED' => 'BEED',
    'BSMathematics' => 'BS Mathematics',
    'BSSocialStudies' => 'BS Social Studies',
    'BSIT' => 'BSIT',
    'BSEntrep' => 'BS Entrep'
];

$years = [
    '1st Yr' => '1st Yr',
    '2nd Yr' => '2nd Yr',
    '3rd Yr' => '3rd Yr',
    '4th Yr' => '4th Yr'
];


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
    <title>Edit User - Admin Console</title>
    <link rel="stylesheet" href="admin.css">

    <style>
        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            min-height: 100vh;
            padding: 28px 30px;
            display: block;
        }

        .edit-page-header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
        }

        .edit-heading h1 {
            margin: 0;
            color: var(--text);
            font-size: 24px;
            font-weight: 700;
        }

        .edit-heading p {
            margin-top: 5px;
            color: var(--muted);
            font-size: 13px;
        }

        .edit-toolbar {
            margin-bottom: 15px;
        }

        .edit-back {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
            transition: .2s;
        }

        .edit-back:hover {
            color: var(--text);
        }

        .edit-alert {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
            font-weight: 600;
        }

        .edit-alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .edit-alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .edit-form-card {
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .edit-form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }

        .edit-form-header h2 {
            margin: 0;
            color: var(--text);
            font-size: 16px;
            font-weight: 700;
        }

        .edit-user-id {
            color: var(--muted);
            font-size: 11px;
        }

        .edit-form-body {
            padding: 24px;
        }

        .edit-photo-section {
            display: flex;
            align-items: center;
            gap: 18px;
            padding-bottom: 22px;
            margin-bottom: 22px;
            border-bottom: 1px solid #f1f5f9;
        }

        .edit-photo-wrapper {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            background: #f1f5f9;
            border: 2px solid var(--border);
        }

        .edit-photo-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .edit-photo-info {
            display: flex;
            flex-direction: column;
            gap: 7px;
            min-width: 0;
        }

        .edit-photo-title {
            color: var(--text);
            font-size: 13px;
            font-weight: 700;
        }

        .edit-photo-hint {
            color: var(--muted);
            font-size: 11px;
        }

        .edit-file-input {
            margin-top: 2px;
            width: 100%;
            max-width: 330px;
            padding: 8px 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #fff;
            color: var(--text);
            font-size: 12px;
        }

        .edit-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .edit-form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
            min-width: 0;
        }

        .edit-form-group label {
            color: #374151;
            font-size: 12px;
            font-weight: 700;
        }

        .edit-required {
            color: var(--red);
        }

        .edit-hint {
            color: var(--muted);
            font-size: 11px;
            font-weight: 400;
        }

        .edit-control {
            width: 100%;
            height: 42px;
            padding: 0 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            color: var(--text);
            font-size: 13px;
            outline: none;
            transition: .2s;
        }

        .edit-control:hover {
            border-color: #94a3b8;
        }

        .edit-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
        }

        .edit-form-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid var(--border);
        }

        .edit-btn {
            min-height: 38px;
            padding: 0 17px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s;
        }

        .edit-btn-cancel {
            background: #64748b;
            color: #fff;
            border: 1px solid #64748b;
        }

        .edit-btn-cancel:hover {
            background: #475569;
            border-color: #475569;
        }

        .edit-btn-save {
            background: var(--primary);
            color: #fff;
            border: 1px solid var(--primary);
        }

        .edit-btn-save:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        @media (max-width: 1100px) {
            .main-content {
                padding: 24px 22px;
            }

            .edit-form-grid {
                gap: 15px;
            }
        }

        @media (max-width: 850px) {
            .main-content {
                margin-left: 260px;
                width: calc(100% - 260px);
                padding: 22px 18px;
            }

            .edit-page-header {
                align-items: flex-start;
            }

            .edit-heading h1 {
                font-size: 21px;
            }

            .edit-heading p {
                font-size: 12px;
            }

            .edit-form-grid {
                grid-template-columns: 1fr;
            }

            .edit-form-body {
                padding: 20px;
            }
        }

        @media (max-width: 700px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px 16px;
            }

            .edit-page-header {
                margin-bottom: 20px;
            }

            .edit-form-header {
                padding: 18px 20px;
            }

            .edit-form-body {
                padding: 18px;
            }

            .edit-photo-section {
                align-items: flex-start;
            }

            .edit-photo-info {
                flex: 1;
            }

            .edit-file-input {
                max-width: 100%;
            }

            .edit-form-actions {
                flex-wrap: wrap;
            }

            .edit-btn {
                min-width: 100px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 16px 12px;
            }

            .edit-page-header {
                align-items: flex-start;
                gap: 10px;
            }

            .edit-heading h1 {
                font-size: 19px;
            }

            .edit-heading p {
                font-size: 11px;
                line-height: 1.4;
            }

            .edit-toolbar {
                margin-bottom: 12px;
            }

            .edit-back {
                font-size: 12px;
            }

            .edit-form-header {
                padding: 16px;
            }

            .edit-form-header h2 {
                font-size: 14px;
            }

            .edit-user-id {
                font-size: 10px;
            }

            .edit-form-body {
                padding: 16px;
            }

            .edit-photo-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .edit-photo-wrapper {
                width: 78px;
                height: 78px;
            }

            .edit-form-grid {
                gap: 15px;
            }

            .edit-control {
                height: 40px;
                font-size: 12px;
            }

            .edit-form-actions {
                justify-content: stretch;
                gap: 8px;
            }

            .edit-btn {
                flex: 1;
                min-width: 0;
                padding: 0 10px;
            }
        }

        @media (max-width: 360px) {
            .main-content {
                padding: 14px 10px;
            }

            .edit-form-header,
            .edit-form-body {
                padding: 14px;
            }

            .edit-btn {
                font-size: 11px;
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

    <aside class="sidebar">

        <div class="sidebar-brand">
            <div class="brand-icon">SLF</div>

            <div>
                <h2>Lost & Found</h2>
                <span>Administrator</span>
            </div>
        </div>

        <nav class="navigation">

            <a href="admin_dashboard.php" class="nav-item">
                <span class="nav-icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <a href="manage_users.php" class="nav-item active">
                <span class="nav-icon">♙</span>
                <span>Manage Users</span>
            </a>

            <a href="view_lost.php" class="nav-item">
                <span class="nav-icon">▣</span>
                <span>Lost Item Reports</span>

                <?php if ($lost_count > 0): ?>
                    <span class= "nav-count">
                        <?php echo $lost_count; ?>
                    </span>
                <?php endif; ?>
            </a>

            <a href="view_found.php" class="nav-item">
                <span class="nav-icon">⌕</span>
                <span>Found Item Reports</span>
                <?php if ($found_count > 0): ?>
                    <span class="nav-count">
                        <?php echo $found_count; ?>
                    </span>
                <?php endif;?>
            </a>

            <a href="admin_profile.php" class="nav-item">
                <span class="nav-icon">○</span>
                <span>My Profile</span>
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a href="../../authentication/logout.php" class="nav-item"
             onclick="return confirmLogout();">
                <span class="nav-icon">↪</span>
                <span>Log Out</span>
            </a>

        </div>
    </aside>


    <main class="main-content">

        <header class="edit-page-header">

            <div class="edit-heading">
                <h1>Edit User</h1>
                <p>Update account information and profile settings.</p>
            </div>

        </header>

        <?php if (isset($_SESSION['error_message'])): ?>

            <div class="edit-alert edit-alert-error">
                <?php
                echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']);
                ?>
            </div>

        <?php endif; ?>


        <?php if (isset($_SESSION['success_message'])): ?>

            <div class="edit-alert edit-alert-success">
                <?php
                echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']);
                ?>
            </div>

        <?php endif; ?>


        <div class="edit-form-card">

            <div class="edit-form-header">
                <h2>Account Information</h2>

                <span class="edit-user-id">
                    ID #<?php echo htmlspecialchars($user['id']); ?>
                </span>
            </div>


            <div class="edit-form-body">

                <form
                    action="update_user.php"
                    method="POST"
                    enctype="multipart/form-data"
                >

                    <input
                        type="hidden"
                        name="id"
                        value="<?php echo (int)$user['id']; ?>"
                    >


                    <div class="edit-photo-section">

                        <div class="edit-photo-wrapper">

                            <img
                                id="preview"
                                src="<?php echo htmlspecialchars($currentImgSrc); ?>"
                                alt="Current Profile Picture"
                                onerror="this.onerror=null;this.src='../../uploads/default.png';"
                            >

                        </div>

                        <div class="edit-photo-info">

                            <span class="edit-photo-title">
                                Profile Picture
                            </span>

                            <span class="edit-photo-hint">
                                Choose a new image to replace the current profile picture.
                            </span>

                            <input
                                type="file"
                                name="profile_picture"
                                accept="image/jpeg,image/png,image/jpg"
                                class="edit-file-input"
                            >

                        </div>

                    </div>


                    <div class="edit-form-grid">

                        <div class="edit-form-group">

                            <label>
                                Full Name
                                <span class="edit-required">*</span>
                            </label>

                            <input
                                type="text"
                                name="fullname"
                                class="edit-control"
                                value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>"
                                required
                            >

                        </div>


                        <div class="edit-form-group">

                            <label>
                                Email
                                <span class="edit-required">*</span>
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="edit-control"
                                value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                required
                            >

                        </div>


                        <div class="edit-form-group">

                            <label>Program</label>

                            <select
                                name="course"
                                class="edit-control"
                            >

                                <option value="">Select Program</option>

                                <?php foreach ($courses as $val => $label): ?>

                                    <option
                                        value="<?php echo htmlspecialchars($val); ?>"
                                        <?php echo ($user['course'] === $val) ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="edit-form-group">

                            <label>Year Level</label>

                            <select
                                name="year_level"
                                class="edit-control"
                            >

                                <option value="">Select Year Level</option>

                                <?php foreach ($years as $val => $label): ?>

                                    <option
                                        value="<?php echo htmlspecialchars($val); ?>"
                                        <?php echo ($user['year_level'] === $val) ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="edit-form-group">

                            <label>Contact Number</label>

                            <input
                                type="text"
                                name="contact_number"
                                class="edit-control"
                                value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>"
                            >

                        </div>


                        <div class="edit-form-group">

                            <label>
                                Password
                                <span class="edit-hint">
                                    (leave blank to keep current)
                                </span>
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="edit-control"
                                autocomplete="new-password"
                            >

                        </div>


                        <div class="edit-form-group">

                            <label>
                                Role
                                <span class="edit-required">*</span>
                            </label>

                            <select
                                name="role"
                                class="edit-control"
                                required
                            >

                                <option
                                    value="student"
                                    <?php echo ($user['role'] === 'student') ? 'selected' : ''; ?>
                                >
                                    Student
                                </option>

                                <option
                                    value="admin"
                                    <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>
                                >
                                    Admin
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="edit-form-actions">

                        <a
                            href="manage_users.php?id=<?php echo (int)$user['id']; ?>"
                            class="edit-btn edit-btn-cancel"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="edit-btn edit-btn-save"
                        >
                            Save Changes
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </main>

</div>


<script>
const imageInput = document.querySelector('input[name="profile_picture"]');
const imagePreview = document.getElementById('preview');

if (imageInput && imagePreview) {
    imageInput.addEventListener('change', function() {
        const file = this.files && this.files[0];

        if (!file) return;

        if (!file.type.startsWith('image/')) {
            this.value = '';
            return;
        }

        const reader = new FileReader();

        reader.onload = function(event) {
            imagePreview.src = event.target.result;
        };

        reader.readAsDataURL(file);
    });
}
</script>
 <script>
function confirmLogout() {
    return confirm("Are you sure you want to log out?");
}
</script>

</body>
</html>
