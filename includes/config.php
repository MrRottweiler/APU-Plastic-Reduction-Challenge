<?php
/**
 * CONFIG.PHP - Central Configuration File
 * ========================================
 * PURPOSE: This file contains all global configuration constants and settings for the application.
 * RELATIONSHIPS: 
 *   - Included by EVERY file in the application (auth.php, database.php, all page files)
 *   - Must be loaded FIRST before any other includes
 *   - Sets up database credentials, site constants, and session handling
 * WITHOUT THIS FILE: No other files can work; app cannot connect to database or handle sessions
 */

// ===== DATABASE CREDENTIALS =====
// These constants define how the application connects to the MySQL database
define('DB_HOST', 'localhost');      // MySQL server address (localhost for local development)
define('DB_USER', 'root');           // MySQL username (default 'root' for WAMP)
define('DB_PASS', 'caution_12warning@'); // MySQL password (should be kept secure in production)
define('DB_NAME', 'apu_plastic_challenge'); // Database name where all tables are stored

// ===== APPLICATION SETTINGS =====
// These constants are used throughout the app for consistent branding and URLs
define('SITE_NAME', 'APU Plastic Reduction Challenge'); // Used in page titles, headers, footers
define('SITE_URL', 'http://localhost/apu-plastic-challenge'); // Base URL for the application

// ===== SESSION SECURITY CONFIGURATION =====
// These settings enhance security by restricting session cookie access
ini_set('session.cookie_httponly', 1);   // Prevents JavaScript from accessing cookies (XSS protection)
ini_set('session.use_only_cookies', 1);  // Uses only cookies for session (not URL parameters)
ini_set('session.cookie_secure', 0);     // Set to 1 if using HTTPS; 0 for HTTP (local development)

// ===== SESSION INITIALIZATION =====
// Start the PHP session if it hasn't been started yet
// Sessions allow user login state to persist across page requests
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== TIMEZONE SETTING =====
// Set the default timezone for all date/time functions to Asia/Kuala_Lumpur (Malaysia timezone)
date_default_timezone_set('Asia/Kuala_Lumpur');
?>