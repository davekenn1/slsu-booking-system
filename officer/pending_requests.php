<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/send_email.php';

// Use the role-specific connection from session
if(isset($_SESSION['db_conn'])) {
    $conn = $_SESSION['db_conn'];
} else {
    $conn = getDefaultConnection();
}

// Check if user is logged in and is officer
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'officer') {
    header("Location: ../login.php");
    exit();
}

// Handle approval or rejection
if(isset($_GET['action']) && isset($_GET['booking_id'])) {
    $booking_id = $_GET['booking_id'];
    $action = $_GET['action'];
    $remarks = isset($_GET['remarks']) ? mysqli_real_escape_string($conn, $_GET['remarks']) : '';
    
    // Get facility name first
    $facility_sql = "SELECT f.name FROM bookings b JOIN facilities f ON b.facility_id = f.id WHERE b.id = $booking_id";
    $facility_result = mysqli_query($conn, $facility_sql);
    $facility_row = mysqli_fetch_assoc($facility_result);
    $facility_name = $facility_row['name'];

    if($action == 'approve') {
         // Get the student's email from the database
        $email_sql = "SELECT u.email, u.full_name FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.id = $booking_id";
        $email_result = mysqli_query($conn, $email_sql);
        $student = mysqli_fetch_assoc($email_result);
        
        $student_email = $student['email'];
        $student_name = $student['full_name'];
        
        // Prepare email content
        $email_subject = "Booking Approved: " . $facility_name;
        $email_body = "
            <h3>Hello $student_name,</h3>
            <p>Great news! Your booking request for <strong>$facility_name</strong> has been <strong>APPROVED</strong>.</p>
            <p>Please log in to your account to view the details and download your receipt.</p>
            <br>
            <p>Thank you,<br>SLSU Facility Management</p>
        ";
        
        // Send the email
        sendEmailNotification($student_email, $student_name, $email_subject, $email_body);
        
        $status = 'approved';
        $message = "✅ Your booking for **$facility_name** has been APPROVED!";
    } else {
        $status = 'rejected';
        $message = "❌ Your booking for **$facility_name** has been REJECTED. Reason: " . $remarks;
    }
    
    // Update booking status
    $update_sql = "UPDATE bookings SET status = '$status', officer_remarks = '$remarks' WHERE id = $booking_id";
    mysqli_query($conn, $update_sql);
    
    // Add notification for student
    $get_user_sql = "SELECT user_id FROM bookings WHERE id = $booking_id";
    $result_user = mysqli_query($conn, $get_user_sql);
    $user = mysqli_fetch_assoc($result_user);
    $student_id = $user['user_id'];
    
    $notif_sql = "INSERT INTO notifications (user_id, message) VALUES ($student_id, '$message')";
    mysqli_query($conn, $notif_sql);

    $sql_pending = "SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'";
    $result_pending = mysqli_query($conn, $sql_pending);
    $pending_count = mysqli_fetch_assoc($result_pending)['count'];
    
    header("Location: pending_requests.php");
    exit();

    
}

// Get all pending bookings
$sql = "SELECT b.*, f.name as facility_name, u.full_name as student_name 
        FROM bookings b 
        JOIN facilities f ON b.facility_id = f.id 
        JOIN users u ON b.user_id = u.id 
        WHERE b.status = 'pending' 
        ORDER BY b.created_at ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Requests - Officer</title>
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
            clear: both;  /* ← ADD THIS LINE */
        }

        .table-responsive {
            clear: both;
            overflow-x: auto;
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
                <i class="fas fa-university me-2"></i>SLSU Facility Booking - Officer
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white-50 me-3">
                    <i class="fas fa-user-circle me-1"></i><?php echo $_SESSION['full_name']; ?>
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
                                <span class="menu-text-header ms-2">Admin Menu</span>
                            </span>
                        </h5>
                    </div>

                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="dashboard.php" class="list-group-item">
                                <i class="fas fa-tachometer-alt"></i>
                                <span class="menu-text">Dashboard</span>
                            </a>
                            <a href="pending_requests.php" class="list-group-item active">
                                <i class="fas fa-clock"></i>
                                <span class="menu-text">Pending Requests</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="content-wrapper">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-clock me-2" style="color: var(--slsu-blue);"></i>Pending Booking Requests</h2>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
                
                <div class="card content-card">
                    <div class="card-header">
                        <i class="fas fa-list me-2" style="color: var(--slsu-blue);"></i>Requests Awaiting Approval
                    </div>
                    <div class="card-body table-responsive">
                        <?php if(mysqli_num_rows($result) == 0): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>No pending requests! All bookings have been processed.
                            </div>
                        <?php else: ?>
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Facility</th>
                                        <th>Date</th>
                                        <th>Time Slot</th>
                                        <th>Event Details</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td>
                                                    <i class="fas fa-user-graduate me-1"></i><?php echo $row['student_name']; ?><br>
                                                    <small class="text-muted"><i class="fas fa-phone me-1"></i><?php echo $row['contact_number']; ?></small>
                                                </td>
                                                <td><i class="fas fa-building me-1"></i><?php echo $row['facility_name']; ?></td>
                                                <td><i class="fas fa-calendar-day me-1"></i><?php echo date('F j, Y', strtotime($row['booking_date'])); ?></td>
                                                <td><i class="fas fa-clock me-1"></i><?php echo $row['time_slot']; ?></td>
                                                <td>
                                                    <strong><?php echo $row['event_type']; ?></strong><br>
                                                    <small><strong>Price:</strong> ₱<?php echo number_format($row['total_price'], 2); ?></small><br>
                                                    <small><strong>Org:</strong> <?php echo $row['organization_name']; ?></small><br>
                                                    <small class="text-muted"><?php echo $row['purpose']; ?></small>
                                                    
                                                    <!-- SHOW DOCUMENT IF EXISTS -->
                                                    <?php if(!empty($row['document_path'])): ?>
                                                        <br>
                                                        <a href="../uploads/documents/<?php echo $row['document_path']; ?>" target="_blank" class="btn btn-sm btn-info mt-2">
                                                            <i class="fas fa-file-alt me-1"></i> View Document
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            <td>
                                                <!-- Approve button -->
                                                <button class="btn btn-success btn-sm approve-btn" 
                                                        data-booking-id="<?php echo $row['id']; ?>"
                                                        data-facility-name="<?php echo $row['facility_name']; ?>"
                                                        data-student-name="<?php echo $row['student_name']; ?>"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#approveModal<?php echo $row['id']; ?>">
                                                    <i class="fas fa-check-circle me-1"></i>Approve
                                                </button>
                                                
                                                <!-- Reject button -->
                                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $row['id']; ?>">
                                                    <i class="fas fa-times-circle me-1"></i>Reject
                                                </button>
                                            </small>
                                        </tr>
                                        
                                        <!-- Approve Modal -->
                                        <div class="modal fade" id="approveModal<?php echo $row['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-success text-white">
                                                        <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Approve Booking</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Approve booking for <strong><?php echo $row['student_name']; ?></strong>?</p>
                                                        <hr>
                                                        <p><strong>Facility:</strong> <?php echo $row['facility_name']; ?></p>
                                                        <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($row['booking_date'])); ?></p>
                                                        <p><strong>Time:</strong> <?php echo $row['time_slot']; ?></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <a href="pending_requests.php?action=approve&booking_id=<?php echo $row['id']; ?>" class="btn btn-success">
                                                            <i class="fas fa-check me-1"></i>Yes, Approve
                                                        </a>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Reject Modal -->
                                        <div class="modal fade" id="rejectModal<?php echo $row['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title"><i class="fas fa-times-circle me-2"></i>Reject Booking</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="GET">
                                                        <div class="modal-body">
                                                            <p>Reject booking for <strong><?php echo $row['student_name']; ?></strong>?</p>
                                                            <hr>
                                                            <p><strong>Facility:</strong> <?php echo $row['facility_name']; ?></p>
                                                            <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($row['booking_date'])); ?></p>
                                                            <div class="mb-3">
                                                                <label class="form-label">Reason for rejection:</label>
                                                                <textarea name="remarks" class="form-control" rows="3" required placeholder="e.g., Facility under maintenance, Date not available, etc."></textarea>
                                                            </div>
                                                            <input type="hidden" name="action" value="reject">
                                                            <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-danger">
                                                                <i class="fas fa-times me-1"></i>Yes, Reject
                                                            </button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </div>
                        <?php endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const approveButtons = document.querySelectorAll('.approve-btn');
    
    approveButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            
            // ==========================================
            // EVENT DATA
            // ==========================================
            const bookingId = this.dataset.bookingId;
            const facilityName = this.dataset.facilityName;
            const studentName = this.dataset.studentName;
            
            console.log("========================================");
            console.log(" EVENT: Approve Booking clicked");
            console.log(" Event Data 1 - Booking ID:", bookingId);
            console.log(" Event Data 2 - Facility:", facilityName);
            console.log(" Event Data 3 - Student:", studentName);
            console.log("========================================");
            
            // ==========================================
            // HANDLER 1: Update Status on Screen
            // ==========================================
            function updateStatus() {
                // Find the row for this booking
                const row = document.querySelector(`[data-booking-id="${bookingId}"]`).closest('tr');
                
                // Find and update the status badge
                const statusBadge = row.querySelector('.badge-pending');
                if(statusBadge) {
                    statusBadge.className = 'badge-approved';
                    statusBadge.innerHTML = ' Approved';
                }
                
                console.log(" Handler 1: Status updated on screen for booking #" + bookingId);
            }
            
            // ==========================================
            // HANDLER 2: Show Notification on Screen
            // ==========================================
            function sendNotification() {
                // Create a visible notification on the page
                const notif = document.createElement('div');
                notif.className = 'alert alert-success';
                notif.style.position = 'fixed';
                notif.style.top = '20px';
                notif.style.right = '20px';
                notif.style.zIndex = '9999';
                notif.style.minWidth = '300px';
                notif.innerHTML = ' Notification sent to <strong>' + studentName + '</strong>';
                
                document.body.appendChild(notif);
                
                // Auto-remove after 3 seconds
                setTimeout(() => notif.remove(), 3000);
                
                console.log(" Handler 2: Notification displayed for " + studentName);
            }
            
            // ==========================================
            // HANDLER 3: Anonymous/Lambda Handler
            // ==========================================
            const logActivity = () => {
                // Save to localStorage as a log
                const logs = JSON.parse(localStorage.getItem('activityLogs') || '[]');
                logs.push({ 
                    action: 'approve',
                    bookingId: bookingId,
                    facility: facilityName,
                    student: studentName,
                    timestamp: new Date().toLocaleString()
                });
                localStorage.setItem('activityLogs', JSON.stringify(logs));
                
                console.log(" Handler 3 (Lambda): Activity saved to local log");
            };
            
            // ==========================================
            // CALL ALL HANDLERS
            // ==========================================
            updateStatus();
            sendNotification();
            logActivity();
            
            console.log(" All handlers executed successfully!");
            console.log("========================================");
        });
    });
});
</script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>