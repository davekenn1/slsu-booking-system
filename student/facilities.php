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

// Get search filters from URL
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$min_price = isset($_GET['min_price']) && $_GET['min_price'] != '' ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] != '' ? (int)$_GET['max_price'] : 999999;

// Build the query with filters
$sql = "SELECT * FROM facilities WHERE status = 'available'";

if($search != '') {
    $sql .= " AND (name LIKE '%$search%' OR description LIKE '%$search%')";
}
if($min_price > 0) {
    $sql .= " AND price >= $min_price";
}
if($max_price < 999999) {
    $sql .= " AND price <= $max_price";
}

$sql .= " ORDER BY name";
$result = mysqli_query($conn, $sql);

// Also get count of results for debugging
$count = mysqli_num_rows($result);
// For debugging - remove after testing
echo "<!-- Search: " . ($search ?? 'empty') . " -->";
echo "<!-- Min Price: " . ($min_price ?? 'empty') . " -->";
echo "<!-- Max Price: " . ($max_price ?? 'empty') . " -->";
echo "<!-- Count: " . ($count ?? 'empty') . " -->";

$full_name = $_SESSION['full_name'];

// Get ONLY available facilities
// $sql = "SELECT * FROM facilities WHERE status = 'available' ORDER BY name";
// $result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facilities - SLSU Booking</title>
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

        .btn-book {
            background: var(--slsu-blue);
            border: none;
            border-radius: 10px;
            padding: 10px;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-book:hover {
            background: var(--slsu-blue-dark);
            transform: translateX(5px);
            color: white;
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
                            <a href="facilities.php" class="list-group-item active">
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
                    <h2><i class="fas fa-building me-2" style="color: var(--slsu-blue);"></i>Available Facilities</h2>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>

                <!-- Search Filter Card -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <i class="fas fa-search me-2" style="color: var(--slsu-blue);"></i>Search & Filter Facilities
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Search by name or description</label>
                                <input type="text" name="search" class="form-control" 
                                    placeholder="e.g., basketball, theater, court" 
                                    value="<?php echo $search; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Min Price (₱)</label>
                                <input type="number" name="min_price" class="form-control" 
                                    placeholder="0" value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Max Price (₱)</label>
                                <input type="number" name="max_price" class="form-control" 
                                    placeholder="10000" value="<?php echo $max_price < 10000 ? $max_price : ''; ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i> Search
                                </button>
                                <a href="facilities.php" class="btn btn-secondary w-100 ms-2">
                                    <i class="fas fa-sync-alt me-1"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="alert alert-info">
                    Found <strong><?php echo $count; ?></strong> facility(ies) matching your search.
                </div>
                <?php 
// Debug: Show how many results are in $result
echo "<!-- Number of results in \$result: " . mysqli_num_rows($result) . " -->";
?>
                <?php if(mysqli_num_rows($result) == 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No facilities are currently available. Please check back later.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php while($facility = mysqli_fetch_assoc($result)): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card facility-card">
                                    <div class="card-body">
                                        <div class="text-center">
                                            <img src="../uploads/facilities/<?php echo $facility['image']; ?>" 
                                                class="img-fluid rounded mb-3" 
                                                style="height: 150px; width: 100%; object-fit: cover;"
                                                onerror="this.src='../uploads/facilities/default.jpg'">
                                            <div class="facility-icon">
                                                <i class="fas fa-building"></i>
                                            </div>
                                            <h5 class="facility-title"><?php echo $facility['name']; ?></h5>
                                            <span class="badge badge-available mb-2">
                                                <i class="fas fa-check-circle me-1"></i>Active
                                            </span>
                                        </div>
                                        <p class="facility-detail">
                                            <i class="fas fa-align-left me-2" style="color: var(--slsu-blue);"></i>
                                            <?php echo substr($facility['description'], 0, 80); ?>...
                                        </p>
                                        <p class="facility-detail">
                                            <i class="fas fa-users me-2" style="color: var(--slsu-blue);"></i>
                                            Capacity: <?php echo $facility['capacity']; ?> people
                                        </p>
                                        <p class="facility-detail">
                                            <i class="fas fa-tag me-2" style="color: var(--slsu-blue);"></i>
                                            Price: ₱<?php echo number_format($facility['price'], 2); ?>
                                        </p>
                                        <p class="facility-detail">
                                            <i class="fas fa-star me-2" style="color: #F39C12;"></i>
                                            Rating: 
                                            <?php
                                            $rating_sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE facility_id = " . $facility['id'];
                                            $rating_result = mysqli_query($conn, $rating_sql);
                                            $rating_data = mysqli_fetch_assoc($rating_result);
                                            $avg = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
                                            $count = $rating_data['review_count'] ?? 0;
                                            ?>
                                            <?php if($count > 0): ?>
                                                ⭐ <?php echo $avg; ?> / 5 (<?php echo $count; ?> reviews)
                                            <?php else: ?>
                                                No reviews yet
                                            <?php endif; ?>
                                        </p>
                                        <p class="facility-detail">
                                            <i class="fas fa-clock me-2" style="color: var(--slsu-blue);"></i>
                                            Hours: <?php echo date('g:i A', strtotime($facility['open_time'])); ?> - <?php echo date('g:i A', strtotime($facility['close_time'])); ?>
                                        </p>
                                        <a href="calendar.php?facility_id=<?php echo $facility['id']; ?>" class="btn btn-book w-100 text-white">
                                            <i class="fas fa-calendar-alt me-2"></i>View Calendar & Book
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>