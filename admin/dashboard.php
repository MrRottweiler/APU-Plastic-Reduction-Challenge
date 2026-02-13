<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

$stats = getCommunityStats();

// Get total users
$conn = getDBConnection();

// Using prepared statement for query without user input to ensure consistency, though simple queries are safe
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role IN ('participant', 'admin')");
$user_count = $result->fetch_assoc()['total'];

// Get recent logs
// **CORRECTED SQL QUERY:** Joining 'logs' (l) with 'environmental_factors' (e) and users (u)
$recent_logs_query = "
    SELECT 
        l.*, 
        u.username,
        e.item_type    -- Retrieves item_type from the linked environmental_factors table
    FROM logs l 
    JOIN users u ON l.user_id = u.user_id 
    JOIN environmental_factors e ON l.factor_id = e.factor_id -- JOIN condition
    ORDER BY l.created_at DESC 
    LIMIT 10
";
$recent_logs_result = $conn->query($recent_logs_query);
$recent_logs = $recent_logs_result->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <header class="main-header">
        <div class="container">
            <!-- 1. Hidden checkbox for CSS-only menu toggle -->
            <input type="checkbox" id="menu-toggle" class="menu-toggle">

            <div class="header-content">
                <!-- Site Title linked to Admin Dashboard -->
                <a href="dashboard.php" class="site-title">
                    <h1><?php echo SITE_NAME; ?> - Admin</h1>
                </a>

                <!-- 2. Hamburger Icon (Label that controls the checkbox by targeting its 'id') -->
                <label for="menu-toggle" class="nav-icon">&#9776;</label>
            </div>

            <!-- 3. MAIN NAV: Must be a sibling of the checkbox for the CSS selector to work -->
            <nav class="main-nav">
                <a href="../index.php" class="nav-link">Home</a>
                <a href="dashboard.php" class="nav-link active">Admin Dashboard</a>
                <a href="users.php" class="nav-link">Users</a>
                <a href="logs.php" class="nav-link">Logs</a>
                <a href="certificates.php" class="nav-link">Certificates</a>
                <a href="reports.php" class="nav-link">Reports</a>
                <a href="../dashboard.php" class="nav-link">User View</a>
                <a href="?logout=1" class="nav-link">Logout</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <h2 class="section-title">Administrative Overview</h2>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo formatNumber($user_count); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo formatNumber($stats['total_items'] ?? 0); ?></div>
                    <div class="stat-label">Total Items Saved</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo formatNumber($stats['total_co2'] ?? 0); ?>g</div>
                    <div class="stat-label">CO₂ Prevented</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo formatNumber($stats['total_water'] ?? 0); ?>L</div>
                    <div class="stat-label">Water Saved</div>
                </div>
            </div>

            <!-- Recent Activity -->
            <section class="activity-section">
                <h3 class="section-title">Recent Logs</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Item Type</th>
                                <th>Quantity</th>
                                <th>Date</th>
                                <th>CO₂ Saved (g)</th>
                                <th>Water Saved (L)</th>
                                <th>View/Edit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_logs)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No logs yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_logs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['username']); ?></td>
                                        <td><span class="item-badge item-<?php echo $log['item_type']; ?>"><?php echo ucfirst($log['item_type']); ?></span></td>
                                        <td><?php echo formatNumber($log['quantity']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($log['log_date'])); ?></td>
                                        <td><?php echo formatNumber($log['co2_saved']); ?></td>
                                        <td><?php echo formatNumber($log['water_saved']); ?></td>
                                        <td><a href="edit_log.php?id=<?php echo $log['log_id']; ?>" class="nav-link" style="padding: 0; display: inline;">Edit</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center" style="margin-top: 1.5rem;">
                    <a href="logs.php" class="btn btn-secondary">View All Logs</a>
                </div>
            </section>
        </div>
    </main>

    <footer class="main-footer">
        <div class="container">
            <p>&copy; 2024 <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="../js/script.js"></script>
</body>

</html>

<?php
if (isset($_GET['logout'])) {
    logoutUser();
}
?>