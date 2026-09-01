<?php
// Include database connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Force destroy any existing session on login page
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}
session_start();

require_once 'includes/config.php';
$conn = getDefaultConnection();

// If user is already logged in, redirect to their dashboard
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] == 'user') {
        header("Location: student/dashboard.php");
    } elseif($_SESSION['role'] == 'officer') {
        header("Location: officer/dashboard.php");
    } elseif($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
    }
    exit();
}

$error = ''; // Variable to store error messages

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']); 
    
    // Query to check if user exists
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password' AND status = 'active'";
    $result = mysqli_query($conn, $sql);
    
    if(mysqli_num_rows($result) == 1) {
        // User found - login successful
        $user = mysqli_fetch_assoc($result);
        
        // Store user info in session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        // Create role-specific database connection and store in session
        setSessionConnection($user['role']);
        
        // Redirect based on role
        if($user['role'] == 'user') {
            header("Location: student/dashboard.php");
        } elseif($user['role'] == 'officer') {
            header("Location: officer/dashboard.php");
        } elseif($user['role'] == 'admin') {
            header("Location: admin/dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid username or password, or your account is inactive.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLSU Facility Booking System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        /* ============================================
   SYNC MODE - OVERLAY (Fixed position)
   ============================================ */
#syncOverlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    backdrop-filter: blur(4px);
    animation: fadeInOverlay 0.3s ease;
}

@keyframes fadeInOverlay {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

#syncOverlay .spinner-border {
    width: 50px;
    height: 50px;
    border-width: 4px;
}

#syncOverlay p {
    margin-top: 16px;
    font-weight: 500;
    color: #1A5276;
    font-size: 16px;
    letter-spacing: 0.3px;
}
        /* ============================================
            SYNC MODE - FROZEN STATE
            ============================================ */
            .frozen {
                opacity: 0.6;
                pointer-events: none;
                user-select: none;
                transition: all 0.3s ease;
            }
            
            /* Also disable all inputs inside frozen card */
            .frozen input,
            .frozen button,
            .frozen select,
            .frozen textarea {
                pointer-events: none;
                opacity: 0.5;
            }

            /* ============================================
            SYNC MODE - BLUE HIGHLIGHT
            ============================================ */
            .validation-progress {
                background: #e8f0fe !important;
                border-color: #2E75B6 !important;
                box-shadow: 0 0 0 3px rgba(46, 117, 182, 0.15) !important;
                transition: all 0.3s ease;
            }

            /* ============================================
            ASYNC MODE - BUTTON LOADING STATE
            ============================================ */
            .btn-loading {
                opacity: 0.7;
                pointer-events: none;
                cursor: not-allowed;
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
        }
        
        .btn-login:hover {
            transform: translateX(5px);
            background: linear-gradient(135deg, #1A5276 0%, #2E75B6 100%);
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
        
        .demo-accounts {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .demo-accounts small {
            font-size: 0.75rem;
        }
        
        footer {
            text-align: center;
            margin-top: 20px;
            color: rgba(255,255,255,0.7);
            font-size: 0.8rem;
        }

        /* ============================================
        PROFESSIONAL PASSWORD HINT
        ============================================ */
        #passwordHint {
            display: none;
            margin-top: 6px;
            padding: 10px 16px;
            background: #f0f7ff;
            border-radius: 8px;
            border-left: 4px solid #2E75B6;
            font-size: 13px;
            color: #1a3a5c;
            animation: fadeInUp 0.3s ease;
            box-shadow: 0 2px 8px rgba(46, 117, 182, 0.08);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #passwordHint i {
            color: #2E75B6;
        }

        #passwordHint .hint-detail {
            display: inline-block;
            margin-left: 12px;
            font-size: 12px;
            color: #6b7280;
        }

        /* When password field is focused - subtle glow */
        #password:focus {
            border-color: #2E75B6;
            box-shadow: 0 0 0 3px rgba(46, 117, 182, 0.12);
            background: white;
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
                <div class="login-card" id="loginContainer">
                    <div class="login-header">
                        <div class="login-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <h3>SLSU Facility Booking</h3>
                        <p>Login to your account to book facilities</p>
                    </div>

                    <!-- Notification Area for Event Messages (Hidden Initially) -->
                        <div id="notificationArea" style="display: none; margin-top: 15px; margin-bottom: 15px;">
                            <div class="alert alert-info" id="notificationMessage">
                                <i class="fas fa-info-circle me-2"></i>
                                <span id="notificationText">Events will appear here</span>
                            </div>
                        </div>
                    
                    <?php if($error != ''): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="loginForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-1" style="color: #2E75B6;"></i>Username
                            </label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1" style="color: #2E75B6;"></i>Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <small>Don't have an account? <a href="register.php">Register here</a></small>
                    </div>
                </div>


                        <!-- Mode Selection for Sync vs Async 
                        <div class="mb-3" style="margin-top: 15px;">
                            <label class="form-label fw-bold"><i class="fas fa-code-branch me-1"></i>Validation Mode:</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-primary active" id="syncModeBtn">
                                    <i class="fas fa-sync me-1"></i>Synchronous (Blocking)
                                </button>
                                <button type="button" class="btn btn-outline-success" id="asyncModeBtn">
                                    <i class="fas fa-spinner me-1"></i>Asynchronous (Non-Blocking)
                                </button>
                            </div>
                        </div>
                            -->
                    

                       <!-- ===== SYNC MODE LOADING OVERLAY (NOW OUTSIDE CARD) ===== -->
                        <div id="syncOverlay" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p>Processing login... Please wait</p>
                        </div>

                <footer>
                    <i class="fas fa-copyright me-1"></i> <?php echo date('Y'); ?> Southern Leyte State University - Facility Booking System
                </footer>
            </div>
        </div>
    </div>


        <script>
            // Mode variable: 'sync' or 'async'
            var validationMode = 'sync';  // Default is sync
            
            // Helper function to show notification
            function showNotification(message) {
                var area = document.getElementById('notificationArea');
                var text = document.getElementById('notificationText');
                
                text.innerHTML = message;
                area.style.display = 'block';
            }

            // Helper function to hide notification
            function hideNotification() {
                var area = document.getElementById('notificationArea');
                area.style.display = 'none';
            }

            // ============================================
            // STEP 6: NOTIFICATION CENTER (OBSERVER PATTERN)
            // ============================================
            var NotificationCenter = {
                // List of subscribers for each event type
                subscribers: {
                    loginAttempt: [],
                    loginSuccess: [],
                    loginFailed: []
                },
                
                // Subscribe to an event
                subscribe: function(eventName, callback) {
                    if (this.subscribers[eventName]) {
                        this.subscribers[eventName].push(callback);
                        console.log('📢 Subscribed to: ' + eventName);
                    } else {
                        console.warn('Event "' + eventName + '" does not exist');
                    }
                },
                
                // Publish an event (notify all subscribers)
                publish: function(eventName, data) {
                    if (this.subscribers[eventName]) {
                        console.log('📢 Publishing: ' + eventName);
                        this.subscribers[eventName].forEach(function(callback) {
                            callback(data);
                        });
                    } else {
                        console.warn('Event "' + eventName + '" has no subscribers');
                    }
                }
            };

            // Main event handlers
            document.addEventListener('DOMContentLoaded', function() {
                
                var loginButton = document.querySelector('.btn-login');
                var loginContainer = document.getElementById('loginContainer');
                var passwordField = document.getElementById('password');
                var loginForm = document.getElementById('loginForm');
                var loginCard = document.querySelector('.login-card');

                    // ============================================
                    // STEP 6: SUBSCRIBE TO EVENTS
                    // ============================================
                    NotificationCenter.subscribe('loginAttempt', function(data) {
                        console.log('📢 [NotificationCenter] Login attempt: ' + data.username);
                        showNotification('🔵 loginAttempt - Validating credentials for: ' + data.username);
                    });

                    NotificationCenter.subscribe('loginSuccess', function(data) {
                        console.log('📢 [NotificationCenter] Login success: ' + data.username);
                        showNotification('✅ loginSuccess - Welcome back, ' + data.username + '!');
                    });

                    NotificationCenter.subscribe('loginFailed', function(data) {
                        console.log('📢 [NotificationCenter] Login failed: ' + data.reason);
                        showNotification('❌ loginFailed - ' + data.reason);
                    });
                
                // ============================================
                // STEP 3: CLICK EVENT ON LOGIN BUTTON
                // ============================================
                loginButton.addEventListener('click', function(event) {
                    event.preventDefault();
                    
                    var username = document.getElementById('username').value;
                    var password = document.getElementById('password').value;
                    
                    // Check if fields are empty
                    if (username === '' || password === '') {
                        NotificationCenter.publish('loginFailed', { 
                            reason: 'Please fill in all fields' 
                        });
                        console.log('Login failed: Empty fields');
                        return;
                    }
                    
                    NotificationCenter.publish('loginAttempt', { 
                        username: username 
                    });
                    console.log('Mode: ' + validationMode);
                    console.log('Username: ' + username);
                    
                    // ============================================
                    // SYNC MODE - Blue highlight + Frozen
                    // ============================================
                  if (validationMode === 'sync') {
    console.log('🔒 SYNC MODE: Starting 3-second processing...');
    
    // ===== SHOW OVERLAY =====
    var overlay = document.getElementById('syncOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
    }
    
    var allInputs = document.querySelectorAll('#loginForm input, #loginForm button');
    
    // 1. Disable all inputs (freeze the form)
    allInputs.forEach(function(input) {
        input.disabled = true;
    });
    
    // 2. Add blue highlight AND frozen effect
    loginCard.classList.add('validation-progress');
    loginCard.classList.add('frozen');
    showNotification('⏳ Sync validation in progress... Form is FROZEN for 3 seconds');
    
    // ===== REMOVED THE BLOCKING WHILE LOOP =====
    // Instead, use setTimeout to simulate delay
    setTimeout(function() {
        // 4. Remove blue highlight AND frozen effect
        loginCard.classList.remove('validation-progress');
        loginCard.classList.remove('frozen');
        
        // 5. Enable all inputs
        allInputs.forEach(function(input) {
            input.disabled = false;
        });
        
        // ===== HIDE OVERLAY =====
        if (overlay) {
            overlay.style.display = 'none';
        }
        
        console.log('🔓 SYNC MODE: 3 seconds completed');
        NotificationCenter.publish('loginSuccess', { 
            username: username 
        });
        loginForm.submit();
    }, 3000);

                        
                    // ============================================
                    // ASYNC MODE - Button only (no card change)
                    // ============================================
                    } else if (validationMode === 'async') {
                        console.log('⚡ ASYNC MODE: Starting background validation...');
                        showNotification('⚡ Async validation in progress... Form is still responsive!');
                        
                        // 1. Change button to loading state ONLY
                        loginButton.classList.add('btn-loading');
                        loginButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Validating...';
                        
                        // 2. Non-blocking wait (3 seconds)
                        setTimeout(function() {
                            // 3. Restore button
                            loginButton.classList.remove('btn-loading');
                            loginButton.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Login';
                            
                        console.log('✅ ASYNC MODE: 3 seconds completed');
                        NotificationCenter.publish('loginSuccess', { 
                            username: username 
                        });
                        loginForm.submit();
                        }, 3000);
                    }
                });
                
                // ============================================
                // STEP 4: EVENT PROPAGATION (BUBBLING)
                // ============================================
                loginContainer.addEventListener('click', function(event) {
                    if (event.target === loginContainer) {
                        showNotification('🟢 Event bubbled up to container! (Click detected on form container)');
                        console.log('Container click detected! Event propagated upward.');
                    } else if (event.target === loginButton) {
                        console.log('Container handler: Event bubbled from button!');
                    } else {
                        console.log('Container handler caught click on: ' + event.target.tagName);
                    }
                });
                
                // ============================================
                // STEP 5: KEYBOARD EVENT - ENTER KEY
                // ============================================
                passwordField.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        
                        var username = document.getElementById('username').value;
                        var password = document.getElementById('password').value;
                        
                        if (username === '' || password === '') {
                            NotificationCenter.publish('loginFailed', { 
                                reason: 'Please fill in all fields' 
                            });
                            return;
                        }
                        
                        NotificationCenter.publish('loginAttempt', { 
                            username: username 
                        });
                        console.log('Enter key pressed in password field!');
                        console.log('Mode: ' + validationMode);
                        
                        // ============================================
                        // SYNC MODE (Enter) - Blue highlight + Frozen
                        // ============================================
                        if (validationMode === 'sync') {
    console.log('🔒 SYNC MODE (Enter): Starting 3-second processing...');

    // ===== SHOW OVERLAY =====
    var overlay = document.getElementById('syncOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
    }

    var allInputs = document.querySelectorAll('#loginForm input, #loginForm button');
    
    allInputs.forEach(function(input) {
        input.disabled = true;
    });
    
    loginCard.classList.add('validation-progress');
    loginCard.classList.add('frozen');
    showNotification('⏳ Sync validation in progress... Form is FROZEN for 3 seconds');
    
    // ===== REMOVED THE BLOCKING WHILE LOOP =====
    setTimeout(function() {
        // ===== HIDE OVERLAY =====
        if (overlay) {
            overlay.style.display = 'none';
        }
        
        loginCard.classList.remove('validation-progress');
        loginCard.classList.remove('frozen');
        
        allInputs.forEach(function(input) {
            input.disabled = false;
        });
        
        console.log('🔓 SYNC MODE (Enter): 3 seconds completed');
        NotificationCenter.publish('loginSuccess', { 
            username: username 
        });
        loginForm.submit();
    }, 3000);


                            
                        // ============================================
                        // ASYNC MODE (Enter) - Button only
                        // ============================================
                        } else if (validationMode === 'async') {
                            console.log('⚡ ASYNC MODE (Enter): Starting background validation...');
                            showNotification('⚡ Async validation in progress... Form is still responsive!');
                            
                            loginButton.classList.add('btn-loading');
                            loginButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Validating...';
                            
                            setTimeout(function() {
                                loginButton.classList.remove('btn-loading');
                                loginButton.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Login';
                                NotificationCenter.publish('loginSuccess', { 
                                    username: username 
                                });
                                loginForm.submit();
                            }, 3000);
                        }
                    }
                });
                
                // ============================================
                // STEP 5b: FORM SUBMIT EVENT
                // ============================================
                loginForm.addEventListener('submit', function(event) {
                    console.log('Form is submitting...');
                });
                
                // ============================================
                // STEP 6: FOCUS/BLUR EVENT - PASSWORD HINT
                // ============================================
                var passwordHint = document.getElementById('passwordHint');

                passwordField.addEventListener('focus', function() {
                    passwordHint.style.display = 'block';
                    passwordField.style.borderColor = '#2E75B6';
                    showNotification('🔍 focus - Password field focused');
                    console.log('Password field focused - showing hint');
                });

                passwordField.addEventListener('blur', function() {
                    passwordHint.style.display = 'none';
                    passwordField.style.borderColor = '#ddd';
                    showNotification('👁️ blur - Password field blurred');
                    console.log('Password field blurred - hiding hint');
                });

                // ============================================
                // STEP 7: SYNC/ASYNC MODE SWITCHING
                // ============================================
                var syncBtn = document.getElementById('syncModeBtn');
                var asyncBtn = document.getElementById('asyncModeBtn');

                syncBtn.addEventListener('click', function() {
                    validationMode = 'sync';
                    syncBtn.classList.add('active');
                    syncBtn.classList.remove('btn-outline-primary');
                    syncBtn.classList.add('btn-primary');
                    asyncBtn.classList.remove('active');
                    asyncBtn.classList.add('btn-outline-success');
                    asyncBtn.classList.remove('btn-success');
                    showNotification('⏳ Sync mode enabled - Form will freeze on login');
                    console.log('Switched to Synchronous mode');
                });

                asyncBtn.addEventListener('click', function() {
                    validationMode = 'async';
                    asyncBtn.classList.add('active');
                    asyncBtn.classList.remove('btn-outline-success');
                    asyncBtn.classList.add('btn-success');
                    syncBtn.classList.remove('active');
                    syncBtn.classList.add('btn-outline-primary');
                    syncBtn.classList.remove('btn-primary');
                    showNotification('⚡ Async mode enabled - Form stays responsive on login');
                    console.log('Switched to Asynchronous mode');
                });
            });

            // Helper function to check credentials (optional - not used)
            function checkCredentials() {
                var username = document.getElementById('username').value;
                var password = document.getElementById('password').value;
                
                console.log('🔍 Checking credentials...');
                console.log('Username: ' + username);
                console.log('Password: ' + password);
                
                if (username === 'admin' && password === 'admin123') {
                    showNotification('✅ loginSuccess - Welcome admin!');
                    console.log('LOGIN SUCCESS: Valid credentials');
                    document.getElementById('loginForm').submit();
                } else {
                    showNotification('❌ loginFailed - Invalid credentials (use admin/admin123)');
                    console.log('LOGIN FAILED: Invalid credentials');
                }
            }
    </script>
</body>
</html>