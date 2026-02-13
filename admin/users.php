<!-- ****************************************** -->

<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

$error = '';
$success = '';

// Handle user status toggle
if (isset($_POST['toggle_status'])) {
    $user_id = intval($_POST['user_id']);
    $new_status = sanitizeInput($_POST['new_status']);

    $conn = getDBConnection();
    // Use prepared statement for security
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    $stmt->bind_param("si", $new_status, $user_id);

    if ($stmt->execute()) {
        $success = 'User status updated successfully!';
    } else {
        $error = 'Failed to update user status.';
    }
    $stmt->close();
    closeDBConnection($conn);
}

// Get all users
$conn = getDBConnection();
$users = $conn->query("
    SELECT u.*, 
            COUNT(l.log_id) as total_entries,
            SUM(l.quantity) as total_items
    FROM users u
    LEFT JOIN logs l ON u.user_id = l.user_id
    WHERE u.role != 'guest'
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - <?php echo SITE_NAME; ?></title>
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
                <a href="users.php" class="nav-link active">Users</a>
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
            <h2 class="section-title">User Management</h2>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <p>Total Registered Users: **<?php echo formatNumber(count($users)); ?>**</p>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Entries</th>
                            <th>Items Saved</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No users found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['user_id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="item-badge item-<?php echo ($user['role'] === 'admin' ? 'bottle' : 'bag'); ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="item-badge status-<?php echo $user['status']; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatNumber($user['total_entries'] ?? 0); ?></td>
                                    <td><?php echo formatNumber($user['total_items'] ?? 0); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <?php
                                            $current_status = $user['status'];
                                            $new_status = $current_status === 'active' ? 'inactive' : 'active';
                                            $button_text = $current_status === 'active' ? 'Deactivate' : 'Activate';
                                            $button_class = $current_status === 'active' ? 'btn-danger' : 'btn-success';
                                            ?>
                                            <input type="hidden" name="new_status" value="<?php echo $new_status; ?>">
                                            <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $button_class; ?>">
                                                <?php echo $button_text; ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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