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

// --- Prevent back arrow to login if already logged in ---
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/admin_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'student') {
        header("Location: student/students.php");
        exit();
    }
}

// --- Database connection ---
$conn = new mysqli("localhost", "root", "", "slf_system_db", 3308);
if ($conn->connect_error) {
    die("Something went wrong. Please try again later.");
}

// --- Brute force protection ---
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['last_attempt_time'])) {
    $_SESSION['last_attempt_time'] = time();
}
if (time() - $_SESSION['last_attempt_time'] > 300) {
    $_SESSION['login_attempts'] = 0;
}
if ($_SESSION['login_attempts'] >= 5) {
    $conn->close();
    die("Too many failed attempts. Please wait 5 minutes before trying again.");
}

// --- Handle login POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $e = trim($_POST['email'] ?? '');
    $p = $_POST['password'] ?? '';

    if ($e === '' || $p === '') {
        echo "Invalid email or password.";
    } else {
        $sql = "SELECT id, fullname, password, role FROM users WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Something went wrong. Please try again later.");
        }

        $stmt->bind_param("s", $e);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            if (password_verify($p, $row['password'])) {
                $_SESSION['login_attempts'] = 0;
                $_SESSION['last_attempt_time'] = time();

                session_regenerate_id(true);

                $_SESSION['user_id'] = $row['id'];
                $_SESSION['fullname'] = $row['fullname'];
                $_SESSION['role'] = $row['role'];

                if ($row['role'] === 'admin') {
                    header("Location: admin/admin_dashboard.php");
                    exit();
                } elseif ($row['role'] === 'student') {
                    header("Location: student/students.php");
                    exit();
                } else {
                    $_SESSION = [];
                    session_destroy();
                    die("Invalid account role.");
                }
            } else {
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = time();
                echo "Invalid email or password.";
            }
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
            echo "Invalid email or password.";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Secure login for the Student Lost and Found Management System.">

    <meta name="theme-color" content="#0f172a">

    <title>Login | Student Lost & Found</title>

    <link rel="stylesheet" href="auth.css">
</head>

<body>

<div class="auth-page">

    <div class="auth-layout">

        <!-- LEFT SIDE -->

        <section class="auth-intro">

            <a href="../index.php" class="auth-brand">

                <div class="brand-logo">

                    <svg viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2"
                         stroke-linecap="round"
                         stroke-linejoin="round">

                        <circle cx="10.5" cy="10.5" r="7"></circle>
                        <path d="M16 16l5 5"></path>
                        <path d="M10.5 7.5v3l2 2"></path>

                    </svg>

                </div>

                <div>
                    <strong>STUDENT L&amp;F</strong>
                    <span>Lost &amp; Found System</span>
                </div>

            </a>


            <div class="intro-content">

                <span class="intro-label">
                    STUDENT LOST &amp; FOUND
                </span>

                <h1>
                    Welcome back.
                    <span>Find what matters.</span>
                </h1>

                <p>
                    Sign in to manage your lost and found reports,
                    track submitted items, and help reconnect
                    belongings with their rightful owners.
                </p>


                <div class="intro-features">

                    <div class="intro-feature">

                        <div class="feature-check">
                            ✓
                        </div>

                        <div>
                            <strong>Secure Access</strong>
                            <span>Your account is protected.</span>
                        </div>

                    </div>


                    <div class="intro-feature">

                        <div class="feature-check">
                            ✓
                        </div>

                        <div>
                            <strong>Organized Reports</strong>
                            <span>Manage your item reports easily.</span>
                        </div>

                    </div>


                    <div class="intro-feature">

                        <div class="feature-check">
                            ✓
                        </div>

                        <div>
                            <strong>Easy Management</strong>
                            <span>Everything in one platform.</span>
                        </div>

                    </div>

                </div>

            </div>


            <div class="intro-footer">
                © 2026 Student Lost &amp; Found Management System
            </div>

        </section>


        <!-- RIGHT SIDE -->

        <section class="auth-form-area">

            <div class="auth-form-wrapper">

                <div class="mobile-brand">

                    <a href="../index.php" class="auth-brand">

                        <div class="brand-logo">

                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">

                                <circle cx="10.5" cy="10.5" r="7"></circle>
                                <path d="M16 16l5 5"></path>
                                <path d="M10.5 7.5v3l2 2"></path>

                            </svg>

                        </div>

                        <div>
                            <strong>STUDENT L&amp;F</strong>
                            <span>Lost &amp; Found System</span>
                        </div>

                    </a>

                </div>


                <div class="form-heading">

                    <span class="form-eyebrow">
                        ACCOUNT ACCESS
                    </span>

                    <h2>
                        Log in to your account
                    </h2>

                    <p>
                        Enter your account credentials to continue.
                    </p>

                </div>


                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SESSION['user_id'])): ?>

                    <div class="form-alert">

                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <circle cx="12" cy="12" r="9"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <circle cx="12" cy="16" r=".5"></circle>

                        </svg>

                        <span>
                            Invalid email or password.
                        </span>

                    </div>

                <?php endif; ?>


                <form action="login.php"
                      method="POST"
                      autocomplete="off"
                      class="auth-form">


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
                                placeholder="Enter your email address"
                                autocomplete="off"
                                required
                            >

                        </div>

                    </div>


                    <div class="form-group">

                        <div class="label-row">

                            <label for="password">
                                Password
                            </label>

                        </div>

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
                                placeholder="Enter your password"
                                autocomplete="new-password"
                                required
                            >

                        </div>

                    </div>


                    <button type="submit"
                            class="auth-submit">

                        <span>
                            Log In
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
                    <span>New to the system?</span>
                </div>


                <a href="register.php"
                   class="secondary-action">

                    Create Account

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
                        Your account information is handled securely.
                    </span>

                </div>

            </div>

        </section>

    </div>

</div>

</body>
</html>