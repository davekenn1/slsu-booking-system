<?php
session_start();
require_once 'includes/config.php';

echo "Session ID: " . session_id() . "<br>";
echo "Session role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";

if(!isset($_SESSION['user_id'])) {
    echo "Not logged in. <a href='login.php'>Go to login</a>";
    exit();
}

echo "<h1>You are logged in!</h1>";
echo "<p>Role: " . $_SESSION['role'] . "</p>";
echo "<p><a href='logout.php'>Logout</a></p>";
?>