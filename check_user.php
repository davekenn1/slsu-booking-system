<?php
session_start();
require_once 'includes/config.php';

// Get connection using session function
$conn = getSessionConnection();

echo "Session role: " . $_SESSION['role'] . "<br>";

// Get the current database user
$result = mysqli_query($conn, "SELECT CURRENT_USER() as user");
$row = mysqli_fetch_assoc($result);
echo "Database user: " . $row['user'] . "<br>";

// Also show connection info from session
if(isset($_SESSION['db_user'])) {
    echo "Session DB user: " . $_SESSION['db_user'] . "<br>";
} else {
    echo "Session DB user: NOT SET<br>";
}
?>