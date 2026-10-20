<?php
session_start();
include '../../db.php';

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit();

}


$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {

    $id = $_GET['id'];

    $result = mysqli_query($conn,
        "SELECT * FROM lost_items WHERE id='$id'"
    );


    if (mysqli_num_rows($result) > 0) {


        $row = mysqli_fetch_assoc($result);

        if (isset($_POST['confirm_delete'])) {

            if (!empty($row['image'])) {

                $image = "../uploads/" . $row['image'];

                if (file_exists($image)) {

                    unlink($image);

                }

            }

            $delete = mysqli_query($conn,
                "DELETE FROM lost_items WHERE id='$id'"
            );


            if ($delete) {

                echo "
                <h3>Lost report deleted successfully!</h3>
                <a href='my_reports.php'>Back to My Reports</a>
                ";

            }


        } 
        
        elseif (isset($_POST['cancel'])) {


            header("Location: my_reports.php");
            exit();


        }

        else {

?>

<h2>Are you sure you want to delete this Lost Report?</h2>

<p>
<b>Item Name:</b> <?php echo $row['item_name']; ?>
</p>

<form method="POST">

    <button type="submit" name="confirm_delete">
        Yes, Delete
    </button>


    <button type="submit" name="cancel">
        Cancel
    </button>

</form>


<?php

        }



    } else {


        echo "
        <h3>Report not found.</h3>
        <a href='my_reports.php'>Back</a>
        ";

    }



} else {


    header("Location: my_reports.php");
    exit();

}
?>