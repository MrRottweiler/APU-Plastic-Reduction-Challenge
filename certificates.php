<?php
/**
 * CERTIFICATES.PHP - User Certificates Display Page
 * ==================================================
 * PURPOSE: Displays all certificates earned by logged-in user
 * RELATIONSHIPS:
 *   - Requires: config.php, auth.php, functions.php
 *   - requireLogin(): Ensures only logged-in users can view
 *   - getCurrentUser(): Gets current user ID
 *   - getUserCertificates(): Fetches user's earned certificates from database
 *   - Displays achievements and gamification elements
 * 
 * DATABASE FLOW:
 *   user_certificates table (joined with certificates table) → displayed on page
 * 
 * FEATURES:
 *   - Shows all earned certificates with details
 *   - Displays award date and who awarded it
 *   - Shows personal messages from admins
 *   - Motivational message if no certificates yet (encourage logging)
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Ensure user is logged in before accessing this page
requireLogin();

// Get current logged-in user
$user = getCurrentUser();

// Fetch all certificates earned by this user from database
$certificates = getUserCertificates($user['user_id']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificates - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- Main page header with navigation -->
    <header class="main-header">
        <div class="container">
            <!-- Hidden checkbox for CSS-only menu toggle (mobile responsiveness) -->
            <input type="checkbox" id="menu-toggle" class="menu-toggle">

            <div class="header-content">
                <!-- Site Title linked to Dashboard -->
                <a href="dashboard.php" class="site-title">
                    <h1><?php echo SITE_NAME; ?></h1>
                </a>

                <!-- Hamburger Icon (Label that controls the checkbox) -->
                <label for="menu-toggle" class="nav-icon">&#9776;</label>
            </div>

            <!-- Navigation menu - sibling of checkbox for CSS selector to work -->
            <nav class="main-nav">
                <a href="index.php" class="nav-link">Home</a>
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="log_entry.php" class="nav-link">Log Entry</a>
                <a href="certificates.php" class="nav-link active">Certificates</a>
                <a href="profile.php" class="nav-link">Profile</a>
                <!-- Show Admin link only if user is admin -->
                <?php if (hasRole('admin')): ?>
                    <a href="admin/dashboard.php" class="nav-link">Admin</a>
                <?php endif; ?>
                <a href="?logout=1" class="nav-link">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Main content area -->
    <main class="main-content">
        <div class="container">
            <!-- Page title and description -->
            <h2 class="section-title">My Certificates</h2>
            <p class="section-description">Celebrate your achievements! Every certificate represents a significant milestone in reducing plastic waste.</p>

            <!-- Check if user has earned any certificates -->
            <?php if (empty($certificates)): ?>
                <!-- Empty state: User has no certificates yet -->
                <div class="empty-state card">
                    <p>
                        <span class="icon-large">&#127881;</span> <!-- Party emoji -->
                    </p>
                    <h3>No Certificates Yet!</h3>
                    <p>You haven't earned any certificates yet. Keep logging your plastic reduction efforts to unlock achievements and become a **Plastic Hero**!</p>
                    <!-- Motivational button to log first entry -->
                    <a href="log_entry.php" class="btn btn-primary btn-lg">Log Your First Entry</a>
                </div>
            <?php else: ?>
                <!-- Display all earned certificates in grid layout -->
                <div class="certificates-grid">
                    <?php foreach ($certificates as $cert): ?>
                        <!-- Individual certificate card -->
                        <div class="certificate-card certificate-<?php echo $cert['design_style']; ?>">
                            <!-- Certificate header with icon and name -->
                            <div class="certificate-header">
                                <span class="certificate-icon">&#127942;</span> <!-- Trophy emoji -->
                                <h4><?php echo htmlspecialchars($cert['name']); ?></h4>
                            </div>
                            
                            <!-- Certificate description -->
                            <p><?php echo htmlspecialchars($cert['description']); ?></p>
                            
                            <!-- Optional personal message from admin -->
                            <?php if ($cert['personal_message']): ?>
                                <p class="message-quote"><em>"<?php echo htmlspecialchars($cert['personal_message']); ?>"</em></p>
                            <?php endif; ?>
                            
                            <!-- Certificate footer with metadata -->
                            <div class="certificate-footer">
                                <small>Awarded on: <strong><?php echo date('M d, Y', strtotime($cert['awarded_date'])); ?></strong></small>
                                <small>By: <?php echo htmlspecialchars($cert['awarded_by_name']); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Page footer -->
    <footer class="main-footer">
        <div class="container">
            <p>&copy; 2024 <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- JavaScript for interactivity -->
    <script src="js/script.js"></script>
</body>

</html>

<?php
// Handle logout if user clicks logout link
if (isset($_GET['logout'])) {
    logoutUser(); // Destroys session and redirects to login.php
}
?>