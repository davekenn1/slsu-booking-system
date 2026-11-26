<?php
session_start();
require_once 'includes/config.php';
$conn = getResetConnection();

// If already logged in, redirect to their dashboard
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] == 'admin') header("Location: admin/dashboard.php");
    elseif($_SESSION['role'] == 'officer') header("Location: officer/dashboard.php");
    else header("Location: student/dashboard.php");
    exit();
}

$message = '';
$message_type = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));

    if(empty($email)) {
        $message = "Please enter your email address.";
        $message_type = "danger";
    } else {
        // Check if email exists
        $sql = "SELECT id, full_name FROM users WHERE email = '$email' LIMIT 1";
        $result = mysqli_query($conn, $sql);

        if(mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);

            // Generate a unique token
            $token = bin2hex(random_bytes(32)); // 64-character random string
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); 

            // Save token + expiry to database
            $update_sql = "UPDATE users SET reset_token = '$token', reset_expires = '$expires' WHERE id = {$user['id']}";
            mysqli_query($conn, $update_sql);

            // Build the reset link
            $reset_link = "http://localhost/slsu_booking_system/reset_password.php?token=$token";

            // Prepare email
            require_once 'includes/send_email.php';
            $subject = "Password Reset - SLSU Facility Booking System";
            $body = "
                <h3>Hello {$user['full_name']},</h3>
                <p>We received a request to reset your password for the SLSU Facility Booking System.</p>
                <p>Click the link below to reset your password. This link will expire in <strong>1 hour</strong>.</p>
                <p><a href='$reset_link' style='background:#2E75B6;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;'>Reset My Password</a></p>
                <p>If the button doesn't work, copy and paste this link into your browser:</p>
                <p style='word-break:break-all;'>$reset_link</p>
                <br>
                <p>If you did not request this, you can safely ignore this email.</p>
                <p>Thank you,<br>SLSU Facility Management</p>
            ";

            sendEmailNotification($email, $user['full_name'], $subject, $body);
        }

        // ALWAYS show the same message (don't reveal if email exists or not — security)
        $message = "If that email is registered in our system, a password reset link has been sent. Please check your inbox.";
        $message_type = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SLSU Facility Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

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
            position: relative;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-icon {
            font-size: 3rem;
            color: #2E75B6;
            margin-bottom: 15px;
        }
        
        .login-header h3 {
            color: #2E75B6;
            font-weight: 700;
        }
        
        .login-header p {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #2E75B6 0%, #1A5276 100%);
            border: none;
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            color: white;
        }
        
        .btn-login:hover {
            transform: translateX(5px);
            background: linear-gradient(135deg, #1A5276 0%, #2E75B6 100%);
            color: white;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #2E75B6;
            box-shadow: 0 0 0 0.2rem rgba(46, 117, 182, 0.25);
        }

        footer {
            text-align: center;
            margin-top: 20px;
            color: rgba(255,255,255,0.7);
            font-size: 0.8rem;
        }
        
        @media (max-width: 768px) {
            .login-card {
                margin: 20px;
                padding: 30px;
            }
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
                            <i class="fas fa-lock"></i>
                        </div>
                        <h3>Forgot Password?</h3>
                        <p>Enter your email and we'll send you a reset link.</p>
                    </div>

                    <?php if(!empty($message)): ?>
                        <div class="alert alert-<?php echo $message_type; ?>" style="border-radius: 10px;">
                            <i class="fas fa-info-circle me-2"></i><?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1" style="color: #2E75B6;"></i>Email Address
                            </label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your registered email" required autofocus>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <small><a href="login.php" class="text-decoration-none"><i class="fas fa-arrow-left me-1"></i>Back to Login</a></small>
                    </div>
                </div>

                <footer>
                    <i class="fas fa-copyright me-1"></i> <?php echo date('Y'); ?> Southern Leyte State University - Facility Booking System
                </footer>
            </div>
        </div>
    </div>
</body>
</html>