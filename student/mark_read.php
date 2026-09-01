<?php
session_start();
require_once '../includes/config.php';

// Use session-based connection
$conn = getSessionConnection();

// Check if user is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark all notifications as read for this user
$update_sql = "UPDATE notifications SET is_read = 1 WHERE user_id = $user_id";
mysqli_query($conn, $update_sql);

// Redirect back to dashboard
header("Location: dashboard.php");
exit();
?>