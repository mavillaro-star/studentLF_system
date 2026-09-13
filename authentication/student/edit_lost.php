<?php
session_start();
include '../../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header("Location: my_reports.php");
    exit();
}

$id = intval($_GET['id']);

$result = mysqli_query($conn, "SELECT * FROM lost_items WHERE id='$id' AND user_id='$user_id'");

if (mysqli_num_rows($result) == 0) {
    die("Report not found.");
}

$row = mysqli_fetch_assoc($result);

if (isset($_POST['update'])) {

    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $date_lost = $_POST['date_lost'];
    $location_lost = mysqli_real_escape_string($conn, $_POST['location_lost']);

    $image = $row['image'];

    if (!empty($_FILES['image']['name'])) {

        $image = time() . "_" . $_FILES['image']['name'];

        move_uploaded_file(
            $_FILES['image']['tmp_name'],
            "../uploads/" . $image
        );
    }

    mysqli_query($conn, "UPDATE lost_items SET
        item_name='$item_name',
        description='$description',
        date_lost='$date_lost',
        location_lost='$location_lost',
        image='$image'
        WHERE id='$id' AND user_id='$user_id'
    ");

    header("Location: my_reports.php");
    exit();
}
?>

    <!DOCTYPE html>
    <html>
    <head>
        <title>Edit Lost Report</title>
        <link rel="stylesheet" href="student_style.css">
    </head>
    <body>

    <h2>Edit Lost Report</h2>

    <form method="POST" enctype="multipart/form-data">

    <label>Item Name</label><br>
    <input type="text" name="item_name" value="<?php echo $row['item_name']; ?>" required><br><br>

    <label>Description</label><br>
    <textarea name="description" required><?php echo $row['description']; ?></textarea><br><br>

    <label>Date Lost</label><br>
    <input type="date" name="date_lost" value="<?php echo $row['date_lost']; ?>" required><br><br>

    <label>Location Lost</label><br>
    <input type="text" name="location_lost" value="<?php echo $row['location_lost']; ?>" required><br><br>

    <label>Current Image</label><br>

    <?php
    if (!empty($row['image'])) {
    ?>
    <img src="../uploads/<?php echo $row['image']; ?>" width="150"><br><br>
    <?php
    }
    ?>

    <label>Change Image</label><br>
    <input type="file" name="image"><br><br>

    

    <input type="submit" name="update" value="Update Report">

    </form>

    <br>

    <a href="my_reports.php">Back</a>

    </body>
    </html>