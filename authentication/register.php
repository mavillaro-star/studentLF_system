<?php
// --- Secure session settings ---
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

session_start();

// --- Cache control headers ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// --- Security headers ---
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");

// --- Prevent access to registration if already logged in ---
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/admin_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'student') {
        header("Location: student/student_dashboard.php");
        exit();
    }
}

include '../db.php'; // adjust path if needed

// helper: convert php.ini size string (e.g., "2M") to bytes
function returnBytes(string $val): int {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $num = (int) $val;
    switch($last) {
        case 'g': $num *= 1024;
        case 'm': $num *= 1024;
        case 'k': $num *= 1024;
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
    $r = "student";

    if ($fn === '' || $e === '' || $p_raw === '') {
        $message = "Please fill required fields!";
    } else {
        // check duplicate email
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($check, 's', $e);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $message = "Email already exists!";
            mysqli_stmt_close($check);
        } else {
            mysqli_stmt_close($check);

            // hash password
            $p = password_hash($p_raw, PASSWORD_DEFAULT);

            // --- UPLOAD VALIDATION START ---
            $profile_picture_path = null;
            $uploadDir = 'uploads/profile_pics/';
            $appMaxBytes = 2 * 1024 * 1024; // 2 MB limit

            $iniUploadMax = returnBytes(ini_get('upload_max_filesize'));
            $iniPostMax = returnBytes(ini_get('post_max_size'));
            if ($iniUploadMax > 0 && $iniUploadMax < $appMaxBytes) $appMaxBytes = $iniUploadMax;
            if ($iniPostMax > 0 && $iniPostMax < $appMaxBytes) $appMaxBytes = $iniPostMax;

            $allowedMimes = ['image/png','image/jpeg','image/webp','image/gif'];

            if (!empty($_FILES['profile_picture']['name'])) {
                $fileError = $_FILES['profile_picture']['error'];
                if ($fileError !== UPLOAD_ERR_OK) {
                    $message = "File upload error code: " . $fileError;
                } else {
                    $tmp = $_FILES['profile_picture']['tmp_name'];
                    $size = @filesize($tmp);

                    if ($size === false || $size > $appMaxBytes) {
                        $message = "File too large. Max " . ($appMaxBytes / 1024 / 1024) . " MB.";
                    } else {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_file($finfo, $tmp);
                        finfo_close($finfo);

                        $imgInfo = @getimagesize($tmp);
                        if ($imgInfo === false || !in_array($mime, $allowedMimes, true)) {
                            $message = "Invalid file type. Allowed: PNG, JPG, JPEG, WEBP, GIF.";
                        } else {
                            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                            if ($message === "") {
                                $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                                if (!in_array($ext, ['png','jpg','jpeg','webp','gif'], true)) {
                                    if ($mime === 'image/png') $ext = 'png';
                                    elseif ($mime === 'image/webp') $ext = 'webp';
                                    elseif ($mime === 'image/gif') $ext = 'gif';
                                    else $ext = 'jpg';
                                }
                                $filename = 'user_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                                $dest = $uploadDir . $filename;
                                if (move_uploaded_file($tmp, $dest)) {
                                    $profile_picture_path = $dest;
                                } else {
                                    $message = "Failed to save uploaded file.";
                                }
                            }
                        }
                    }
                }
            }
            

            if ($message === "") {
                $stmt = mysqli_prepare($conn, "INSERT INTO users (fullname, course, year_level, contact_number, email, password, role, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'ssssssss', $fn, $c, $yl, $cn, $e, $p, $r, $profile_picture_path);
                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_close($stmt);
                        header("Location: login.php");
                        exit();
                    } else {
                        if ($profile_picture_path && file_exists($profile_picture_path)) @unlink($profile_picture_path);
                        $message = "Registration Failed! " . mysqli_stmt_error($stmt);
                        mysqli_stmt_close($stmt);
                    }
                } else {
                    if ($profile_picture_path && file_exists($profile_picture_path)) @unlink($profile_picture_path);
                    $message = "Prepare failed: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Create a student account for the Student Lost and Found Management System.">

    <meta name="theme-color"
          content="#0f172a">

    <title>Register | Student Lost & Found</title>

    <link rel="stylesheet" href="auth.css">

</head>

<body>

<div class="auth-page register-page">

    <div class="auth-layout">

        <!-- LEFT SIDE -->

        <section class="auth-intro">

            <a href="../index.php"
               class="auth-brand">

                <div class="brand-logo">

                    <svg viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2"
                         stroke-linecap="round"
                         stroke-linejoin="round">

                        <circle cx="10.5"
                                cy="10.5"
                                r="7">
                        </circle>

                        <path d="M16 16l5 5"></path>

                        <path d="M10.5 7.5v3l2 2"></path>

                    </svg>

                </div>

                <div>

                    <strong>
                        STUDENT L&amp;F
                    </strong>

                    <span>
                        Lost &amp; Found System
                    </span>

                </div>

            </a>


            <div class="intro-content">

                <span class="intro-label">
                    JOIN THE SYSTEM
                </span>

                <h1>
                    Create your
                    <span>student account.</span>
                </h1>

                <p>
                    Register once and gain access to an organized
                    platform for reporting and managing lost and
                    found belongings.
                </p>


                <div class="intro-features">

                    <div class="intro-feature">

                        <div class="feature-check">
                            ✓
                        </div>

                        <div>

                            <strong>
                                Simple Registration
                            </strong>

                            <span>
                                Create your account in minutes.
                            </span>

                        </div>

                    </div>


                    <div class="intro-feature">

                        <div class="feature-check">
                            ✓
                        </div>

                        <div>

                            <strong>
                                Secure Account
                            </strong>

                            <span>
                                Your credentials stay protected.
                            </span>

                        </div>

                    </div>


                    <div class="intro-feature">

                        <div class="feature-check">
                            ✓
                        </div>

                        <div>

                            <strong>
                                Report &amp; Reconnect
                            </strong>

                            <span>
                                Help return belongings to owners.
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <div class="intro-footer">
                © 2026 Student &amp; Found Management System
            </div>

        </section>


        <!-- FORM SIDE -->

        <section class="auth-form-area">

            <div class="auth-form-wrapper register-wrapper">


                <div class="mobile-brand">

                    <a href="../index.php"
                       class="auth-brand">

                        <div class="brand-logo">

                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">

                                <circle cx="10.5"
                                        cy="10.5"
                                        r="7">
                                </circle>

                                <path d="M16 16l5 5"></path>

                                <path d="M10.5 7.5v3l2 2"></path>

                            </svg>

                        </div>

                        <div>

                            <strong>
                                STUDENT L&amp;F
                            </strong>

                            <span>
                                Lost &amp; Found System
                            </span>

                        </div>

                    </a>

                </div>


                <div class="form-heading">

                    <span class="form-eyebrow">
                        STUDENT REGISTRATION
                    </span>

                    <h2>
                        Create your account
                    </h2>

                    <p>
                        Fill in your information to get started.
                    </p>

                </div>


                <?php if (!empty($message)): ?>

                    <div class="form-alert">

                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <circle cx="12"
                                    cy="12"
                                    r="9">
                            </circle>

                            <line x1="12"
                                  y1="8"
                                  x2="12"
                                  y2="12">
                            </line>

                            <circle cx="12"
                                    cy="16"
                                    r=".5">
                            </circle>

                        </svg>

                        <span>
                            <?php echo htmlspecialchars($message); ?>
                        </span>

                    </div>

                <?php endif; ?>


                <form method="POST"
                      enctype="multipart/form-data"
                      autocomplete="off"
                      class="auth-form register-form">


                    <div class="form-row">

                        <div class="form-group">

                            <label for="fullname">
                                Full Name
                            </label>

                            <div class="input-wrapper">

                                <svg viewBox="0 0 24 24"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="2">

                                    <circle cx="12"
                                            cy="8"
                                            r="4">
                                    </circle>

                                    <path d="M4 21a8 8 0 0 1 16 0">
                                    </path>

                                </svg>

                                <input
                                    type="text"
                                    id="fullname"
                                    name="fullname"
                                    placeholder="Your full name"
                                    required
                                >

                            </div>

                        </div>


                        <div class="form-group">

                            <label for="email">
                                Email Address
                            </label>

                            <div class="input-wrapper">

                                <svg viewBox="0 0 24 24"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="2">

                                    <rect x="3"
                                          y="5"
                                          width="18"
                                          height="14"
                                          rx="2">
                                    </rect>

                                    <polyline points="3 7 12 13 21 7">
                                    </polyline>

                                </svg>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    placeholder="student@email.com"
                                    required
                                >

                            </div>

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="password">
                            Password
                        </label>

                        <div class="input-wrapper">

                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">

                                <rect x="3"
                                      y="11"
                                      width="18"
                                      height="10"
                                      rx="2">
                                </rect>

                                <path d="M7 11V7a5 5 0 0 1 10 0v4">
                                </path>

                            </svg>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Create a password"
                                autocomplete="new-password"
                                required
                            >

                        </div>

                    </div>


                    <div class="form-row">

                        <div class="form-group">

                            <label for="course">
                                Program
                            </label>

                            <div class="select-wrapper">

                                <select name="course"
                                        id="course"
                                        required>

                                    <option value="" selected disabled>
                                        Select program
                                    </option>

                                    <option value="BEED">
                                        BEED
                                    </option>

                                    <option value="BSMathematics">
                                        BSMathematics
                                    </option>

                                    <option value="BSSocial Studies">
                                        BSSocial Studies
                                    </option>

                                    <option value="BSIT">
                                        BSIT
                                    </option>

                                    <option value="BSEntrep">
                                        BSEntrep
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="form-group">

                            <label for="year_level">
                                Year Level
                            </label>

                            <div class="select-wrapper">

                                <select name="year_level"
                                        id="year_level"
                                        required>

                                    <option value="" selected disabled>
                                        Select year
                                    </option>

                                    <option value="1st Yr">
                                        1st Year
                                    </option>

                                    <option value="2nd Yr">
                                        2nd Year
                                    </option>

                                    <option value="3rd Yr">
                                        3rd Year
                                    </option>

                                    <option value="4th Yr">
                                        4th Year
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="contact_number">
                            Contact Number
                        </label>

                        <div class="input-wrapper">

                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">

                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2
                                         19.79 19.79 0 0 1-8.63-3.07
                                         19.5 19.5 0 0 1-6-6
                                         19.79 19.79 0 0 1-3.07-8.67
                                         A2 2 0 0 1 4.11 2h3
                                         a2 2 0 0 1 2 1.72
                                         12.84 12.84 0 0 0 .7 2.81
                                         2 2 0 0 1-.45 2.11L8.09 9.91
                                         a16 16 0 0 0 6 6l1.27-1.27
                                         a2 2 0 0 1 2.11-.45
                                         12.84 12.84 0 0 0 2.81.7
                                         A2 2 0 0 1 22 16.92z">
                                </path>

                            </svg>

                            <input
                                type="text"
                                id="contact_number"
                                name="contact_number"
                                maxlength="20"
                                placeholder="09XXXXXXXXX"
                            >

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="profile_picture">
                            Profile Picture
                            <span class="optional">
                                Optional
                            </span>
                        </label>

                        <div class="file-wrapper">

                            <input
                                type="file"
                                id="profile_picture"
                                name="profile_picture"
                                accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
                            >

                            <label for="profile_picture"
                                   class="file-label">

                                <div class="file-icon">

                                    <svg viewBox="0 0 24 24"
                                         fill="none"
                                         stroke="currentColor"
                                         stroke-width="2">

                                        <rect x="3"
                                              y="3"
                                              width="18"
                                              height="18"
                                              rx="2">
                                        </rect>

                                        <circle cx="8.5"
                                                cy="8.5"
                                                r="1.5">
                                        </circle>

                                        <polyline points="21 15 16 10 5 21">
                                        </polyline>

                                    </svg>

                                </div>

                                <div>

                                    <strong>
                                        Choose profile picture
                                    </strong>

                                    <span>
                                        PNG, JPG, WEBP or GIF • Max 2 MB
                                    </span>

                                </div>

                            </label>

                        </div>

                    </div>


                    <button type="submit"
                            class="auth-submit">

                        <span>
                            Create Account
                        </span>

                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <path d="M5 12h14"></path>

                            <path d="M13 6l6 6-6 6"></path>

                        </svg>

                    </button>

                </form>


                <div class="auth-divider">
                    <span>Already have an account?</span>
                </div>


                <a href="login.php"
                   class="secondary-action">

                    Log In Instead

                </a>


                <a href="../index.php"
                   class="back-home">

                    <svg viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2">

                        <path d="M19 12H5"></path>

                        <path d="M12 19l-7-7 7-7"></path>

                    </svg>

                    Back to Homepage

                </a>


                <div class="security-note">

                    <svg viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2">

                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z">
                        </path>

                    </svg>

                    <span>
                        Your information is handled securely.
                    </span>

                </div>

            </div>

        </section>

    </div>

</div>

</body>
</html>