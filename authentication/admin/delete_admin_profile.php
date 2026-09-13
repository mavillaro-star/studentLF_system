<?php
session_start();
include '../../db.php';

// Check if logged in and admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_SESSION['user_id']); // always delete own account
    $password = $_POST['password'] ?? '';

    // Get current password hash
    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ? AND role = 'admin'");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($user && password_verify($password, $user['password'])) {
        // Delete account
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ? AND role = 'admin'");
        mysqli_stmt_bind_param($stmt, 'i', $id);

        if (mysqli_stmt_execute($stmt)) {
            session_destroy();
            header("Location: ../login.php?deleted=1");
            exit();
        } else {
            $_SESSION['error_message'] = "Delete failed: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error_message'] = "Incorrect password. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Delete Admin Account</title>
<link rel="stylesheet" href="admin.css">
</head>
<body>

<h2>Delete My Admin Account</h2>

<?php
if (!empty($_SESSION['error_message'])) {
    echo "<p style='color:red;'>" . $_SESSION['error_message'] . "</p>";
    unset($_SESSION['error_message']);
}
?>

<p style="color:red; font-weight:bold;">
⚠️ Warning: This action cannot be undone. Once deleted, your admin account will be permanently removed.
</p>

<form method="POST">
    <label>Enter your password to confirm:<br>
        <input type="password" name="password" required>
    </label><br><br>

    <button type="submit" style="color:white; background:red; padding:10px;">Yes, Delete My Account</button>
    <a href="admin_dashboard.php" style="margin-left:15px;">Cancel</a>
</form>

</body>
</html>