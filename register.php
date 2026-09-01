<?php
require_once 'includes/config.php';

$error = '';
$success = '';

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    
    // Validation
    if($password != $confirm_password) {
        $error = "❌ Passwords do not match!";
    }  elseif(strlen($password) < 8) {
    $error = "❌ Password must be at least 8 characters!";
    } elseif(!preg_match('/[A-Z]/', $password)) {
        $error = "❌ Password must contain at least one uppercase letter!";
    } elseif(!preg_match('/[a-z]/', $password)) {
        $error = "❌ Password must contain at least one lowercase letter!";
    } elseif(!preg_match('/[0-9]/', $password)) {
        $error = "❌ Password must contain at least one number!";
    } elseif(!preg_match('/[!@#$%^&*]/', $password)) {
        $error = "❌ Password must contain at least one special character (!@#$%^&*)!";
    } else {
        // Check if username exists
        $check_sql = "SELECT id FROM users WHERE username = '$username'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if(mysqli_num_rows($check_result) > 0) {
            $error = "❌ Username already exists! Please choose another.";
        } else {
            // Check if email exists
            $check_email = "SELECT id FROM users WHERE email = '$email'";
            $check_email_result = mysqli_query($conn, $check_email);
            
            if(mysqli_num_rows($check_email_result) > 0) {
                $error = "❌ Email already registered!";
            } else {
                // Insert new student (default role = 'student', status = 'active')
                $insert_sql = "INSERT INTO users (username, password, full_name, email, contact_number, role, status) 
                 VALUES ('$username', MD5('$password'), '$full_name', '$email', '$contact_number', 'user', 'active')";
                if(mysqli_query($conn, $insert_sql)) {
                    $success = "✅ Registration successful! You can now login.";
                } else {
                    $error = "Error: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SLSU Facility Booking</title>
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
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            padding: 40px;
            transition: transform 0.3s ease;
        }
        
        .register-card:hover {
            transform: translateY(-5px);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-icon {
            font-size: 3rem;
            color: #2E75B6;
            margin-bottom: 15px;
        }
        
        .register-header h3 {
            color: #2E75B6;
            font-weight: 700;
        }
        
        .btn-register {
            background: linear-gradient(135deg, #2E75B6 0%, #1A5276 100%);
            border: none;
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-register:hover {
            transform: translateX(5px);
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #ddd;
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

                /* Password Requirements Styling */
        .requirement {
            padding: 2px 0;
            transition: all 0.3s ease;
            font-size: 12px;
        }

        .requirement.valid {
            color: #10b981 !important;
        }

        .requirement.valid i {
            color: #10b981 !important;
        }

        .requirement.invalid {
            color: #ef4444 !important;
        }

        .requirement.invalid i {
            color: #ef4444 !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="register-card">
                    <div class="register-header">
                        <div class="register-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h3>Create Account</h3>
                        <p>Register as a student to book facilities</p>
                    </div>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                        <a href="login.php" class="btn btn-primary w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                        </a>
                    <?php else: ?>
                        <form method="POST" onsubmit="return validateForm()">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user me-1" style="color: #2E75B6;"></i>Username
                                </label>
                                <input type="text" class="form-control" name="username" id="username" required>
                                <small class="text-muted">Choose a unique username</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-signature me-1" style="color: #2E75B6;"></i>Full Name
                                </label>
                                <input type="text" class="form-control" name="full_name" id="full_name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-envelope me-1" style="color: #2E75B6;"></i>Email
                                </label>
                                <input type="email" class="form-control" name="email" id="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-phone me-1" style="color: #2E75B6;"></i>Contact Number
                                </label>
                                <input type="text" class="form-control" name="contact_number" id="contact_number" placeholder="09XX XXX XXXX">
                            </div>
                            
                            <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-lock me-1" style="color: #2E75B6;"></i>Password
                            </label>
                            <input type="password" class="form-control" name="password" id="password" required>
                            
                            <!-- Password Requirements (NEW) -->
                            <div id="passwordRequirements" style="margin-top: 8px; font-size: 13px; color: #6b7280;">
                                <div id="reqLength" class="requirement">
                                    <i class="fas fa-circle text-secondary me-1" style="font-size: 8px;"></i> At least 8 characters
                                </div>
                                <div id="reqUppercase" class="requirement">
                                    <i class="fas fa-circle text-secondary me-1" style="font-size: 8px;"></i> At least one uppercase letter
                                </div>
                                <div id="reqLowercase" class="requirement">
                                    <i class="fas fa-circle text-secondary me-1" style="font-size: 8px;"></i> At least one lowercase letter
                                </div>
                                <div id="reqNumber" class="requirement">
                                    <i class="fas fa-circle text-secondary me-1" style="font-size: 8px;"></i> At least one number
                                </div>
                                <div id="reqSpecial" class="requirement">
                                    <i class="fas fa-circle text-secondary me-1" style="font-size: 8px;"></i> At least one special character (!@#$%^&*)
                                </div>
                            </div>
                        </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-check-circle me-1" style="color: #2E75B6;"></i>Confirm Password
                                </label>
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-register">
                                <i class="fas fa-user-plus me-2"></i>Register
                            </button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <small>Already have an account? <a href="login.php">Login here</a></small>
                        </div>
                    <?php endif; ?>
                </div>
                <footer>
                    <i class="fas fa-copyright me-1"></i> <?php echo date('Y'); ?> Southern Leyte State University - Facility Booking System
                </footer>
            </div>
        </div>
    </div>
    
    
                <script>
            // ============================================
            // PASSWORD VALIDATION - REAL TIME
            // ============================================
            document.addEventListener('DOMContentLoaded', function() {
                var passwordInput = document.getElementById('password');
                var confirmInput = document.getElementById('confirm_password');
                var requirements = {
                    length: document.getElementById('reqLength'),
                    uppercase: document.getElementById('reqUppercase'),
                    lowercase: document.getElementById('reqLowercase'),
                    number: document.getElementById('reqNumber'),
                    special: document.getElementById('reqSpecial')
                };
                
                // Real-time password validation
                passwordInput.addEventListener('input', function() {
                    var value = this.value;
                    
                    // Check length (at least 8)
                    if (value.length >= 8) {
                        requirements.length.className = 'requirement valid';
                        requirements.length.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> At least 8 characters';
                    } else {
                        requirements.length.className = 'requirement invalid';
                        requirements.length.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i> At least 8 characters';
                    }
                    
                    // Check uppercase
                    if (/[A-Z]/.test(value)) {
                        requirements.uppercase.className = 'requirement valid';
                        requirements.uppercase.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> At least one uppercase letter';
                    } else {
                        requirements.uppercase.className = 'requirement invalid';
                        requirements.uppercase.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i> At least one uppercase letter';
                    }
                    
                    // Check lowercase
                    if (/[a-z]/.test(value)) {
                        requirements.lowercase.className = 'requirement valid';
                        requirements.lowercase.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> At least one lowercase letter';
                    } else {
                        requirements.lowercase.className = 'requirement invalid';
                        requirements.lowercase.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i> At least one lowercase letter';
                    }
                    
                    // Check number
                    if (/[0-9]/.test(value)) {
                        requirements.number.className = 'requirement valid';
                        requirements.number.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> At least one number';
                    } else {
                        requirements.number.className = 'requirement invalid';
                        requirements.number.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i> At least one number';
                    }
                    
                    // Check special character
                    if (/[!@#$%^&*]/.test(value)) {
                        requirements.special.className = 'requirement valid';
                        requirements.special.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> At least one special character (!@#$%^&*)';
                    } else {
                        requirements.special.className = 'requirement invalid';
                        requirements.special.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i> At least one special character (!@#$%^&*)';
                    }
                });
            });
            
            // ============================================
            // FORM SUBMIT VALIDATION
            // ============================================
            function validateForm() {
                var password = document.getElementById('password').value;
                var confirm = document.getElementById('confirm_password').value;
                
                if(password !== confirm) {
                    alert('❌ Passwords do not match!');
                    return false;
                }
                
                if(password.length < 8) {
                    alert('❌ Password must be at least 8 characters!');
                    return false;
                }
                
                if(!/[A-Z]/.test(password)) {
                    alert('❌ Password must contain at least one uppercase letter!');
                    return false;
                }
                
                if(!/[a-z]/.test(password)) {
                    alert('❌ Password must contain at least one lowercase letter!');
                    return false;
                }
                
                if(!/[0-9]/.test(password)) {
                    alert('❌ Password must contain at least one number!');
                    return false;
                }
                
                if(!/[!@#$%^&*]/.test(password)) {
                    alert('❌ Password must contain at least one special character (!@#$%^&*)!');
                    return false;
                }
                
                return true;
            }
        </script>

</body>
</html>