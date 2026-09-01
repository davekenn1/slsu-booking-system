<?php
session_start();
require_once '../includes/config.php';

// Use the role-specific connection from session
if(isset($_SESSION['db_conn'])) {
    $conn = $_SESSION['db_conn'];
} else {
    $conn = getDefaultConnection();
}

// Check login
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Get facility ID from URL
$facility_id = isset($_GET['facility_id']) ? $_GET['facility_id'] : 0;

// Get facility name
$facility_name = "";
if($facility_id > 0) {
    $fac_sql = "SELECT name, status FROM facilities WHERE id = $facility_id";
    $fac_result = mysqli_query($conn, $fac_sql);
    $facility = mysqli_fetch_assoc($fac_result);
    
    // Check if facility exists and is available
    if(!$facility || $facility['status'] != 'available') {
        header("Location: facilities.php");
        exit();
    }
    
    $facility_name = $facility['name'];
}

// Get ALL bookings for this facility (including pending)
$booked_sql = "SELECT booking_date, time_slot, status 
               FROM bookings 
               WHERE facility_id = $facility_id";
$booked_result = mysqli_query($conn, $booked_sql);

// Create arrays for different statuses
$approved_dates = [];
$pending_dates = [];

while($row = mysqli_fetch_assoc($booked_result)) {
    if($row['status'] == 'approved') {
        $approved_dates[] = $row['booking_date'];
    } elseif($row['status'] == 'pending') {
        $pending_dates[] = $row['booking_date'];
    }
}

// Convert to unique sets
$approved_dates = array_unique($approved_dates);
$pending_dates = array_unique($pending_dates);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar View - SLSU Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- FullCalendar -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
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

        /* Calendar container */
        .calendar-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        /* Fix calendar header */
        .fc .fc-toolbar-title {
            font-size: 1.2rem;
        }

        .fc .fc-button-primary {
            background: var(--slsu-blue);
            border: none;
        }

        .fc .fc-button-primary:hover {
            background: var(--slsu-blue-dark);
        }

        /* Legend box */
        .legend-box {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .legend-color {
            width: 30px;
            height: 30px;
            border-radius: 5px;
            display: inline-block;
            margin-right: 10px;
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
                <i class="fas fa-university me-2"></i>SLSU Facility Booking - Student
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white-50 me-3">
                    <i class="fas fa-user-circle me-1"></i><?php echo $full_name; ?>
                </span>
                <a href="../logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid mt-4">
        <div class="row-flex">
            
            <!-- Sidebar -->
            <div class="sidebar-wrapper">
                <div class="card sidebar-card">
                    <div class="card-header toggle-sidebar" style="cursor: pointer;">
                        <h5 class="mb-0 d-flex align-items-center justify-content-between">
                            <span>
                                <i class="fas fa-bars menu-icon-open"></i>
                                <i class="fas fa-times menu-icon-close" style="display: none;"></i>
                                <span class="menu-text-header ms-2">Customer Menu</span>
                            </span>
                        </h5>
                    </div>

                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="dashboard.php" class="list-group-item">
                                <i class="fas fa-tachometer-alt"></i>
                                <span class="menu-text">Dashboard</span>
                            </a>
                            <a href="facilities.php" class="list-group-item">
                                <i class="fas fa-building"></i>
                                <span class="menu-text">Facilities</span>
                            </a>
                            <a href="my_bookings.php" class="list-group-item">
                                <i class="fas fa-calendar-check"></i>
                                <span class="menu-text">My Bookings</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="content-wrapper">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-calendar-alt me-2" style="color: var(--slsu-blue);"></i>Booking Calendar: <?php echo $facility_name; ?></h2>
                    <a href="facilities.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Facilities
                    </a>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="calendar-container">
                            <div id="calendar"></div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="legend-box">
                            <h5><i class="fas fa-info-circle me-2" style="color: var(--slsu-blue);"></i>Legend & Instructions</h5>
                            <hr>
                            <p><span class="legend-color" style="background: #d4edda; border: 1px solid #27ae60;"></span> Available (No bookings)</p>
                            <p><span class="legend-color" style="background: #fff3cd; border: 1px solid #F39C12;"></span> Pending Approval</p>
                            <p><span class="legend-color" style="background: #f8d7da; border: 1px solid #e74c3c;"></span> Approved/Booked</p>
                            <p><span class="legend-color" style="background: #cfe2ff; border: 1px solid var(--slsu-blue);"></span> Today's Date</p>
                            <hr>
                            <h6><i class="fas fa-check-circle me-2" style="color: #27ae60;"></i>How to Book:</h6>
                            <ol class="mt-2">
                                <li>Click on any <strong class="text-success">GREEN</strong> available date</li>
                                <li>Fill out the booking form</li>
                                <li>Wait for officer approval</li>
                            </ol>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Note:</strong> Yellow dates have pending requests. You can still request, but approval may be uncertain.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <i class="fas fa-copyright me-1"></i> <?php echo date('Y'); ?> Southern Leyte State University - Facility Booking System
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Get dates from PHP
        const approvedDates = <?php echo json_encode(array_values($approved_dates)); ?>;
        const pendingDates = <?php echo json_encode(array_values($pending_dates)); ?>;
        
        // Create sets for quick lookup
        const approvedSet = new Set(approvedDates);
        const pendingSet = new Set(pendingDates);
        
        // Get TODAY in local time
        const today = new Date();
        const todayYear = today.getFullYear();
        const todayMonth = String(today.getMonth() + 1).padStart(2, '0');
        const todayDay = String(today.getDate()).padStart(2, '0');
        const todayStr = `${todayYear}-${todayMonth}-${todayDay}`;
        
        // Initialize calendar
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                firstDay: 1,
                height: 'auto',
                timeZone: 'local',
                nowIndicator: false,
                dayCellDidMount: function(info) {
                    const year = info.date.getFullYear();
                    const month = String(info.date.getMonth() + 1).padStart(2, '0');
                    const day = String(info.date.getDate()).padStart(2, '0');
                    const dateStr = `${year}-${month}-${day}`;
                    
                    // Reset styles
                    info.el.style.backgroundColor = '';
                    info.el.style.border = '';
                    info.el.style.borderRadius = '';
                    
                    // Check status
                    if(approvedSet.has(dateStr)) {
                        info.el.style.backgroundColor = '#f8d7da';
                        info.el.style.borderRadius = '5px';
                    } else if(pendingSet.has(dateStr)) {
                        info.el.style.backgroundColor = '#fff3cd';
                        info.el.style.borderRadius = '5px';
                    } else if(dateStr >= todayStr) {
                        info.el.style.backgroundColor = '#d4edda';
                        info.el.style.borderRadius = '5px';
                    }
                    
                    // Today highlight
                    if(dateStr === todayStr) {
                        info.el.style.backgroundColor = '#cfe2ff';
                        info.el.style.border = '2px solid var(--slsu-blue)';
                        info.el.style.borderRadius = '5px';
                    }
                },
                dateClick: function(info) {
                    const year = info.date.getFullYear();
                    const month = String(info.date.getMonth() + 1).padStart(2, '0');
                    const day = String(info.date.getDate()).padStart(2, '0');
                    const clickedDate = `${year}-${month}-${day}`;
                    
                    if(approvedSet.has(clickedDate)) {
                        alert('❌ This date is already APPROVED and booked. Please choose another date.');
                    } else if(pendingSet.has(clickedDate)) {
                        if(confirm('⚠️ This date has PENDING requests... Continue?')) {
                            window.location.href = `book.php?facility_id=<?php echo $facility_id; ?>&date=${clickedDate}`;
                        }
                    } else if(clickedDate >= todayStr) {
                        if(confirm(`✅ Date ${clickedDate} is AVAILABLE! Book this facility?`)) {
                            window.location.href = `book.php?facility_id=<?php echo $facility_id; ?>&date=${clickedDate}`;
                        }
                    } else {
                        alert('❌ Cannot book past dates. Please select a future date.');
                    }
                }
            });
            calendar.render();
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
</body>
</html>