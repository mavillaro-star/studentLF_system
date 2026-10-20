<?php
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/admin_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] == 'student') {
        header("Location: student/student_dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Lost and Found System</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="responsive.css">
</head>
<body>

    <!-- Navigation -->
   <header>
        <a href="index.php" class="logo">
            <span>SLF</span>
            Student Lost & Found
        </a>

        <!-- Hamburger Toggle
        <input type="checkbox" id="menu-toggle">

        <label for="menu-toggle" class="menu-icon"
               aria-label="Toggle navigation menu">
            <span></span>
            <span></span>
            <span></span>
            <span></span>
        </label> -->

        <!-- Navigation Links -->
        <!-- <nav>
            <a href="#home">Home</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
            <a href="authentication/login.php">Login</a>
            <a href="authentication/register.php">Register</a>
            
        </nav> -->
    </header>

    <main>
        <!-- Home -->
        <section id="home">
            <h1>Lost Something?</h1>
            <h2>Let's Find It Together.</h2>

            <p>
                A simple and organized way to report lost items,
                share found belongings, and help students find
                what they have lost.
            </p>

            <a href="authentication/login.php" class="get-started">
                Get Started
            </a>
        </section>

        <!-- About -->
        <section id="about">
            <h2>About Our System</h2>
            <p>
                The Student Lost and Found System helps students
                report lost and found items and track their reports.
            </p>
        </section>

        <!-- Contact -->
        <section id="contact">
            <h2>Contact Us</h2>
            <p>
                Need help? Please contact your school administrator
                for assistance with lost and found items.
            </p>
        </section>
    </main>

    <footer>
        <p>
            &copy; <?php echo date("Y"); ?>
            Student Lost & Found System.
        </p>
    </footer>

</body>
</html>