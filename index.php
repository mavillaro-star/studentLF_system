<?php
session_start();

$is_logged_in = isset($_SESSION['user_id']);
$fullname = $_SESSION['fullname'] ?? '';
$role = $_SESSION['role'] ?? '';

if ($is_logged_in) {
    $dashboard_link = ($role === 'admin')
        ? 'admin/admin_dashboard.php'
        : 'student/students.php';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Student Lost & Found System - A centralized platform for reporting and managing lost and found items.">

    <meta name="theme-color" content="#0f172a">

    <title>Student Lost & Found System</title>

    <link rel="stylesheet" href="index.css">
</head>

<body>

<header class="site-header">

    <div class="nav-container">

        <a href="index.php" class="brand">

            <div class="brand-logo">
                <svg viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <circle cx="10.5" cy="10.5" r="7"></circle>
                    <path d="M16 16l5 5"></path>
                    <path d="M10.5 7.5v3l2 2"></path>

                </svg>
            </div>

            <div class="brand-text">
                <strong>STUDENT L&amp;F</strong>
                <span>Lost &amp; Found System</span>
            </div>

        </a>


        <!-- Desktop Navigation -->

        <nav class="desktop-nav">

            <a href="#home">Home</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#features">Features</a>
            <a href="#about">About</a>

            <?php if ($is_logged_in): ?>

                <a href="<?php echo htmlspecialchars($dashboard_link); ?>"
                   class="nav-dashboard">
                    Dashboard
                </a>

            <?php else: ?>

                <a href="authentication/login.php"
                   class="nav-login">
                    Login
                </a>

                <a href="authentication/register.php"
                   class="nav-register">
                    Register
                </a>

            <?php endif; ?>

        </nav>


        <!-- CSS-only Mobile Menu -->

        <input type="checkbox"
               id="menu-toggle"
               class="menu-toggle">

        <label for="menu-toggle"
               class="menu-button"
               aria-label="Open navigation menu">

            <span></span>
            <span></span>
            <span></span>

        </label>

    </div>


    <!-- Mobile Navigation -->

    <nav class="mobile-nav">

        <a href="#home">Home</a>
        <a href="#how-it-works">How It Works</a>
        <a href="#features">Features</a>
        <a href="#about">About</a>

        <?php if ($is_logged_in): ?>

            <a href="<?php echo htmlspecialchars($dashboard_link); ?>">
                Dashboard
            </a>

        <?php else: ?>

            <a href="authentication/login.php">
                Login
            </a>

            <a href="authentication/register.php">
                Register
            </a>

        <?php endif; ?>

    </nav>

</header>


<main>


    <!-- HERO -->

    <section class="hero" id="home">

        <div class="hero-container">

            <div class="hero-content">

                <div class="system-badge">
                    <span></span>
                    Student Lost &amp; Found System
                </div>


                <?php if ($is_logged_in): ?>

                    <h1>
                        Welcome back,
                        <span><?php echo htmlspecialchars($fullname); ?>.</span>
                    </h1>

                    <p>
                        Manage your lost and found reports and help
                        reconnect belongings with their rightful owners.
                    </p>

                    <div class="hero-buttons">

                        <a href="<?php echo htmlspecialchars($dashboard_link); ?>"
                           class="btn-primary">

                            Go to Dashboard

                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">
                                <path d="M5 12h14"></path>
                                <path d="M13 6l6 6-6 6"></path>
                            </svg>

                        </a>

                        <a href="#how-it-works"
                           class="btn-secondary">
                            Learn More
                        </a>

                    </div>

                <?php else: ?>

                    <h1>
                        Lost something?
                        <span>We can help you find it.</span>
                    </h1>

                    <p>
                        The Student Lost &amp; Found System provides a
                        centralized and organized way to report lost items,
                        submit found items, and help students recover
                        their belongings.
                    </p>

                    <div class="hero-buttons">

                        <a href="authentication/register.php"
                           class="btn-primary">

                            Get Started

                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">
                                <path d="M5 12h14"></path>
                                <path d="M13 6l6 6-6 6"></path>
                            </svg>

                        </a>

                        <a href="authentication/login.php"
                           class="btn-secondary">
                            Sign In
                        </a>

                    </div>

                <?php endif; ?>


                <div class="hero-highlights">

                    <div>
                        <div class="highlight-icon blue">
                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z">
                                </path>
                            </svg>
                        </div>

                        <div>
                            <strong>Secure</strong>
                            <span>Student accounts</span>
                        </div>
                    </div>


                    <div>
                        <div class="highlight-icon green">
                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">
                                <path d="M20 6L9 17l-5-5"></path>
                            </svg>
                        </div>

                        <div>
                            <strong>Organized</strong>
                            <span>Item reports</span>
                        </div>
                    </div>


                    <div>
                        <div class="highlight-icon orange">
                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 7v5l3 2"></path>
                            </svg>
                        </div>

                        <div>
                            <strong>Efficient</strong>
                            <span>Easy reporting</span>
                        </div>
                    </div>

                </div>

            </div>


            <!-- HERO SYSTEM CARD -->

            <div class="hero-visual">

                <div class="visual-circle"></div>

                <div class="system-card">

                    <div class="system-card-header">

                        <div>
                            <span>LOST &amp; FOUND</span>
                            <h3>Item Reports</h3>
                        </div>

                        <div class="card-search-icon">

                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">

                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="M20 20l-4-4"></path>

                            </svg>

                        </div>

                    </div>


                    <div class="fake-search">

                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="M20 20l-4-4"></path>

                        </svg>

                        <span>Search lost or found items</span>

                    </div>


                    <div class="report-item">

                        <div class="report-icon blue-bg">

                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">

                                <rect x="4" y="3"
                                      width="16"
                                      height="18"
                                      rx="2">
                                </rect>

                                <line x1="8"
                                      y1="8"
                                      x2="16"
                                      y2="8">
                                </line>

                                <line x1="8"
                                      y1="12"
                                      x2="16"
                                      y2="12">
                                </line>

                            </svg>

                        </div>

                        <div class="report-details">

                            <strong>Student ID</strong>
                            <span>Library Area</span>

                        </div>

                        <span class="status found">
                            Found
                        </span>

                    </div>


                    <div class="report-item">

                        <div class="report-icon orange-bg">

                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">

                                <rect x="5"
                                      y="3"
                                      width="14"
                                      height="18"
                                      rx="2">
                                </rect>

                                <line x1="8"
                                      y1="9"
                                      x2="16"
                                      y2="9">
                                </line>

                                <line x1="8"
                                      y1="13"
                                      x2="14"
                                      y2="13">
                                </line>

                            </svg>

                        </div>

                        <div class="report-details">

                            <strong>Notebook</strong>
                            <span>Classroom Building</span>

                        </div>

                        <span class="status pending">
                            Pending
                        </span>

                    </div>


                    <div class="system-card-footer">

                        <span>
                            Organized item management
                        </span>

                        <span class="system-online">
                            <i></i>
                            System Online
                        </span>

                    </div>

                </div>


                <div class="floating-card found-card">

                    <div class="floating-icon green-bg">

                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <path d="M20 6L9 17l-5-5"></path>

                        </svg>

                    </div>

                    <div>
                        <strong>Found Item</strong>
                        <span>Report submitted</span>
                    </div>

                </div>


                <div class="floating-card report-card">

                    <div class="floating-icon blue-bg">

                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <path d="M12 5v14"></path>
                            <path d="M5 12h14"></path>

                        </svg>

                    </div>

                    <div>
                        <strong>Report Item</strong>
                        <span>Quick and simple</span>
                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- FEATURES -->

    <section class="features-section" id="features">

        <div class="section-container">

            <div class="section-heading">

                <span class="eyebrow">
                    SYSTEM FEATURES
                </span>

                <h2>
                    Everything in one
                    organized platform.
                </h2>

                <p>
                    Designed to make the process of reporting and
                    managing lost and found belongings easier for
                    students and administrators.
                </p>

            </div>


            <div class="feature-grid">


                <div class="feature-card">

                    <span class="feature-number">
                        01
                    </span>

                    <div class="feature-icon blue-bg">

                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>

                        </svg>

                    </div>

                    <h3>
                        Report Lost Items
                    </h3>

                    <p>
                        Submit details about a lost belonging,
                        including its description, location,
                        date, and other important information.
                    </p>

                </div>


                <div class="feature-card">

                    <span class="feature-number">
                        02
                    </span>

                    <div class="feature-icon orange-bg">

                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <path d="M20 6L9 17l-5-5"></path>

                        </svg>

                    </div>

                    <h3>
                        Report Found Items
                    </h3>

                    <p>
                        Help return someone's belongings by
                        reporting an item you found on campus.
                    </p>

                </div>


                <div class="feature-card">

                    <span class="feature-number">
                        03
                    </span>

                    <div class="feature-icon green-bg">

                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M16 11l2 2 4-4"></path>

                        </svg>

                    </div>

                    <h3>
                        Reconnect Owners
                    </h3>

                    <p>
                        Organized reports allow administrators
                        to review items and help return them
                        to their rightful owners.
                    </p>

                </div>


                <div class="feature-card">

                    <span class="feature-number">
                        04
                    </span>

                    <div class="feature-icon blue-bg">

                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <path d="M3 3h18v18H3z"></path>
                            <path d="M8 8h8"></path>
                            <path d="M8 12h8"></path>
                            <path d="M8 16h5"></path>

                        </svg>

                    </div>

                    <h3>
                        Organized Reports
                    </h3>

                    <p>
                        Lost and found submissions are kept
                        organized so important information can
                        be reviewed more efficiently.
                    </p>

                </div>


                <div class="feature-card">

                    <span class="feature-number">
                        05
                    </span>

                    <div class="feature-icon green-bg">

                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>

                        </svg>

                    </div>

                    <h3>
                        Secure Accounts
                    </h3>

                    <p>
                        Student accounts and system access are
                        separated according to their appropriate
                        roles and permissions.
                    </p>

                </div>


                <div class="feature-card">

                    <span class="feature-number">
                        06
                    </span>

                    <div class="feature-icon orange-bg">

                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 7v5l3 2"></path>

                        </svg>

                    </div>

                    <h3>
                        Faster Management
                    </h3>

                    <p>
                        A centralized system reduces manual
                        handling and makes lost and found
                        management more efficient.
                    </p>

                </div>

            </div>

        </div>

    </section>


    <!-- HOW IT WORKS -->

    <section class="how-section" id="how-it-works">

        <div class="section-container">

            <div class="how-layout">

                <div class="how-intro">

                    <span class="eyebrow">
                        HOW IT WORKS
                    </span>

                    <h2>
                        A simple process for
                        <span>every student.</span>
                    </h2>

                    <p>
                        Whether you lost something or found an
                        item, the system provides an organized
                        way to submit and manage reports.
                    </p>

                </div>


                <div class="steps">


                    <div class="step">

                        <div class="step-number">
                            01
                        </div>

                        <div>
                            <h3>
                                Create an Account
                            </h3>

                            <p>
                                Register your student account
                                to access the system.
                            </p>
                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            02
                        </div>

                        <div>
                            <h3>
                                Report an Item
                            </h3>

                            <p>
                                Submit the details of the lost
                                or found item.
                            </p>
                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            03
                        </div>

                        <div>
                            <h3>
                                Review the Report
                            </h3>

                            <p>
                                Reports are reviewed and managed
                                through the system.
                            </p>

                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            04
                        </div>

                        <div>
                            <h3>
                                Reconnect the Item
                            </h3>

                            <p>
                                Help return the identified item
                                to its rightful owner.
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- ABOUT -->

    <section class="about-section" id="about">

        <div class="section-container">

            <div class="about-card">

                <div class="about-content">

                    <span class="eyebrow">
                        ABOUT THE SYSTEM
                    </span>

                    <h2>
                        Making lost and found
                        <span>management easier.</span>
                    </h2>

                    <p>
                        The Student Lost &amp; Found Management System
                        is a centralized platform developed to organize
                        the reporting and management of lost and found
                        belongings within the school community.
                    </p>


                    <div class="about-list">

                        <div>
                            <span class="check">
                                ✓
                            </span>
                            Centralized lost and found reports
                        </div>

                        <div>
                            <span class="check">
                                ✓
                            </span>
                            Organized student information
                        </div>

                        <div>
                            <span class="check">
                                ✓
                            </span>
                            Administrative report management
                        </div>

                        <div>
                            <span class="check">
                                ✓
                            </span>
                            Role-based system access
                        </div>

                    </div>

                </div>


                <div class="about-visual">

                    <div class="about-circle circle-one"></div>
                    <div class="about-circle circle-two"></div>

                    <div class="about-icon">

                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="1.7"
                             stroke-linecap="round"
                             stroke-linejoin="round">

                            <circle cx="10.5" cy="10.5" r="7"></circle>
                            <path d="M16 16l5 5"></path>
                            <path d="M10.5 7.5v3l2 2"></path>

                        </svg>

                    </div>

                    <strong>
                        FIND • REPORT • RECONNECT
                    </strong>

                </div>

            </div>

        </div>

    </section>


    <!-- CALL TO ACTION -->

    <section class="cta-section">

        <div class="cta-container">

            <div>

                <span class="eyebrow light">
                    STUDENT LOST &amp; FOUND
                </span>

                <h2>
                    Lost something?
                    <span>Start your report today.</span>
                </h2>

                <p>
                    Make lost and found reporting more organized
                    for everyone in the school community.
                </p>

            </div>


            <div class="cta-buttons">

                <?php if ($is_logged_in): ?>

                    <a href="<?php echo htmlspecialchars($dashboard_link); ?>"
                       class="btn-white">

                        Open Dashboard

                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <path d="M5 12h14"></path>
                            <path d="M13 6l6 6-6 6"></path>

                        </svg>

                    </a>

                <?php else: ?>

                    <a href="authentication/register.php"
                       class="btn-white">

                        Create Account

                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <path d="M5 12h14"></path>
                            <path d="M13 6l6 6-6 6"></path>

                        </svg>

                    </a>

                    <a href="authentication/login.php"
                       class="btn-outline">
                        Sign In
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </section>

</main>


<!-- FOOTER -->

<footer class="footer">

    <div class="footer-container">

        <div class="footer-top">

            <div class="footer-brand">

                <a href="index.php" class="brand">

                    <div class="brand-logo">

                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <circle cx="10.5" cy="10.5" r="7"></circle>
                            <path d="M16 16l5 5"></path>
                            <path d="M10.5 7.5v3l2 2"></path>

                        </svg>

                    </div>

                    <div class="brand-text">

                        <strong>STUDENT L&amp;F</strong>
                        <span>Lost &amp; Found System</span>

                    </div>

                </a>

                <p>
                    A centralized platform for managing
                    student lost and found reports.
                </p>

            </div>


            <div class="footer-column">

                <h4>System</h4>

                <a href="#home">Home</a>
                <a href="#features">Features</a>
                <a href="#how-it-works">How It Works</a>
                <a href="#about">About</a>

            </div>


            <div class="footer-column">

                <h4>Account</h4>

                <?php if ($is_logged_in): ?>

                    <a href="<?php echo htmlspecialchars($dashboard_link); ?>">
                        Dashboard
                    </a>

                <?php else: ?>

                    <a href="authentication/login.php">
                        Login
                    </a>

                    <a href="authentication/register.php">
                        Register
                    </a>

                <?php endif; ?>

            </div>

        </div>


        <div class="footer-bottom">

            <span>
                © 2026 Student Lost &amp; Found Management System.
                All rights reserved.
            </span>

            <span class="footer-status">
                <i></i>
                System Online
            </span>

        </div>

    </div>

</footer>

</body>
</html>