<?php

session_start();

include '../../db.php';


// Check if logged in

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit();

}


$user_id = $_SESSION['user_id'];


// Delete user account

$sql = "DELETE FROM users WHERE id='$user_id'";


if(mysqli_query($conn,$sql)){


    // Remove session

    session_unset();

    session_destroy();


    // Redirect to login

    header("Location: ../log in.php");

    exit();


}
else{


    echo "Error deleting account: " . mysqli_error($conn);


}


?>