<?php
session_start();
include '../../db.php';

// Check if logged in and admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Restrict access to POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_users.php');
    exit();
}

$id = intval($_POST['id'] ?? 0);
$fullname = trim($_POST['fullname'] ?? '');
$course = trim($_POST['course'] ?? '');
$year_level = trim($_POST['year_level'] ?? '');
$contact_number = trim($_POST['contact_number'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? '');
$password = $_POST['password'] ?? '';

if ($id <= 0) {
    $_SESSION['error_message'] = "Invalid user ID.";
    header("Location: manage_users.php");
    exit();
}

// Handle profile picture upload safely
$profile_picture = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $targetDir = "../../uploads/profile_pics/";
    
    // Create upload directory if it doesn't exist
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileExtension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $fileName = time() . "_" . uniqid() . "." . $fileExtension;
    $targetFilePath = $targetDir . $fileName;

    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFilePath)) {
        $profile_picture = $fileName;
    }
}

// Dynamic query construction based on provided inputs
if (!empty($password)) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    if ($profile_picture) {
        $stmt = $conn->prepare("UPDATE users 
            SET fullname=?, course=?, year_level=?, contact_number=?, email=?, role=?, password=?, profile_picture=? 
            WHERE id=?");
        $stmt->bind_param("ssssssssi", $fullname, $course, $year_level, $contact_number, $email, $role, $hashedPassword, $profile_picture, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users 
            SET fullname=?, course=?, year_level=?, contact_number=?, email=?, role=?, password=? 
            WHERE id=?");
        $stmt->bind_param("sssssssi", $fullname, $course, $year_level, $contact_number, $email, $role, $hashedPassword, $id);
    }
} else {
    if ($profile_picture) {
        $stmt = $conn->prepare("UPDATE users 
            SET fullname=?, course=?, year_level=?, contact_number=?, email=?, role=?, profile_picture=? 
            WHERE id=?");
        $stmt->bind_param("sssssssi", $fullname, $course, $year_level, $contact_number, $email, $role, $profile_picture, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users 
            SET fullname=?, course=?, year_level=?, contact_number=?, email=?, role=? 
            WHERE id=?");
        $stmt->bind_param("ssssssi", $fullname, $course, $year_level, $contact_number, $email, $role, $id);
    }
}

if ($stmt->execute()) {
    $_SESSION['success_message'] = "User updated successfully.";
} else {
    $_SESSION['error_message'] = "Error updating user: " . $stmt->error;
}

$stmt->close();

header("Location: manage_users.php");
exit();
?>