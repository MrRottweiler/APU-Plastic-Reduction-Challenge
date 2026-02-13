<!-- ****************************************** -->



<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

$error = '';
$success = '';

// Handle log deletion
if (isset($_POST['delete_log'])) {
    $log_id = intval($_POST['log_id']);

    $conn = getDBConnection();
    // Use prepared statement for security
    $stmt = $conn->prepare("DELETE FROM logs WHERE log_id = ?");
    $stmt->bind_param("i", $log_id);

    if ($stmt->execute()) {
        $success = 'Log deleted successfully!';
    } else {
        $error = 'Failed to delete log.';
    }
    $stmt->close();
    closeDBConnection($conn);
}

// Get all logs with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$conn = getDBConnection();

// Get total count
$total_result = $conn->query("SELECT COUNT(*) as total FROM logs");
$total_logs = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_logs / $per_page);

// Get logs for current page
// **CORRECTED SQL QUERY:** Joining with environmental_factors to get item_type
$logs = $conn->query("
    SELECT 
        l.*, 
        u.username,
        e.item_type    -- Retrieves item_type from the linked environmental_factors table
    FROM logs l 
    JOIN users u ON l.user_id = u.user_id 
    JOIN environmental_factors e ON l.factor_id = e.factor_id -- JOIN condition
    ORDER BY l.log_date DESC, l.created_at DESC 
    LIMIT $per_page OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Management - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <header class="main-header">
        <div class="container">
            <!-- Hidden checkbox for CSS-only menu toggle -->
            <input type="checkbox" id="menu-toggle" class="menu-toggle">

            <div class="header-content">
                <!-- Site Title linked to Admin Dashboard -->
                <a href="dashboard.php" class="site-title">
                    <h1><?php echo SITE_NAME; ?> - Admin</h1>
                </a>

                <!-- Hamburger Icon (Label that controls the checkbox) -->
                <label for="menu-toggle" class="nav-icon">&#9776;</label>
            </div>

            <!-- MAIN NAV: Needs to be a sibling of the checkbox for the CSS selector to work -->
            <nav class="main-nav">
                <a href="../index.php" class="nav-link">Home</a>
                <a href="dashboard.php" class="nav-link">Admin Dashboard</a>
                <a href="users.php" class="nav-link">Users</a>
                <a href="logs.php" class="nav-link active">Logs</a>
                <a href="certificates.php" class="nav-link">Certificates</a>
                <a href="reports.php" class="nav-link">Reports</a>
                <a href="../dashboard.php" class="nav-link">User View</a>
                <a href="?logout=1" class="nav-link">Logout</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <h2 class="section-title">Log Management</h2>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <p>Total Logs: **<?php echo formatNumber($total_logs); ?>**</p>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Item Type</th>
                            <th>Quantity</th>
                            <th>Log Date</th>
                            <th>CO₂ (g)</th>
                            <th>Water (L)</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No logs found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['log_id']; ?></td>
                                    <td><?php echo htmlspecialchars($log['username']); ?></td>
                                    <td><span class="item-badge item-<?php echo $log['item_type']; ?>"><?php echo ucfirst($log['item_type']); ?></span></td>
                                    <td><?php echo formatNumber($log['quantity']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($log['log_date'])); ?></td>
                                    <td><?php echo formatNumber($log['co2_saved']); ?></td>
                                    <td><?php echo formatNumber($log['water_saved']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                                    <td>
                                        <!-- Using custom classes for styling and expected JS confirmation -->
                                        <form method="POST" style="display: inline;" class="delete-form">
                                            <input type="hidden" name="log_id" value="<?php echo $log['log_id']; ?>">
                                            <button type="submit" name="delete_log" class="btn btn-delete confirm-delete" title="Permanently delete this log entry">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">Previous</a>
                    <?php endif; ?>
                    <span class="page-info">Page **<?php echo $page; ?>** of **<?php echo $total_pages; ?>**</span>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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