<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

$success = '';
$error = '';

// Handle CSV Report Generation
if (isset($_POST['generate_report'])) {
    $report_type = sanitizeInput($_POST['report_type']);
    $conn = getDBConnection();
    $filename = '';

    if ($report_type === 'users') {
        $filename = 'users_summary_' . date('Y-m-d') . '.csv';
        $result = $conn->query("
            SELECT u.user_id, u.username, u.email, u.role, u.status, 
                   COUNT(l.log_id) as total_entries,
                   IFNULL(SUM(l.quantity), 0) as total_items,
                   IFNULL(SUM(l.co2_saved), 0) as total_co2,
                   IFNULL(SUM(l.water_saved), 0) as total_water,
                   u.created_at
            FROM users u
            LEFT JOIN logs l ON u.user_id = l.user_id
            WHERE u.role != 'guest'
            GROUP BY u.user_id
            ORDER BY total_items DESC
        ");
        $headers = ['User ID', 'Username', 'Email', 'Role', 'Status', 'Entries Count', 'Items Saved', 'CO2 Saved (g)', 'Water Saved (L)', 'Join Date'];
    } elseif ($report_type === 'logs') {
        $filename = 'detailed_logs_' . date('Y-m-d') . '.csv';
        $result = $conn->query("
            SELECT l.log_id, u.username, ef.item_type, l.quantity, l.co2_saved, l.water_saved, l.log_date, l.created_at
            FROM logs l
            JOIN users u ON l.user_id = u.user_id
            JOIN environmental_factors ef ON l.factor_id = ef.factor_id
            ORDER BY l.log_date DESC, l.created_at DESC
        ");
        $headers = ['Log ID', 'Username', 'Item Category', 'Quantity', 'CO2 Saved (g)', 'Water Saved (L)', 'Activity Date', 'System Recorded At'];
    } elseif ($report_type === 'statistics') {
        $filename = 'community_stats_' . date('Y-m-d') . '.csv';
        $stats = getCommunityStats();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // Add a clean title header for the stats file
        fputcsv($output, ['COMMUNITY IMPACT REPORT']);
        fputcsv($output, ['Generated On', date('Y-m-d H:i:s')]);
        fputcsv($output, []); // Empty row for tidiness
        fputcsv($output, ['Metric Description', 'Value']);
        fputcsv($output, ['Total Registered Participants', $stats['total_participants'] ?? 0]);
        fputcsv($output, ['Total Contributions Logged', $stats['total_logs'] ?? 0]);
        fputcsv($output, ['Total Physical Items Reused/Saved', $stats['total_items'] ?? 0]);
        fputcsv($output, ['Total Carbon Footprint Reduction (grams)', number_format($stats['total_co2'] ?? 0, 2, '.', '')]);
        fputcsv($output, ['Total Water Conserved (liters)', number_format($stats['total_water'] ?? 0, 2, '.', '')]);

        fclose($output);
        closeDBConnection($conn);
        exit;
    } else {
        $error = "Invalid report type selected.";
    }

    // Process Users or Logs into a tidy CSV
    if (isset($result) && isset($headers)) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Add BOM for Excel UTF-8 compatibility (prevents weird characters)
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, $headers);

        while ($row = $result->fetch_assoc()) {
            // Tidy up numbers: Ensure they are decimals without thousand-separators 
            // This makes them "math-ready" for Excel/Sheets formulas
            if (isset($row['co2_saved'])) $row['co2_saved'] = number_format($row['co2_saved'], 2, '.', '');
            if (isset($row['water_saved'])) $row['water_saved'] = number_format($row['water_saved'], 2, '.', '');
            if (isset($row['total_co2'])) $row['total_co2'] = number_format($row['total_co2'], 2, '.', '');
            if (isset($row['total_water'])) $row['total_water'] = number_format($row['total_water'], 2, '.', '');

            fputcsv($output, $row);
        }

        fclose($output);
        closeDBConnection($conn);
        exit;
    }
    closeDBConnection($conn);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo SITE_NAME; ?></title>
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
                <a href="certificates.php" class="nav-link">Certificates</a>
                <a href="reports.php" class="nav-link active">Reports</a>
                <a href="../dashboard.php" class="nav-link">User View</a>
                <a href="?logout=1" class="nav-link">Logout</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <h2 class="section-title">Data Reporting & Exports</h2>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <p class="section-description">Select a report type below to generate a clean, spreadsheet-ready CSV export.</p>

            <div class="reports-grid">
                <div class="card report-card">
                    <h3 class="card-title">User Contribution Data</h3>
                    <p class="card-text">Export a summary of all participants, ranked by their total environmental impact and logged items.</p>
                    <form method="POST" action="">
                        <input type="hidden" name="report_type" value="users">
                        <button type="submit" name="generate_report" class="btn btn-primary">Generate Users CSV</button>
                    </form>
                </div>

                <div class="card report-card">
                    <h3 class="card-title">All Log Entries</h3>
                    <p class="card-text">Export a detailed chronological list of every item logged in the system with associated savings.</p>
                    <form method="POST" action="">
                        <input type="hidden" name="report_type" value="logs">
                        <button type="submit" name="generate_report" class="btn btn-primary">Generate Logs CSV</button>
                    </form>
                </div>

                <div class="card report-card">
                    <h3 class="card-title">Community Statistics Summary</h3>
                    <p class="card-text">Export a tidy high-level summary of the platform's total cumulative environmental performance.</p>
                    <form method="POST" action="">
                        <input type="hidden" name="report_type" value="statistics">
                        <button type="submit" name="generate_report" class="btn btn-primary">Generate Statistics CSV</button>
                    </form>
                </div>
            </div>

            <div class="info-box" style="margin-top: 2.5rem;">
                <h4>CSV Export Details</h4>
                <ul>
                    <li><strong>Compatibility:</strong> Files include a UTF-8 BOM to ensure special characters display correctly in Excel.</li>
                    <li><strong>Formatting:</strong> Numbers use a period (.) as a decimal separator for easy calculation in spreadsheet software.</li>
                    <li><strong>Units:</strong> CO₂ values are in grams; Water values are in liters.</li>
                </ul>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <div class="container">
            <p>&copy; 2024 <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>
</body>

</html>
<?php
if (isset($_GET['logout'])) {
    logoutUser();
}
?>