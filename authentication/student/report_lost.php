```php
<?php
session_start();
include '../../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if user is student
if ($_SESSION['role'] != "student") {
    header("Location: ../login.php");
    exit();
}

$fullname = $_SESSION['fullname'];
$message = "";

if (isset($_POST['submit'])) {

    $user_id = $_SESSION['user_id'];

    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $date_lost = $_POST['date_lost'];
    $location_lost = mysqli_real_escape_string($conn, $_POST['location_lost']);

    $status = "Lost";
    $image = "";

    // Upload Image
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {

        $image = time() . "_" . basename($_FILES['image']['name']);
        $upload_folder = "../uploads/";

        if (!file_exists($upload_folder)) {
            mkdir($upload_folder, 0777, true);
        }

        if (!move_uploaded_file(
            $_FILES['image']['tmp_name'],
            $upload_folder . $image
        )) {
            $message = "Image upload failed!";
        }
    }

    if (empty($message)) {

        $sql = "INSERT INTO lost_items
                (user_id, item_name, description, date_lost, location_lost, image, status)
                VALUES
                ('$user_id', '$item_name', '$description', '$date_lost', '$location_lost', '$image', '$status')";

        if (mysqli_query($conn, $sql)) {

            // Notify all admins
            $admins = mysqli_query(
                $conn,
                "SELECT id FROM users WHERE role = 'admin'"
            );

            while ($admin = mysqli_fetch_assoc($admins)) {

                $admin_id = $admin['id'];
                $notif_msg = "New Lost Item reported: '$item_name'";

                $notif_stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO notifications (user_id, message)
                     VALUES (?, ?)"
                );

                mysqli_stmt_bind_param(
                    $notif_stmt,
                    "is",
                    $admin_id,
                    $notif_msg
                );

                mysqli_stmt_execute($notif_stmt);
                mysqli_stmt_close($notif_stmt);
            }

            // Redirect to My Reports
            header("Location: my_reports.php");
            exit();

        } else {

            $message = "Database Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Report Lost Item</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
        }

        body {
            background: #eef3f8;
            color: #172033;
        }

        /* =========================
           MAIN LAYOUT
        ========================= */

        .dashboard {
            width: 100%;
            min-height: 100vh;
            display: flex;
        }


        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 230px;
            height: 100vh;
            background: #111a2d;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            padding: 24px 16px;
            z-index: 100;
            border-right: 1px solid #1f2b43;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 2px 8px 25px;
            border-bottom: 1px solid #26334c;
            margin-bottom: 22px;
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #1d2a44;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .brand-mark::before {
            content: "";
            width: 18px;
            height: 18px;
            border: 2px solid #4da3ff;
            border-radius: 50%;
            position: absolute;
        }

        .brand-mark::after {
            content: "";
            width: 24px;
            height: 2px;
            background: #4da3ff;
            transform: rotate(-45deg);
            position: absolute;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
        }

        .brand-text strong {
            font-size: 13px;
            letter-spacing: 1px;
            color: #ffffff;
        }

        .brand-text span {
            font-size: 10px;
            color: #75839b;
            margin-top: 3px;
            letter-spacing: .5px;
        }


        /* =========================
           NAVIGATION
        ========================= */

        .nav-title {
            font-size: 9px;
            text-transform: uppercase;
            color: #64728a;
            letter-spacing: 1.3px;
            padding: 0 10px;
            margin-bottom: 9px;
        }

        .nav-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .nav-item {
            height: 44px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 12px;
            border-radius: 10px;
            text-decoration: none;
            color: #91a0b8;
            font-size: 12px;
            font-weight: 500;
            transition: .2s ease;
            position: relative;
        }

        .nav-icon {
            width: 27px;
            height: 27px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #19253c;
            font-size: 13px;
            flex-shrink: 0;
        }

        .nav-item:hover {
            color: #ffffff;
            background: #18243a;
        }

        .nav-item:hover .nav-icon {
            background: #223352;
        }

        .nav-item.active {
            color: #ffffff;
            background: #1c2b47;
        }

        .nav-item.active::before {
            content: "";
            position: absolute;
            left: 0;
            width: 3px;
            height: 22px;
            border-radius: 0 5px 5px 0;
            background: #4da3ff;
        }

        .nav-item.active .nav-icon {
            background: #2468a9;
            color: #ffffff;
        }

        .nav-lost .nav-icon {
            color: #ff7676;
        }

        .nav-found .nav-icon {
            color: #35d39a;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #26334c;
        }

        .sidebar-bottom .nav-item:hover {
            background: #241d2a;
            color: #ff7f7f;
        }


        /* =========================
           MAIN CONTENT
        ========================= */

        .main-content {
            margin-left: 230px;
            width: calc(100% - 230px);
            min-height: 100vh;
            background: #f3f6fa;
        }


        /* =========================
           TOP HEADER
        ========================= */

        .topbar {
            min-height: 74px;
            background: #ffffff;
            border-bottom: 1px solid #e4e9ef;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-left h1 {
            font-size: 19px;
            color: #172033;
            font-weight: 750;
        }

        .topbar-left p {
            font-size: 11px;
            color: #8793a5;
            margin-top: 3px;
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .notification {
            width: 35px;
            height: 35px;
            border: 1px solid #e2e7ed;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            color: #64748b;
            font-size: 14px;
            position: relative;
        }

        .notification::after {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #ff5c5c;
            position: absolute;
            top: 7px;
            right: 7px;
            border: 2px solid #ffffff;
        }

        .user-divider {
            height: 32px;
            width: 1px;
            background: #e3e8ee;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #172033;
        }

        .user-role {
            display: block;
            font-size: 10px;
            color: #8b96a7;
            margin-top: 2px;
        }

        .avatar {
            width: 37px;
            height: 37px;
            border-radius: 11px;
            background: #1d6fc5;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(29, 111, 197, .18);
        }


        /* =========================
           PAGE CONTENT
        ========================= */

        .page-content {
            padding: 28px 30px 35px;
            width: 100%;
        }


        /* =========================
           PAGE INTRO
        ========================= */

        .page-intro {
            margin-bottom: 20px;
        }

        .page-intro h2 {
            font-size: 21px;
            font-weight: 750;
            color: #172033;
        }

        .page-intro p {
            font-size: 11px;
            color: #8793a5;
            margin-top: 5px;
        }


        /* =========================
           REPORT CARD
        ========================= */

        .report-card {
            background: #ffffff;
            border: 1px solid #e5eaf0;
            border-radius: 15px;
            overflow: hidden;
            max-width: 1000px;
        }

        .report-header {
            padding: 20px 22px;
            border-bottom: 1px solid #e8edf2;
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .report-header-icon {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: #fff0f0;
            color: #f05252;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
        }

        .report-header-text h3 {
            font-size: 14px;
            color: #172033;
            font-weight: 750;
        }

        .report-header-text p {
            font-size: 10px;
            color: #8b96a7;
            margin-top: 3px;
        }


        /* =========================
           FORM
        ========================= */

        .report-form {
            padding: 24px 22px 22px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 10px;
            font-weight: 700;
            color: #344054;
            margin-bottom: 7px;
        }

        .required {
            color: #f05252;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            border: 1px solid #dfe5ec;
            background: #fafbfd;
            border-radius: 9px;
            padding: 11px 12px;
            color: #172033;
            font-family: inherit;
            font-size: 11px;
            outline: none;
            transition: .2s ease;
        }

        .form-group input {
            height: 42px;
        }

        .form-group textarea {
            min-height: 115px;
            resize: vertical;
            line-height: 1.5;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #a4adba;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            background: #ffffff;
            border-color: #4da3ff;
            box-shadow: 0 0 0 3px rgba(77, 163, 255, .08);
        }


        /* =========================
           FILE UPLOAD
        ========================= */

        .file-upload {
            border: 1px dashed #cfd7e1;
            background: #fafbfd;
            border-radius: 9px;
            padding: 14px;
        }

        .file-upload input {
            border: none;
            padding: 0;
            height: auto;
            background: transparent;
            box-shadow: none;
        }

        .file-upload input:focus {
            box-shadow: none;
        }

        .file-note {
            font-size: 9px;
            color: #9aa5b4;
            margin-top: 7px;
        }


        /* =========================
           INFORMATION BOX
        ========================= */

        .form-info {
            margin-top: 19px;
            padding: 12px 14px;
            border-radius: 9px;
            background: #f3f8fd;
            border: 1px solid #dceaf7;
            color: #718096;
            font-size: 9px;
            line-height: 1.6;
        }

        .form-info strong {
            color: #4a6480;
        }


        /* =========================
           ERROR MESSAGE
        ========================= */

        .error-message {
            margin-bottom: 18px;
            padding: 11px 13px;
            border-radius: 8px;
            background: #fff0f0;
            border: 1px solid #ffd4d4;
            color: #d04444;
            font-size: 10px;
        }


        /* =========================
           FORM BUTTONS
        ========================= */

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 9px;
            margin-top: 22px;
            padding-top: 19px;
            border-top: 1px solid #edf0f4;
        }

        .form-btn {
            min-height: 38px;
            padding: 0 16px;
            border: none;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-family: inherit;
            font-size: 10px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: .2s ease;
        }

        .cancel-btn {
            background: #edf1f5;
            color: #657184;
        }

        .cancel-btn:hover {
            background: #e2e7ed;
        }

        .submit-btn {
            background: #ff5f61;
            color: #ffffff;
        }

        .submit-btn:hover {
            background: #ed4d50;
            transform: translateY(-1px);
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media screen and (max-width: 1000px) {

            .sidebar {
                width: 205px;
            }

            .main-content {
                margin-left: 205px;
                width: calc(100% - 205px);
            }

            .page-content {
                padding: 23px;
            }
        }


        @media screen and (max-width: 760px) {

            body {
                overflow: auto;
            }

            .dashboard {
                min-height: 100vh;
                height: auto;
                display: block;
            }

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                min-height: auto;
                padding: 15px;
                border-right: none;
                border-bottom: 1px solid #26334c;
            }

            .brand {
                margin-bottom: 13px;
                padding-bottom: 13px;
            }

            .nav-title {
                display: none;
            }

            .nav-menu {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 5px;
            }

            .nav-item {
                height: 40px;
            }

            .sidebar-bottom {
                margin-top: 10px;
                padding-top: 10px;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                min-height: calc(100vh - 230px);
            }

            .topbar {
                padding: 0 17px;
                min-height: 65px;
            }

            .topbar-left h1 {
                font-size: 16px;
            }

            .topbar-left p {
                display: none;
            }

            .user-info,
            .user-divider {
                display: none;
            }

            .page-content {
                padding: 18px;
            }

            .report-card {
                max-width: 100%;
            }
        }


        @media screen and (max-width: 600px) {

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .page-intro h2 {
                font-size: 19px;
            }

            .report-form {
                padding: 20px 18px;
            }

            .report-header {
                padding: 18px;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .form-btn {
                width: 100%;
            }
        }


        @media screen and (max-width: 430px) {

            .brand {
                justify-content: center;
            }

            .nav-menu {
                grid-template-columns: 1fr;
            }

            .page-content {
                padding: 14px;
            }

            .page-intro {
                margin-bottom: 15px;
            }

            .page-intro h2 {
                font-size: 18px;
            }

            .report-card {
                border-radius: 12px;
            }

            .report-header {
                padding: 16px;
            }

            .report-header-icon {
                width: 38px;
                height: 38px;
            }

            .report-form {
                padding: 17px 15px;
            }
        }

    </style>

</head>

<body>

<div class="dashboard">


    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar">

        <a href="students.php"
           style="text-decoration:none; color:inherit;">

            <div class="brand">

                <div class="brand-mark"></div>

                <div class="brand-text">
                    <strong>LOST &amp; FOUND</strong>
                    <span>STUDENT PORTAL</span>
                </div>

            </div>

        </a>


        <div class="nav-title">
            Main Menu
        </div>


        <ul class="nav-menu">

            <li>
                <a href="students.php" class="nav-item">

                    <span class="nav-icon">⌂</span>
                    <span>Dashboard</span>

                </a>
            </li>


            <li>
                <a href="report_lost.php"
                   class="nav-item nav-lost active">

                    <span class="nav-icon">⌕</span>
                    <span>Report Lost Item</span>

                </a>
            </li>


            <li>
                <a href="report_found.php"
                   class="nav-item nav-found">

                    <span class="nav-icon">□</span>
                    <span>Report Found Item</span>

                </a>
            </li>


            <li>
                <a href="my_reports.php"
                   class="nav-item">

                    <span class="nav-icon">▤</span>
                    <span>My Reports</span>

                </a>
            </li>


            <li>
                <a href="profile.php"
                   class="nav-item">

                    <span class="nav-icon">○</span>
                    <span>Profile</span>

                </a>
            </li>

        </ul>


        <div class="sidebar-bottom">

            <a href="../../authentication/logout.php"
               class="nav-item"
               onclick="return confirmLogout();">

                <span class="nav-icon">↪</span>
                <span>Log Out</span>

            </a>

        </div>

    </aside>



    <!-- ================= MAIN CONTENT ================= -->

    <main class="main-content">


        <!-- ================= TOP BAR ================= -->

        <header class="topbar">

            <div class="topbar-left">

                <h1>Report Lost Item</h1>

                <p>
                    Submit information about an item you have lost.
                </p>

            </div>


            <div class="user-area">

                <div class="notification">
                    ♢
                </div>


                <div class="user-divider"></div>


                <div class="user-info">

                    <span class="user-name">
                        <?php echo htmlspecialchars($fullname); ?>
                    </span>

                    <span class="user-role">
                        Student Account
                    </span>

                </div>


                <div class="avatar">

                    <?php
                    echo strtoupper(substr($fullname, 0, 1));
                    ?>

                </div>

            </div>

        </header>



        <!-- ================= PAGE CONTENT ================= -->

        <div class="page-content">


            <!-- PAGE INTRO -->

            <div class="page-intro">

                <h2>
                    Report a Lost Item
                </h2>

                <p>
                    Provide accurate details so the item can be identified
                    and matched if it is found.
                </p>

            </div>

            <section class="report-card">


                <!-- CARD HEADER -->

                <div class="report-header">

                    <div class="report-header-icon">
                        !
                    </div>

                    <div class="report-header-text">

                        <h3>
                            Lost Item Information
                        </h3>

                        <p>
                            Fill in the required information below.
                        </p>

                    </div>

                </div>

                <form method="POST"
                      enctype="multipart/form-data"
                      class="report-form">


                    <?php if (!empty($message)) { ?>

                        <div class="error-message">

                            <?php
                            echo htmlspecialchars($message);
                            ?>

                        </div>

                    <?php } ?>



                    <div class="form-grid">

                        <div class="form-group">

                            <label>
                                Item Name
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                name="item_name"
                                placeholder="Example: Black Wallet"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label>
                                Date Lost
                                <span class="required">*</span>
                            </label>

                            <input
                                type="date"
                                name="date_lost"
                                max="<?php echo date('Y-m-d'); ?>"
                                required
                            >

                        </div>

                        <div class="form-group full">

                            <label>
                                Location Lost
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                name="location_lost"
                                placeholder="Example: Library, Room 204, Cafeteria"
                                required
                            >

                        </div>


                        <div class="form-group full">

                            <label>
                                Description
                                <span class="required">*</span>
                            </label>

                            <textarea
                                name="description"
                                placeholder="Describe the item's color, brand, size, design, markings, or other identifying details."
                                required
                            ></textarea>

                        </div>

                        <div class="form-group full">

                            <label>
                                Upload Image
                                <span style="color:#9aa5b4;">
                                    (Optional)
                                </span>
                            </label>

                            <div class="file-upload">

                                <input
                                    type="file"
                                    name="image"
                                    accept="image/*"
                                >

                                <div class="file-note">
                                    Upload a clear image of the lost item if available.
                                </div>

                            </div>

                        </div>


                    </div>


                    <div class="form-info">

                        <strong>Important:</strong>
                        Please provide truthful and accurate information.
                        Your report will be reviewed by the administrator
                        and will appear in your My Reports section after submission.

                    </div>


                    <div class="form-actions">

                        <a href="students.php"
                           class="form-btn cancel-btn">

                            Cancel

                        </a>


                        <button
                            type="submit"
                            name="submit"
                            class="form-btn submit-btn">

                            Submit Report

                        </button>

                    </div>


                </form>

            </section>


        </div>

    </main>

</div>



<script>

function confirmLogout() {

    return confirm(
        "Are you sure you want to log out?"
    );

}

</script>
</body>
</html>