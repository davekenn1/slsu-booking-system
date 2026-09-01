<?php
session_start();
require_once '../includes/config.php';

// Use the role-specific connection from session
if(isset($_SESSION['db_conn'])) {
    $conn = $_SESSION['db_conn'];
} else {
    $conn = getDefaultConnection();
}

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$facility_id = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;

// Verify booking belongs to this user and is approved
$check_sql = "SELECT * FROM bookings WHERE id = $booking_id AND user_id = $user_id AND status = 'approved'";
$check_result = mysqli_query($conn, $check_sql);

if(mysqli_num_rows($check_result) == 0) {
    header("Location: my_bookings.php");
    exit();
}

// Check if review already exists
$review_sql = "SELECT * FROM reviews WHERE booking_id = $booking_id";
$review_result = mysqli_query($conn, $review_sql);

if(mysqli_num_rows($review_result) > 0) {
    header("Location: my_bookings.php?msg=already_reviewed");
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = (int)$_POST['rating'];
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    
    $insert_sql = "INSERT INTO reviews (booking_id, facility_id, user_id, rating, comment) 
                   VALUES ($booking_id, $facility_id, $user_id, $rating, '$comment')";
    
    if(mysqli_query($conn, $insert_sql)) {
        $success = "✅ Thank you for your review!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write Review - SLSU Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: #f0f2f5; }
        :root { --slsu-blue: #2E75B6; --slsu-blue-dark: #1A5276; }
        .navbar { background: var(--slsu-blue) !important; }
        .rating i { font-size: 2rem; cursor: pointer; color: #ddd; transition: all 0.2s; }
        .rating i.selected, .rating i.hover { color: #F39C12; }
        footer { background: var(--slsu-blue-dark); color: white; padding: 15px; text-align: center; margin-top: 30px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-university me-2"></i>SLSU Facility Booking - Student</a>
            <div class="d-flex align-items-center">
                <span class="text-white-50 me-3"><i class="fas fa-user-circle me-1"></i><?php echo $_SESSION['full_name']; ?></span>
                <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">⭐ Write a Review</h4>
                    </div>
                    <div class="card-body">
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                            <a href="my_bookings.php" class="btn btn-primary">Back to My Bookings</a>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3 text-center">
                                    <label class="form-label fw-bold">Your Rating</label>
                                    <div class="rating">
                                        <i class="far fa-star" data-value="1"></i>
                                        <i class="far fa-star" data-value="2"></i>
                                        <i class="far fa-star" data-value="3"></i>
                                        <i class="far fa-star" data-value="4"></i>
                                        <i class="far fa-star" data-value="5"></i>
                                    </div>
                                    <input type="hidden" name="rating" id="rating" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Your Review (Optional)</label>
                                    <textarea name="comment" class="form-control" rows="4" placeholder="Share your experience..."></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">Submit Review</button>
                                <a href="my_bookings.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <i class="fas fa-copyright me-1"></i> <?php echo date('Y'); ?> Southern Leyte State University - Facility Booking System
    </footer>
    
    <script>
        const stars = document.querySelectorAll('.rating i');
        const ratingInput = document.getElementById('rating');
        
        function setRating(value) {
            ratingInput.value = value;
            stars.forEach((star, index) => {
                if(index < value) {
                    star.className = 'fas fa-star selected';
                } else {
                    star.className = 'far fa-star';
                }
            });
        }
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                setRating(parseInt(this.dataset.value));
            });
            
            star.addEventListener('mouseenter', function() {
                const value = parseInt(this.dataset.value);
                stars.forEach((s, index) => {
                    if(index < value) {
                        s.classList.add('hover');
                        s.classList.remove('selected');
                    } else {
                        s.classList.remove('hover');
                    }
                });
            });
            
            star.addEventListener('mouseleave', function() {
                stars.forEach(s => {
                    s.classList.remove('hover');
                });
                const currentRating = parseInt(ratingInput.value);
                if(currentRating > 0) {
                    stars.forEach((s, index) => {
                        if(index < currentRating) {
                            s.className = 'fas fa-star selected';
                        } else {
                            s.className = 'far fa-star';
                        }
                    });
                } else {
                    stars.forEach(s => {
                        s.className = 'far fa-star';
                    });
                }
            });
        });
    </script>
</body>
</html>