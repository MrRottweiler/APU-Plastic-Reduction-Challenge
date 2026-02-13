<!-- Participant -->

<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$user = getCurrentUser();
$stats = getUserStats($user['user_id']);
$certificates = getUserCertificates($user['user_id']);

// Get recent logs
$conn = getDBConnection();

// **CORRECTED SQL QUERY:** Joining 'logs' (l) with 'environmental_factors' (e)
$stmt = $conn->prepare("
    SELECT 
        l.log_id, 
        e.item_type,    -- Retrieves item_type from the linked environmental_factors table
        l.quantity, 
        l.log_date, 
        l.co2_saved, 
        l.water_saved, 
        l.created_at 
    FROM 
        logs l 
    JOIN 
        environmental_factors e ON l.factor_id = e.factor_id -- JOIN condition
    WHERE 
        l.user_id = ? 
    ORDER BY 
        l.log_date DESC, l.created_at DESC 
    LIMIT 10
");
$stmt->bind_param("i", $user['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$recent_logs = [];
while ($row = $result->fetch_assoc()) {
    $recent_logs[] = $row;
}
$stmt->close();
closeDBConnection($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <header class="main-header">
        <div class="container">
            <input type="checkbox" id="menu-toggle" class="menu-toggle">

            <div class="header-content">
                <a href="index.php" class="site-title">
                    <h1><?php echo SITE_NAME; ?></h1>
                </a>

                <label for="menu-toggle" class="nav-icon">&#9776;</label>
            </div>

            <nav class="main-nav">
                <a href="index.php" class="nav-link">Home</a>
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="log_entry.php" class="nav-link">Log Entry</a>
                <a href="certificates.php" class="nav-link">Certificates</a>
                <a href="profile.php" class="nav-link">Profile</a>
                <?php if (hasRole('admin')): ?>
                    <a href="admin/dashboard.php" class="nav-link">Admin</a>
                <?php endif; ?>
                <a href="?logout=1" class="nav-link">Logout</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="dashboard-header">
                <h2>Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h2>
                <a href="log_entry.php" class="btn btn-primary">Log New Entry</a>
            </div>

            <section class="stats-section">
                <h3 class="section-title">Your Impact</h3>
                <div class="stats-grid">
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
                        <div class="stat-label">Water Conserved</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo formatNumber($stats['total_entries'] ?? 0); ?></div>
                        <div class="stat-label">Total Entries</div>
                    </div>
                </div>
            </section>

            <?php if (!empty($certificates)): ?>
                <section class="certificates-section">
                    <h3 class="section-title">Your Achievements</h3>
                    <div class="certificates-grid">
                        <?php foreach (array_slice($certificates, 0, 4) as $cert): ?>
                            <div class="certificate-card certificate-<?php echo $cert['design_style']; ?>">
                                <h4><?php echo htmlspecialchars($cert['name']); ?></h4>
                                <p><?php echo htmlspecialchars($cert['description']); ?></p>
                                <small>Awarded: <?php echo date('M d, Y', strtotime($cert['awarded_date'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center">
                        <a href="certificates.php" class="btn btn-secondary">View All Certificates</a>
                    </div>
                </section>
            <?php endif; ?>

            <section class="activity-section">
                <h3 class="section-title">Recent Activity</h3>
                <?php if (empty($recent_logs)): ?>
                    <div class="empty-state">
                        <p>No entries yet. Start logging your plastic reduction efforts!</p>
                        <a href="log_entry.php" class="btn btn-primary">Create First Entry</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Item Type</th>
                                    <th>Quantity</th>
                                    <th>CO₂ Saved (g)</th>
                                    <th>Water Saved (L)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_logs as $log): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($log['log_date'])); ?></td>
                                        <td><span class="item-badge item-<?php echo $log['item_type']; ?>"><?php echo ucfirst($log['item_type']); ?></span></td>
                                        <td><?php echo formatNumber($log['quantity']); ?></td>
                                        <td><?php echo formatNumber($log['co2_saved']); ?></td>
                                        <td><?php echo formatNumber($log['water_saved']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>
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