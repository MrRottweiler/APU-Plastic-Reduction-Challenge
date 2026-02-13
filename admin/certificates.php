<!-- ****************************************** -->



<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

$error = '';
$success = '';

// Handle certificate awarding
if (isset($_POST['award_certificate'])) {
    // Sanitize and validate inputs
    $user_id = intval($_POST['user_id']);
    $certificate_id = intval($_POST['certificate_id']);
    $message = sanitizeInput($_POST['personal_message'] ?? '');
    $admin_id = getCurrentUser()['user_id'];

    // Basic validation
    if ($user_id > 0 && $certificate_id > 0) {
        $conn = getDBConnection();
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("
            INSERT INTO user_certificates (user_id, certificate_id, awarded_by, personal_message) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiis", $user_id, $certificate_id, $admin_id, $message);

        if ($stmt->execute()) {
            $success = 'Certificate awarded successfully!';
        } else {
            // Check for duplicate key error (if the user already has this certificate)
            if ($conn->errno === 1062) {
                $error = 'Failed to award certificate. The selected user already has this certificate.';
            } else {
                $error = 'Failed to award certificate. An unknown database error occurred.';
            }
        }
        $stmt->close();
        closeDBConnection($conn);
    } else {
        $error = 'Invalid User or Certificate selected.';
    }
}

// --- NEW FUNCTIONALITY: Handle certificate creation ---
if (isset($_POST['create_certificate'])) {
    // Sanitize and validate inputs
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $criteria_type = sanitizeInput($_POST['criteria_type'] ?? ''); // 'manual' or 'auto'
    $criteria_value = intval($_POST['criteria_value'] ?? 0); // Used for 'auto' criteria (e.g., number of items)
    $design_style = sanitizeInput($_POST['design_style'] ?? '');

    // Basic validation
    if (empty($name) || empty($description) || !in_array($criteria_type, ['manual', 'auto']) || empty($design_style)) {
        $error = 'Failed to create certificate: Please fill in all required fields and ensure the type is valid.';
    } else {
        // Validation for 'auto' type
        if ($criteria_type === 'auto' && $criteria_value <= 0) {
            $error = 'Failed to create certificate: "Auto" criteria requires a Criteria Value greater than 0.';
        } else {
            $conn = getDBConnection();

            // Set criteria_value to 0 if criteria_type is 'manual'
            $value_to_insert = ($criteria_type === 'manual') ? 0 : $criteria_value;

            // Use prepared statement
            $stmt = $conn->prepare("
                INSERT INTO certificates (name, description, criteria_type, criteria_value, design_style) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssis", $name, $description, $criteria_type, $value_to_insert, $design_style);

            if ($stmt->execute()) {
                // Use a redirect to prevent form resubmission on refresh
                $redirect_success = 'Certificate "**' . htmlspecialchars($name) . '**" created successfully!';
                header('Location: certificates.php?success=' . urlencode($redirect_success));
                exit();
            } else {
                $error = 'Failed to create certificate. An unknown database error occurred.';
            }
            $stmt->close();
            closeDBConnection($conn);
        }
    }
}

// Handle certificate deletion
if (isset($_POST['delete_certificate'])) {
    $certificate_id = intval($_POST['certificate_id'] ?? 0);

    if ($certificate_id > 0) {
        $conn = getDBConnection();

        // Fetch name for friendly message
        $name_stmt = $conn->prepare("SELECT name FROM certificates WHERE certificate_id = ?");
        $name_stmt->bind_param("i", $certificate_id);
        $name_stmt->execute();
        $name_result = $name_stmt->get_result();
        $cert_row = $name_result->fetch_assoc();
        $cert_name = $cert_row['name'] ?? '';
        $name_stmt->close();

        // Delete certificate
        $del_stmt = $conn->prepare("DELETE FROM certificates WHERE certificate_id = ?");
        $del_stmt->bind_param("i", $certificate_id);

        if ($del_stmt->execute()) {
            $redirect_success = 'Certificate "' . htmlspecialchars($cert_name) . '" deleted successfully!';
            header('Location: certificates.php?success=' . urlencode($redirect_success));
            exit();
        } else {
            $error = 'Failed to delete certificate. An unknown database error occurred.';
        }

        $del_stmt->close();
        closeDBConnection($conn);
    } else {
        $error = 'Invalid certificate selected.';
    }
}

// Check for success or error message in URL after redirect
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

// Get all certificates and users
$conn = getDBConnection();
$certificates = $conn->query("SELECT * FROM certificates ORDER BY certificate_id")->fetch_all(MYSQLI_ASSOC);
$users = $conn->query("
    SELECT user_id, username 
    FROM users 
    WHERE role IN ('participant', 'admin') 
    ORDER BY username
")->fetch_all(MYSQLI_ASSOC);

$awarded = $conn->query("
    SELECT uc.*, u.username, c.name as cert_name, a.username as awarded_by_name
    FROM user_certificates uc
    JOIN users u ON uc.user_id = u.user_id
    JOIN certificates c ON uc.certificate_id = c.certificate_id
    JOIN users a ON uc.awarded_by = a.user_id
    ORDER BY uc.awarded_date DESC
    LIMIT 20
")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Management - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <header class="main-header">
        <div class="container">
            <input type="checkbox" id="menu-toggle" class="menu-toggle">
            <div class="header-content">
                <a href="dashboard.php" class="site-title">
                    <h1><?php echo SITE_NAME; ?> - Admin</h1>
                </a>
                <label for="menu-toggle" class="nav-icon">&#9776;</label>
            </div>
            <nav class="main-nav">
                <a href="../index.php" class="nav-link">Home</a>
                <a href="dashboard.php" class="nav-link">Admin Dashboard</a>
                <a href="users.php" class="nav-link">Users</a>
                <a href="logs.php" class="nav-link">Logs</a>
                <a href="certificates.php" class="nav-link active">Certificates</a>
                <a href="reports.php" class="nav-link">Reports</a>
                <a href="../dashboard.php" class="nav-link">User View</a>
                <a href="?logout=1" class="nav-link">Logout</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <h2 class="section-title">Certificate Management</h2>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="card form-create-certificate" style="margin-bottom: 3rem;">
                <h3 class="card-title">Create New Certificate</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="cert_name">Certificate Name</label>
                        <input type="text" id="cert_name" name="name" required placeholder="e.g., 'Crown of Plastic'">
                    </div>

                    <div class="form-group">
                        <label for="cert_description">Description</label>
                        <textarea id="cert_description" name="description" rows="3" required placeholder="Briefly describe what this certificate represents."></textarea>
                    </div>

                    <div class="form-group form-row">
                        <div class="form-group-half">
                            <label for="criteria_type">Awarding Type</label>
                            <select id="criteria_type" name="criteria_type" required onchange="toggleCriteriaValue()">
                                <option value="">Select type...</option>
                                <option value="manual">Manual (Admin must award)</option>
                                <option value="auto">Automatic (Based on criteria)</option>
                            </select>
                        </div>

                        <div class="form-group-half">
                            <label for="criteria_value">Criteria Value</label>
                            <input type="number" id="criteria_value" name="criteria_value" value="0" min="0" placeholder="e.g., 5" disabled>
                            <small class="form-text-muted" id="criteria_help">For 'auto' type only. 0 for manual.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="design_style">Design Style/Template</label>
                        <select id="design_style" name="design_style" required>
                            <option value="">Choose template...</option>
                            <option value="professional">Professional</option>
                            <option value="modern">Modern</option>
                            <option value="classic">Classic</option>
                        </select>
                    </div>

                    <button type="submit" name="create_certificate" class="btn btn-secondary">Create Certificate</button>
                </form>
            </div>

            <div class="card form-award-certificate">
                <h3 class="card-title">Award Certificate Manually</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="user_id">Select User</label>
                        <select id="user_id" name="user_id" required>
                            <option value="">Choose user...</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="certificate_id">Select Certificate</label>
                        <select id="certificate_id" name="certificate_id" required>
                            <option value="">Choose certificate...</option>
                            <?php foreach ($certificates as $cert): ?>
                                <option value="<?php echo $cert['certificate_id']; ?>"><?php echo htmlspecialchars($cert['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="personal_message">Personal Message (Optional)</label>
                        <textarea id="personal_message" name="personal_message" rows="3" placeholder="Add a note..."></textarea>
                    </div>

                    <button type="submit" name="award_certificate" class="btn btn-primary">Award Certificate</button>
                </form>
            </div>

            <section style="margin-top: 3rem;">
                <h3 class="section-title">Available Certificates</h3>
                <div class="certificates-grid">
                    <?php foreach ($certificates as $cert): ?>
                        <div class="certificate-card certificate-<?php echo $cert['design_style']; ?>">
                            <h4><?php echo htmlspecialchars($cert['name']); ?></h4>
                            <p><?php echo htmlspecialchars($cert['description']); ?></p>
                            <div class="certificate-actions" style="margin-top:0.75rem;">
                                <form method="POST" action="" onsubmit="return confirm('Delete this certificate?');" style="display:inline;">
                                    <input type="hidden" name="certificate_id" value="<?php echo $cert['certificate_id']; ?>">
                                    <button type="submit" name="delete_certificate" class="btn btn-danger">Delete Certificate</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section style="margin-top: 3rem;">
                <h3 class="section-title">Recently Awarded</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Certificate</th>
                                <th>Awarded By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($awarded)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No certificates awarded yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($awarded as $award): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($award['username']); ?></td>
                                        <td><?php echo htmlspecialchars($award['cert_name']); ?></td>
                                        <td><?php echo htmlspecialchars($award['awarded_by_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($award['awarded_date'])); ?></td>
                                        <td>
                                            <a href="revoke_certificate.php?id=<?php echo $award['user_certificate_id']; ?>"
                                                class="action-link delete-link"
                                                onclick="return confirm('Revoke this certificate?');">Revoke</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <footer class="main-footer">
        <div class="container">
            <p>&copy; 2024 <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function toggleCriteriaValue() {
            const type = document.getElementById('criteria_type').value;
            const valueInput = document.getElementById('criteria_value');
            if (type === 'auto') {
                valueInput.disabled = false;
                valueInput.value = 1;
            } else {
                valueInput.disabled = true;
                valueInput.value = 0;
            }
        }
        document.addEventListener('DOMContentLoaded', toggleCriteriaValue);
    </script>
</body>

</html>

<?php
if (isset($_GET['logout'])) {
    logoutUser();
}
?>