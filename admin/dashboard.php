<?php
session_start();
require_once '../includes/config.php';

// Use the role-specific connection from session
// Use session-based connection
$conn = getSessionConnection();

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$full_name = $_SESSION['full_name'];

// Count statistics
$sql_facilities = "SELECT COUNT(*) as count FROM facilities";
$result = mysqli_query($conn, $sql_facilities);
$facility_count = mysqli_fetch_assoc($result)['count'];

$sql_users = "SELECT COUNT(*) as count FROM users";
$result = mysqli_query($conn, $sql_users);
$user_count = mysqli_fetch_assoc($result)['count'];

$sql_bookings = "SELECT COUNT(*) as count FROM bookings";
$result = mysqli_query($conn, $sql_bookings);
$booking_count = mysqli_fetch_assoc($result)['count'];

$sql_pending = "SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'";
$result = mysqli_query($conn, $sql_pending);
$pending_count = mysqli_fetch_assoc($result)['count'];

// Get current month revenue
$current_month = date('Y-m');
$revenue_sql = "SELECT SUM(total_price) as total_revenue FROM bookings 
                WHERE DATE_FORMAT(booking_date, '%Y-%m') = '$current_month' 
                AND status = 'approved'";
$revenue_result = mysqli_query($conn, $revenue_sql);
$revenue = mysqli_fetch_assoc($revenue_result);
$monthly_revenue = $revenue['total_revenue'] ?? 0;

// Get current month bookings count
$count_sql = "SELECT COUNT(*) as total FROM bookings 
              WHERE DATE_FORMAT(booking_date, '%Y-%m') = '$current_month' 
              AND status = 'approved'";
$count_result = mysqli_query($conn, $count_sql);
$count = mysqli_fetch_assoc($count_result);
$monthly_count = $count['total'] ?? 0;

// Get last 6 months revenue for chart
$chart_sql = "SELECT 
                DATE_FORMAT(booking_date, '%Y-%m') as month,
                SUM(total_price) as total_revenue,
                COUNT(*) as booking_count
              FROM bookings 
              WHERE status = 'approved'
              GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
              ORDER BY month DESC
              LIMIT 6";
$chart_result = mysqli_query($conn, $chart_sql);

// Store data in arrays for JavaScript
$months = [];
$revenues = [];
$counts = [];

while($row = mysqli_fetch_assoc($chart_result)) {
    $months[] = date('M Y', strtotime($row['month'] . '-01'));
    $revenues[] = $row['total_revenue'];
    $counts[] = $row['booking_count'];
}

// Reverse arrays to show oldest to newest
$months = array_reverse($months);
$revenues = array_reverse($revenues);
$counts = array_reverse($counts);

// Get recent 5 bookings
$recent_sql = "SELECT b.*, f.name as facility_name, u.full_name as student_name 
               FROM bookings b 
               JOIN facilities f ON b.facility_id = f.id 
               JOIN users u ON b.user_id = u.id 
               ORDER BY b.created_at DESC LIMIT 5";
$recent_result = mysqli_query($conn, $recent_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SLSU Facility Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>   
    <style>
        * {
        font-family: 'Poppins', sans-serif;
         }

        body {
            background: #f0f2f5;
        }

        :root {
            --slsu-blue: #2E75B6;
            --slsu-blue-light: #5B9BD5;
            --slsu-blue-dark: #1A5276;
        }

        .navbar {
            background: var(--slsu-blue) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
        }

        .row-flex {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
        }

        /* SIDEBAR */
        .sidebar-wrapper {
            width: 70px;
            transition: width 0.3s ease-in-out;
            flex-shrink: 0;
            margin-right: 20px;
            align-self: flex-start;
        }

        .sidebar-wrapper.expanded {
            width: 250px;
        }

        .sidebar-card {
            background: white;
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            height: auto;
        }

        .sidebar-card .card-header {
            background: var(--slsu-blue);
            border-bottom: none;
            color: white;
            padding: 12px;
        }

        /* Header - make icon always visible */
        .sidebar-card .card-header h5 {
            margin: 0;
            white-space: nowrap;
        }

        .sidebar-card .card-header h5 i {
            font-size: 1.3rem;
            margin-right: 0;
        }

        /* Hide text when collapsed */
        .sidebar-card .card-header h5 .menu-text-header {
            display: none;
        }

        /* Show text when expanded */
        .sidebar-wrapper.expanded .sidebar-card .card-header h5 .menu-text-header {
            display: inline;
            margin-left: 8px;
        }

        /* Toggle button - clickable area */
        .toggle-sidebar {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .toggle-sidebar:hover {
            background: var(--slsu-blue-dark) !important;
        }

        /* Menu items */
        .sidebar-card .list-group-item {
            border: none;
            color: #333;
            padding: 12px;
            transition: all 0.3s ease;
            background: white;
            white-space: nowrap;
        }

        .sidebar-card .list-group-item:hover {
            background: #e6f0ff;
            color: var(--slsu-blue);
            padding-left: 20px;
        }

        .sidebar-card .list-group-item.active {
            background: var(--slsu-blue);
            color: white;
            border-radius: 10px;
            margin: 5px 8px;
            width: auto;
        }

        .sidebar-card .list-group-item i {
            margin-right: 0;
            width: 25px;
            font-size: 1.2rem;
        }

        .sidebar-wrapper.expanded .sidebar-card .list-group-item i {
            margin-right: 10px;
        }

        .sidebar-card .list-group-item .menu-text {
            display: none;
            margin-left: 5px;
        }

        .sidebar-wrapper.expanded .sidebar-card .list-group-item .menu-text {
            display: inline;
        }

        .content-wrapper {
            flex: 1;
            padding-left: 20px;
            min-width: 0;
        }

        /* Menu icon animation */
        .menu-icon-open, .menu-icon-close {
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .menu-icon-open {
            display: inline-block;
        }

        .menu-icon-close {
            display: none;
        }

        .sidebar-wrapper.expanded .menu-icon-open {
            display: none;
        }

        .sidebar-wrapper.expanded .menu-icon-close {
            display: inline-block;
        }

        /* STAT CARDS */
        .stat-card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease-in-out;
            cursor: pointer;
        }

        .stat-card .card-body {
            padding: 12px;
            transition: all 0.3s ease-in-out;
        }

        .stat-card .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            transition: all 0.3s ease-in-out;
        }

        .stat-card p {
            font-size: 0.75rem;
            margin: 0;
            transition: all 0.3s ease-in-out;
        }

        .stat-card i {
            font-size: 1.5rem;
            transition: all 0.3s ease-in-out;
        }

        .stat-card:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .stat-card:hover .card-body {
            padding: 20px;
        }

        .stat-card:hover .stat-number {
            font-size: 2rem;
        }

        .stat-card:hover p {
            font-size: 0.85rem;
        }

        .stat-card:hover i {
            font-size: 2.2rem;
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--slsu-blue) 0%, var(--slsu-blue-dark) 100%);
            border: none;
            border-radius: 15px;
            color: white;
        }

        .content-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease-in-out;
        }

        .content-card:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .content-card .card-header {
            background: white;
            border-bottom: 2px solid var(--slsu-blue);
            border-radius: 15px 15px 0 0;
            padding: 15px 20px;
            font-weight: 600;
        }

        .btn-menu {
            border-radius: 10px;
            padding: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid var(--slsu-blue);
            color: var(--slsu-blue);
        }

        .btn-menu:hover {
            transform: translateX(5px);
            background: var(--slsu-blue);
            color: white;
        }

        .btn-primary {
            background: var(--slsu-blue);
            border: none;
        }

        .btn-primary:hover {
            background: var(--slsu-blue-dark);
        }

        .btn-warning {
            background: #F39C12;
            border: none;
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            border: none;
        }

        .btn-success {
            background: #27ae60;
            border: none;
        }

        .table thead {
            background: var(--slsu-blue);
            color: white;
        }

        .table tbody tr {
            transition: all 0.2s ease-in-out;
        }

        .table tbody tr:hover {
            background-color: #e6f0ff;
            transform: scale(1.01);
        }

        .badge-admin {
            background: #e74c3c;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .badge-officer {
            background: var(--slsu-blue);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .badge-student {
            background: #27ae60;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .badge-approved {
            background: #27ae60;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .badge-pending {
            background: #F39C12;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .badge-rejected {
            background: #e74c3c;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        footer {
            background: var(--slsu-blue-dark);
            color: white;
            padding: 15px;
            text-align: center;
            margin-top: 30px;
        }

        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease-in-out;
        }

        .form-card:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .form-card .card-header {
            background: var(--slsu-blue);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 15px 20px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .sidebar-wrapper {
                width: 100%;
                margin-bottom: 20px;
            }
            .sidebar-card .list-group-item .menu-text {
                display: inline;
            }
            .sidebar-card .card-header h5 .menu-text-header {
                display: inline;
            }
            .sidebar-card .list-group-item i {
                margin-right: 10px;
            }
            .content-wrapper {
                padding-left: 0;
            }
            .row-flex {
                flex-direction: column;
            }
            .stat-card:hover {
                transform: scale(1.02);
            }
        }

        @media (max-width: 992px) {
            .stat-card .stat-number {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 576px) {
            .stat-card .stat-number {
                font-size: 1rem;
            }
            .btn-menu {
                font-size: 0.8rem;
            }
        }* {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f0f2f5;
        }

        :root {
            --slsu-blue: #2E75B6;
            --slsu-blue-light: #5B9BD5;
            --slsu-blue-dark: #1A5276;
        }

        .navbar {
            background: var(--slsu-blue) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
        }

        .row-flex {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
        }

        /* SIDEBAR */
        .sidebar-wrapper {
            width: 70px;
            transition: width 0.3s ease-in-out;
            flex-shrink: 0;
            margin-right: 20px;
            align-self: flex-start;
        }

        .sidebar-wrapper.expanded {
            width: 250px;
        }

        .sidebar-card {
            background: white;
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            height: auto;
        }

        .sidebar-card .card-header {
            background: var(--slsu-blue);
            border-bottom: none;
            color: white;
            padding: 12px;
        }

        /* Header - make icon always visible */
        .sidebar-card .card-header h5 {
            margin: 0;
            white-space: nowrap;
        }

        .sidebar-card .card-header h5 i {
            font-size: 1.3rem;
            margin-right: 0;
        }

        /* Hide text when collapsed */
        .sidebar-card .card-header h5 .menu-text-header {
            display: none;
        }

        /* Show text when expanded */
        .sidebar-wrapper.expanded .sidebar-card .card-header h5 .menu-text-header {
            display: inline;
            margin-left: 8px;
        }

        /* Toggle button - clickable area */
        .toggle-sidebar {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .toggle-sidebar:hover {
            background: var(--slsu-blue-dark) !important;
        }

        /* Menu items */
        .sidebar-card .list-group-item {
            border: none;
            color: #333;
            padding: 12px;
            transition: all 0.3s ease;
            background: white;
            white-space: nowrap;
        }

        .sidebar-card .list-group-item:hover {
            background: #e6f0ff;
            color: var(--slsu-blue);
            padding-left: 20px;
        }

        .sidebar-card .list-group-item.active {
            background: var(--slsu-blue);
            color: white;
            border-radius: 10px;
            margin: 5px 8px;
            width: auto;
        }

        .sidebar-card .list-group-item i {
            margin-right: 0;
            width: 25px;
            font-size: 1.2rem;
        }

        .sidebar-wrapper.expanded .sidebar-card .list-group-item i {
            margin-right: 10px;
        }

        .sidebar-card .list-group-item .menu-text {
            display: none;
            margin-left: 5px;
        }

        .sidebar-wrapper.expanded .sidebar-card .list-group-item .menu-text {
            display: inline;
        }

        .content-wrapper {
            flex: 1;
            padding-left: 20px;
            min-width: 0;
        }

        /* Menu icon animation */
        .menu-icon-open, .menu-icon-close {
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .menu-icon-open {
            display: inline-block;
        }

        .menu-icon-close {
            display: none;
        }

        .sidebar-wrapper.expanded .menu-icon-open {
            display: none;
        }

        .sidebar-wrapper.expanded .menu-icon-close {
            display: inline-block;
        }

        /* STAT CARDS */
        .stat-card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease-in-out;
            cursor: pointer;
        }

        .stat-card .card-body {
            padding: 12px;
            transition: all 0.3s ease-in-out;
        }

        .stat-card .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            transition: all 0.3s ease-in-out;
        }

        .stat-card p {
            font-size: 0.75rem;
            margin: 0;
            transition: all 0.3s ease-in-out;
        }

        .stat-card i {
            font-size: 1.5rem;
            transition: all 0.3s ease-in-out;
        }

        .stat-card:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .stat-card:hover .card-body {
            padding: 20px;
        }

        .stat-card:hover .stat-number {
            font-size: 2rem;
        }

        .stat-card:hover p {
            font-size: 0.85rem;
        }

        .stat-card:hover i {
            font-size: 2.2rem;
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--slsu-blue) 0%, var(--slsu-blue-dark) 100%);
            border: none;
            border-radius: 15px;
            color: white;
        }

        .content-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease-in-out;
        }

        .content-card:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .content-card .card-header {
            background: white;
            border-bottom: 2px solid var(--slsu-blue);
            border-radius: 15px 15px 0 0;
            padding: 15px 20px;
            font-weight: 600;
        }

        .btn-menu {
            border-radius: 10px;
            padding: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid var(--slsu-blue);
            color: var(--slsu-blue);
        }

        .btn-menu:hover {
            transform: translateX(5px);
            background: var(--slsu-blue);
            color: white;
        }

        .btn-primary {
            background: var(--slsu-blue);
            border: none;
        }

        .btn-primary:hover {
            background: var(--slsu-blue-dark);
        }

        .btn-warning {
            background: #F39C12;
            border: none;
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            border: none;
        }

        .btn-success {
            background: #27ae60;
            border: none;
        }

        .table thead {
            background: var(--slsu-blue);
            color: white;
        }

        .table tbody tr {
            transition: all 0.2s ease-in-out;
        }

        .table tbody tr:hover {
            background-color: #e6f0ff;
            transform: scale(1.01);
        }

        .badge-admin {
            background: #e74c3c;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .badge-officer {
            background: var(--slsu-blue);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .badge-student {
            background: #27ae60;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .badge-approved {
            background: #27ae60;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .badge-pending {
            background: #F39C12;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .badge-rejected {
            background: #e74c3c;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        footer {
            background: var(--slsu-blue-dark);
            color: white;
            padding: 15px;
            text-align: center;
            margin-top: 30px;
        }

        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease-in-out;
        }

        .form-card:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .form-card .card-header {
            background: var(--slsu-blue);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 15px 20px;
            font-weight: 600;
        }

        .table thead,
        .table thead tr,
        .table thead th {
            background-color: #2E75B6 !important;
            color: white !important;
        }

        @media (max-width: 768px) {
            .sidebar-wrapper {
                width: 100%;
                margin-bottom: 20px;
            }
            .sidebar-card .list-group-item .menu-text {
                display: inline;
            }
            .sidebar-card .card-header h5 .menu-text-header {
                display: inline;
            }
            .sidebar-card .list-group-item i {
                margin-right: 10px;
            }
            .content-wrapper {
                padding-left: 0;
            }
            .row-flex {
                flex-direction: column;
            }
            .stat-card:hover {
                transform: scale(1.02);
            }
        }

        @media (max-width: 992px) {
            .stat-card .stat-number {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 576px) {
            .stat-card .stat-number {
                font-size: 1rem;
            }
            .btn-menu {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-university me-2"></i>SLSU Facility Booking - Admin
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white-50 me-3">
                    <i class="fas fa-user-circle me-1"></i>Admin Name
                </span>
                <a href="../logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid mt-4">
        <!-- Use row-flex instead of row -->
        <div class="row-flex">
            
            <!-- SIDEBAR WRAPPER - collapses to 70px, expands to 250px on hover -->
            <div class="sidebar-wrapper">
                <div class="card sidebar-card">
                  <div class="card-header toggle-sidebar" style="cursor: pointer;">
                        <h5 class="mb-0 d-flex align-items-center justify-content-between">
                            <span>
                                <i class="fas fa-bars menu-icon-open"></i>
                                <i class="fas fa-times menu-icon-close" style="display: none;"></i>
                                <span class="menu-text-header ms-2">Admin Menu</span>
                            </span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="dashboard.php" class="list-group-item active">
                                <i class="fas fa-tachometer-alt"></i>
                                <span class="menu-text">Dashboard</span>
                            </a>
                            <a href="manage_facilities.php" class="list-group-item">
                                <i class="fas fa-building"></i>
                                <span class="menu-text">Manage Facilities</span>
                            </a>
                            <a href="manage_users.php" class="list-group-item">
                                <i class="fas fa-users"></i>
                                <span class="menu-text">Manage Users</span>
                            </a>
                            <a href="reports.php" class="list-group-item">
                                <i class="fas fa-chart-bar"></i>
                                <span class="menu-text">Reports</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- CONTENT WRAPPER - automatically fills remaining space -->
            <div class="content-wrapper">
                
                <!-- Your main content goes here -->
                <div class="card welcome-card mb-4">
                    <div class="card-body">
                        <h3 class="mb-1">
                            <i class="fas fa-hand-wave me-2"></i>Welcome, Admin!
                        </h3>
                        <p class="mb-0 opacity-75">Manage your facility bookings from this dashboard.</p>
                    </div>
                </div>
                
                <!-- Stats cards row (normal Bootstrap row inside content) -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card text-white" style="background: var(--slsu-blue);">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="mb-0 opacity-75">Total Facilities</p>
                                        <h2 class="stat-number"><?php echo $facility_count; ?></h2>
                                    </div>
                                    <i class="fas fa-building"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card text-white" style="background: var(--slsu-blue-light);">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="mb-0 opacity-75">Total Users</p>
                                        <h2 class="stat-number"><?php echo $user_count; ?></h2>
                                    </div>
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card text-white" style="background: var(--slsu-blue-light);">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="mb-0 opacity-75">Total Bookings</p>
                                        <h2 class="stat-number"><?php echo $booking_count; ?></h2>
                                    </div>
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card" style="background: #F39C12; color: white;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="mb-0 opacity-75">Pending Approvals</p>
                                         <h2 class="stat-number"><?php echo $pending_count; ?></h2>
                                    </div>
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
<!-- Debug: Months: <?php echo json_encode($months); ?> -->
                <!-- Revenue Chart Card - THIS IS WHERE THE CHART GOES -->
                <div class="card content-card mb-4">
                    <div class="card-header">
                        <i class="fas fa-chart-line me-2" style="color: var(--slsu-blue);"></i>Monthly Revenue Trend (Last 6 Months)
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="100"></canvas>
                    </div>
                </div>

                <!-- Recent Bookings Card -->
                <div class="card content-card mb-4">
                    <div class="card-header">
                        <i class="fas fa-clock me-2" style="color: var(--slsu-blue);"></i>Recent Bookings (Last 5)
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead style="background-color: #2E75B6 !important; color: white !important;">
                                <tr>
                                    <th>Student</th>
                                    <th>Facility</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($recent_result)): ?>
                                <tr>
                                    <td><?php echo $row['student_name']; ?></td>
                                    <td><?php echo $row['facility_name']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($row['booking_date'])); ?></td>
                                    <td>
                                        <?php if($row['status'] == 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php elseif($row['status'] == 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Quick Actions Card -->
                <div class="card content-card">
                    <div class="card-header">
                        <i class="fas fa-bolt me-2" style="color: var(--slsu-blue);"></i>Quick Actions
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <a href="manage_facilities.php" class="btn btn-menu w-100 text-start">
                                    <i class="fas fa-plus-circle me-2"></i> Add New Facility
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="manage_users.php" class="btn btn-menu w-100 text-start">
                                    <i class="fas fa-user-plus me-2"></i> Add New User
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="reports.php" class="btn btn-menu w-100 text-start">
                                    <i class="fas fa-print me-2"></i> Generate Report
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div> <!-- END content-wrapper -->
        </div> <!-- END row-flex -->
    </div> <!-- END container-fluid -->
    
    <footer>
        <i class="fas fa-copyright me-1"></i> 2025 Southern Leyte State University - Facility Booking System
    </footer>

    <script>
            // Revenue Chart
            const ctx = document.getElementById('revenueChart').getContext('2d');
            const revenueChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($months); ?>,
                    datasets: [{
                        label: 'Monthly Revenue (₱)',
                        data: <?php echo json_encode($revenues); ?>,
                        backgroundColor: '#2E75B6',
                        borderColor: '#1A5276',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '₱' + context.raw.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
            </script>
<script>
    const sidebar = document.querySelector('.sidebar-wrapper');
    const toggleBtn = document.querySelector('.toggle-sidebar');
    const iconOpen = document.querySelector('.menu-icon-open');
    const iconClose = document.querySelector('.menu-icon-close');
    
    // Check localStorage for saved state
    const isExpanded = localStorage.getItem('sidebarExpanded') === 'true';
    
    if(isExpanded && sidebar) {
        sidebar.classList.add('expanded');
        if(iconOpen) iconOpen.style.display = 'none';
        if(iconClose) iconClose.style.display = 'inline-block';
    }
    
    if(toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('expanded');
            const expanded = sidebar.classList.contains('expanded');
            localStorage.setItem('sidebarExpanded', expanded);
            
            if(expanded) {
                iconOpen.style.display = 'none';
                iconClose.style.display = 'inline-block';
            } else {
                iconOpen.style.display = 'inline-block';
                iconClose.style.display = 'none';
            }
        });
    }
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>