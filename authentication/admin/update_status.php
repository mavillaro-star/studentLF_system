<?php
session_start();
include '../../db.php';

// Check if logged in and admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $type = $_POST['type'] ?? ''; // 'lost' or 'found'
    $new_status = $_POST['status'] ?? '';

    // Validate table name to prevent SQL injection
    $allowed_tables = ['lost' => 'lost_items', 'found' => 'found_items'];
    
    if (array_key_exists($type, $allowed_tables) && $id > 0) {
        $table = $allowed_tables[$type];

        // 1. Fetch item details to get the owner student's user_id
        $stmt = mysqli_prepare($conn, "SELECT user_id, item_name FROM $table WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $item = mysqli_fetch_assoc($result);

        if ($item) {
            $student_id = $item['user_id'];
            $item_name = $item['item_name'];

            // 2. Update status
            $update_stmt = mysqli_prepare($conn, "UPDATE $table SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($update_stmt, "si", $new_status, $id);

            if (mysqli_stmt_execute($update_stmt)) {
                // 3. NOTIFY THE STUDENT WHO REPORTED THIS ITEM
                $notif_msg = "Your $type item report ('$item_name') status was updated to '$new_status'.";
                $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                mysqli_stmt_bind_param($notif_stmt, "is", $student_id, $notif_msg);
                mysqli_stmt_execute($notif_stmt);

                $_SESSION['success_message'] = "Status updated and student notified successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to update status.";
            }
        } else {
            $_SESSION['error_message'] = "Item not found.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid request parameters.";
    }
}

header("Location: view_reports.php");
exit();
?>