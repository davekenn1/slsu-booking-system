<?php
/**
 * Generate time slots based on open time, close time, and interval
 */
function generateTimeSlots($open_time, $close_time, $interval_minutes = 60) {
    $slots = [];
    $open = strtotime($open_time);
    $close = strtotime($close_time);
    
    // Total available minutes
    $total_minutes = ($close - $open) / 60;
    
    // If interval is larger than total, return empty
    if ($interval_minutes > $total_minutes) {
        return $slots;
    }
    
    // Determine step size based on interval
    if ($interval_minutes >= 480) { // 8 hours or more
        $step = 60; // 1 hour step for long intervals
    } elseif ($interval_minutes >= 240) { // 4 hours
        $step = 60; // 1 hour step
    } elseif ($interval_minutes >= 120) { // 2 hours
        $step = 60; // 1 hour step
    } else { // 1 hour
        $step = 60; // 1 hour step
    }
    
    $current_start = $open;
    
    while (($current_start + ($interval_minutes * 60)) <= $close) {
        $next = $current_start + ($interval_minutes * 60);
        
        $slots[] = [
            'start' => date('g:i A', $current_start),
            'end' => date('g:i A', $next),
            'start_time' => date('H:i:s', $current_start),
            'end_time' => date('H:i:s', $next),
            'display' => date('g:i A', $current_start) . ' - ' . date('g:i A', $next)
        ];
        
        // Move start time by step (1 hour = 60 minutes)
        $current_start = $current_start + ($step * 60);
    }
    
    return $slots;
}
/**
 * Check if a specific time slot is booked for a facility on a given date
 */
function isSlotBooked($facility_id, $date, $start_time, $end_time) {
    global $conn;
    
    // Convert to timestamps for comparison
    $start_ts = strtotime($start_time);
    $end_ts = strtotime($end_time);
    
    $sql = "SELECT * FROM bookings 
            WHERE facility_id = $facility_id 
            AND booking_date = '$date' 
            AND status != 'cancelled' 
            AND status != 'rejected'";
    
    $result = mysqli_query($conn, $sql);
    
    while($row = mysqli_fetch_assoc($result)) {
        // Parse the booked time slot
        $times = explode(' - ', $row['time_slot']);
        if(count($times) == 2) {
            $booked_start = strtotime($times[0]);
            $booked_end = strtotime($times[1]);
            
            // Check if slots overlap
            if($start_ts < $booked_end && $end_ts > $booked_start) {
                return true; // Overlap found!
            }
        }
    }
    
    return false; // No overlap
}
?>