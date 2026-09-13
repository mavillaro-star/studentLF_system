<?php
session_start();
include '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

$users = mysqli_query(
    $conn,
    "SELECT id, fullname, course, year_level, contact_number, email, role
     FROM users
     ORDER BY
        CASE
            WHEN role = 'admin' THEN 1
            WHEN role = 'student' THEN 2
            ELSE 3
        END,
        id ASC"
);

$lost_count = 0;
$found_count = 0;
$modals = [];

$lost_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM lost_items");

if ($lost_result) {
    $lost_data = mysqli_fetch_assoc($lost_result);
    $lost_count = (int)($lost_data['total'] ?? 0);
}

$found_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM found_items");

if ($found_result) {
    $found_data = mysqli_fetch_assoc($found_result);
    $found_count = (int)($found_data['total'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users - Admin Console</title>
<link rel="stylesheet" href="admin.css">

<style>
*{
    box-sizing:border-box;
}

body{
    overflow-x:hidden;
}

.main-content{
    margin-left:260px;
    width:calc(100% - 260px);
    min-width:0;
    min-height:100vh;
}

.content-display{
    width:100%;
    min-width:0;
    padding:30px;
}

.top-bar{
    width:100%;
    display:flex;
    justify-content:flex-end;
    align-items:center;
    margin-bottom:18px;
}

.add-user{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:10px 15px;
    background:#2563eb;
    color:#fff;
    border-radius:7px;
    text-decoration:none;
    font-size:13px;
    font-weight:600;
    white-space:nowrap;
    box-shadow:0 2px 5px rgba(37,99,235,.15);
    transition:.2s ease;
}

.add-user:hover{
    background:#1d4ed8;
    transform:translateY(-1px);
}

.add-user-icon{
    width:19px;
    height:19px;
    display:flex;
    align-items:center;
    justify-content:center;
    border:1.5px solid rgba(255,255,255,.85);
    border-radius:5px;
    font-size:17px;
    line-height:1;
}

.alert-message{
    width:100%;
    padding:13px 16px;
    margin-bottom:18px;
    border-radius:8px;
    font-size:13px;
    font-weight:500;
}

.alert-message.success{
    background:#ecfdf5;
    color:#047857;
    border:1px solid #a7f3d0;
}

.alert-message.error{
    background:#fef2f2;
    color:#b91c1c;
    border:1px solid #fecaca;
}

.table-section{
    width:100%;
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:24px;
    box-shadow:0 2px 8px rgba(15,23,42,.04);
}

.table-section h2{
    margin:0 0 20px;
    font-size:20px;
    color:#111827;
}

.table-responsive{
    width:100%;
    max-width:100%;
    overflow-x:auto;
    overflow-y:hidden;
    -webkit-overflow-scrolling:touch;
    scrollbar-width:thin;
}

.users-table{
    width:100%;
    min-width:950px;
    border-collapse:collapse;
    table-layout:auto;
}

.users-table th{
    padding:13px 14px;
    background:#f8fafc;
    color:#374151;
    text-align:left;
    font-size:12px;
    font-weight:600;
    text-transform:uppercase;
    letter-spacing:.4px;
    border-bottom:1px solid #e5e7eb;
    white-space:nowrap;
}

.users-table td{
    padding:14px;
    color:#374151;
    font-size:14px;
    border-bottom:1px solid #edf0f3;
    vertical-align:middle;
}

.users-table tbody tr:hover{
    background:#f9fafb;
}

.users-table td:first-child{
    color:#6b7280;
    font-weight:600;
}

.users-table strong{
    color:#111827;
}

.users-table td:nth-child(2){
    min-width:170px;
}

.users-table td:nth-child(6){
    min-width:220px;
}

.role-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:5px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
    white-space:nowrap;
}

.role-admin{
    background:#eef2ff;
    color:#4338ca;
}

.role-student{
    background:#ecfdf5;
    color:#047857;
}

.empty-value{
    color:#9ca3af;
}

.action-links{
    display:flex;
    align-items:center;
    gap:6px;
    white-space:nowrap;
}

.action-links a{
    width:32px;
    height:32px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:6px;
    text-decoration:none;
    transition:.2s ease;
}

.view-btn{
    background:#eff6ff;
    color:#2563eb;
}

.view-btn:hover{
    background:#dbeafe;
}

.edit-btn{
    background:#fffbeb;
    color:#d97706;
}

.edit-btn:hover{
    background:#fef3c7;
}

.delete-btn{
    background:#fef2f2;
    color:#dc2626;
}

.delete-btn:hover{
    background:#fee2e2;
}

.view-icon{
    width:15px;
    height:10px;
    border:1.8px solid currentColor;
    border-radius:80% 20%;
    transform:rotate(45deg);
    position:relative;
}

.view-icon:after{
    content:"";
    width:4px;
    height:4px;
    border:1.5px solid currentColor;
    border-radius:50%;
    position:absolute;
    top:1px;
    left:4px;
}

.edit-icon{
    width:14px;
    height:5px;
    border:2px solid currentColor;
    border-radius:2px;
    transform:rotate(-45deg);
}
.delete-icon{
    width:11px;
    height:13px;
    border:1.8px solid currentColor;
    border-top:0;
    border-radius:0 0 2px 2px;
    position:relative;
}

.delete-icon:before{
    content:"";
    position:absolute;
    width:14px;
    height:2px;
    background:currentColor;
    left:-3.5px;
    top:-4px;
}

.delete-icon:after{
    content:"";
    position:absolute;
    width:5px;
    height:2px;
    background:currentColor;
    left:1px;
    top:-7px;
    border-radius:2px 2px 0 0;
}

.empty-table{
    text-align:center;
    color:#6b7280;
    padding:35px 20px !important;
}

.modal{
    display:none;
    position:fixed;
    inset:0;
    z-index:9999;
    align-items:center;
    justify-content:center;
    background:rgba(15,23,42,.55);
    padding:20px;
}

.modal:target{
    display:flex;
}

.modal-content{
    width:100%;
    max-width:400px;
    padding:25px;
    background:#fff;
    border-radius:12px;
    box-shadow:0 15px 40px rgba(0,0,0,.15);
}

.modal-content p{
    margin:0 0 22px;
    color:#374151;
    font-size:15px;
    line-height:1.6;
}

.modal-content strong{
    color:#111827;
}

.modal-actions{
    display:flex;
    justify-content:flex-end;
    gap:8px;
}

.btn-confirm,
.btn-cancel{
    padding:9px 14px;
    border:0;
    border-radius:6px;
    font-size:13px;
    font-weight:600;
    text-decoration:none;
    cursor:pointer;
}

.btn-confirm{
    background:#dc2626;
    color:#fff;
}

.btn-confirm:hover{
    background:#b91c1c;
}

.btn-cancel{
    background:#e5e7eb;
    color:#374151;
}

.btn-cancel:hover{
    background:#d1d5db;
}

@media(max-width:1100px){

    .main-content{
        margin-left:230px;
        width:calc(100% - 230px);
    }

    .content-display{
        padding:25px;
    }

    .table-section{
        padding:22px;
    }
}

@media(max-width:900px){

    .main-content{
        margin-left:210px;
        width:calc(100% - 210px);
    }

    .content-display{
        padding:20px;
    }

    .table-section{
        padding:20px;
    }

    .table-section h2{
        font-size:19px;
    }
}


@media(max-width:700px){

    .main-content{
        margin-left:0;
        width:100%;
        min-height:100vh;
    }

    .content-display{
        width:100%;
        padding:15px;
    }

    .top-bar{
        width:100%;
        margin-bottom:15px;
    }

    .add-user{
        width:auto;
        padding:10px 13px;
        font-size:12px;
    }

    .table-section{
        width:100%;
        padding:16px;
        border-radius:10px;
    }

    .table-section h2{
        font-size:18px;
        margin-bottom:16px;
    }

    .table-responsive{
        margin:0;
        width:100%;
    }

    .users-table{
        min-width:900px;
    }

    .users-table th{
        padding:12px;
        font-size:11px;
    }

    .users-table td{
        padding:12px;
        font-size:13px;
    }

    .modal{
        padding:15px;
    }

    .modal-content{
        max-width:100%;
        padding:20px;
    }
}



@media(max-width:480px){

    .content-display{
        padding:12px;
    }

    .top-bar{
        margin-bottom:12px;
    }

    .add-user{
        width:100%;
        justify-content:center;
    }

    .table-section{
        padding:13px;
        border-radius:9px;
    }

    .table-section h2{
        font-size:17px;
    }

    .users-table{
        min-width:850px;
    }

    .alert-message{
        font-size:12px;
        padding:12px;
    }

    .modal{
        padding:12px;
    }

    .modal-content{
        padding:18px;
        border-radius:10px;
    }

    .modal-content p{
        font-size:14px;
    }

    .modal-actions{
        flex-direction:column-reverse;
    }

    .btn-confirm,
    .btn-cancel{
        width:100%;
        text-align:center;
    }
}
</style>

</head>

<body>

<div class="dashboard-wrapper">

<aside class="sidebar">

<div class="sidebar-brand">
<div class="brand-icon">SLF</div>

<div>
<h2>Lost & Found</h2>
<span>Administrator</span>
</div>
</div>

<nav class="navigation">

<a href="admin_dashboard.php" class="nav-item">
<span class="nav-icon">⌂</span>
<span>Dashboard</span>
</a>

<a href="manage_users.php" class="nav-item active">
<span class="nav-icon">♙</span>
<span>Manage Users</span>
</a>

<a href="view_lost.php" class="nav-item">
<span class="nav-icon">▣</span>
<span>Lost Item Reports</span>

<?php if ($lost_count > 0): ?>
<span class="nav-count"><?php echo $lost_count; ?></span>
<?php endif; ?>

</a>

<a href="view_found.php" class="nav-item">
<span class="nav-icon">⌕</span>
<span>Found Item Reports</span>

<?php if ($found_count > 0): ?>
<span class="nav-count"><?php echo $found_count; ?></span>
<?php endif; ?>

</a>

<a href="admin_profile.php" class="nav-item">
<span class="nav-icon">👤</span>
<span>My Profile</span>
</a>

</nav>

<div class="sidebar-bottom">

<a href="ad_logout_con.php" class="logout-link">
<span>↪</span>
<span>Log Out</span>
</a>

</div>

</aside>

<main class="main-content">

<section class="content-display">

<div class="top-bar">

<a href="add_new_user.php" class="add-user">
<span class="add-user-icon">+</span>
<span>Add New User</span>
</a>

</div>

<?php if (isset($_SESSION['success_message'])): ?>

<div class="alert-message success">
<?php
echo htmlspecialchars($_SESSION['success_message']);
unset($_SESSION['success_message']);
?>
</div>

<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>

<div class="alert-message error">
<?php
echo htmlspecialchars($_SESSION['error_message']);
unset($_SESSION['error_message']);
?>
</div>

<?php endif; ?>

<div class="table-section">

<h2>User Accounts</h2>

<div class="table-responsive">

<table class="users-table">

<thead>

<tr>
<th>ID</th>
<th>Full Name</th>
<th>Course</th>
<th>Year Level</th>
<th>Contact Number</th>
<th>Email</th>
<th>Role</th>
<th>Action</th>
</tr>

</thead>

<tbody>

<?php

if ($users && mysqli_num_rows($users) > 0):

while ($row = mysqli_fetch_assoc($users)):

$id = htmlspecialchars($row['id'] ?? '');
$name = htmlspecialchars($row['fullname'] ?? '');
$course = htmlspecialchars($row['course'] ?? '');
$year = htmlspecialchars($row['year_level'] ?? '');
$contact = htmlspecialchars($row['contact_number'] ?? '');
$email = htmlspecialchars($row['email'] ?? '');
$role = strtolower($row['role'] ?? '');

if ($role === 'admin') {

$role_display = '<span class="role-badge role-admin">Admin</span>';
$course_display = '<span class="empty-value">—</span>';
$year_display = '<span class="empty-value">—</span>';

} else {

$role_display = '<span class="role-badge role-student">Student</span>';

$course_display = $course !== ''
    ? $course
    : '<span class="empty-value">—</span>';

$year_display = $year !== ''
    ? $year
    : '<span class="empty-value">—</span>';

}

?>

<tr>

<td><?php echo $id; ?></td>

<td>
<strong><?php echo $name; ?></strong>
</td>

<td><?php echo $course_display; ?></td>

<td><?php echo $year_display; ?></td>

<td>
<?php echo $contact !== '' ? $contact : '<span class="empty-value">—</span>'; ?>
</td>

<td><?php echo $email; ?></td>

<td><?php echo $role_display; ?></td>

<td>

<div class="action-links">

<a
href="view.php?id=<?php echo $id; ?>"
class="view-btn"
title="View User"
aria-label="View User"
>
<span class="view-icon"></span>
</a>

<a
href="edit_user.php?id=<?php echo $id; ?>"
class="edit-btn"
title="Edit User"
aria-label="Edit User"
>
<span class="edit-icon"></span>
</a>

<a
href="#deleteModal<?php echo $id; ?>"
class="delete-btn"
title="Delete User"
aria-label="Delete User"
>
<span class="delete-icon"></span>
</a>

</div>

</td>

</tr>

<?php

$modals[] = '

<div id="deleteModal'.$id.'" class="modal">

<div class="modal-content">

<p>
Are you sure you want to delete
<strong>'.$name.'</strong>?
</p>

<form action="delete_user.php" method="POST">

<input
type="hidden"
name="id"
value="'.$id.'"
>

<div class="modal-actions">

<button
type="submit"
class="btn-confirm"
>
Yes, Delete
</button>

<a
href="#"
class="btn-cancel"
>
Cancel
</a>

</div>

</form>

</div>

</div>

';

endwhile;

else:

?>

<tr>
<td colspan="8" class="empty-table">
No user accounts found.
</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

</section>

</main>

</div>

<?php

foreach ($modals as $modal) {
echo $modal;
}

?>

</body>
</html>
