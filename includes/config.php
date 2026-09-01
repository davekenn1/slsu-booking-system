<?php
// Database configuration
$host = 'localhost';
$database = 'slsu_booking_system';

// Default connection for login page (uses student_user)
function getDefaultConnection() {
    global $host, $database;
    $conn = mysqli_connect($host, 'student_user', 'StudentPass123', $database);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    return $conn;
}

// Role-based connection - returns connection
function getConnectionByRole($role) {
    global $host, $database;
    
    if($role == 'admin') {
        $username = 'admin_user';
        $password = 'AdminPass123';
    } elseif($role == 'officer') {
        $username = 'officer_user';
        $password = 'OfficerPass123';
    } else {
        $username = 'student_user';
        $password = 'StudentPass123';
    }
    
    $conn = mysqli_connect($host, $username, $password, $database);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    return $conn;
}

// Store connection in session after login
function setSessionConnection($role) {
    $_SESSION['db_host'] = $host;
    $_SESSION['db_database'] = $database;
    
    if($role == 'admin') {
        $_SESSION['db_user'] = 'admin_user';
        $_SESSION['db_pass'] = 'AdminPass123';
    } elseif($role == 'officer') {
        $_SESSION['db_user'] = 'officer_user';
        $_SESSION['db_pass'] = 'OfficerPass123';
    } else {
        $_SESSION['db_user'] = 'student_user';
        $_SESSION['db_pass'] = 'StudentPass123';
    }
}

// Get connection from session or create new
function getSessionConnection() {
    if(isset($_SESSION['db_user'])) {
        $conn = mysqli_connect('localhost', $_SESSION['db_user'], $_SESSION['db_pass'], 'slsu_booking_system');
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }
        return $conn;
    } else {
        return getDefaultConnection();
    }
}

// Generate SHA256 hash for data integrity
function generateRowHash($data) {
    // Convert array to string and generate hash
    $string = implode('|', $data);
    return hash('sha256', $string);
}

// Verify if a row's data matches its stored hash
function verifyRowHash($stored_hash, $data) {
    $current_hash = generateRowHash($data);
    return ($stored_hash === $current_hash);
}
?>