<?php
session_start();

// ✅ Check if logged in and admin (Gisundan ang imong logic sa ibabaw)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

// ✅ Process Logout Direct Inig Pindot sa "Yes, Log Out" (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Unset all session variables
    $_SESSION = array();

    // Destroy session
    session_destroy();

    // Redirect to login page
    header("Location: ../login.php?logout=success");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Logout</title>
</head>
<body>

<h2>Confirm Logout</h2>

<p style="color:#d9534f; font-weight:bold;">
⚠️ Are you sure you want to log out, <b><?php echo htmlspecialchars($_SESSION['fullname']); ?></b>?
</p>

<!-- ✅ Self-submitting Form (Parihas sa imong Delete Account code) -->
<form method="POST">
    <button type="submit" style="color:white; background:red; padding:10px; border:none; cursor:pointer; border-radius:4px;">
        Yes, Log Out
    </button>
    <a href="admin_dashboard.php" style="margin-left:15px; text-decoration:none;">Cancel</a>
</form>

</body>
</html>