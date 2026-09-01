<?php
session_start();
require_once 'includes/config.php';

$conn = getSessionConnection();

echo "<!DOCTYPE html>";
echo "<html><head><title>Data Integrity Check</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body>";
echo "<div class='container mt-5'>";
echo "<h2>🔒 Data Integrity Verification Report</h2>";
echo "<hr>";

$all_valid = true;
$results = [];

// Check facilities table
$result = mysqli_query($conn, "SELECT f.*, h.row_hash as stored_hash 
                               FROM facilities f 
                               LEFT JOIN hash_table h ON h.table_name = 'facilities' AND h.record_id = f.id");

while($row = mysqli_fetch_assoc($result)) {
    if(!$row['stored_hash']) {
        $results[] = ["type" => "warning", "msg" => "⚠️ Facility ID {$row['id']}: NO HASH (old data)"];
        continue;
    }
    
    $hash_data = [$row['id'], $row['name'], $row['description'], $row['capacity'], $row['price'], $row['operating_hours'], $row['status']];
    $current_hash = generateRowHash($hash_data);
    
    if($current_hash == $row['stored_hash']) {
        $results[] = ["type" => "success", "msg" => "✅ Facility ID {$row['id']}: INTACT"];
    } else {
        $results[] = ["type" => "danger", "msg" => "❌ Facility ID {$row['id']}: COMPROMISED!"];
        $all_valid = false;
    }
}

// Check bookings table
$result = mysqli_query($conn, "SELECT b.*, h.row_hash as stored_hash 
                               FROM bookings b 
                               LEFT JOIN hash_table h ON h.table_name = 'bookings' AND h.record_id = b.id");

while($row = mysqli_fetch_assoc($result)) {
    if(!$row['stored_hash']) {
        $results[] = ["type" => "warning", "msg" => "⚠️ Booking ID {$row['id']}: NO HASH (old data)"];
        continue;
    }
    
    $hash_data = [$row['id'], $row['user_id'], $row['facility_id'], $row['booking_date'], $row['time_slot'], $row['total_price'], $row['status']];
    $current_hash = generateRowHash($hash_data);
    
    if($current_hash == $row['stored_hash']) {
        $results[] = ["type" => "success", "msg" => "✅ Booking ID {$row['id']}: INTACT"];
    } else {
        $results[] = ["type" => "danger", "msg" => "❌ Booking ID {$row['id']}: COMPROMISED!"];
        $all_valid = false;
    }  
}

// Check users table
    $result = mysqli_query($conn, "SELECT u.*, h.row_hash as stored_hash 
                                FROM users u 
                                LEFT JOIN hash_table h ON h.table_name = 'users' AND h.record_id = u.id");

    while($row = mysqli_fetch_assoc($result)) {
        if(!$row['stored_hash']) {
            $results[] = ["type" => "warning", "msg" => "⚠️ User ID {$row['id']}: NO HASH (old data)"];
            continue;
        }
    
    $hash_data = [$row['id'], $row['username'], $row['full_name'], $row['email'], $row['role']];
    $current_hash = generateRowHash($hash_data);
    
    if($current_hash == $row['stored_hash']) {
        $results[] = ["type" => "success", "msg" => "✅ User ID {$row['id']}: INTACT"];
    } else {
        $results[] = ["type" => "danger", "msg" => "❌ User ID {$row['id']}: COMPROMISED!"];
        $all_valid = false;
    }
}

// Display results
echo "<table class='table table-bordered'>";
echo "<thead class='table-dark'><tr><th>Status</th><th>Message</th></tr></thead>";
echo "<tbody>";
foreach($results as $r) {
    $color = ($r['type'] == 'success') ? 'success' : (($r['type'] == 'warning') ? 'warning' : 'danger');
    echo "<tr class='table-$color'><td>" . ($r['type'] == 'success' ? '✓' : ($r['type'] == 'warning' ? '⚠' : '✗')) . "</td><td>{$r['msg']}</td></tr>";
}
echo "</tbody></table>";
echo "<hr>";

if($all_valid) {
    echo "<div class='alert alert-success'>✓ All data integrity checks PASSED</div>";
} else {
    echo "<div class='alert alert-danger'>✗ Data integrity checks FAILED - Some records have been tampered!</div>";
}

echo "<a href='admin/dashboard.php' class='btn btn-primary'>Back to Dashboard</a>";
echo "</div></body></html>";
?>