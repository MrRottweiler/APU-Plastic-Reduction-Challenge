<?php
/**
 * AUTH.PHP - Authentication & Authorization System
 * =================================================
 * PURPOSE: Manages user login/logout, role checking, and session management
 * RELATIONSHIPS:
 *   - Requires: config.php (for session constants), database.php (for DB connection)
 *   - Used by: Every protected page (dashboard.php, log_entry.php, certificates.php, profile.php)
 *   - Also used by: Admin pages (admin/users.php, admin/logs.php, etc.)
 * WITHOUT THIS FILE: Users could bypass login; no authorization checks would exist
 * 
 * KEY CONCEPT: This file checks if users are logged in before allowing access to protected pages
 */

require_once 'config.php';
require_once 'database.php';

/**
 * isLoggedIn() - Checks if a user is currently logged in
 * 
 * RETURNS: true if user is logged in, false otherwise
 * USAGE: if (isLoggedIn()) { ... }
 * 
 * HOW IT WORKS:
 *   - Checks if $_SESSION['user_id'] exists and is not empty
 *   - $_SESSION['user_id'] is set when user successfully logs in (see loginUser function)
 *   - $_SESSION is destroyed when user logs out (see logoutUser function)
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * hasRole($role) - Checks if logged-in user has a specific role
 * 
 * PARAMETER: $role - The role to check ('admin' or 'participant')
 * RETURNS: true if user is logged in AND has the specified role
 * USAGE: if (hasRole('admin')) { ... }
 * 
 * ROLES IN THE SYSTEM:
 *   - 'participant': Regular users who can log plastic reduction and earn certificates
 *   - 'admin': Users with special permissions to manage users, logs, and certificates
 * 
 * HOW IT WORKS:
 *   1. First checks if user is logged in (prevents errors)
 *   2. Then checks if $_SESSION['role'] matches the requested role
 *   3. Returns true only if both conditions are met
 */
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * requireLogin() - Forces user to login if not already logged in
 * 
 * USAGE: Call at the top of any protected page
 *        require_once 'auth.php';
 *        requireLogin();  // If not logged in, redirects to login.php
 * 
 * HOW IT WORKS:
 *   1. Calls isLoggedIn() to check if user is logged in
 *   2. If not logged in, redirects to login.php
 *   3. Exits script to prevent further execution
 * 
 * PAGES THAT USE THIS:
 *   - dashboard.php
 *   - log_entry.php
 *   - certificates.php
 *   - profile.php
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php'); // Redirect to login page
        exit();
    }
}

/**
 * requireRole($role) - Forces user to have specific role or redirects
 * 
 * PARAMETER: $role - The required role ('admin', 'participant', etc.)
 * USAGE: requireRole('admin');  // Only admins can proceed
 * 
 * HOW IT WORKS:
 *   1. First calls requireLogin() to ensure user is logged in
 *   2. Then checks if user has the required role
 *   3. If not, redirects to dashboard.php (general user page)
 * 
 * PAGES THAT USE THIS:
 *   - admin/dashboard.php
 *   - admin/users.php
 *   - admin/logs.php
 *   - admin/certificates.php
 *   - admin/reports.php
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: dashboard.php'); // Redirect to user dashboard (not admin)
        exit();
    }
}

/**
 * loginUser($user_id, $username, $email, $role) - Sets up user session after successful login
 * 
 * PARAMETERS:
 *   - $user_id: The unique ID of the user (from users table)
 *   - $username: The username for display throughout the app
 *   - $email: The user's email address
 *   - $role: The user's role ('admin' or 'participant')
 * 
 * USAGE: Called from login.php after password verification
 *        loginUser($user['user_id'], $user['username'], $user['email'], $user['role']);
 * 
 * WHAT IT DOES:
 *   1. Stores user information in $_SESSION (stored on server, accessible across pages)
 *   2. Records login time for tracking
 *   3. Updates the 'last_login' timestamp in the users table (database)
 *   4. After this function, user is considered logged in
 * 
 * SESSION VARIABLES SET:
 *   - $_SESSION['user_id']: Used to track which user is logged in
 *   - $_SESSION['username']: Displayed in greeting ("Welcome back, {username}!")
 *   - $_SESSION['email']: Used for email verification/notifications
 *   - $_SESSION['role']: Used to check permissions (admin vs participant)
 *   - $_SESSION['login_time']: Records when user logged in
 */
function loginUser($user_id, $username, $email, $role) {
    // Store user info in session (server-side storage)
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['login_time'] = time();
    
    // Update last login timestamp in database
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    closeDBConnection($conn);
}

/**
 * logoutUser() - Destroys user session and logs them out
 * 
 * USAGE: logoutUser();  // Call this to log out current user
 * 
 * WHAT IT DOES:
 *   1. Unsets all session variables (clears $_SESSION)
 *   2. Destroys the session (removes session file from server)
 *   3. Redirects user to login page
 * 
 * RESULT: User must log in again to access protected pages
 * 
 * HOW IT'S TRIGGERED:
 *   - When user clicks "Logout" link (?logout=1)
 *   - Scripts check for $_GET['logout'] and call logoutUser()
 */
function logoutUser() {
    session_unset();    // Clear all session variables
    session_destroy();  // Destroy the session file
    header('Location: login.php'); // Redirect to login page
    exit();
}

/**
 * getCurrentUser() - Returns an array of current logged-in user's information
 * 
 * RETURNS: Array with keys: user_id, username, email, role
 *          Returns null if no user is logged in
 * 
 * USAGE: $user = getCurrentUser();
 *        echo $user['username'];  // Access user information
 * 
 * WHAT IT DOES:
 *   1. Checks if user is logged in
 *   2. If not, returns null
 *   3. If yes, returns array with all session data
 * 
 * USED BY: Almost every page that needs user information
 *          Example: dashboard.php greets user with $user['username']
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    // Return an array containing all logged-in user's information
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role']
    ];
}
?>