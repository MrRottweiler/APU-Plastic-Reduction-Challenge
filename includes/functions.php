<?php
/**
 * FUNCTIONS.PHP - Utility & Business Logic Functions
 * ===================================================
 * PURPOSE: Contains all reusable functions for data validation, calculations, and database queries
 * RELATIONSHIPS:
 *   - Requires: database.php (for getDBConnection, closeDBConnection)
 *   - Used by: log_entry.php, dashboard.php, index.php, all admin pages, and more
 *   - Works with database tables: logs, environmental_factors, certificates, user_certificates, users
 * WITHOUT THIS FILE: Core business logic (impact calculation, stats, certificates) cannot work
 */

require_once 'database.php';

/**
 * sanitizeInput($data) - Cleans user input to prevent security attacks
 * 
 * PARAMETER: $data - Raw user input (from form submission, $_POST, $_GET)
 * RETURNS: Cleaned, safe string
 * USAGE: $safe_name = sanitizeInput($_POST['name']);
 * 
 * WHAT IT DOES:
 *   1. trim(): Removes whitespace from beginning/end ("  hello  " → "hello")
 *   2. stripslashes(): Removes escape characters (added by PHP magic quotes)
 *   3. htmlspecialchars(): Converts special chars to HTML entities (< → &lt;)
 *                          Prevents HTML/JavaScript injection
 * 
 * WHY IMPORTANT:
 *   - Users might submit malicious code in forms
 *   - This protects against XSS (Cross-Site Scripting) attacks
 *   - ALWAYS use this for user input before displaying or storing
 * 
 * USED BY: Nearly every form processing page
 */
function sanitizeInput($data) {
    $data = trim($data);                    // Remove whitespace
    $data = stripslashes($data);            // Remove escape characters
    $data = htmlspecialchars($data);        // Convert special chars to HTML entities
    return $data;
}

/**
 * isValidEmail($email) - Validates email address format
 * 
 * PARAMETER: $email - Email address to validate
 * RETURNS: true if valid email format, false otherwise
 * USAGE: if (isValidEmail($email)) { ... }
 * 
 * WHAT IT DOES:
 *   - Uses PHP's built-in FILTER_VALIDATE_EMAIL filter
 *   - Checks if email matches standard email format (name@domain.com)
 *   - Does NOT verify if email actually exists (that would require sending test email)
 * 
 * USED BY: register.php, profile.php (when validating email input)
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * getEnvironmentalFactors() - Retrieves all active environmental impact factors
 * 
 * RETURNS: Array with structure:
 *          [
 *            'bottle' => ['co2' => 10.5, 'water' => 25.0, 'source' => 'EPA Data'],
 *            'bag' => ['co2' => 3.2, 'water' => 8.0, 'source' => 'EPA Data'],
 *            'container' => ['co2' => 5.0, 'water' => 12.0, 'source' => 'EPA Data']
 *          ]
 * 
 * USAGE: $factors = getEnvironmentalFactors();
 *        echo $factors['bottle']['co2'];  // Get CO2 saved per bottle
 * 
 * WHAT IT DOES:
 *   1. Connects to database
 *   2. Selects all active environmental factors (is_active = 1)
 *   3. Creates an array where item_type is the key for easy lookup
 *   4. Stores CO2 and water savings data for each item type
 *   5. Closes connection and returns array
 * 
 * DATABASE TABLE: environmental_factors
 *   - Columns: factor_id, item_type, co2_saved_grams, water_saved_liters, data_source, is_active
 * 
 * USED BY: calculateImpact(), log_entry.php (for client-side calculation)
 */
function getEnvironmentalFactors() {
    $conn = getDBConnection();
    $factors = [];
    
    // Query gets all active factors from environmental_factors table
    $result = $conn->query("SELECT * FROM environmental_factors WHERE is_active = 1");
    
    // Loop through each factor and organize by item_type for easy access
    while ($row = $result->fetch_assoc()) {
        $factors[$row['item_type']] = [
            'co2' => $row['co2_saved_grams'],      // CO2 saved per item (in grams)
            'water' => $row['water_saved_liters'],  // Water saved per item (in liters)
            'source' => $row['data_source']         // Data source (EPA, research, etc.)
        ];
    }
    
    closeDBConnection($conn);
    return $factors;
}

/**
 * calculateImpact($item_type, $quantity) - Calculates environmental impact of logged items
 * 
 * PARAMETERS:
 *   - $item_type: Type of item ('bottle', 'bag', 'container')
 *   - $quantity: Number of items saved
 * 
 * RETURNS: Array ['co2' => total_co2_saved, 'water' => total_water_saved]
 * USAGE: $impact = calculateImpact('bottle', 5);
 *        // Returns: ['co2' => 52.5, 'water' => 125.0]
 * 
 * CALCULATION LOGIC:
 *   - Gets environmental factors (how much each item saves)
 *   - Multiplies per-item savings by quantity to get total
 *   - Example: If bottle saves 10g CO2, and user saves 5 bottles:
 *             Total = 10 × 5 = 50g CO2
 * 
 * USED BY: 
 *   - log_entry.php (when user submits a log entry)
 *   - Calculates values stored in 'logs' table
 * 
 * DATABASE FLOW:
 *   logs table stores: quantity, co2_saved, water_saved
 *   These values are calculated by this function
 */
function calculateImpact($item_type, $quantity) {
    $factors = getEnvironmentalFactors();
    
    // Check if item_type exists in our factors (validation)
    if (!isset($factors[$item_type])) {
        return ['co2' => 0, 'water' => 0]; // Return zeros if invalid item type
    }
    
    // Multiply per-item savings by quantity to get total impact
    return [
        'co2' => $factors[$item_type]['co2'] * $quantity,
        'water' => $factors[$item_type]['water'] * $quantity
    ];
}

/**
 * getUserStats($user_id) - Gets cumulative statistics for a specific user
 * 
 * PARAMETER: $user_id - ID of the user to get stats for
 * RETURNS: Array with keys:
 *          - total_entries: Number of log entries created
 *          - total_items: Total quantity of items saved
 *          - total_co2: Total CO2 prevented (in grams)
 *          - total_water: Total water conserved (in liters)
 *          - first_entry: Date of user's first log entry
 * 
 * USAGE: $stats = getUserStats($user_id);
 *        echo $stats['total_items'];  // e.g., 45 items
 * 
 * WHAT IT DOES:
 *   1. Queries the logs table for current user
 *   2. Counts total entries (how many times they logged)
 *   3. Sums all quantities (total items saved)
 *   4. Sums all CO2 saved (environmental benefit)
 *   5. Sums all water saved (environmental benefit)
 *   6. Finds date of first entry (shows how long they've been active)
 * 
 * DATABASE TABLE: logs
 *   - Stores individual log entries with quantity, co2_saved, water_saved
 *   - This function aggregates all entries for a user
 * 
 * USED BY: 
 *   - dashboard.php (displays user's impact statistics)
 *   - profile.php (shows lifetime statistics)
 */
function getUserStats($user_id) {
    $conn = getDBConnection();
    
    // SQL aggregation query
    // COUNT(*) = total entries
    // SUM(quantity) = total items
    // SUM(co2_saved) = total CO2 prevented
    // SUM(water_saved) = total water conserved
    // MIN(log_date) = earliest log date
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_entries,
            SUM(quantity) as total_items,
            SUM(co2_saved) as total_co2,
            SUM(water_saved) as total_water,
            MIN(log_date) as first_entry
        FROM logs 
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    $stmt->close();
    
    closeDBConnection($conn);
    return $stats;
}

/**
 * getCommunityStats() - Gets aggregate statistics for entire community
 * 
 * RETURNS: Array with keys:
 *          - total_participants: Number of users who have logged entries
 *          - total_items: Total items saved by all users
 *          - total_co2: Total CO2 prevented by community (in grams)
 *          - total_water: Total water conserved by community (in liters)
 * 
 * USAGE: $stats = getCommunityStats();
 *        echo $stats['total_participants'];  // e.g., 156 users
 * 
 * WHAT IT DOES:
 *   1. Sums ALL values from logs table (not filtered by user)
 *   2. Counts DISTINCT users who have logs (users who participated)
 *   3. Shows overall community impact
 * 
 * USED BY:
 *   - index.php (home page - shows community impact)
 *   - admin/dashboard.php (admin overview)
 * 
 * IMPACT: Shows the scale of the project's environmental benefit
 */
function getCommunityStats() {
    $conn = getDBConnection();
    
    // Query that sums ALL logs from ALL users
    $query = "
        SELECT 
            COUNT(DISTINCT user_id) as total_participants,
            SUM(quantity) as total_items,
            SUM(co2_saved) as total_co2,
            SUM(water_saved) as total_water
        FROM logs
    ";
    
    $result = $conn->query($query);
    $stats = $result->fetch_assoc();
    
    closeDBConnection($conn);
    return $stats;
}

/**
 * getLeaderboard($limit) - Gets top contributors ranked by items saved
 * 
 * PARAMETER: $limit - Maximum number of users to return (default: 10)
 * RETURNS: Array of user records, each with:
 *          - username: User's username
 *          - total_items: Total items they've saved
 *          - total_co2: Total CO2 they've prevented
 *          - total_water: Total water they've conserved
 * 
 * USAGE: $leaderboard = getLeaderboard(10);  // Get top 10 contributors
 *        foreach ($leaderboard as $rank => $user) {
 *            echo ($rank + 1) . ". " . $user['username'] . " - " . $user['total_items'] . " items";
 *        }
 * 
 * WHAT IT DOES:
 *   1. JOINs users table with logs table
 *   2. Groups results by user
 *   3. Sums each user's total items, CO2, water
 *   4. Orders by total_items DESC (highest first)
 *   5. Limits to specified number
 * 
 * SQL LOGIC:
 *   - GROUP BY groups all logs per user
 *   - ORDER BY ... DESC shows highest contributors first
 *   - LIMIT restricts number of results
 * 
 * USED BY:
 *   - index.php (home page - shows top contributors)
 *   - Gamification: encourages users to compete
 * 
 * DATABASE FLOW:
 *   users table + logs table → aggregated & ranked
 */
function getLeaderboard($limit = 10) {
    $conn = getDBConnection();
    
    // Prepared statement for safe parameter passing
    $stmt = $conn->prepare("
        SELECT 
            u.username,
            SUM(l.quantity) as total_items,
            SUM(l.co2_saved) as total_co2,
            SUM(l.water_saved) as total_water
        FROM users u
        JOIN logs l ON u.user_id = l.user_id
        WHERE u.role IN ('participant', 'admin')
        GROUP BY u.user_id, u.username
        ORDER BY total_items DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch all results into array
    $leaderboard = [];
    while ($row = $result->fetch_assoc()) {
        $leaderboard[] = $row;
    }
    
    $stmt->close();
    closeDBConnection($conn);
    return $leaderboard;
}

/**
 * checkAndAwardCertificates($user_id) - Automatically awards certificates user qualifies for
 * 
 * PARAMETER: $user_id - ID of user to check and award certificates to
 * RETURNS: None (performs database modifications)
 * USAGE: Called after user creates a log entry
 *        checkAndAwardCertificates($user_id);
 * 
 * WHAT IT DOES:
 *   1. Calculates user's total items saved (cumulative)
 *   2. Finds all 'auto' type certificates (automatic triggers)
 *   3. Checks if user qualifies for any new certificates
 *   4. Inserts new certificates into user_certificates table
 *   5. Prevents duplicate awards (user won't get same cert twice)
 * 
 * CERTIFICATE TYPES:
 *   - 'auto': Awarded automatically when criteria_value is reached
 *            Example: Auto certificate for 10 items saved
 *            User automatically gets it when total hits 10
 *   - 'manual': Awarded by admin through admin panel
 *              Requires manual decision
 * 
 * LOGIC FLOW:
 *   1. Get user's total items from logs
 *   2. Find certificates where:
 *      - criteria_type = 'auto' (automatic triggers)
 *      - criteria_value <= total_items (user qualifies)
 *      - User doesn't already have it (LEFT JOIN check)
 *   3. Insert each new certificate into user_certificates
 * 
 * USED BY: log_entry.php (after successful log submission)
 * GAMIFICATION: Encourages users to log more entries to unlock achievements
 */
function checkAndAwardCertificates($user_id) {
    $conn = getDBConnection();
    
    // Step 1: Get user's current total items saved
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM logs WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_items = $row['total'] ?? 0;  // Use 0 if no logs yet
    $stmt->close();
    
    // Step 2: Find qualifying auto certificates
    // LEFT JOIN + WHERE ... IS NULL checks if user doesn't already have the certificate
    $stmt = $conn->prepare("
        SELECT c.certificate_id 
        FROM certificates c
        LEFT JOIN user_certificates uc ON c.certificate_id = uc.certificate_id AND uc.user_id = ?
        WHERE c.criteria_type = 'auto' 
        AND c.criteria_value <= ?
        AND uc.user_certificate_id IS NULL
    ");
    $stmt->bind_param("ii", $user_id, $total_items);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Step 3: Award each new certificate
    while ($row = $result->fetch_assoc()) {
        $cert_stmt = $conn->prepare("
            INSERT INTO user_certificates (user_id, certificate_id, awarded_by) 
            VALUES (?, ?, ?)
        ");
        // awarded_by = user_id (system awards it, user is the "awarder")
        $cert_stmt->bind_param("iii", $user_id, $row['certificate_id'], $user_id);
        $cert_stmt->execute();
        $cert_stmt->close();
    }
    
    $stmt->close();
    closeDBConnection($conn);
}

/**
 * getUserCertificates($user_id) - Retrieves all certificates earned by a user
 * 
 * PARAMETER: $user_id - ID of user whose certificates to retrieve
 * RETURNS: Array of certificate records, each with:
 *          - certificate_id: Unique ID
 *          - name: Certificate name (e.g., "Plastic Hero")
 *          - description: What the certificate represents
 *          - design_style: CSS class for styling (e.g., "gold", "silver")
 *          - awarded_date: When certificate was earned
 *          - personal_message: Optional message from admin
 *          - awarded_by_name: Username of person who awarded it
 * 
 * USAGE: $certs = getUserCertificates($user_id);
 *        foreach ($certs as $cert) {
 *            echo $cert['name'];  // Display certificate name
 *        }
 * 
 * WHAT IT DOES:
 *   1. JOINs three tables together:
 *      - user_certificates: Links users to their certificates
 *      - certificates: Contains certificate definitions
 *      - users: Contains awarder information
 *   2. Filters by user_id
 *   3. Orders by most recent first
 *   4. Returns complete certificate information
 * 
 * DATABASE JOINS:
 *   user_certificates ← links to → certificates (certificate info)
 *                   ← links to → users (who awarded it)
 * 
 * USED BY:
 *   - certificates.php (displays all user certificates)
 *   - dashboard.php (shows recent achievements)
 * 
 * DISPLAY: Shows user's achievements with award dates and details
 */
function getUserCertificates($user_id) {
    $conn = getDBConnection();
    
    // JOINs to get complete certificate information
    $stmt = $conn->prepare("
        /**
         * Retrieves all certificates awarded to a specific user with details.
         *
         * This SQL query fetches a complete list of certificates that have been awarded to a user,
         * including certificate information and award metadata. It performs two JOIN operations to
         * retrieve certificate details and the name of the user who awarded the certificate.
         *
         * @return array Returns an array of certificate records, each containing:
         *   - certificate_id: Unique identifier of the certificate
         *   - name: Name/title of the certificate
         *   - description: Detailed description of the certificate
         *   - design_style: Visual style or design template of the certificate
         *   - awarded_date: Timestamp when the certificate was awarded to the user
         *   - personal_message: Optional personalized message included with the award
         *   - awarded_by_name: Username of the user who awarded the certificate
         *
         * @param int $user_id The ID of the user whose certificates should be retrieved
         *
         * The results are sorted in descending order by awarded_date, displaying the most
         * recently awarded certificates first.
         *
         * Database tables involved:
         * - user_certificates: Junction table storing award records
         * - certificates: Contains certificate definitions and metadata
         * - users: Contains user information for the awarder
         */
        SELECT 
            c.certificate_id,
            c.name,
            c.description,
            c.design_style,
            uc.awarded_date,
            uc.personal_message,
            u.username as awarded_by_name
        FROM user_certificates uc
        JOIN certificates c ON uc.certificate_id = c.certificate_id
        JOIN users u ON uc.awarded_by = u.user_id
        WHERE uc.user_id = ?
        ORDER BY uc.awarded_date DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch all certificates into array
    $certificates = [];
    while ($row = $result->fetch_assoc()) {
        $certificates[] = $row;
    }
    
    $stmt->close();
    closeDBConnection($conn);
    return $certificates;
}

/**
 * formatNumber($number) - Formats number with thousand separators
 * 
 * PARAMETER: $number - Number to format
 * RETURNS: Formatted string
 * USAGE: echo formatNumber(1000);  // Outputs: "1,000"
 * 
 * WHAT IT DOES:
 *   - Uses PHP's number_format() with 0 decimal places
 *   - Adds commas every 3 digits for readability
 *   - Example: 1234567 → "1,234,567"
 * 
 * USED BY: Almost every page that displays numbers
 * EXAMPLE USES:
 *   - Display total items: 45 → "45"
 *   - Display large numbers: 1000000 → "1,000,000"
 *   - Stats cards, tables, leaderboards
 */
function formatNumber($number) {
    return number_format($number, 0);
}

/**
 * formatDecimal($number) - Formats number with 2 decimal places
 * 
 * PARAMETER: $number - Number to format
 * RETURNS: Formatted string with exactly 2 decimal places
 * USAGE: echo formatDecimal(123.4);  // Outputs: "123.40"
 * 
 * WHAT IT DOES:
 *   - Uses number_format() with exactly 2 decimal places
 *   - Ensures consistent display of decimal values
 *   - Adds trailing zeros if needed: 10 → "10.00"
 * 
 * USED BY:
 *   - Displaying water savings (liters): "25.50L"
 *   - Monetary values if used in future
 *   - Any field requiring decimal precision
 */
function formatDecimal($number) {
    return number_format($number, 2);
}
?>
