<?php
/**
 * DATABASE.PHP - Database Connection Management
 * ==============================================
 * PURPOSE: Handles all database connection creation and cleanup
 * RELATIONSHIPS:
 *   - Requires: config.php (for DB_HOST, DB_USER, DB_PASS, DB_NAME constants)
 *   - Used by: auth.php, functions.php, and all page files
 *   - Called whenever database queries are needed
 * WITHOUT THIS FILE: Cannot connect to database; all queries would fail
 */

require_once 'config.php';

/**
 * getDBConnection() - Creates and returns a new database connection
 * 
 * RETURNS: mysqli object representing the database connection
 * USAGE: $conn = getDBConnection();
 * 
 * WHAT IT DOES:
 *   1. Creates a new MySQLi connection using credentials from config.php
 *   2. Checks for connection errors and logs them
 *   3. Sets character set to UTF-8 (utf8mb4) for proper encoding of special characters
 *   4. Returns the connection object for use in queries
 * 
 * ERROR HANDLING:
 *   - If connection fails, displays error message and exits
 *   - Logs error to PHP error log for debugging
 */
function getDBConnection() {
    // Create new MySQLi connection with credentials from config.php
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check if connection was successful
    if ($conn->connect_error) {
        // Log error to PHP error log (saved in error_log file)
        error_log("Database connection failed: " . $conn->connect_error);
        // Display friendly error message to user
        die("Connection failed. Please try again later.");
    }
    
    // Set character encoding to UTF-8 (supports special characters, emojis, etc.)
    $conn->set_charset("utf8mb4");
    
    return $conn; // Return the connection object
}

/**
 * closeDBConnection() - Safely closes a database connection
 * 
 * PARAMETER: $conn - The mysqli connection object to close
 * USAGE: closeDBConnection($conn);
 * 
 * WHAT IT DOES:
 *   1. Checks if connection exists (not null)
 *   2. Closes the connection, freeing up database resources
 *   3. Prevents resource leaks by ensuring connections are properly closed
 * 
 * WHY IMPORTANT:
 *   - Database has limited connections; not closing them wastes resources
 *   - Good practice: always close connections after queries are complete
 *   - Called at the end of every script that uses database
 */
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>