<?php
// Secure session settings
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Redirect logged-in users
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/admin_dashboard.php');
        exit();
    }

    if ($_SESSION['role'] === 'student') {
        header('Location: student/student_dashboard.php');
        exit();
    }
}

require_once __DIR__ . '/../db.php';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function returnBytes($value) {
    $value = trim((string) $value);

    if ($value === '') {
        return 0;
    }

    $unit = strtolower(substr($value, -1));
    $number = (float) $value;

    switch ($unit) {
        case 'g':
            $number *= 1024;
        case 'm':
            $number *= 1024;
        case 'k':
            $number *= 1024;
    }

    return (int) $number;
}

$courses = ['BEED', 'BSMathematics', 'BSSocial Studies', 'BSIT', 'BSEntrep'];
$year_levels = ['1st Yr', '2nd Yr', '3rd Yr', '4th Yr'];

$error = '';
$fullname = '';
$course = '';
$year_level = '';
$contact_number = '';
$email = '';

if (empty($_SESSION['register_csrf_token'])) {
    $_SESSION['register_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    $post_max = returnBytes(ini_get('post_max_size'));
    $content_length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

    if ($post_max > 0 && $content_length > $post_max) {
        $error = 'Submitted data is too large. Please use a smaller profile picture.';
    } elseif (
        !is_string($csrf_token) ||
        !hash_equals($_SESSION['register_csrf_token'], $csrf_token)
    ) {
        $error = 'Invalid form request. Please refresh the page and try again.';
    } elseif (
        $fullname === '' ||
        $email === '' ||
        $password === '' ||
        $course === '' ||
        $year_level === ''
    ) {
        $error = 'Please complete all required fields.';
    } elseif (strlen($fullname) > 150) {
        $error = 'Full name must not exceed 150 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($course, $courses, true) || !in_array($year_level, $year_levels, true)) {
        $error = 'Please select a valid program and year level.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (strlen($password) > 4096) {
        $error = 'Password is too long.';
    } elseif (strlen($contact_number) > 20) {
        $error = 'Contact number must not exceed 20 characters.';
    }

    // Check whether the email is already registered
    if ($error === '') {
        $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');

        if (!$check) {
            error_log('Registration email-check query failed.');
            $error = 'Something went wrong. Please try again later.';
        } else {
            $check->bind_param('s', $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = 'This email address is already registered.';
            }

            $check->close();
        }
    }

    $profile_picture = '';
    $uploaded_file = '';

    // Validate and upload profile picture
    if (
        $error === '' &&
        isset($_FILES['profile_picture']) &&
        $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $file = $_FILES['profile_picture'];
        $max_size = 2 * 1024 * 1024;
        $php_upload_max = returnBytes(ini_get('upload_max_filesize'));

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Profile picture upload failed. Please choose the file again.';
        } elseif (
            $file['size'] <= 0 ||
            $file['size'] > $max_size ||
            ($php_upload_max > 0 && $file['size'] > $php_upload_max)
        ) {
            $error = 'Profile picture must not exceed 2 MB.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);

            $allowed_types = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif'
            ];

            $image_info = @getimagesize($file['tmp_name']);

            if (
                !isset($allowed_types[$mime]) ||
                $image_info === false ||
                ($image_info['mime'] ?? '') !== $mime
            ) {
                $error = 'Only JPG, JPEG, PNG, WEBP, and GIF images are allowed.';
            } else {
                $upload_dir = dirname(__DIR__) . '/uploads/profile_pics/';

                if (
                    !is_dir($upload_dir) &&
                    !mkdir($upload_dir, 0755, true) &&
                    !is_dir($upload_dir)
                ) {
                    $error = 'Unable to prepare the profile picture folder.';
                } else {
                    $filename = bin2hex(random_bytes(16)) . '.' . $allowed_types[$mime];
                    $destination = $upload_dir . $filename;

                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $uploaded_file = $destination;
                        $profile_picture = 'uploads/profile_pics/' . $filename;
                    } else {
                        $error = 'Unable to upload profile picture. Please try again.';
                    }
                }
            }
        }
    }

    // Save account to database
    if ($error === '') {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'student';

        $stmt = $conn->prepare(
            'INSERT INTO users (
                fullname, course, year_level, contact_number,
                email, password, role, profile_picture
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        if (!$stmt) {
            error_log('Registration insert statement could not be prepared.');
            $error = 'Registration failed. Please try again later.';
        } else {
            $stmt->bind_param(
                'ssssssss',
                $fullname,
                $course,
                $year_level,
                $contact_number,
                $email,
                $hashed_password,
                $role,
                $profile_picture
            );

            if ($stmt->execute()) {
                $stmt->close();

                // Remove the registration CSRF token
                unset($_SESSION['register_csrf_token']);

                // Set a one-time registration success flag
                $_SESSION['registration_success'] = true;

                // Redirect to login to show the JavaScript popup
                header('Location: login.php');
                exit();
            }

            error_log('Registration insert failed: ' . $stmt->error);
            $error = 'Registration failed. Please check your details and try again.';
            $stmt->close();

            // Remove the uploaded image if registration failed
            if ($uploaded_file !== '' && is_file($uploaded_file)) {
                unlink($uploaded_file);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="strict-origin-when-cross-origin">

    <title>Create Account | Student Lost &amp; Found</title>

    <style>
        :root {
            --navy: #111a2d;
            --blue: #2563eb;
            --blue-dark: #1d4ed8;
            --bg: #f3f6fb;
            --white: #fff;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
            --red: #dc2626;
            --red-bg: #fef2f2;
            --shadow: 0 18px 50px rgba(17, 26, 45, .09);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--text);
            background: var(--bg);
        }

        a {
            color: var(--blue);
            text-decoration: none;
        }

        a:hover {
            color: var(--blue-dark);
        }

        input, select, button {
            font: inherit;
        }

        .auth-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .auth-main {
            flex: 1;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 38px 20px;
        }

        .auth-form-wrapper {
            width: 100%;
            max-width: 620px;
            padding: 34px;
            background: var(--white);
            border: 1px solid rgba(226, 232, 240, .9);
            border-radius: 16px;
            box-shadow: var(--shadow);
        }

        .auth-brand {
            width: fit-content;
            max-width: 100%;
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0 auto 28px;
            color: var(--navy);
        }

        /* .auth-brand:hover {
            color: var(--navy);
        } */

        .brand-logo {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            color: var(--white);
            background: var(--blue);
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .brand-text strong {
            color: var(--blue-dark);
            font-size: 16px;
            font-weight: 750;
            line-height: 1.3;
        }

        .form-heading {
            margin-bottom: 22px;
        }

        .form-heading h1 {
            margin-bottom: 7px;
            color: var(--navy);
            font-size: 26px;
            line-height: 1.3;
        }

        .form-heading p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .form-alert {
            margin-bottom: 18px;
            padding: 12px 14px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            color: var(--red);
            background: var(--red-bg);
            font-size: 12px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }

        .form-group {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group label {
            color: var(--text);
            font-size: 12px;
            font-weight: 700;
        }

        .required {
            color: var(--red);
        }

        .optional {
            color: var(--muted);
            font-size: 11px;
            font-weight: 400;
        }

        .form-group input:not([type="file"]),
        .form-group select {
            width: 100%;
            min-width: 0;
            height: 44px;
            padding: 0 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            outline: none;
            color: var(--text);
            background: var(--white);
            font-size: 12px;
        }

        .form-group input::placeholder {
            color: #94a3b8;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
        }

        .file-wrapper input[type="file"] {
            display: block;
            width: 100%;
            max-width: 100%;
            padding: 10px;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            color: var(--muted);
            background: #fafcff;
            font-size: 11px;
        }

        .upload-help {
            color: var(--muted);
            font-size: 11px;
            line-height: 1.5;
        }

        .auth-submit {
            width: 100%;
            min-height: 46px;
            margin-top: 3px;
            padding: 12px 16px;
            border: 0;
            border-radius: 8px;
            color: var(--white);
            background: var(--blue);
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .auth-submit:hover {
            background: var(--blue-dark);
        }

        .secondary-action {
            margin-top: 21px;
            color: var(--muted);
            font-size: 12px;
            text-align: center;
            line-height: 1.7;
        }

        .secondary-action a {
            font-weight: 700;
        }

        .back-home {
            display: block;
            width: fit-content;
            margin: 14px auto 0;
            color: var(--muted);
            font-size: 12px;
            text-align: center;
        }

        .back-home:hover {
            color: var(--blue);
        }

        .auth-footer {
            width: 100%;
            padding: 17px 15px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            background: var(--white);
            font-size: 11px;
            text-align: center;
        }

        :focus-visible {
            outline: 2px solid var(--blue);
            outline-offset: 3px;
        }

        @media (max-width: 600px) {
            .auth-main {
                padding: 24px 14px;
                align-items: flex-start;
            }

            .auth-form-wrapper {
                padding: 26px 20px;
                border-radius: 13px;
            }

            .auth-brand {
                margin-bottom: 25px;
            }

            .brand-logo {
                width: 44px;
                height: 44px;
                font-size: 16px;
            }

            .brand-text strong {
                font-size: 15px;
            }

            .form-heading h1 {
                font-size: 23px;
            }

            .form-row {
                grid-template-columns: minmax(0, 1fr);
                gap: 16px;
            }
        }

        @media (max-width: 350px) {
            .auth-main {
                padding: 16px 10px;
            }

            .auth-form-wrapper {
                padding: 22px 15px;
            }

            .brand-text strong {
                font-size: 14px;
            }

            .form-heading h1 {
                font-size: 21px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                transition: none !important;
            }
        }
    </style>
    <link rel="stylesheet" href="../responsive.css">
</head>

<body>
    <div class="auth-page">
        <main class="auth-main">
            <section class="auth-form-wrapper">
                <a class="auth-brand">
                    <span class="brand-logo">SLF</span>

                    <span class="brand-text">
                        <strong>Student Lost &amp; Found</strong>
                    </span>
                </a>

                <div class="form-heading">
                    <h1>Create Account</h1>
                    <p>Enter your information to register.</p>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="form-alert" role="alert">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form class="auth-form" method="POST" enctype="multipart/form-data">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION['register_csrf_token']) ?>"
                    >

                    <div class="form-group">
                        <label for="fullname">
                            Full Name <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="fullname"
                            name="fullname"
                            placeholder="Enter your full name"
                            value="<?= e($fullname) ?>"
                            maxlength="150"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">
                            Email Address <span class="required">*</span>
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email address"
                            value="<?= e($email) ?>"
                            maxlength="254"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">
                            Password <span class="required">*</span>
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="At least 8 characters"
                            minlength="8"
                            maxlength="4096"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="course">
                                Program <span class="required">*</span>
                            </label>

                            <select id="course" name="course" required>
                                <option value="">Select program</option>

                                <?php foreach ($courses as $item): ?>
                                    <option
                                        value="<?= e($item) ?>"
                                        <?= $course === $item ? 'selected' : '' ?>
                                    >
                                        <?= e($item) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="year_level">
                                Year Level <span class="required">*</span>
                            </label>

                            <select id="year_level" name="year_level" required>
                                <option value="">Select year level</option>

                                <?php foreach ($year_levels as $year): ?>
                                    <option
                                        value="<?= e($year) ?>"
                                        <?= $year_level === $year ? 'selected' : '' ?>
                                    >
                                        <?= e($year) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="contact_number">
                            Contact Number <span class="optional">(Optional)</span>
                        </label>

                        <input
                            type="tel"
                            id="contact_number"
                            name="contact_number"
                            placeholder="Enter your contact number"
                            value="<?= e($contact_number) ?>"
                            maxlength="20"
                            autocomplete="tel"
                        >
                    </div>

                    <div class="form-group file-wrapper">
                        <label for="profile_picture">
                            Profile Picture <span class="optional">(Optional)</span>
                        </label>

                        <input
                            type="file"
                            id="profile_picture"
                            name="profile_picture"
                            accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                        >

                        <p class="upload-help">
                            JPG, JPEG, PNG, WEBP, or GIF. Maximum file size: 2 MB.
                        </p>
                    </div>

                    <button type="submit" class="auth-submit">
                        Create Account
                    </button>
                </form>

                <div class="secondary-action">
                    Already have an account? <a href="login.php">Sign In</a>
                </div>

                <a href="../index.php" class="back-home"> Back to Home </a>
            </section>
        </main>

        <footer class="auth-footer">
            &copy; 2026 Student Lost &amp; Found System
        </footer>
    </div>
</body>
</html>