<?php

session_start();

include '../../db.php';


// Check if logged in
if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit();

}


$user_id = $_SESSION['user_id'];


// Check if ID exists
if (isset($_GET['id'])) {


    $id = $_GET['id'];


    // Get found report
    $result = mysqli_query($conn,
        "SELECT * FROM found_items WHERE id='$id'"
    );


    if (mysqli_num_rows($result) > 0) {


        $row = mysqli_fetch_assoc($result);



        // When user confirms delete
        if (isset($_POST['confirm_delete'])) {



            // Delete image
            if (!empty($row['image'])) {


                $image = "../uploads/" . $row['image'];


                if (file_exists($image)) {

                    unlink($image);

                }

            }



            // Delete record
            $delete = mysqli_query($conn,

            "DELETE FROM found_items WHERE id='$id'"

            );



            if ($delete) {

                echo "
                <h3>Found report deleted successfully!</h3>
                <a href='my_reports.php'>Back to My Reports</a>
                ";

            }



        }


        // Cancel delete
        elseif (isset($_POST['cancel'])) {


            header("Location: my_reports.php");
            exit();


        }


        // Show confirmation
        else {

?>

<h2>Are you sure you want to delete this Found Report?</h2>

<p>
<b>Item Name:</b>
<?php echo $row['item_name']; ?>
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
        <a href='my_reports.php'>Back to My Reports</a>
        ";

    }



} else {


    header("Location: my_reports.php");
    exit();

}


?>