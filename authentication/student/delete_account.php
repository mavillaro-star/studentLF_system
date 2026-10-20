<?php
session_start();
include '../../db.php';

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit();

}

$user_id = $_SESSION['user_id'];


$sql = "DELETE FROM users WHERE id='$user_id'";

if(mysqli_query($conn,$sql)){

    session_unset();

    session_destroy();


    header("Location: ../log in.php");

    exit();

}
else{


    echo "Error deleting account: " . mysqli_error($conn);


}

?>