<?php
session_start();
require_once 'includes/config.php';
$conn = getResetConnection(); // Use the reset connection we made earlier

$message = '';
$message_type = '';
$valid_token = false;
$user_id = null;

// 1. Check if token exists in URL
if(isset($_GET['token']) && !empty($_GET['token'])) {

    $token = mysqli_real_escape_string($conn, $_GET['token']);
    
    // Check if token is valid and not expired
    $sql = "SELECT id FROM users WHERE reset_token = '$token' AND reset_expires > NOW() LIMIT 1";
    $result = mysqli_query($conn, $sql);
    
    if(mysqli_num_rows($result) == 1) {
        $valid_token = true;
        $user = mysqli_fetch_assoc($result);
        $user_id = $user['id'];
    } else {
        $message = "This password reset link is invalid or has expired. Please request a new one.";
        $message_type = "danger";
    }
} else {
    $message = "No reset token provided.";
    $message_type = "danger";
}

// 2. Handle form submission (Saving the new password)
if($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $message_type = "danger";
    } elseif($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "danger";
    } else {
        // Hash the new password
        $hashed_password = md5($new_password);
        $token = mysqli_real_escape_string($conn, $_GET['token']);
        
        // Update password and CLEAR the token
        $update_sql = "UPDATE users SET password = '$hashed_password', reset_token = NULL, reset_expires = NULL WHERE id = $user_id";
        
        if(mysqli_query($conn, $update_sql)) {
            $message = "Success! Your password has been reset. You can now login.";
            $message_type = "success";
            $valid_token = false; 
        } else {
            $message = "Something went wrong. Please try again.";
            $message_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SLSU Facility Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body {
            background: linear-gradient(135deg, #2E75B6 0%, #1A5276 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            padding: 40px;
            transition: transform 0.3s ease;
        }
        .login-card:hover { transform: translateY(-5px); }
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-icon { font-size: 3rem; color: #2E75B6; margin-bottom: 15px; }
        .login-header h3 { color: #2E75B6; font-weight: 700; }
        .login-header p { color: #6c757d; font-size: 0.9rem; }
        .btn-login {
            background: linear-gradient(135deg, #2E75B6 0%, #1A5276 100%);
            border: none; width: 100%; padding: 12px;
            font-size: 16px; font-weight: 600; border-radius: 10px;
            transition: all 0.3s ease; color: white;
        }
        .btn-login:hover {
            transform: translateX(5px);
            background: linear-gradient(135deg, #1A5276 0%, #2E75B6 100%);
            color: white;
        }
        .form-control {
            border-radius: 10px; padding: 12px;
            border: 1px solid #ddd; transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #2E75B6; box-shadow: 0 0 0 0.2rem rgba(46, 117, 182, 0.25);
        }
        footer {
            text-align: center; margin-top: 20px;
            color: rgba(255,255,255,0.7); font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card">
                    <div class="login-header">
                        <div class="login-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <h3>Reset Password</h3>
                        <p>Enter your new password below.</p>
                    </div>

                    <?php if(!empty($message)): ?>
                        <div class="alert alert-<?php echo $message_type; ?>" style="border-radius: 10px;">
                            <i class="fas fa-info-circle me-2"></i><?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($valid_token): ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-lock me-1" style="color: #2E75B6;"></i>New Password
                            </label>
                            <input type="password" class="form-control" name="password" placeholder="Enter new password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-check-circle me-1" style="color: #2E75B6;"></i>Confirm Password
                            </label>
                            <input type="password" class="form-control" name="confirm_password" placeholder="Confirm new password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="fas fa-save me-2"></i>Save New Password
                        </button>
                    </form>
                    <?php else: ?>
                        <!-- If token is invalid or expired -->
                        <?php if($message_type == 'danger'): ?>
                            <div class="text-center mt-4">
                                <a href="forgot_password.php" class="btn btn-primary btn-login mb-2">
                                    <i class="fas fa-redo me-2"></i>Request New Link
                                </a>
                                <a href="login.php" class="text-decoration-none"><small>Back to Login</small></a>
                            </div>
                        <?php elseif($message_type == 'success'): ?>
                            <div class="text-center mt-4">
                                <a href="login.php" class="btn btn-primary btn-login">
                                    <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if($valid_token): ?>
                    <div class="text-center mt-4">
                        <small><a href="login.php" class="text-decoration-none"><i class="fas fa-arrow-left me-1"></i>Back to Login</a></small>
                    </div>
                    <?php endif; ?>
                </div>

                <footer>
                    <i class="fas fa-copyright me-1"></i> <?php echo date('Y'); ?> Southern Leyte State University - Facility Booking System
                </footer>
            </div>
        </div>
    </div>
</body>
</html>