<?php
/**
 * REGISTER.PHP - User Registration Page
 * =====================================
 * PURPOSE: Allows new users to create accounts
 * RELATIONSHIPS:
 *   - Requires: config.php, auth.php, database.php, functions.php
 *   - Collects user data and inserts into 'users' table
 *   - After registration, user must log in via login.php
 *   - Part of authentication flow: register.php → login.php → dashboard.php
 * 
 * SECURITY FEATURES:
 *   - Password hashing using PASSWORD_DEFAULT (bcrypt)
 *   - Input sanitization
 *   - Email validation
 *   - Duplicate checking (username and email)
 *   - Client-side validation (HTML5) + server-side validation
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Redirect if already logged in (no need to register again)
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Initialize error and success messages
$error = '';
$success = '';

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form input and sanitize
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // ===== VALIDATION =====
    // Check all fields are filled
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } 
    // Check username length (3-50 characters)
    elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Username must be between 3 and 50 characters.';
    } 
    // Validate email format
    elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } 
    // Check password minimum length
    elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } 
    // Check passwords match
    elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } 
    // All validation passed - proceed with registration
    else {
        $conn = getDBConnection();
        
        // Check if username already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Username already exists.';
        }
        $stmt->close();
        
        // Check if email already exists (only if no username error)
        if (empty($error)) {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = 'Email already registered.';
            }
            $stmt->close();
        }
        
        // Register user if all checks passed
        if (empty($error)) {
            // Hash password using bcrypt (one-way encryption)
            // PASSWORD_DEFAULT uses bcrypt which is slow (intentionally) to resist brute force
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user into users table
            // role defaults to 'participant' (regular user, not admin)
            // status defaults to 'active' (can log in immediately)
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'participant')");
            $stmt->bind_param("sss", $username, $email, $password_hash);
            
            if ($stmt->execute()) {
                // Registration successful!
                $success = 'Registration successful! You can now login.';
                // Clear form fields
                $username = '';
                $email = '';
            } else {
                // Database error during insertion
                $error = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
        
        closeDBConnection($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Authentication container - centered form styling -->
    <div class="auth-container">
        <div class="auth-box">
            <!-- Page heading -->
            <h1 class="auth-title">Join the Challenge</h1>
            <p class="auth-subtitle">Start tracking your plastic reduction today</p>
            
            <!-- Display error if validation failed -->
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Display success if registration completed -->
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Registration form -->
            <form method="POST" action="" class="auth-form">
                <!-- Username field -->
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>"
                           minlength="3" maxlength="50">
                </div>
                
                <!-- Email field -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>
                
                <!-- Password field -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                    <small>Minimum 6 characters</small>
                </div>
                
                <!-- Confirm password field -->
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <!-- Submit button -->
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>
            
            <!-- Links to login and home -->
            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <p><a href="index.php">Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>