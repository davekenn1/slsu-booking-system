<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Use session connection
if(isset($_SESSION['db_conn'])) {
    $conn = $_SESSION['db_conn'];
} else {
    $conn = getDefaultConnection();
}

$facility_id = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$interval = isset($_GET['interval']) ? (int)$_GET['interval'] : 60;

// Get facility details
$sql = "SELECT * FROM facilities WHERE id = $facility_id";
$result = mysqli_query($conn, $sql);
$facility = mysqli_fetch_assoc($result);

if(!$facility) {
    echo json_encode(['error' => 'Facility not found']);
    exit();
}

// Generate slots with the selected interval
$slots = generateTimeSlots($facility['open_time'], $facility['close_time'], $interval);

// Check availability for each slot
foreach($slots as $slot) {
    $booked = isSlotBooked($facility_id, $date, $slot['start_time'], $slot['end_time']);
    $available_slots[] = [
        'display' => $slot['display'],
        'available' => !$booked
    ];
}

echo json_encode(['slots' => $available_slots]);
?>