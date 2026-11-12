<?php
session_start();
require_once '../includes/config.php';

// Use session-based connection
$conn = getSessionConnection();

// Check login
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$reason = isset($_GET['reason']) ? mysqli_real_escape_string($conn, $_GET['reason']) : 'No reason provided';
$user_id = $_SESSION['user_id'];

// Verify the booking belongs to this user and is still pending
$check_sql = "SELECT * FROM bookings WHERE id = $booking_id AND user_id = $user_id AND status = 'pending'";
$check_result = mysqli_query($conn, $check_sql);

if(mysqli_num_rows($check_result) == 1) {
    // Update status to 'cancelled' AND save the reason
    $update_sql = "UPDATE bookings SET status = 'cancelled', cancel_reason = '$reason' WHERE id = $booking_id";
    mysqli_query($conn, $update_sql);
    
    // Get facility name first
    $facility_sql = "SELECT f.name FROM bookings b JOIN facilities f ON b.facility_id = f.id WHERE b.id = $booking_id";
    $facility_result = mysqli_query($conn, $facility_sql);
    $facility_row = mysqli_fetch_assoc($facility_result);
    $facility_name = $facility_row['name'];

    $notif_sql = "INSERT INTO notifications (user_id, message) 
                VALUES ($user_id, '❌ Your booking for **$facility_name** has been CANCELLED.')";
        mysqli_query($conn, $notif_sql);
    
    $_SESSION['message'] = "Booking cancelled successfully!";
} else {
    $_SESSION['error'] = "Unable to cancel this booking.";
}

header("Location: my_bookings.php");
exit();
?>