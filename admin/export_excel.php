<?php
session_start();
require_once '../includes/config.php';

// Use the role-specific connection from session
if(isset($_SESSION['db_conn'])) {
    $conn = $_SESSION['db_conn'];
} else {
    $conn = getDefaultConnection();
}

// Check if user is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get filter values from URL
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$facility_filter = isset($_GET['facility_id']) ? $_GET['facility_id'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$sql = "SELECT b.*, f.name as facility_name, u.full_name as student_name 
        FROM bookings b 
        JOIN facilities f ON b.facility_id = f.id 
        JOIN users u ON b.user_id = u.id 
        WHERE b.booking_date BETWEEN '$start_date' AND '$end_date'";

if($facility_filter != '') {
    $sql .= " AND b.facility_id = $facility_filter";
}
if($status_filter != '') {
    $sql .= " AND b.status = '$status_filter'";
}

$sql .= " ORDER BY b.booking_date DESC";
$result = mysqli_query($conn, $sql);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="bookings_report_' . date('Y-m-d') . '.xls"');

// Create HTML table for Excel
echo '<table border="1">';
echo '<tr>
        <th>ID</th>
        <th>Student Name</th>
        <th>Facility</th>
        <th>Booking Date</th>
        <th>Time Slot</th>
        <th>Price</th>
        <th>Purpose</th>
        <th>Status</th>
        <th>Requested On</th>
      </tr>';

while($row = mysqli_fetch_assoc($result)) {
    echo '<tr>';
    echo '<td>' . $row['id'] . '</td>';
    echo '<td>' . $row['student_name'] . '</td>';
    echo '<td>' . $row['facility_name'] . '</td>';
    echo '<td>' . $row['booking_date'] . '</td>';
    echo '<td>' . $row['time_slot'] . '</td>';
    echo '<td>' . $row['total_price'] . '</td>';
    echo '<td>' . $row['purpose'] . '</td>';
    echo '<td>' . $row['status'] . '</td>';
    echo '<td>' . $row['created_at'] . '</td>';
    echo '</tr>';
}

echo '</table>';
?>