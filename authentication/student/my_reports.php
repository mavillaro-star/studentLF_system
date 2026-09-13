<?php 
session_start(); 
include '../../db.php'; 
 
// Check if user is logged in 
if (!isset($_SESSION['user_id'])) { 
    header("Location: ../login.php"); 
    exit(); 
} 
 
// Check if user is a student 
if ($_SESSION['role'] != "student") { 
    header("Location: ../login.php"); 
    exit(); 
} 
 
$user_id = $_SESSION['user_id']; 
 
$fullname = isset($_SESSION['fullname']) 
    ? $_SESSION['fullname'] 
    : 'Student'; 
 
$role = isset($_SESSION['role']) 
    ? $_SESSION['role'] 
    : 'Student'; 
 
 
// Get notifications for this student 
$notifications_query = mysqli_query( 
    $conn, 
    "SELECT * 
     FROM notifications 
     WHERE user_id = '$user_id' 
     ORDER BY created_at DESC 
     LIMIT 5" 
); 
 
 
// Get Lost Reports 
$lost = mysqli_query( 
    $conn, 
    "SELECT * 
     FROM lost_items 
     WHERE user_id = '$user_id' 
     ORDER BY id DESC" 
); 
 
 
// Get Found Reports 
$found = mysqli_query( 
    $conn, 
    "SELECT * 
     FROM found_items 
     WHERE user_id = '$user_id' 
     ORDER BY id DESC" 
); 
 
?> 
 
<!DOCTYPE html> 
 
<html lang="en"> 
 
<head> 
 
<meta charset="UTF-8"> 
 
<meta 
    name="viewport" 
    content="width=device-width, initial-scale=1.0" 
> 
 
<title>My Reports</title> 
 
 
<style> 
 
    /* ========================================== 
       RESET 
    ========================================== */ 
 
    * { 
        margin: 0; 
        padding: 0; 
        box-sizing: border-box; 
 
        font-family: 
            -apple-system, 
            BlinkMacSystemFont, 
            "Segoe UI", 
            Arial, 
            sans-serif; 
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
 
 
    /* ========================================== 
       MAIN LAYOUT 
    ========================================== */ 
 
    .dashboard { 
        width: 100%; 
        min-height: 100vh; 
 
        display: flex; 
    } 
 
 
    /* ========================================== 
       MOBILE MENU CHECKBOX 
    ========================================== */ 
 
    #menu-toggle { 
        display: none; 
    } 
 
 
    /* ========================================== 
       SIDEBAR 
    ========================================== */ 
 
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
 
 
    .brand-link { 
        text-decoration: none; 
        color: inherit; 
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
 
        flex-shrink: 0; 
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
 
 
    /* ========================================== 
       MOBILE HAMBURGER 
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
 
 
    /* ========================================== 
       MENU OVERLAY 
    ========================================== */ 
 
    .menu-overlay { 
        display: none; 
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
 
        background: #f3f6fa; 
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
 
        box-shadow: 
            0 4px 10px 
            rgba(29, 111, 197, .18); 
    } 
 
 
    /* ========================================== 
       PAGE CONTENT 
    ========================================== */ 
 
    .page-content { 
        width: 100%; 
 
        padding: 28px 30px 35px; 
    } 
 
 
    .page-intro { 
        display: flex; 
        align-items: flex-end; 
        justify-content: space-between; 
 
        gap: 20px; 
 
        margin-bottom: 20px; 
    } 
 
 
    .page-intro h2 { 
        font-size: 20px; 
 
        color: #172033; 
 
        font-weight: 750; 
 
        margin-bottom: 5px; 
    } 
 
 
    .page-intro p { 
        font-size: 11px; 
 
        color: #8793a5; 
 
        line-height: 1.6; 
    } 
 
 
    /* ========================================== 
       REPORT SUMMARY 
    ========================================== */ 
 
    .report-summary { 
        display: grid; 
 
        grid-template-columns: 
            repeat(3, minmax(0, 1fr)); 
 
        gap: 14px; 
 
        margin-bottom: 22px; 
    } 
 
 
    .summary-card { 
        background: #ffffff; 
 
        border: 1px solid #e5eaf0; 
 
        border-radius: 13px; 
 
        min-height: 92px; 
 
        padding: 15px 17px; 
 
        display: flex; 
        align-items: center; 
 
        gap: 13px; 
 
        position: relative; 
 
        overflow: hidden; 
    } 
 
 
    .summary-icon { 
        width: 42px; 
        height: 42px; 
 
        border-radius: 11px; 
 
        display: flex; 
        align-items: center; 
        justify-content: center; 
 
        font-size: 16px; 
 
        flex-shrink: 0; 
    } 
 
 
    .summary-icon.blue { 
        background: #edf6ff; 
        color: #3d91dc; 
    } 
 
 
    .summary-icon.red { 
        background: #fff0f0; 
        color: #ef5555; 
    } 
 
 
    .summary-icon.green { 
        background: #eafaf4; 
        color: #13a976; 
    } 
 
 
    .summary-info { 
        display: flex; 
        flex-direction: column; 
    } 
 
 
    .summary-number { 
        font-size: 21px; 
 
        line-height: 1; 
 
        font-weight: 800; 
 
        color: #172033; 
    } 
 
 
    .summary-label { 
        font-size: 9px; 
 
        color: #8793a5; 
 
        margin-top: 5px; 
    } 
 
 
    /* ========================================== 
       GENERAL PANEL 
    ========================================== */ 
 
    .panel { 
        background: #ffffff; 
 
        border: 1px solid #e5eaf0; 
 
        border-radius: 14px; 
 
        overflow: hidden; 
 
        margin-bottom: 22px; 
    } 
 
 
    .panel-header { 
        min-height: 67px; 
 
        display: flex; 
        align-items: center; 
        justify-content: space-between; 
 
        gap: 15px; 
 
        padding: 15px 19px; 
 
        border-bottom: 1px solid #edf0f4; 
    } 
 
 
    .panel-title { 
        display: flex; 
        align-items: center; 
 
        gap: 11px; 
    } 
 
 
    .panel-icon { 
        width: 36px; 
        height: 36px; 
 
        border-radius: 9px; 
 
        display: flex; 
        align-items: center; 
        justify-content: center; 
 
        font-size: 14px; 
 
        flex-shrink: 0; 
    } 
 
 
    .panel-icon.notification-icon { 
        background: #edf6ff; 
 
        color: #3d91dc; 
    } 
 
 
    .panel-icon.lost-icon { 
        background: #fff0f0; 
 
        color: #ef5555; 
    } 
 
 
    .panel-icon.found-icon { 
        background: #eafaf4; 
 
        color: #13a976; 
    } 
 
 
    .panel-title h3 { 
        font-size: 13px; 
 
        font-weight: 750; 
 
        color: #172033; 
    } 
 
 
    .panel-title p { 
        font-size: 9px; 
 
        color: #8b96a7; 
 
        margin-top: 3px; 
    } 
 
 
    .panel-count { 
        font-size: 9px; 
 
        color: #8b96a7; 
 
        background: #f4f6f8; 
 
        border-radius: 6px; 
 
        padding: 5px 8px; 
 
        white-space: nowrap; 
    } 
 
 
    /* ========================================== 
       NOTIFICATIONS 
    ========================================== */ 
 
    .notification-list { 
        padding: 4px 19px; 
    } 
 
 
    .notification-item { 
        display: flex; 
        align-items: flex-start; 
 
        gap: 11px; 
 
        padding: 13px 0; 
 
        border-bottom: 1px solid #edf0f4; 
    } 
 
 
    .notification-item:last-child { 
        border-bottom: none; 
    } 
 
 
    .notification-dot { 
        width: 8px; 
        height: 8px; 
 
        background: #4da3ff; 
 
        border-radius: 50%; 
 
        margin-top: 5px; 
 
        flex-shrink: 0; 
 
        box-shadow: 
            0 0 0 4px #eaf4ff; 
    } 
 
 
    .notification-message { 
        font-size: 10px; 
 
        color: #344054; 
 
        line-height: 1.5; 
    } 
 
 
    .notification-time { 
        display: block; 
 
        font-size: 8px; 
 
        color: #a0a9b5; 
 
        margin-top: 4px; 
    } 
 
 
    .empty-message { 
        padding: 22px 0; 
 
        text-align: center; 
 
        font-size: 10px; 
 
        color: #9aa5b4; 
    } 
 
 
    /* ========================================== 
       TABLE WRAPPER 
    ========================================== */ 
 
    .table-wrapper { 
        width: 100%; 
 
        overflow-x: auto; 
 
        -webkit-overflow-scrolling: touch; 
    } 
 
 
    .report-table { 
        width: 100%; 
 
        min-width: 900px; 
 
        border-collapse: collapse; 
 
        text-align: left; 
    } 
 
 
    .report-table th { 
        background: #f7f9fb; 
 
        color: #687588; 
 
        font-size: 9px; 
 
        font-weight: 750; 
 
        text-transform: uppercase; 
 
        letter-spacing: .4px; 
 
        padding: 12px 15px; 
 
        border-bottom: 1px solid #e6ebf0; 
 
        white-space: nowrap; 
    } 
 
 
    .report-table td { 
        padding: 13px 15px; 
 
        border-bottom: 1px solid #edf0f4; 
 
        color: #4b5565; 
 
        font-size: 10px; 
 
        vertical-align: middle; 
    } 
 
 
    .report-table tbody tr:last-child td { 
        border-bottom: none; 
    } 
 
 
    .report-table tbody tr { 
        transition: .2s ease; 
    } 
 
 
    .report-table tbody tr:hover { 
        background: #fafcfe; 
    } 
 
 
    .report-id { 
        color: #8a96a5; 
 
        font-weight: 700; 
    } 
 
 
    .item-name { 
        color: #172033; 
 
        font-weight: 700; 
    } 
 
 
    .description-cell { 
        max-width: 230px; 
 
        color: #7b8797; 
 
        line-height: 1.5; 
    } 
 
 
    .location-cell { 
        color: #64748b; 
 
        max-width: 150px; 
    } 
 
 
    /* ========================================== 
       IMAGE 
    ========================================== */ 
 
    .report-image { 
        width: 55px; 
        height: 45px; 
 
        border-radius: 7px; 
 
        object-fit: cover; 
 
        display: block; 
 
        border: 1px solid #e4e9ef; 
 
        background: #f4f7fa; 
    } 
 
 
    .no-image { 
        color: #a1aab6; 
 
        font-size: 9px; 
 
        white-space: nowrap; 
    } 
 
 
    /* ========================================== 
       STATUS 
    ========================================== */ 
 
    .status-badge { 
        display: inline-flex; 
 
        align-items: center; 
        justify-content: center; 
 
        padding: 5px 9px; 
 
        border-radius: 6px; 
 
        font-size: 8px; 
 
        font-weight: 750; 
 
        text-transform: uppercase; 
 
        letter-spacing: .3px; 
 
        white-space: nowrap; 
    } 
 
 
    .status-pending { 
        background: #fff7e8; 
 
        color: #d99016; 
    } 
 
 
    .status-matched { 
        background: #edf6ff; 
 
        color: #347fc0; 
    } 
 
 
    .status-claimed { 
        background: #eafaf4; 
 
        color: #109b6d; 
    } 
 
 
    .status-found { 
        background: #eafaf4; 
 
        color: #109b6d; 
    } 
 
 
    .status-lost { 
        background: #fff0f0; 
 
        color: #d94d4d; 
    } 
 
 
    .status-default { 
        background: #f1f4f7; 
 
        color: #697586; 
    } 
 
 
    /* ========================================== 
       ACTIONS 
    ========================================== */ 
 
    .action-buttons { 
        display: flex; 
 
        align-items: center; 
 
        gap: 6px; 
 
        white-space: nowrap; 
    } 
 
 
    .action-link { 
        width: 32px; 
        height: 32px; 
 
        display: flex; 
        align-items: center; 
        justify-content: center; 
 
        border-radius: 6px; 
 
        text-decoration: none; 
 
        transition: .2s ease; 
    } 
 
 
    .edit-action { 
        background: #fffbeb; 
 
        color: #d97706; 
    } 
 
 
    .edit-action:hover { 
        background: #fef3c7; 
    } 
 
 
    .delete-action { 
        background: #fef2f2; 
 
        color: #dc2626; 
    } 
 
 
    .delete-action:hover { 
        background: #fee2e2; 
    } 
 
 
    .edit-icon { 
        width: 14px; 
        height: 5px; 
 
        border: 2px solid currentColor; 
 
        border-radius: 2px; 
 
        transform: rotate(-45deg); 
    } 
 
 
    .delete-icon { 
        width: 11px; 
        height: 13px; 
 
        border: 1.8px solid currentColor; 
 
        border-top: 0; 
 
        border-radius: 0 0 2px 2px; 
 
        position: relative; 
    } 
 
 
    .delete-icon:before { 
        content: ""; 
 
        position: absolute; 
 
        width: 14px; 
        height: 2px; 
 
        background: currentColor; 
 
        left: -3.5px; 
        top: -4px; 
    } 
 
 
    .delete-icon:after { 
        content: ""; 
 
        position: absolute; 
 
        width: 5px; 
        height: 2px; 
 
        background: currentColor; 
 
        left: 1px; 
        top: -7px; 
 
        border-radius: 2px 2px 0 0; 
    } 
 
 
    /* ========================================== 
       EMPTY TABLE 
    ========================================== */ 
 
    .empty-table { 
        text-align: center; 
 
        padding: 35px 15px !important; 
 
        color: #9aa5b4 !important; 
 
        font-size: 10px !important; 
    } 
 
 
    /* ========================================== 
       BACK BUTTON 
    ========================================== */ 
 
    .bottom-actions { 
        display: flex; 
 
        justify-content: flex-start; 
 
        margin-top: 3px; 
    } 
 
 
    .back-btn { 
        min-height: 39px; 
 
        padding: 0 15px; 
 
        border-radius: 9px; 
 
        display: inline-flex; 
 
        align-items: center; 
        justify-content: center; 
 
        gap: 7px; 
 
        background: #172b49; 
 
        color: #ffffff; 
 
        text-decoration: none; 
 
        font-size: 10px; 
 
        font-weight: 700; 
 
        transition: .2s ease; 
    } 
 
 
    .back-btn:hover { 
        background: #1e385e; 
 
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
 
 
        .report-summary { 
            grid-template-columns: 
                repeat(3, minmax(0, 1fr)); 
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
 
 
        /* Hamburger visible */ 
 
        .mobile-menu-btn { 
            display: flex; 
        } 
 
 
        /* Sidebar hidden */ 
 
        .sidebar { 
            position: fixed; 
 
            top: 0; 
            left: 0; 
 
            width: 270px; 
            height: 100vh; 
 
            transform: 
                translateX(-100%); 
 
            transition: 
                transform .25s ease; 
 
            box-shadow: 
                8px 0 25px 
                rgba(0, 0, 0, .18); 
 
            z-index: 200; 
        } 
 
 
        /* Open sidebar */ 
 
        #menu-toggle:checked 
        ~ .dashboard 
        .sidebar { 
 
            transform: 
                translateX(0); 
        } 
 
 
        /* Overlay */ 
 
        .menu-overlay { 
            display: block; 
 
            position: fixed; 
 
            inset: 0; 
 
            background: 
                rgba(8, 15, 28, .45); 
 
            opacity: 0; 
 
            visibility: hidden; 
 
            transition: .25s ease; 
 
            z-index: 150; 
 
            cursor: pointer; 
        } 
 
 
        #menu-toggle:checked 
        ~ .menu-overlay { 
 
            opacity: 1; 
 
            visibility: visible; 
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
            padding: 18px; 
        } 
 
 
        .page-intro { 
            display: block; 
 
            margin-bottom: 18px; 
        } 
 
 
        .page-intro h2 { 
            font-size: 18px; 
        } 
 
 
        .page-intro p { 
            margin-top: 3px; 
        } 
 
 
        .report-summary { 
            grid-template-columns: 1fr; 
 
            gap: 10px; 
 
            margin-bottom: 18px; 
        } 
 
 
        .summary-card { 
            min-height: 78px; 
        } 
 
 
        .panel { 
            border-radius: 12px; 
 
            margin-bottom: 18px; 
        } 
 
 
        .panel-header { 
            padding: 14px 15px; 
        } 
 
 
        .notification-list { 
            padding: 4px 15px; 
        } 
 
 
        .panel-title h3 { 
            font-size: 12px; 
        } 
 
 
        .panel-title p { 
            display: none; 
        } 
 
 
        .panel-count { 
            font-size: 8px; 
        } 
 
 
        .table-wrapper { 
            overflow-x: auto; 
        } 
 
 
        .report-table { 
            min-width: 850px; 
        } 
 
 
        .bottom-actions { 
            width: 100%; 
        } 
 
 
        .back-btn { 
            width: 100%; 
        } 
 
    } 
 
 
    /* ========================================== 
       VERY SMALL PHONES 
    ========================================== */ 
 
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
            padding: 14px; 
        } 
 
 
        .page-intro h2 { 
            font-size: 17px; 
        } 
 
 
        .page-intro p { 
            font-size: 10px; 
        } 
 
 
        .summary-card { 
            padding: 13px 14px; 
        } 
 
 
        .summary-number { 
            font-size: 20px; 
        } 
 
 
        .panel-header { 
            min-height: 61px; 
 
            padding: 13px; 
        } 
 
 
        .panel-icon { 
            width: 34px; 
            height: 34px; 
        } 
 
 
        .notification-list { 
            padding: 3px 13px; 
        } 
 
 
        .notification-item { 
            padding: 12px 0; 
        } 
 
 
        .report-table { 
            min-width: 820px; 
        } 
 
    } 
 
</style> 
</head> 
 
<body> 
 
<input type="checkbox" id="menu-toggle"> 
 
<div class="dashboard"> 
 
<aside class="sidebar"> 
 
 
    <a 
        href="students.php" 
        class="brand-link" 
    > 
 
        <div class="brand"> 
 
            <div class="brand-mark"></div> 
 
            <div class="brand-text"> 
 
                <strong> 
                    LOST &amp; FOUND 
                </strong> 
 
                <span> 
                    STUDENT PORTAL 
                </span> 
 
            </div> 
 
        </div> 
 
    </a> 
 
 
    <div class="nav-title"> 
        Main Menu 
    </div> 
 
 
    <ul class="nav-menu"> 
 
 
        <li> 
 
            <a 
                href="students.php" 
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
                class="nav-item active" 
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
                class="nav-item" 
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
                    My Reports 
                </h1> 
 
                <p> 
                    View and manage the lost and found 
                    items you have reported. 
                </p> 
 
            </div> 
 
 
        </div> 
 
 
        <div class="user-area"> 
 
 
            <div class="notification"> 
                ♢ 
            </div> 
 
 
            <div class="user-divider"></div> 
 
 
            <div class="user-info"> 
 
                <span class="user-name"> 
 
                    <?php 
                    echo htmlspecialchars($fullname); 
                    ?> 
 
                </span> 
 
 
                <span class="user-role"> 
                    Student Account 
                </span> 
 
            </div> 
 
 
            <div class="avatar"> 
 
                <?php 
                echo strtoupper( 
                    substr($fullname, 0, 1) 
                ); 
                ?> 
 
            </div> 
 
 
        </div> 
 
 
    </header> 
 
 
    <!-- ========================================== 
         PAGE CONTENT 
    ========================================== --> 
 
    <div class="page-content"> 
 
 
        <div class="page-intro"> 
 
            <h2> 
                My Reports 
            </h2> 
 
            <p> 
                Keep track of the items you have reported 
                and check their current status. 
            </p> 
 
        </div> 
 
 
        <!-- ========================================== 
             REPORT SUMMARY 
        ========================================== --> 
 
        <?php 
 
        $lost_count = mysqli_num_rows($lost); 
 
        $found_count = mysqli_num_rows($found); 
 
        $total_reports = 
            $lost_count + $found_count; 
 
        ?> 
 
 
        <div class="report-summary"> 
 
 
            <div class="summary-card"> 
 
 
                <div class="summary-icon blue"> 
                    ▤ 
                </div> 
 
 
                <div class="summary-info"> 
 
                    <span class="summary-number"> 
                        <?php echo $total_reports; ?> 
                    </span> 
 
                    <span class="summary-label"> 
                        Total Reports 
                    </span> 
 
                </div> 
 
 
            </div> 
 
 
            <div class="summary-card"> 
 
 
                <div class="summary-icon red"> 
                    ⌕ 
                </div> 
 
 
                <div class="summary-info"> 
 
                    <span class="summary-number"> 
                        <?php echo $lost_count; ?> 
                    </span> 
 
                    <span class="summary-label"> 
                        Lost Reports 
                    </span> 
 
                </div> 
 
 
            </div> 
 
 
            <div class="summary-card"> 
 
 
                <div class="summary-icon green"> 
                    □ 
                </div> 
 
 
                <div class="summary-info"> 
 
                    <span class="summary-number"> 
                        <?php echo $found_count; ?> 
                    </span> 
 
                    <span class="summary-label"> 
                        Found Reports 
                    </span> 
 
                </div> 
 
 
            </div> 
 
 
        </div> 
 
 
        <!-- ========================================== 
             NOTIFICATIONS 
        ========================================== --> 
 
        <section class="panel"> 
 
 
            <div class="panel-header"> 
 
 
                <div class="panel-title"> 
 
 
                    <div class="panel-icon notification-icon"> 
                        ♢ 
                    </div> 
 
 
                    <div> 
 
                        <h3> 
                            My Notifications 
                        </h3> 
 
                        <p> 
                            Recent updates about your reports 
                        </p> 
 
                    </div> 
 
 
                </div> 
 
 
                <span class="panel-count"> 
                    Latest 5 
                </span> 
 
 
            </div> 
 
 
            <div class="notification-list"> 
 
 
                <?php 
                if ( 
                    mysqli_num_rows( 
                        $notifications_query 
                    ) > 0 
                ): 
                ?> 
 
 
                    <?php 
                    while ( 
                        $notif = 
                        mysqli_fetch_assoc( 
                            $notifications_query 
                        ) 
                    ): 
                    ?> 
 
 
                        <div class="notification-item"> 
 
 
                            <span class="notification-dot"></span> 
 
 
                            <div> 
 
 
                                <div class="notification-message"> 
 
                                    <?php 
                                    echo htmlspecialchars( 
                                        $notif['message'] 
                                    ); 
                                    ?> 
 
                                </div> 
 
 
                                <span class="notification-time"> 
 
                                    <?php 
                                    echo date( 
                                        "M d, Y h:i A", 
                                        strtotime( 
                                            $notif['created_at'] 
                                        ) 
                                    ); 
                                    ?> 
 
                                </span> 
 
 
                            </div> 
 
 
                        </div> 
 
 
                    <?php endwhile; ?> 
 
 
                <?php else: ?> 
 
 
                    <div class="empty-message"> 
 
                        No new notifications. 
 
                    </div> 
 
 
                <?php endif; ?> 
 
 
            </div> 
 
 
        </section> 
 
 
        <!-- ========================================== 
             LOST REPORTS 
        ========================================== --> 
 
        <section class="panel"> 
 
 
            <div class="panel-header"> 
 
 
                <div class="panel-title"> 
 
 
                    <div class="panel-icon lost-icon"> 
                        ⌕ 
                    </div> 
 
 
                    <div> 
 
                        <h3> 
                            My Lost Reports 
                        </h3> 
 
                        <p> 
                            Items you have reported as lost 
                        </p> 
 
                    </div> 
 
 
                </div> 
 
 
                <span class="panel-count"> 
 
                    <?php echo $lost_count; ?> 
                    Report<?php 
                        echo $lost_count == 1 
                            ? '' 
                            : 's'; 
                    ?> 
 
                </span> 
 
 
            </div> 
 
 
            <div class="table-wrapper"> 
 
 
                <table class="report-table"> 
 
 
                    <thead> 
 
                        <tr> 
 
                            <th> 
                                ID 
                            </th> 
 
                            <th> 
                                Item 
                            </th> 
 
                            <th> 
                                Description 
                            </th> 
 
                            <th> 
                                Date Lost 
                            </th> 
 
                            <th> 
                                Location 
                            </th> 
 
                            <th> 
                                Image 
                            </th> 
 
                            <th> 
                                Status 
                            </th> 
 
                            <th> 
                                Action 
                            </th> 
 
                        </tr> 
 
                    </thead> 
 
 
                    <tbody> 
 
 
                        <?php 
                        if ($lost_count > 0): 
                        ?> 
 
 
                            <?php 
                            while ( 
                                $row = 
                                mysqli_fetch_assoc($lost) 
                            ): 
                            ?> 
 
 
                                <?php 
 
                                $status = 
                                    strtolower( 
                                        trim( 
                                            $row['status'] 
                                        ) 
                                    ); 
 
                                $status_class = 
                                    'status-default'; 
 
 
                                if ( 
                                    $status === 'pending' 
                                ) { 
 
                                    $status_class = 
                                        'status-pending'; 
 
                                } elseif ( 
                                    $status === 'matched' 
                                ) { 
 
                                    $status_class = 
                                        'status-matched'; 
 
                                } elseif ( 
                                    $status === 'claimed' 
                                ) { 
 
                                    $status_class = 
                                        'status-claimed'; 
 
                                } elseif ( 
                                    $status === 'lost' 
                                ) { 
 
                                    $status_class = 
                                        'status-lost'; 
 
                                } 
 
                                ?> 
 
 
                                <tr> 
 
 
                                    <td class="report-id"> 
 
                                        # 
                                        <?php 
                                        echo $row['id']; 
                                        ?> 
 
                                    </td> 
 
 
                                    <td class="item-name"> 
 
                                        <?php 
                                        echo htmlspecialchars( 
                                            $row['item_name'] 
                                        ); 
                                        ?> 
 
                                    </td> 
 
 
                                    <td class="description-cell"> 
 
                                        <?php 
                                        echo htmlspecialchars( 
                                            $row['description'] 
                                        ); 
                                        ?> 
 
                                    </td> 
 
 
                                    <td> 
 
                                        <?php 
                                        echo htmlspecialchars( 
                                            $row['date_lost'] 
                                        ); 
                                        ?> 
 
                                    </td> 
 
 
                                    <td class="location-cell"> 
 
                                        <?php 
                                        echo htmlspecialchars( 
                                            $row['location_lost'] 
                                        ); 
                                        ?> 
 
                                    </td> 
 
 
                                    <td> 
 
 
                                        <?php 
                                        if ( 
                                            !empty( 
                                                $row['image'] 
                                            ) 
                                        ): 
                                        ?> 
 
 
                                            <img 
                                                src="../../uploads/<?php 
                                                    echo htmlspecialchars( 
                                                        $row['image'] 
                                                    ); 
                                                ?>" 
                                                class="report-image" 
                                                alt="Lost item" 
                                            > 
 
 
                                        <?php else: ?> 
 
 
                                            <span class="no-image"> 
                                                No Image 
                                            </span> 
 
 
                                        <?php endif; ?> 
 
 
                                    </td> 
 
 
                                    <td> 
 
 
                                        <span 
                                            class="status-badge 
                                            <?php 
                                                echo $status_class; 
                                            ?>" 
                                        > 
 
                                            <?php 
                                            echo htmlspecialchars( 
                                                $row['status'] 
                                            ); 
                                            ?> 
 
                                        </span> 
 
 
                                    </td> 
 
 
                                    <td> 
 
 
                                        <div class="action-buttons"> 
 
 
                                            <a 
                                                href="edit_lost.php?id=<?php echo $row['id']; ?>" 
                                                class="action-link edit-action"
                                                title="Edit Report"
                                                aria-label="Edit Report"
                                            > 
                                                <span class="edit-icon"></span>
                                            </a> 
 
 
                                            <a 
                                                href="delete_lost.php?id=<?php echo $row['id']; ?>" 
                                                class="action-link delete-action"
                                                title="Delete Report"
                                                aria-label="Delete Report"
                                                onclick="return confirm('Are you sure you want to delete this report?');" 
                                            > 
                                                <span class="delete-icon"></span>
                                            </a> 
 
 
                                        </div> 
 
 
                                    </td> 
 
 
                                </tr> 
 
 
                            <?php endwhile; ?> 
 
 
                        <?php else: ?> 
 
 
                            <tr> 
 
                                <td 
                                    colspan="8" 
                                    class="empty-table" 
                                > 
 
                                    No Lost Reports Found. 
 
                                </td> 
 
                            </tr> 
 
 
                        <?php endif; ?> 
 
 
                    </tbody> 
 
 
                </table> 
 
 
            </div> 
 
 
        </section> 
 
 
        <!-- ========================================== 
             FOUND REPORTS 
        ========================================== --> 
 
        <section class="panel"> 
 
 
            <div class="panel-header"> 
 
 
                <div class="panel-title"> 
 
 
                    <div class="panel-icon found-icon"> 
                        □ 
                    </div> 
 
 
                    <div> 
 
                        <h3> 
                            My Found Reports 
                        </h3> 
 
                        <p> 
                            Items you have reported as found 
                        </p> 
 
                    </div> 
 
 
                </div> 
 
 
                <span class="panel-count"> 
 
                    <?php echo $found_count; ?> 
                    Report<?php 
                        echo $found_count == 1 
                            ? '' 
                            : 's'; 
                    ?> 
 
                </span> 
 
 
            </div> 
 
 
            <div class="table-wrapper"> 
 
 
                <table class="report-table"> 
 
 
                    <thead> 
 
                        <tr> 
 
                            <th> 
                                ID 
                            </th> 
 
                            <th> 
                                Item 
                            </th> 
 
                            <th> 
                                Description 
                            </th> 
 
                            <th> 
                                Date Found 
                            </th> 
 
                            <th> 
                                Location 
                            </th> 
 
                            <th> 
                                Image 
                            </th> 
 
                            <th> 
                                Status 
                            </th> 
 
                            <th> 
                                Action 
                            </th> 
 
                        </tr> 
 
                    </thead> 
 
 
                    <tbody> 
 
 
                        <?php 
                        if ($found_count > 0): 
                        ?> 
 
 
                            <?php 
                            while ( 
                                $row = 
                                mysqli_fetch_assoc($found) 
                            ): 
                            ?> 
 
 
                                <?php 
 
                                $status = 
                                    strtolower( 
                                        trim( 
                                            $row['status'] 
                                        ) 
                                    ); 
 
                                $status_class = 
                                    'status-default'; 
 
 
                                if ( 
                                    $status === 'pending' 
                                ) { 
 
                                    $status_class = 
                                        'status-pending'; 
 
                                } elseif ( 
                                    $status === 'matched' 
                                ) { 
 
                                    $status_class = 
                                        'status-matched'; 
 
                                } elseif ( 
                                    $status === 'claimed' 
                                ) { 
 
                                    $status_class = 
                                        'status-claimed'; 
 
                                } elseif ( 
                                    $status === 'found' 
                                ) { 
 
                                    $status_class = 
                                        'status-found'; 
 
                                } 
 
                                ?> 
 
 
                                <tr> 
 
 
                                    <td class="report-id"> 
 
                                        # 
                                        <?php 
                                        echo $row['id']; 
                                        ?> 
 
                                    </td> 
 
 
                                    <td class="item-name"> 
 
                                        <?php 
                                        echo htmlspecialchars( 
                                            $row['item_name'] 
                                        ); 
                                        ?> 
 
                                    </td> 
 
 
                                    <td class="description-cell"> 
 
                                        <?php 
                                        echo htmlspecialchars( 
                                            $row['description'] 
                                        ); 
                                        ?> 
 
                                    </td> 
 
 
                                    <td> 
 
                                        <?php 
                                        echo htmlspecialchars( 
                                            $row['date_found'] 
                                        ); 
                                        ?> 
 
                                    </td> 
 
 
                                    <td class="location-cell"> 
 
                                        <?php 
                                        echo htmlspecialchars( 
                                            $row['location_found'] 
                                        ); 
                                        ?> 
 
                                    </td> 
 
 
                                    <td> 
 
 
                                        <?php 
                                        if ( 
                                            !empty( 
                                                $row['image'] 
                                            ) 
                                        ): 
                                        ?> 
 
 
                                            <img 
                                                src="../../uploads/<?php 
                                                    echo htmlspecialchars( 
                                                        $row['image'] 
                                                    ); 
                                                ?>" 
                                                class="report-image" 
                                                alt="Found item" 
                                            > 
 
 
                                        <?php else: ?> 
 
 
                                            <span class="no-image"> 
                                                No Image 
                                            </span> 
 
 
                                        <?php endif; ?> 
 
 
                                    </td> 
 
 
                                    <td> 
 
 
                                        <span 
                                            class="status-badge 
                                            <?php 
                                                echo $status_class; 
                                            ?>" 
                                        > 
 
                                            <?php 
                                            echo htmlspecialchars( 
                                                $row['status'] 
                                            ); 
                                            ?> 
 
                                        </span> 
 
 
                                    </td> 
 
 
                                    <td> 
 
 
                                        <div class="action-buttons"> 
 
 
                                            <a 
                                                href="edit_found.php?id=<?php echo $row['id']; ?>" 
                                                class="action-link edit-action"
                                                title="Edit Report"
                                                aria-label="Edit Report"
                                            > 
                                                <span class="edit-icon"></span>
                                            </a> 
 
 
                                            <a 
                                                href="delete_found.php?id=<?php echo $row['id']; ?>" 
                                                class="action-link delete-action"
                                                title="Delete Report"
                                                aria-label="Delete Report"
                                                onclick="return confirm('Are you sure you want to delete this report?');" 
                                            > 
                                                <span class="delete-icon"></span>
                                            </a> 
 
 
                                        </div> 
 
 
                                    </td> 
 
 
                                </tr> 
 
 
                            <?php endwhile; ?> 
 
 
                        <?php else: ?> 
 
 
                            <tr> 
 
                                <td 
                                    colspan="8" 
                                    class="empty-table" 
                                > 
 
                                    No Found Reports Found. 
 
                                </td> 
 
                            </tr> 
 
 
                        <?php endif; ?> 
 
 
                    </tbody> 
 
 
                </table> 
 
 
            </div> 
 
 
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