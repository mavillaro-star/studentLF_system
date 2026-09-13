<?php

session_start();

include '../../db.php';


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit();

}


$user_id = $_SESSION['user_id'];


// Check ID
if (!isset($_GET['id'])) {

    header("Location: my_reports.php");
    exit();

}


$id = $_GET['id'];


// Get found report
$result = mysqli_query($conn,

"SELECT * FROM found_items 
WHERE id='$id' AND user_id='$user_id'"

);


if (mysqli_num_rows($result) == 0) {

    echo "Report not found.";
    exit();

}


$row = mysqli_fetch_assoc($result);



// Update report
if (isset($_POST['update'])) {


    $item_name = $_POST['item_name'];
    $description = $_POST['description'];
    $date_found = $_POST['date_found'];
    $location_found = $_POST['location_found'];



    // If new image uploaded

    if (!empty($_FILES['image']['name'])) {


        $image = $_FILES['image']['name'];

        $temp = $_FILES['image']['tmp_name'];


        $upload_path = "../uploads/" . $image;


        move_uploaded_file($temp, $upload_path);



        $update = mysqli_query($conn,

        "UPDATE found_items SET

        item_name='$item_name',
        description='$description',
        date_found='$date_found',
        location_found='$location_found',
        image='$image'

        WHERE id='$id' AND user_id='$user_id'"

        );


    } else {


        // Update without changing image

        $update = mysqli_query($conn,

        "UPDATE found_items SET

        item_name='$item_name',
        description='$description',
        date_found='$date_found',
        location_found='$location_found'

        WHERE id='$id' AND user_id='$user_id'"

        );


    }



    if ($update) {

        echo "
        <h3>Found report updated successfully!</h3>
        <a href='my_reports.php'>Back to My Reports</a>
        ";

        exit();

    }


}

?>


<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<title>Edit Found Report</title>
<link rel="stylesheet" href="student_style.css">
</head>

<body>


<h2>Edit Found Report</h2>


<form method="POST" enctype="multipart/form-data">


<label>Item Name:</label><br>

<input type="text" 
name="item_name"
value="<?php echo $row['item_name']; ?>"
required>


<br><br>


<label>Description:</label><br>

<textarea name="description" required><?php echo $row['description']; ?></textarea>


<br><br>


<label>Date Found:</label><br>

<input type="date"
name="date_found"
value="<?php echo $row['date_found']; ?>"
required>


<br><br>


<label>Location Found:</label><br>

<input type="text"
name="location_found"
value="<?php echo $row['location_found']; ?>"
required>


<br><br>


<label>Current Image:</label><br>

<?php

if (!empty($row['image'])) {

?>

<img src="../uploads/<?php echo $row['image']; ?>" width="120">

<?php

} else {

echo "No Image";

}

?>


<br><br>


<label>Change Image:</label><br>

<input type="file" name="image">


<br><br>


<button type="submit" name="update">
Update Report
</button>


</form>


<br>

<a href="my_reports.php">
Back to My Reports
</a>


</body>

</html>