<?php
session_start();
include '../db.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === "admin") {
        header("Location: admin/admin_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === "student") {
        header("Location: student/student_dashboard.php");
        exit();
    } else {
        $_SESSION = [];
        session_destroy();
        session_start();
    }
}

$error = "";
$email = "";

$registered = !empty($_SESSION['registration_success']);

if ($registered) {
    unset($_SESSION['registration_success']);
}

$max_attempts = 5;
$lockout_time = 300;

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if (!isset($_SESSION['last_attempt_time'])) {
    $_SESSION['last_attempt_time'] = 0;
}

$time_passed = time() - $_SESSION['last_attempt_time'];
$remaining_time = $lockout_time - $time_passed;
$is_locked = false;

if ($_SESSION['login_attempts'] >= $max_attempts) {
    if ($remaining_time > 0) {
        $is_locked = true;
    } else {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if ($is_locked) {
        $minutes = ceil($remaining_time / 60);
        $error = "Too many failed login attempts. Please try again in " . $minutes . " minute(s).";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === "" || $password === "") {
            $error = "Please enter your email and password.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email or password.";
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
        } else {
            // Search for the account using its email
            $sql = "SELECT id, fullname, email, password, role, profile_picture FROM users WHERE email = ? LIMIT 1";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();

                // Check the password
                if ($user && password_verify($password, $user['password'])) {
                    if ($user['role'] === "admin" || $user['role'] === "student") {
                        // Create a new session ID after successful login
                        session_regenerate_id(true);

                        // Reset failed login attempts
                        $_SESSION['login_attempts'] = 0;
                        $_SESSION['last_attempt_time'] = 0;

                        // Save the user's information in the session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['fullname'] = $user['fullname'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['profile_picture'] = $user['profile_picture'] ?? '';

                        // Redirect according to the user's role
                        if ($user['role'] === "admin") {
                            header("Location: admin/admin_dashboard.php");
                            exit();
                        } elseif ($user['role'] === "student") {
                            header("Location: student/student_dashboard.php");
                            exit();
                        }
                    } else {
                        $error = "Your account role is not authorized.";
                    }
                } else {
                    // Count the failed login attempt
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt_time'] = time();

                    if ($_SESSION['login_attempts'] >= $max_attempts) {
                        $error = "Too many failed login attempts. Please try again in 5 minutes.";
                    } else {
                        $error = "Invalid email or password.";
                    }
                }
            } else {
                $error = "Unable to process your login. Please try again.";
            }
        }
    }
}

$time_passed = time() - $_SESSION['last_attempt_time'];
$remaining_time = $lockout_time - $time_passed;
$is_locked = false;

if ($_SESSION['login_attempts'] >= $max_attempts) {
    if ($remaining_time > 0) {
        $is_locked = true;
    } else {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Login | Student Lost &amp; Found System</title>
    <link rel="stylesheet" href="auth.css">
    <link rel="stylesheet" href="../responsive.css">
</head>
<body class="auth-page">
    <main class="auth-main">
        <section class="auth-form-wrapper">

            <a class="auth-brand">
                <span class="brand-logo">SLF</span>
                <span class="brand-text"><strong>Student Lost &amp; Found</strong></span>
            </a>

            <div class="form-heading">
                <h1>Log In </h1>
                <p>Access your SLF account.</p>
            </div>

            <?php if ($error !== ""): ?>
                <div class="form-alert" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email"  value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username" maxlength="254" required <?php echo $is_locked ? 'disabled' : ''; ?>>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter Your Password" autocomplete="new-password" required <?php echo $is_locked ? 'disabled' : ''; ?>>
                </div>
                <button type="submit" class="auth-submit" <?php echo $is_locked ? 'disabled' : ''; ?>>Sign In</button>
            </form>
                    <br>
                
            <div class="secondary-action">
              
                <a href="register.php">Create an account</a>
            </div>

            <a href="../index.php" class="back-home"> Back to Home</a>
        </section>
    </main>

    <footer class="auth-footer">&copy; 2026 Student Lost &amp; Found System</footer>

    <?php if ($registered): ?>
        <script>
            alert("Successfully Registered! You can now sign in to your account.");
        </script>
    <?php endif; ?>
</body>
</html>