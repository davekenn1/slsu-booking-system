<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Use the role-specific connection from session
if(isset($_SESSION['db_conn'])) {
    $conn = $_SESSION['db_conn'];
} else {
    $conn = getDefaultConnection();
}

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
$facility_id = isset($_GET['facility_id']) ? $_GET['facility_id'] : 0;
// Get date from URL if passed
$preselected_date = isset($_GET['date']) ? $_GET['date'] : '';
$error = '';
$success = '';

// Get facility details
$sql_facility = "SELECT * FROM facilities WHERE id = $facility_id";
$result_facility = mysqli_query($conn, $sql_facility);
$facility = mysqli_fetch_assoc($result_facility);
// Generate time slots
$slots = generateTimeSlots($facility['open_time'], $facility['close_time'], 60);

// Check if facility exists and is available
if(!$facility || $facility['status'] != 'available') {
    header("Location: facilities.php");
    exit();
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $time_slot = mysqli_real_escape_string($conn, $_POST['time_slot']);
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);
    $organization_name = mysqli_real_escape_string($conn, $_POST['organization_name']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $slot_interval = mysqli_real_escape_string($conn, $_POST['slot_interval']);
    
    // Check for double booking
    $check_sql = "SELECT * FROM bookings 
                  WHERE facility_id = $facility_id 
                  AND booking_date = '$booking_date' 
                  AND time_slot = '$time_slot'
                  AND status != 'rejected'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if(mysqli_num_rows($check_result) > 0) {
        $error = "❌ This time slot is already booked. Please choose another date or time.";
    } else {
        // Insert booking
        $price = mysqli_real_escape_string($conn, $facility['price']);
        $insert_sql = "INSERT INTO bookings (user_id, facility_id, booking_date, time_slot, total_price, purpose, organization_name, contact_number, status, slot_interval) 
               VALUES ($user_id, $facility_id, '$booking_date', '$time_slot', '$price', '$purpose', '$organization_name', '$contact', 'pending', '$slot_interval')";
        
        if(mysqli_query($conn, $insert_sql)) {
            $success = "✅ Booking request submitted! Wait for officer approval.";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Facility - SLSU Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .notification-item {
            background: #e6f0ff;
            border-left: 4px solid var(--slsu-blue);
            margin-bottom: 10px;
            transition: all 0.2s ease;
            padding: 12px;
            border-radius: 8px;
        }

        .notification-item:hover {
            background: #d4e6ff;
            transform: translateX(5px);
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
                    <div class="card-header toggle-sidebar">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="fas fa-bars menu-icon-open"></i>
                            <i class="fas fa-times menu-icon-close" style="display: none;"></i>
                            <span class="menu-text-header">Customer Menu</span>
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
                    <h2><i class="fas fa-calendar-plus me-2" style="color: var(--slsu-blue);"></i>Book Facility</h2>
                    <a href="calendar.php?facility_id=<?php echo $facility_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Calendar
                    </a>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card form-card">
                            <div class="card-header">
                                <i class="fas fa-building me-2"></i> Booking Form: <?php echo $facility['name']; ?>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-tag me-2"></i>
                                <strong>Price:</strong> ₱<?php echo number_format($facility['price'], 2); ?>
                            </div>
                            <div class="card-body">
                                <?php if($error): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if($success): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                    </div>
                                    <a href="facilities.php" class="btn btn-primary">
                                        <i class="fas fa-building me-2"></i>Book Another Facility
                                    </a>
                                    <a href="my_bookings.php" class="btn btn-secondary">
                                        <i class="fas fa-list me-2"></i>View My Bookings
                                    </a>
                                <?php else: ?>
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-calendar-day me-1" style="color: var(--slsu-blue);"></i>Select Date
                                            </label>
                                            <input type="date" class="form-control" id="booking_date" name="booking_date" required 
                                            min="<?php echo date('Y-m-d'); ?>"
                                            value="<?php echo $preselected_date; ?>">
                                        </div>

                                        
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-clock me-1" style="color: var(--slsu-blue);"></i>Booking Duration
                                            </label>
                                            <select class="form-control" id="slot_interval_select" name="slot_interval">
                                                <option value="60">1 hour</option>
                                                <option value="120">2 hours</option>
                                                <option value="180">3 hours</option>
                                                <option value="240">4 hours (Half Day)</option>
                                                <option value="480">8 hours (Full Day)</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-clock me-1" style="color: var(--slsu-blue);"></i>Time Slot
                                            </label>
                                            <select class="form-control" id="time_slot" name="time_slot" required>
                                                <option value="">Select time slot...</option>
                                                <?php 
                                                $selected_interval = isset($_POST['slot_interval']) ? (int)$_POST['slot_interval'] : 60;
                                                $slots = generateTimeSlots($facility['open_time'], $facility['close_time'], $selected_interval);
                                                foreach($slots as $slot): 
                                                    $booked = isSlotBooked($facility_id, $booking_date ?? date('Y-m-d'), $slot['start_time'], $slot['end_time']);
                                                ?>
                                                    <option value="<?php echo $slot['display']; ?>" <?php echo $booked ? 'disabled style="color:red;"' : ''; ?>>
                                                        <?php echo $slot['display']; ?> <?php echo $booked ? '❌ Booked' : '✅ Available'; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-align-left me-1" style="color: var(--slsu-blue);"></i>Purpose / Description
                                            </label>
                                            <textarea class="form-control" id="purpose" name="purpose" rows="3" required
                                                    placeholder="Describe your event or purpose for booking this facility..."></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-users me-1" style="color: var(--slsu-blue);"></i>Organization/Group Name
                                            </label>
                                            <input type="text" class="form-control" id="organization_name" name="organization_name" placeholder="e.g., BSIT 2A, SSC, etc." required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-phone me-1" style="color: var(--slsu-blue);"></i>Contact Number
                                            </label>
                                            <input type="text" class="form-control" id="contact_number" name="contact_number" placeholder="09XX XXX XXXX" required>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-paper-plane me-2"></i>Submit Booking Request
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card facility-info">
                            <div class="card-body">
                                <h5><i class="fas fa-info-circle me-2" style="color: var(--slsu-blue);"></i>Facility Details</h5>
                                <hr>
                                <p><strong>Name:</strong> <?php echo $facility['name']; ?></p>
                                <p><strong>Capacity:</strong> <i class="fas fa-user me-1"></i> <?php echo $facility['capacity']; ?> people</p>
                                <p><strong>Hours:</strong> <i class="fas fa-clock me-1"></i> <?php echo $facility['open_time'] . ' - ' . $facility['close_time']; ?></p>
                                <p><strong>Description:</strong> <?php echo $facility['description']; ?></p>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-clock me-2"></i>
                            <strong>Note:</strong> After submitting, your booking will be <strong class="text-warning">PENDING</strong> until approved by the facility officer.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <i class="fas fa-copyright me-1"></i> <?php echo date('Y'); ?> Southern Leyte State University - Facility Booking System
    </footer>
    
<script>
    const sidebar = document.querySelector('.sidebar-wrapper');
    const toggleBtn = document.querySelector('.toggle-sidebar');
    const iconOpen = document.querySelector('.menu-icon-open');
    const iconClose = document.querySelector('.menu-icon-close');
    
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const intervalSelect = document.getElementById('slot_interval_select');
    const dateInput = document.getElementById('booking_date');
    const timeSlotSelect = document.getElementById('time_slot');
    const facilityId = <?php echo $facility_id; ?>;
    
    function updateSlots() {
        const interval = intervalSelect ? intervalSelect.value : 60;
        const date = dateInput ? dateInput.value : '';
        
        if (!date) {
            timeSlotSelect.innerHTML = '<option value="">Please select a date first</option>';
            return;
        }
        
        fetch(`../get_slots.php?facility_id=${facilityId}&date=${date}&interval=${interval}`)
            .then(response => response.json())
            .then(data => {
                timeSlotSelect.innerHTML = '<option value="">Select time slot...</option>';
                data.slots.forEach(function(slot) {
                    const option = document.createElement('option');
                    option.value = slot.display;
                    option.textContent = slot.display + (slot.available ? ' ✅ Available' : ' ❌ Booked');
                    option.disabled = !slot.available;
                    timeSlotSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading slots:', error);
            });
    }
    
    if (intervalSelect) {
        intervalSelect.addEventListener('change', updateSlots);
    }
    if (dateInput) {
        dateInput.addEventListener('change', updateSlots);
    }
    
    // Initial load if date is already set
    if (dateInput && dateInput.value) {
        updateSlots();
    }
});
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>