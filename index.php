<!-- ****************************************** -->


<!-- Guest Home Page -->

<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/database.php';

// Auto-create admin account if it doesn't exist (runs only once)
function ensureAdminExists()
{
    $conn = getDBConnection();

    // Check if any admin exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // No admin exists, create default admin
        $username = 'admin';
        $email = 'admin@apu.edu.my';
        $password = 'admin123'; // Default password - should be changed after first login
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, 'admin', 'active')");
        $stmt->bind_param("sss", $username, $email, $password_hash);
        $stmt->execute();

        // Log this action (optional)
        error_log("Default admin account created automatically");
    }

    $stmt->close();
    closeDBConnection($conn);
}

// Call the function to ensure admin exists
ensureAdminExists();

$stats = getCommunityStats();
$leaderboard = getLeaderboard(10);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Home</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <header class="main-header">
        <div class="container">
            <!-- 1. Hidden checkbox for CSS-only menu toggle (Must be a direct sibling of .main-nav) -->
            <input type="checkbox" id="menu-toggle" class="menu-toggle">

            <div class="header-content">
                <!-- Site Title (This section contains the title and the icon) -->
                <a href="index.php" class="site-title">
                    <h1><?php echo SITE_NAME; ?></h1>
                </a>

                <!-- 2. Hamburger Icon (Label that controls the checkbox by targeting its 'id') -->
                <label for="menu-toggle" class="nav-icon">&#9776;</label>
            </div>

            <!-- 3. MAIN NAV: This must be a sibling of the checkbox for the CSS selector to work -->
            <nav class="main-nav">
                <a href="index.php" class="nav-link active">Home</a>
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                    <a href="log_entry.php" class="nav-link">Log Entry</a>
                    <a href="certificates.php" class="nav-link">Certificates</a>
                    <?php if (hasRole('admin')): ?>
                        <a href="admin/dashboard.php" class="nav-link">Admin</a>
                    <?php endif; ?>
                    <a href="?logout=1" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Login</a>
                    <a href="register.php" class="nav-link btn-primary">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <h2 class="hero-title">Join the Movement Against Plastic Waste</h2>
                <p class="hero-subtitle">Track your plastic reduction journey and make a real environmental impact</p>
                <?php if (!isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-large btn-primary">Get Started Today</a>
                <?php endif; ?>
            </div>
        </section>

        <!-- Community Stats -->
        <section class="stats-section">
            <div class="container">
                <h2 class="section-title">Community Impact</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo formatNumber($stats['total_participants'] ?? 0); ?></div>
                        <div class="stat-label">Active Participants</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo formatNumber($stats['total_items'] ?? 0); ?></div>
                        <div class="stat-label">Plastic Items Saved</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo formatNumber($stats['total_co2'] ?? 0); ?>g</div>
                        <div class="stat-label">CO₂ Emissions Prevented</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo formatNumber($stats['total_water'] ?? 0); ?>L</div>
                        <div class="stat-label">Water Saved</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Leaderboard -->
        <section class="leaderboard-section">
            <div class="container">
                <h2 class="section-title">Top Contributors</h2>
                <div class="leaderboard">
                    <table class="leaderboard-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Username</th>
                                <th>Items Saved</th>
                                <th>CO₂ Saved (g)</th>
                                <th>Water Saved (L)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($leaderboard)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No data available yet. Be the first to contribute!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($leaderboard as $index => $user): ?>
                                    <tr>
                                        <td>
                                            <span class="rank-badge rank-<?php echo $index + 1; ?>">#<?php echo $index + 1; ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo formatNumber($user['total_items']); ?></td>
                                        <td><?php echo formatNumber($user['total_co2']); ?></td>
                                        <td><?php echo formatNumber($user['total_water']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <footer class="main-footer">
        <div class="container">
            <p>&copy; 2024 <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/script.js"></script>
</body>

</html>

<?php
if (isset($_GET['logout'])) {
    logoutUser();
}
?>