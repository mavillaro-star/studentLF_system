```php
<?php
session_start();
include '../../db.php';

function returnBytes(string $val): int {
    $val = trim($val);

    if ($val === '') {
        return 0;
    }

    $last = strtolower($val[strlen($val) - 1]);
    $num = (int)$val;

    switch ($last) {
        case 'g':
            $num *= 1024;
        case 'm':
            $num *= 1024;
        case 'k':
            $num *= 1024;
    }

    return $num;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $fn = trim($_POST['fullname'] ?? '');
    $c = trim($_POST['course'] ?? '');
    $yl = trim($_POST['year_level'] ?? '');
    $cn = trim($_POST['contact_number'] ?? '');
    $e = trim($_POST['email'] ?? '');
    $p_raw = $_POST['password'] ?? '';
    $r = $_POST['role'] ?? '';

    if ($fn === '' || $e === '' || $p_raw === '' || $r === '') {

        $message = "Please fill in all required fields.";

    } elseif (!filter_var($e, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } elseif (!in_array($r, ['student', 'admin'], true)) {

        $message = "Invalid role selected.";

    } else {

        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        if ($check) {

            mysqli_stmt_bind_param($check, 's', $e);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);

            if (mysqli_stmt_num_rows($check) > 0) {

                $message = "The email address \"$e\" is already registered.";

                mysqli_stmt_close($check);

            } else {

                mysqli_stmt_close($check);

                $p = password_hash($p_raw, PASSWORD_DEFAULT);
                $profile_picture_path = null;

                $uploadDir = 'uploads/profile_pics/';
                $appMaxBytes = 2 * 1024 * 1024;

                $iniUploadMax = returnBytes(
                    ini_get('upload_max_filesize')
                );

                $iniPostMax = returnBytes(
                    ini_get('post_max_size')
                );

                if ($iniUploadMax > 0 && $iniUploadMax < $appMaxBytes) {
                    $appMaxBytes = $iniUploadMax;
                }

                if ($iniPostMax > 0 && $iniPostMax < $appMaxBytes) {
                    $appMaxBytes = $iniPostMax;
                }

                $allowedMimes = [
                    'image/png',
                    'image/jpeg',
                    'image/webp'
                ];

                if (!empty($_FILES['profile_picture']['name'])) {

                    $fileError = $_FILES['profile_picture']['error'];

                    if ($fileError === UPLOAD_ERR_OK) {

                        $tmp = $_FILES['profile_picture']['tmp_name'];
                        $size = @filesize($tmp);

                        if ($size !== false && $size <= $appMaxBytes) {

                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime = finfo_file($finfo, $tmp);
                            finfo_close($finfo);

                            $imgInfo = @getimagesize($tmp);

                            if (
                                $imgInfo !== false &&
                                in_array($mime, $allowedMimes, true)
                            ) {

                                if (!is_dir($uploadDir)) {
                                    mkdir($uploadDir, 0755, true);
                                }

                                $ext = strtolower(
                                    pathinfo(
                                        $_FILES['profile_picture']['name'],
                                        PATHINFO_EXTENSION
                                    )
                                );

                                if (
                                    !in_array(
                                        $ext,
                                        ['png', 'jpg', 'jpeg', 'webp'],
                                        true
                                    )
                                ) {

                                    $ext = ($mime === 'image/png')
                                        ? 'png'
                                        : (
                                            $mime === 'image/webp'
                                                ? 'webp'
                                                : 'jpg'
                                        );
                                }

                                $filename =
                                    'user_' .
                                    time() .
                                    '_' .
                                    bin2hex(random_bytes(6)) .
                                    '.' .
                                    $ext;

                                $dest = $uploadDir . $filename;

                                if (move_uploaded_file($tmp, $dest)) {

                                    $profile_picture_path = $dest;

                                } else {

                                    $message =
                                        "Failed to save the uploaded profile picture.";
                                }

                            } else {

                                $message =
                                    "Invalid image file. Please upload PNG, JPG, JPEG, or WEBP.";
                            }

                        } else {

                            $maxMB = round(
                                $appMaxBytes / 1024 / 1024,
                                2
                            );

                            $message =
                                "Profile picture is too large. Maximum size is {$maxMB} MB.";
                        }

                    } else {

                        $message =
                            "There was an error uploading the profile picture.";
                    }
                }

                if ($message === "") {

                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO users
                        (
                            fullname,
                            course,
                            year_level,
                            contact_number,
                            email,
                            password,
                            role,
                            profile_picture
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );

                    if ($stmt) {

                        mysqli_stmt_bind_param(
                            $stmt,
                            'ssssssss',
                            $fn,
                            $c,
                            $yl,
                            $cn,
                            $e,
                            $p,
                            $r,
                            $profile_picture_path
                        );

                        if (mysqli_stmt_execute($stmt)) {

                            $_SESSION['success_message'] =
                                "User account for $fn has been successfully registered.";

                            mysqli_stmt_close($stmt);

                            header("Location: manage_users.php");
                            exit();
                        }

                        if (mysqli_errno($conn) == 1062) {

                            $message =
                                "The email address \"$e\" is already registered.";

                        } else {

                            $message =
                                "Unable to create the user account.";
                        }

                        mysqli_stmt_close($stmt);

                    } else {

                        $message =
                            "Unable to prepare the registration request.";
                    }
                }
            }

        } else {

            $message =
                "Unable to verify the email address.";
        }
    }
}

$fullname = $_SESSION['fullname'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Add New User - Admin Console</title>

<link rel="stylesheet" href="admin.css">

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100%;
}

body {
    background: #f8fafc;
}

.dashboard-wrapper {
    min-height: 100vh;
}

.main-content {
    margin-left: 260px;
    width: calc(100% - 260px);
    min-height: 100vh;
    padding: 0;
}

.form-content {
    width: 100%;
    min-height: 100vh;
    padding: 25px;

    display: flex;
    flex-direction: column;
}

.form-card {
    width: 100%;
    min-height: calc(100vh - 50px);

    margin: 0;
    padding: 35px;

    background: #ffffff;

    border: 1px solid #e5e7eb;
    border-radius: 12px;

    box-shadow: 0 2px 8px rgba(15,23,42,.05);

    display: flex;
    flex-direction: column;
}

.form-title {
    margin-bottom: 30px;
    padding-bottom: 18px;

    border-bottom: 1px solid #e5e7eb;
}

.form-title h2 {
    margin: 0;

    font-size: 21px;

    color: #111827;
}

.form-title p {
    margin: 6px 0 0;

    font-size: 13px;

    color: #8a94a6;
}

.error-notification {
    width: 100%;

    margin-bottom: 18px;

    display: flex;
    align-items: center;

    gap: 12px;

    padding: 13px 15px;

    background: #fef2f2;

    border: 1px solid #fecaca;
    border-left: 4px solid #ef4444;

    border-radius: 9px;

    box-shadow: 0 2px 7px rgba(15,23,42,.05);
}

.error-icon {
    width: 32px;
    height: 32px;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    background: #ef4444;

    color: #ffffff;

    border-radius: 50%;

    font-size: 17px;
    font-weight: 700;
}

.error-content {
    display: flex;
    flex-direction: column;

    gap: 2px;
}

.error-content strong {
    color: #991b1b;

    font-size: 13px;
}

.error-content span {
    color: #4b5563;

    font-size: 12px;

    line-height: 1.5;
}

.form-grid {
    width: 100%;

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 24px 30px;
}

.form-group {
    width: 100%;
}

.form-group label {
    display: block;

    margin-bottom: 7px;

    font-size: 13px;

    font-weight: 600;

    color: #374151;
}

.form-control {
    width: 100%;
    height: 46px;

    padding: 9px 12px;

    border: 1px solid #d1d5db;

    border-radius: 7px;

    background: #ffffff;

    font-size: 13px;

    color: #111827;

    outline: none;
}

.form-control:focus {
    border-color: #3b82f6;

    box-shadow:
        0 0 0 2px rgba(59,130,246,.1);
}

select.form-control {
    cursor: pointer;
}

input[type="file"].form-control {
    padding: 9px 10px;
}

.form-actions {
    margin-top: auto;

    padding-top: 30px;

    border-top: 1px solid #e5e7eb;

    width: 100%;

    display: flex;

    justify-content: space-between;

    align-items: center;
}

.btn-cancel {
    padding: 11px 22px;

    color: #6b7280;

    text-decoration: none;

    font-size: 13px;

    font-weight: 600;

    border: 1px solid #d1d5db;

    border-radius: 7px;

    background: #ffffff;

    display: inline-flex;

    align-items: center;

    justify-content: center;
}

.btn-cancel{
    color: #ffffff;

    background: #e11818;
}
.btn-cancel:hover{
    background: #de3535;
}
.btn-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 24px;
    border: 0;
    border-radius: 7px;
    background: #2563eb;
    color: #ffffff;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.btn-submit:hover {
    background: #1d4ed8;
}

@media (max-width: 1100px) {

    .main-content {
        margin-left: 230px;

        width: calc(100% - 230px);
    }

    .form-content {
        padding: 20px;
    }

    .form-card {
        min-height: calc(100vh - 40px);

        padding: 30px;
    }
}

@media (max-width: 850px) {

    .main-content {
        margin-left: 210px;

        width: calc(100% - 210px);
    }

    .form-content {
        padding: 18px;
    }

    .form-card {
        min-height: calc(100vh - 36px);

        padding: 25px;
    }

    .form-grid {
        gap: 20px;
    }
}

@media (max-width: 700px) {

    .main-content {
        margin-left: 0;

        width: 100%;

        padding: 15px;
    }

    .form-content {
        min-height: auto;

        padding: 0;
    }

    .form-card {
        min-height: auto;

        padding: 22px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-actions {
        margin-top: 30px;

        padding-top: 20px;

        flex-direction: column;

        align-items: stretch;

        gap: 10px;
    }

    .btn-submit,
    .btn-cancel {
        width: 100%;
    }
}

@media (max-width: 480px) {

    .main-content {
        padding: 12px;
    }

    .form-card {
        padding: 17px;

        border-radius: 10px;
    }

    .form-title h2 {
        font-size: 18px;
    }

    .form-title p {
        font-size: 12px;
    }

    .form-actions {
        margin-top: 25px;

        padding-top: 18px;

        flex-direction: column;

        align-items: stretch;

        gap: 10px;
    }

    .btn-submit,
    .btn-cancel {
        width: 100%;

        min-height: 42px;

        text-align: center;

        justify-content: center;
    }

    .error-notification {
        align-items: flex-start;
    }
}

</style>

</head>


<body>

<div class="dashboard-wrapper">

<aside class="sidebar">

    <div class="sidebar-brand">

        <div class="brand-icon">
            SLF
        </div>

        <div>
            <h2>Lost & Found</h2>
            <span>Administrator</span>
        </div>

    </div>


    <nav class="navigation">

        <a
            href="admin_dashboard.php"
            class="nav-item"
        >
            <span class="nav-icon">⌂</span>
            <span>Dashboard</span>
        </a>


        <a
            href="manage_users.php"
            class="nav-item active"
        >
            <span class="nav-icon">👥</span>
            <span>Manage Users</span>
        </a>


        <a
            href="view_lost.php"
            class="nav-item"
        >
            <span class="nav-icon">📄</span>
            <span>Lost Item Reports</span>
        </a>


        <a
            href="view_found.php"
            class="nav-item"
        >
            <span class="nav-icon">🔎</span>
            <span>Found Item Reports</span>
        </a>


        <a
            href="admin_profile.php"
            class="nav-item"
        >
            <span class="nav-icon">👤</span>
            <span>My Profile</span>
        </a>

    </nav>


    <div class="sidebar-bottom">

        <a
            href="logout.php"
            class="logout-link"
        >
            <span>↪</span>
            <span>Log Out</span>
        </a>

    </div>

</aside>

<main class="main-content">

    <div class="form-content">


        <?php if (!empty($message)): ?>

        <div class="error-notification">

            <div class="error-icon">
                !
            </div>

            <div class="error-content">

                <strong>
                    Registration Failed
                </strong>

                <span>
                    <?php echo htmlspecialchars($message); ?>
                </span>

            </div>

        </div>

        <?php endif; ?>


        <div class="form-card">


            <div class="form-title">

                <h2>
                    Add New User
                </h2>

                <p>
                    Provide the required information to create a new user account.
                </p>

            </div>


            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <div class="form-grid">


                    <div class="form-group">

                        <label for="fullname">
                            Full Name *
                        </label>

                        <input
                            type="text"
                            id="fullname"
                            name="fullname"
                            class="form-control"
                            value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="email">
                            Email *
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required
                            autocomplete="off"
                        >

                    </div>


                    <div class="form-group">

                        <label for="password">
                            Password *
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            required
                            autocomplete="new-password"
                        >

                    </div>


                    <div class="form-group">

                        <label for="role">
                            Role *
                        </label>

                        <select
                            id="role"
                            name="role"
                            class="form-control"
                            required
                        >

                            <option value="">
                            </option>

                            <option
                                value="student"
                                <?php echo ($_POST['role'] ?? '') === 'student' ? 'selected' : ''; ?>
                            >
                                Student
                            </option>

                            <option
                                value="admin"
                                <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>
                            >
                                Admin
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="course">
                            Course
                        </label>

                        <select
                            id="course"
                            name="course"
                            class="form-control"
                        >

                            <option value="">
                            </option>

                            <option
                                value="BEED"
                                <?php echo ($_POST['course'] ?? '') === 'BEED' ? 'selected' : ''; ?>
                            >
                                BEED
                            </option>

                            <option
                                value="BSMathematics"
                                <?php echo ($_POST['course'] ?? '') === 'BSMathematics' ? 'selected' : ''; ?>
                            >
                                BS Mathematics
                            </option>

                            <option
                                value="BSSocial Studies"
                                <?php echo ($_POST['course'] ?? '') === 'BSSocial Studies' ? 'selected' : ''; ?>
                            >
                                BS Social Studies
                            </option>

                            <option
                                value="BSIT"
                                <?php echo ($_POST['course'] ?? '') === 'BSIT' ? 'selected' : ''; ?>
                            >
                                BSIT
                            </option>

                            <option
                                value="BSEntrep"
                                <?php echo ($_POST['course'] ?? '') === 'BSEntrep' ? 'selected' : ''; ?>
                            >
                                BS Entrep
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="year_level">
                            Year Level
                        </label>

                        <select
                            id="year_level"
                            name="year_level"
                            class="form-control"
                        >

                            <option value="">
                                Select year level
                            </option>

                            <option
                                value="1st Yr"
                                <?php echo ($_POST['year_level'] ?? '') === '1st Yr' ? 'selected' : ''; ?>
                            >
                                1st Yr
                            </option>

                            <option
                                value="2nd Yr"
                                <?php echo ($_POST['year_level'] ?? '') === '2nd Yr' ? 'selected' : ''; ?>
                            >
                                2nd Yr
                            </option>

                            <option
                                value="3rd Yr"
                                <?php echo ($_POST['year_level'] ?? '') === '3rd Yr' ? 'selected' : ''; ?>
                            >
                                3rd Yr
                            </option>

                            <option
                                value="4th Yr"
                                <?php echo ($_POST['year_level'] ?? '') === '4th Yr' ? 'selected' : ''; ?>
                            >
                                4th Yr
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="contact_number">
                            Contact Number
                        </label>

                        <input
                            type="text"
                            id="contact_number"
                            name="contact_number"
                            class="form-control"
                            maxlength="20"
                            value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label for="profile_picture">
                            Profile Picture
                        </label>

                        <input
                            type="file"
                            id="profile_picture"
                            name="profile_picture"
                            class="form-control"
                            accept="image/png,image/jpeg,image/webp"
                        >

                    </div>

                </div>

                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn-submit"
                    >
                        Register User
                    </button>


                    <a
                        href="manage_users.php"
                        class="btn-cancel"
                    >
                        Cancel
                    </a>

                </div>


            </form>


        </div>

    </div>

</main>

</div>

</body>
</html>
```
