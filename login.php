<?php
/**
 * LOGIN.PHP - User Authentication Page
 * ====================================
 * PURPOSE: Allows users to log in with username/email and password
 * RELATIONSHIPS:
 *   - Requires: config.php (site constants, session setup)
 *   - Requires: auth.php (loginUser function, isLoggedIn function)
 *   - Requires: database.php (database connection)
 *   - Requires: functions.php (sanitizeInput, isValidEmail)
 *   - Processes login, redirects to dashboard.php on success
 *   - Part of the authentication flow: register.php → login.php → dashboard.php
 * 
 * WHEN THIS PAGE IS USED:
 *   - When user clicks "Login" link
 *   - When user tries to access protected page without being logged in
 *   - After registering a new account (user must log in)
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// If user is already logged in, redirect to dashboard (no need to login again)
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Initialize error and success messages
$error = '';
$success = '';

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get username and password from form
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation: both fields must be filled
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Connect to database
        $conn = getDBConnection();
        
        // Prepare statement to find user by username OR email
        // This allows login with either username or email
        $stmt = $conn->prepare("SELECT user_id, username, email, password_hash, role, status FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Check if exactly one user was found
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check if user account is active (not deactivated by admin)
            if ($user['status'] === 'inactive') {
                $error = 'Your account has been deactivated. Please contact support.';
            } 
            // Verify password using PHP's password_verify (one-way hashing)
            elseif (password_verify($password, $user['password_hash'])) {
                // Password is correct! Log the user in
                loginUser($user['user_id'], $user['username'], $user['email'], $user['role']);
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                // Password is incorrect
                $error = 'Invalid username or password.';
            }
        } else {
            // User not found (show generic error to not reveal if email exists)
            $error = 'Invalid username or password.';
        }
        
        $stmt->close();
        closeDBConnection($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Authentication container - centered, styled box for login form -->
    <div class="auth-container">
        <div class="auth-box">
            <!-- Page heading -->
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Login to track your plastic reduction journey</p>
            
            <!-- Display error message if login failed -->
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Display success message if account just created -->
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Login form -->
            <form method="POST" action="" class="auth-form">
                <!-- Username or Email field -->
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>">
                </div>
                
                <!-- Password field -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <!-- Submit button -->
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <!-- Links to other pages -->
            <div class="auth-links">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p><a href="index.php">Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>