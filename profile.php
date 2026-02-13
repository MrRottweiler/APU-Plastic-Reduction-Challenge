<!-- ****************************************** -->


<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$user = getCurrentUser();
$stats = getUserStats($user['user_id']);
$error = '';
$success = '';

// Get user details
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT username, email, role, created_at, last_login FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user_details = $result->fetch_assoc();
$stmt->close();

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = sanitizeInput($_POST['email'] ?? '');

    if (!isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email is already taken by another user
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->bind_param("si", $email, $user['user_id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email already in use.';
        } else {
            $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
            $stmt->bind_param("si", $email, $user['user_id']);
            if ($stmt->execute()) {
                $success = 'Profile updated successfully!';
                $user_details['email'] = $email;
                $_SESSION['email'] = $email;
            } else {
                $error = 'Failed to update profile.';
            }
        }
        $stmt->close();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } else {
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close();

        if (password_verify($current_password, $user_data['password_hash'])) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->bind_param("si", $new_hash, $user['user_id']);
            if ($stmt->execute()) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password.';
            }
            $stmt->close();
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <header class="main-header">
        <div class="container">
            <!-- Hidden checkbox for CSS-only menu toggle -->
            <input type="checkbox" id="menu-toggle" class="menu-toggle">

            <div class="header-content">
                <!-- Site Title linked to Dashboard -->
                <a href="dashboard.php" class="site-title">
                    <h1><?php echo SITE_NAME; ?></h1>
                </a>

                <!-- Hamburger Icon (Label that controls the checkbox) -->
                <label for="menu-toggle" class="nav-icon">&#9776;</label>
            </div>

            <!-- MAIN NAV: Needs to be a sibling of the checkbox for the CSS selector to work -->
            <nav class="main-nav">
                <a href="index.php" class="nav-link">Home</a>
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="log_entry.php" class="nav-link">Log Entry</a>
                <a href="certificates.php" class="nav-link">Certificates</a>
                <a href="profile.php" class="nav-link active">Profile</a>
                <?php if (hasRole('admin')): ?>
                    <a href="admin/dashboard.php" class="nav-link">Admin</a>
                <?php endif; ?>
                <a href="?logout=1" class="nav-link">Logout</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <h2 class="section-title">My Profile & Account Settings</h2>
            <p class="section-description">Manage your account details and track your lifetime impact.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Key Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo formatNumber($stats['total_items'] ?? 0); ?></div>
                    <div class="stat-label">Total Plastic Items Saved</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo formatNumber($stats['total_entries'] ?? 0); ?></div>
                    <div class="stat-label">Total Log Entries</div>
                </div>
            </div>

            <div class="form-container">
                <!-- Profile Information -->
                <h3 class="card-title">Account Information</h3>
                <div class="info-box" style="margin-bottom: 2rem;">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user_details['username']); ?></p>
                    <p><strong>Role:</strong> <span class="item-badge status-<?php echo strtolower($user_details['role']); ?>"><?php echo ucfirst($user_details['role']); ?></span></p>
                    <p><strong>Member Since:</strong> <?php echo date('F d, Y', strtotime($user_details['created_at'])); ?></p>
                    <p><strong>Last Login:</strong> <?php echo $user_details['last_login'] ? date('F d, Y H:i', strtotime($user_details['last_login'])) : 'N/A'; ?></p>
                </div>

                <!-- Update Email -->
                <h3 class="card-title" style="margin-top: 2rem;">Update Email</h3>
                <form method="POST" action="" class="auth-form">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required
                            value="<?php echo htmlspecialchars($user_details['email']); ?>">
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary btn-block">Update Email</button>
                </form>

                <!-- Change Password -->
                <h3 class="card-title" style="margin-top: 2rem;">Change Password</h3>
                <form method="POST" action="" class="auth-form">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password (Min 6 characters)</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary btn-block">Change Password</button>
                </form>
            </div>
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