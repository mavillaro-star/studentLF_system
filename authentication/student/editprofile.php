<?php
session_start();
include '../../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "student") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT id, fullname, profile_picture, course, year_level,
           contact_number, email, password, role
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

if (!$user) {
    header("Location: students.php");
    exit();
}

$message = "";
$message_type = "";

if (isset($_POST['update'])) {

    $fullname = trim($_POST['fullname'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['password'] ?? '';

    if (
        empty($fullname) ||
        empty($course) ||
        empty($year_level) ||
        empty($email)
    ) {

        $message = "Please complete all required fields.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } else {

        $emailCheck = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            AND id != ?
            LIMIT 1
        ");

        $emailCheck->bind_param("si", $email, $user_id);
        $emailCheck->execute();

        $emailResult = $emailCheck->get_result();

        if ($emailResult->num_rows > 0) {

            $message = "That email address is already being used by another account.";
            $message_type = "error";

        } else {

            $profile_picture = $user['profile_picture'] ?? '';

            if (
                isset($_FILES['profile_picture']) &&
                $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK
            ) {

                $file = $_FILES['profile_picture'];

                $fileSize = $file['size'];
                $fileName = $file['name'];
                $tmpName = $file['tmp_name'];

                $allowedExtensions = [
                    'jpg',
                    'jpeg',
                    'png',
                    'webp'
                ];

                $extension = strtolower(
                    pathinfo($fileName, PATHINFO_EXTENSION)
                );

                if ($fileSize > 2 * 1024 * 1024) {

                    $message = "Profile picture must not exceed 2 MB.";
                    $message_type = "error";

                } elseif (!in_array($extension, $allowedExtensions)) {

                    $message = "Only JPG, JPEG, PNG, and WEBP images are allowed.";
                    $message_type = "error";

                } else {

                    $uploadFolder = "../../uploads/profile_pics/";

                    if (!file_exists($uploadFolder)) {
                        mkdir($uploadFolder, 0777, true);
                    }

                    $newFileName =
                        time() . "_" .
                        uniqid() . "." .
                        $extension;

                    $destination =
                        $uploadFolder . $newFileName;

                    if (move_uploaded_file($tmpName, $destination)) {

                        $profile_picture = $newFileName;

                    } else {

                        $message = "Failed to upload the profile picture.";
                        $message_type = "error";
                    }
                }
            }

            if ($message_type !== "error") {

                if (!empty($new_password)) {

                    if (strlen($new_password) < 6) {

                        $message = "Password must be at least 6 characters.";
                        $message_type = "error";

                    } else {

                        $hashed_password =
                            password_hash(
                                $new_password,
                                PASSWORD_DEFAULT
                            );

                        $stmt = $conn->prepare("
                            UPDATE users
                            SET fullname = ?,
                                course = ?,
                                year_level = ?,
                                contact_number = ?,
                                email = ?,
                                password = ?,
                                profile_picture = ?
                            WHERE id = ?
                        ");

                        $stmt->bind_param(
                            "sssssssi",
                            $fullname,
                            $course,
                            $year_level,
                            $contact_number,
                            $email,
                            $hashed_password,
                            $profile_picture,
                            $user_id
                        );
                    }

                } else {

                    /*
                     * PASSWORD LEFT BLANK
                     * Keep the current password.
                     */

                    $stmt = $conn->prepare("
                        UPDATE users
                        SET fullname = ?,
                            course = ?,
                            year_level = ?,
                            contact_number = ?,
                            email = ?,
                            profile_picture = ?
                        WHERE id = ?
                    ");

                    $stmt->bind_param(
                        "ssssssi",
                        $fullname,
                        $course,
                        $year_level,
                        $contact_number,
                        $email,
                        $profile_picture,
                        $user_id
                    );
                }

                if ($message_type !== "error") {

                    if ($stmt->execute()) {

                        $_SESSION['fullname'] = $fullname;

                        $stmt->close();
                        $emailCheck->close();

                        header("Location: profile.php");
                        exit();

                    } else {

                        $message =
                            "Unable to update your profile. Please try again.";

                        $message_type = "error";

                        $stmt->close();
                    }
                }
            }
        }

        $emailCheck->close();
    }
}

$fullname = $user['fullname'] ?? 'Student';

$profilePic = !empty($user['profile_picture'])
    ? $user['profile_picture']
    : '';

$profileFolder = "../../uploads/profile_pics/";
$mainUploadFolder = "../../uploads/";

$profileServerPath =
    __DIR__ . "/../../uploads/profile_pics/" . $profilePic;

$mainServerPath =
    __DIR__ . "/../../uploads/" . $profilePic;

if (
    !empty($profilePic) &&
    file_exists($profileServerPath)
) {

    $profileImage =
        $profileFolder .
        rawurlencode($profilePic) .
        "?v=" . time();

} elseif (
    !empty($profilePic) &&
    file_exists($mainServerPath)
) {

    $profileImage =
        $mainUploadFolder .
        rawurlencode($profilePic) .
        "?v=" . time();

} else {

    $profileImage = "../../uploads/default.png";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Profile - Student Portal</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
                
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
        }

        body {
            background: #f3f6fa;
            color: #172033;
            overflow-x: hidden;
        }

        a,
        a:visited,
        a:hover,
        a:active {
            text-decoration: none !important;
        }

        #menu-toggle {
            display: none;
        }

        .dashboard {
            width: 100%;
            min-height: 100vh;
            display: flex;
        }

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

            z-index: 200;

            border-right: 1px solid #1f2b43;

            overflow-y: auto;
        }

            .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 2px 8px 25px;
            margin-bottom: 22px;
            border-bottom: 1px solid #2563eb;
            text-decoration: none;
            color: #ffffff;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            border-radius: 12px;
            background: #1d2a44;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4da3ff;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
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

            text-decoration: none !important;

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

        /* ==========================================
           MOBILE MENU
        ========================================== */

        .mobile-menu-btn {
            display: none;

            width: 38px;
            height: 38px;

            border: 1px solid #e2e7ed;

            border-radius: 10px;

            background: #ffffff;

            cursor: pointer;

            align-items: center;
            justify-content: center;

            flex-direction: column;

            gap: 4px;

            flex-shrink: 0;
        }

        .mobile-menu-btn span {
            width: 17px;
            height: 2px;

            background: #172033;

            border-radius: 5px;

            transition: .2s ease;
        }

        .menu-overlay {
            display: none;

            position: fixed;

            inset: 0;

            background: rgba(8, 15, 28, .45);

            opacity: 0;
            visibility: hidden;

            transition: .25s ease;

            z-index: 150;

            cursor: pointer;
        }

        /* ==========================================
           MAIN CONTENT
        ========================================== */

        .main-content {
            margin-left: 230px;

            width: calc(100% - 230px);

            min-height: 100vh;

            display: flex;
            flex-direction: column;
        }

        /* ==========================================
           TOPBAR
        ========================================== */

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

        .topbar-left {
            display: flex;

            align-items: center;

            gap: 12px;

            min-width: 0;
        }

        .topbar-title {
            min-width: 0;
        }

        .topbar-title h1 {
            font-size: 19px;

            color: #172033;

            font-weight: 750;
        }

        .topbar-title p {
            font-size: 11px;

            color: #8793a5;

            margin-top: 3px;
        }

        .user-area {
            display: flex;

            align-items: center;

            gap: 14px;

            flex-shrink: 0;
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

            overflow: hidden;

            box-shadow:
                0 4px 10px
                rgba(29, 111, 197, .18);
        }

        .avatar img {
            width: 100%;
            height: 100%;

            object-fit: cover;
        }

        /* ==========================================
           PAGE CONTENT
        ========================================== */

        .page-content {
            width: 100%;

            padding: 28px 30px 40px;
        }

        .page-header {
            width: 100%;

            margin-bottom: 22px;
        }

        .page-header h2 {
            color: #172033;

            font-size: 21px;

            font-weight: 750;
        }

        .page-header p {
            margin-top: 5px;

            color: #8793a5;

            font-size: 11px;

            line-height: 1.5;
        }

        /* ==========================================
           EDIT CARD
        ========================================== */

        .edit-card {
            width: 100%;

            max-width: 850px;

            margin: 0 auto;

            background: #ffffff;

            border: 1px solid #e3e8ee;

            border-radius: 15px;

            box-shadow:
                0 5px 18px
                rgba(25, 45, 70, .045);

            overflow: hidden;
        }

        .card-header {
            padding: 21px 25px;

            border-bottom: 1px solid #edf0f4;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;
        }

        .card-header h3 {
            color: #172033;

            font-size: 14px;

            font-weight: 750;
        }

        .card-header span {
            color: #8b96a7;

            font-size: 9px;

            text-transform: uppercase;

            letter-spacing: .5px;
        }

        /* ==========================================
           ALERT
        ========================================== */

        .alert {
            margin: 20px 25px 0;

            padding: 11px 13px;

            border-radius: 8px;

            font-size: 10px;

            line-height: 1.5;
        }

        .alert.error {
            background: #fff3f3;

            border: 1px solid #f1d0d0;

            color: #c84d4d;
        }

        .alert.success {
            background: #edf9f4;

            border: 1px solid #ccefe0;

            color: #178760;
        }

        /* ==========================================
           FORM
        ========================================== */

        .edit-form {
            padding: 25px;
        }

        .form-section {
            margin-bottom: 27px;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .section-label {
            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 17px;
        }

        .section-number {
            width: 27px;
            height: 27px;

            border-radius: 8px;

            background: #eaf3fc;

            color: #1d6fc5;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 9px;

            font-weight: 800;
        }

        .section-label h4 {
            color: #172033;

            font-size: 12px;

            font-weight: 750;
        }

        .section-label p {
            color: #9aa5b4;

            font-size: 9px;

            margin-top: 2px;
        }

        /* ==========================================
           PROFILE PHOTO
        ========================================== */

        .photo-area {
            display: flex;

            align-items: center;

            gap: 18px;

            padding: 16px;

            background: #f8fafc;

            border: 1px solid #e8edf2;

            border-radius: 11px;
        }

        .photo-preview {
            width: 78px;
            height: 78px;

            border-radius: 50%;

            overflow: hidden;

            background: #e8f2fc;

            border: 3px solid #ffffff;

            box-shadow:
                0 3px 10px
                rgba(25, 45, 70, .10);

            flex-shrink: 0;

            display: flex;

            align-items: center;
            justify-content: center;

            color: #2468a9;

            font-size: 25px;

            font-weight: 750;
        }

        .photo-preview img {
            width: 100%;
            height: 100%;

            object-fit: cover;
        }

        .photo-info {
            min-width: 0;
        }

        .photo-info strong {
            display: block;

            color: #172033;

            font-size: 11px;

            font-weight: 700;
        }

        .photo-info p {
            color: #8b96a7;

            font-size: 9px;

            line-height: 1.5;

            margin-top: 4px;

            margin-bottom: 9px;
        }

        .file-input {
            width: 100%;

            font-size: 9px;

            color: #718096;
        }

        .file-input::file-selector-button {
            border: 1px solid #dce4ed;

            background: #ffffff;

            color: #31516f;

            border-radius: 7px;

            padding: 7px 10px;

            margin-right: 8px;

            font-size: 9px;

            font-weight: 700;

            cursor: pointer;
        }

        /* ==========================================
           FORM GROUP
        ========================================== */

        .form-group {
            width: 100%;

            margin-bottom: 17px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;

            color: #526174;

            font-size: 10px;

            font-weight: 700;

            margin-bottom: 7px;
        }

        .required {
            color: #e85d5d;
        }

        .form-group input,
        .form-group select {
            width: 100%;

            height: 42px;

            border: 1px solid #dce3eb;

            border-radius: 8px;

            background: #ffffff;

            color: #172033;

            padding: 0 12px;

            outline: none;

            font-size: 11px;

            transition: .2s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #4da3ff;

            box-shadow:
                0 0 0 3px
                rgba(77, 163, 255, .10);
        }

        .form-group input::placeholder {
            color: #a6afbb;
        }

        .form-help {
            display: block;

            color: #9aa5b4;

            font-size: 9px;

            margin-top: 5px;

            line-height: 1.5;
        }

        /* ==========================================
           ACCOUNT INFORMATION
        ========================================== */

        .account-readonly {
            width: 100%;

            min-height: 42px;

            display: flex;

            align-items: center;

            padding: 0 12px;

            background: #f5f7fa;

            border: 1px solid #e2e7ed;

            border-radius: 8px;

            color: #68778b;

            font-size: 11px;

            font-weight: 600;
        }

        .role-badge {
            display: inline-flex;

            align-items: center;

            padding: 5px 10px;

            border-radius: 20px;

            background: #eafaf4;

            color: #12966c;

            font-size: 9px;

            font-weight: 750;

            text-transform: uppercase;

            letter-spacing: .4px;
        }

        /* ==========================================
           ACTION FOOTER
        ========================================== */

        .form-actions {
            margin-top: 28px;

            padding-top: 20px;

            border-top: 1px solid #e5eaf0;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;
        }

        .cancel-link {
            color: #718096;

            font-size: 10px;

            font-weight: 650;

            text-decoration: none !important;

            transition: .2s ease;
        }

        .cancel-link:hover {
            color: #172033;
        }

        .button-group {
            display: flex;

            align-items: center;

            gap: 9px;
        }

        .form-button {
            min-height: 39px;

            padding: 0 17px;

            border-radius: 8px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            font-size: 10px;

            font-weight: 700;

            cursor: pointer;

            text-decoration: none !important;

            transition: .2s ease;
        }

        .cancel-button {
            background: #ffffff;

            color: #718096;

            border: 1px solid #dce3eb;
        }

        .cancel-button:hover {
            background: #f7f9fb;

            color: #172033;
        }

        .save-button {
            background: #1d6fc5;

            color: #ffffff;

            border: 1px solid #1d6fc5;
        }

        .save-button:hover {
            background: #155da7;

            border-color: #155da7;

            transform: translateY(-1px);
        }

        /* ==========================================
           TABLET
        ========================================== */

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

            .topbar {
                padding: 0 23px;
            }

        }

        /* ==========================================
           MOBILE
        ========================================== */

        @media screen and (max-width: 760px) {

            body {
                overflow-x: hidden;
            }

            .dashboard {
                display: block;

                min-height: 100vh;
            }

            .mobile-menu-btn {
                display: flex;
            }

            .sidebar {
                position: fixed;

                top: 0;
                left: 0;

                width: 270px;
                height: 100vh;

                transform: translateX(-100%);

                transition:
                    transform .25s ease;

                box-shadow:
                    8px 0 25px
                    rgba(0, 0, 0, .18);

                z-index: 200;
            }

            #menu-toggle:checked
            ~ .dashboard
            .sidebar {
                transform: translateX(0);
            }

            #menu-toggle:checked
            ~ .menu-overlay {
                opacity: 1;

                visibility: visible;
            }

            .menu-overlay {
                display: block;
            }

            .main-content {
                margin-left: 0;

                width: 100%;

                min-height: 100vh;
            }

            .topbar {
                min-height: 65px;

                padding: 0 17px;
            }

            .topbar-title h1 {
                font-size: 16px;

                white-space: nowrap;

                overflow: hidden;

                text-overflow: ellipsis;
            }

            .topbar-title p {
                display: none;
            }

            .user-info,
            .user-divider {
                display: none;
            }

            .user-area {
                gap: 8px;
            }

            .notification {
                width: 34px;
                height: 34px;
            }

            .avatar {
                width: 35px;
                height: 35px;
            }

            .page-content {
                padding: 19px 17px 30px;
            }

            .page-header {
                margin-bottom: 17px;
            }

            .page-header h2 {
                font-size: 18px;
            }

            .page-header p {
                font-size: 10px;
            }

            .edit-card {
                border-radius: 13px;
            }

            .card-header {
                padding: 17px 19px;
            }

            .edit-form {
                padding: 20px 19px;
            }

            .photo-area {
                align-items: flex-start;
            }

            .form-actions {
                flex-direction: column;

                align-items: stretch;
            }

            .cancel-link {
                text-align: center;
            }

            .button-group {
                width: 100%;
            }

            .form-button {
                flex: 1;
            }

        }

        @media screen and (max-width: 430px) {

            .sidebar {
                width: 285px;
            }

            .topbar {
                padding: 0 14px;
            }

            .topbar-title h1 {
                font-size: 14px;
            }

            .page-content {
                padding: 15px 13px 25px;
            }

            .page-header h2 {
                font-size: 17px;
            }

            .page-header p {
                font-size: 9px;
            }

            .card-header {
                padding: 15px 16px;
            }

            .edit-form {
                padding: 18px 16px;
            }

            .photo-area {
                padding: 13px;
                gap: 13px;
            }

            .photo-preview {
                width: 65px;
                height: 65px;
            }

            .photo-info p {
                font-size: 8px;
            }

            .file-input {
                font-size: 8px;
            }

        }

        @media screen and (max-width: 350px) {

            .photo-area {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .photo-info {
                width: 100%;
            }

            .button-group {
                flex-direction: column;
            }

            .form-button {
                width: 100%;
            }

        }

    </style>

    <link rel="stylesheet" href="../../responsive.css">
</head>

<body>

<input
    type="checkbox"
    id="menu-toggle"
>

<div class="dashboard">

    <aside class="sidebar">
        <a href="student_dashboard.php" class="sidebar-brand">
    <div class="brand-icon">SLF</div>

    <div class="brand-text">
        <strong>LOST &amp; FOUND</strong>
        <span>STUDENT PORTAL</span>
    </div>
        </a>
        

        <div class="nav-title">
            Main Menu
        </div>

        <ul class="nav-menu">

            <li>

                <a
                    href="student_dashboard.php"
                    class="nav-item"
                >

                    <span class="nav-icon">
                        ⌂
                    </span>

                    <span>
                        Dashboard
                    </span>

                </a>

            </li>

            <li>

                <a
                    href="report_lost.php"
                    class="nav-item nav-lost"
                >

                    <span class="nav-icon">
                        ⌕
                    </span>

                    <span>
                        Report Lost Item
                    </span>

                </a>

            </li>

            <li>

                <a
                    href="report_found.php"
                    class="nav-item nav-found"
                >

                    <span class="nav-icon">
                        □
                    </span>

                    <span>
                        Report Found Item
                    </span>

                </a>

            </li>

            <li>

                <a
                    href="my_reports.php"
                    class="nav-item"
                >

                    <span class="nav-icon">
                        ▤
                    </span>

                    <span>
                        My Reports
                    </span>

                </a>

            </li>

            <li>

                <a
                    href="profile.php"
                    class="nav-item active"
                >

                    <span class="nav-icon">
                        ○
                    </span>

                    <span>
                        Profile
                    </span>

                </a>

            </li>

        </ul>

        <div class="sidebar-bottom">

            <a
                href="../../authentication/logout.php"
                class="nav-item"
                onclick="return confirmLogout();"
            >

                <span class="nav-icon">
                    ↪
                </span>

                <span>
                    Log Out
                </span>

            </a>

        </div>

    </aside>

    <label
        for="menu-toggle"
        class="menu-overlay"
    ></label>

    <main class="main-content">

        <header class="topbar">

            <div class="topbar-left">

                <label
                    for="menu-toggle"
                    class="mobile-menu-btn"
                >

                    <span></span>
                    <span></span>
                    <span></span>

                </label>

                <div class="topbar-title">

                    <h1>
                        Edit Profile
                    </h1>

                    <p>
                        Update your student account information.
                    </p>

                </div>
            </div>
        </header>

        <div class="page-content">

            <div class="page-header">

                <h2>
                    Account Settings
                </h2>

                <p>
                    Update your personal information and account security.
                </p>

            </div>

            <section class="edit-card">

                <div class="card-header">

                    <h3>
                        Edit Student Profile
                    </h3>

                    <span>
                        Account #<?php echo htmlspecialchars($user['id']); ?>
                    </span>

                </div>

                <?php if (!empty($message)): ?>

                    <div class="alert <?php echo $message_type; ?>">

                        <?php
                        echo htmlspecialchars($message);
                        ?>

                    </div>

                <?php endif; ?>

                <form
                    method="POST"
                    enctype="multipart/form-data"
                    class="edit-form"
                >

                    <div class="form-section">

                        <div class="section-label">

                            <div class="section-number">
                                01
                            </div>

                            <div>

                                <h4>
                                    Profile Picture
                                </h4>

                                <p>
                                    Update the photo displayed on your account.
                                </p>

                            </div>

                        </div>

                        <div class="photo-area">

                            <div class="photo-preview">

                                <?php if (
                                    !empty($profilePic) &&
                                    (
                                        file_exists($profileServerPath) ||
                                        file_exists($mainServerPath)
                                    )
                                ): ?>

                                    <img
                                        id="profilePreview"
                                        src="<?php echo htmlspecialchars($profileImage); ?>"
                                        alt="Profile Picture"
                                    >

                                <?php else: ?>

                                    <span id="profileInitial">
                                        <?php
                                        echo htmlspecialchars($studentInitial);
                                        ?>
                                    </span>

                                    <img
                                        id="profilePreview"
                                        src=""
                                        alt="Profile Picture"
                                        style="display:none;"
                                    >

                                <?php endif; ?>

                            </div>

                            <div class="photo-info">

                                <strong>
                                    Change profile picture
                                </strong>

                                <p>
                                    JPG, JPEG, PNG or WEBP. Maximum file size is 2 MB.
                                </p>

                                <input
                                    type="file"
                                    name="profile_picture"
                                    class="file-input"
                                    accept=".jpg,.jpeg,.png,.webp"
                                    onchange="previewImage(this);"
                                >

                            </div>

                        </div>

                    </div>

                    <div class="form-section">

                        <div class="section-label">

                            <div class="section-number">
                                02
                            </div>

                            <div>

                                <h4>
                                    Personal Information
                                </h4>

                                <p>
                                    Keep your student information accurate.
                                </p>

                            </div>

                        </div>

                        <div class="form-group">

                            <label>
                                Full Name
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                name="fullname"
                                value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>"
                                placeholder="Enter your full name"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label>
                                Course
                                <span class="required">*</span>
                            </label>

                            <select
                                name="course"
                                required
                            >

                                <option value="">
                                    Select Course
                                </option>

                                <option
                                    value="BEED"
                                    <?php
                                    echo ($user['course'] ?? '') === 'BEED'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    BEED
                                </option>

                                <option
                                    value="BSMathematics"
                                    <?php
                                    echo ($user['course'] ?? '') === 'BSMathematics'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    BSMathematics
                                </option>

                                <option
                                    value="BSSocialStudies"
                                    <?php
                                    echo ($user['course'] ?? '') === 'BSSocialStudies'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    BSSocialStudies
                                </option>

                                <option
                                    value="BSIT"
                                    <?php
                                    echo ($user['course'] ?? '') === 'BSIT'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    BSIT
                                </option>

                                <option
                                    value="BSEntrep"
                                    <?php
                                    echo ($user['course'] ?? '') === 'BSEntrep'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    BSEntrep
                                </option>

                            </select>

                        </div>

                        <div class="form-group">

                            <label>
                                Year Level
                                <span class="required">*</span>
                            </label>

                            <select
                                name="year_level"
                                required
                            >

                                <option value="">
                                    Select Year Level
                                </option>

                                <option
                                    value="1st Yr"
                                    <?php
                                    echo ($user['year_level'] ?? '') === '1st Yr'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    1st Yr
                                </option>

                                <option
                                    value="2nd Yr"
                                    <?php
                                    echo ($user['year_level'] ?? '') === '2nd Yr'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    2nd Yr
                                </option>

                                <option
                                    value="3rd Yr"
                                    <?php
                                    echo ($user['year_level'] ?? '') === '3rd Yr'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    3rd Yr
                                </option>

                                <option
                                    value="4th Yr"
                                    <?php
                                    echo ($user['year_level'] ?? '') === '4th Yr'
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    4th Yr
                                </option>

                            </select>

                        </div>

                        <div class="form-group">

                            <label>
                                Contact Number
                            </label>

                            <input
                                type="text"
                                name="contact_number"
                                value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>"
                                placeholder="Enter your contact number"
                            >

                        </div>

                        <div class="form-group">

                            <label>
                                Email Address
                                <span class="required">*</span>
                            </label>

                            <input
                                type="email"
                                name="email"
                                value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                placeholder="Enter your email address"
                                required
                            >

                        </div>

                    </div>

                    <div class="form-section">

                        <div class="section-label">

                            <div class="section-number">
                                03
                            </div>

                            <div>

                                <h4>
                                    Account Security
                                </h4>

                                <p>
                                    Change your password when necessary.
                                </p>

                            </div>

                        </div>

                        <div class="form-group">

                            <label>
                                New Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                placeholder="Leave blank to keep current password"
                            >

                            <span class="form-help">
                                Leave this field blank if you do not want to change your password.
                                New passwords must contain at least 6 characters.
                            </span>

                        </div>

                        <div class="form-group">

                            <label>
                                Account Role
                            </label>

                            <div class="account-readonly">

                                <span class="role-badge">
                                    Student
                                </span>

                            </div>

                            <span class="form-help">
                                Your system role cannot be changed from this page.
                            </span>

                        </div>

                    </div>

                    <div class="form-actions">

                        <div class="button-group">
                            <button
                                type="submit"
                                name="update"
                                class="form-button save-button"
                            >
                                Update Profile
                            </button>

                            <a
                                href="profile.php"
                                class="form-button cancel-button"
                            >
                                Cancel
                            </a>
                        </div>

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