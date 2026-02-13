<!-- ****************************************** -->



<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$user = getCurrentUser();
$error = '';
$success = '';

// Get environmental factors for display and JS
$factors = getEnvironmentalFactors();

// Initialize variables for form persistence
$item_type = $_POST['item_type'] ?? '';
$quantity = $_POST['quantity'] ?? '';
$log_date = $_POST['log_date'] ?? date('Y-m-d'); // Default to today's date

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_type = sanitizeInput($item_type);
    $quantity = intval($quantity);
    $log_date = sanitizeInput($log_date);

    // Validation
    if (empty($item_type) || !in_array($item_type, ['bottle', 'bag', 'container'])) {
        $error = 'Please select a valid item type.';
    } elseif ($quantity <= 0) {
        $error = 'Quantity must be greater than zero.';
    } elseif (empty($log_date)) {
        $error = 'Please select a date.';
    } else {
        // Calculate impact
        $impact = calculateImpact($item_type, $quantity);

        // Get the factor_id for the selected item_type
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT factor_id FROM environmental_factors WHERE item_type = ?");
        $stmt->bind_param("s", $item_type);
        $stmt->execute();
        $result = $stmt->get_result();
        $factor = $result->fetch_assoc();
        $stmt->close();

        if (!$factor) {
            $error = 'Invalid item type selected.';
        } else {
            $factor_id = $factor['factor_id'];

            // Insert log with factor_id instead of item_type
            $stmt = $conn->prepare("
                INSERT INTO logs (user_id, factor_id, quantity, log_date, co2_saved, water_saved) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiisdd", $user['user_id'], $factor_id, $quantity, $log_date, $impact['co2'], $impact['water']);

            if ($stmt->execute()) {
                $success = 'Entry logged successfully! You saved ' . formatNumber($impact['co2']) . 'g of CO₂ and ' . formatDecimal($impact['water']) . 'L of water.';

                // Check and award certificates
                checkAndAwardCertificates($user['user_id']);

                // Reset form fields after successful submission
                $item_type = '';
                $quantity = '';
                $log_date = date('Y-m-d');
            } else {
                $error = 'Failed to log entry. Please try again.';
            }

            $stmt->close();
        }

        closeDBConnection($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Entry - <?php echo SITE_NAME; ?></title>
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
                <a href="log_entry.php" class="nav-link active">Log Entry</a>
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
            <div class="form-container">
                <h2>Log Your Plastic Reduction</h2>
                <p class="section-description">Record your plastic-free choices and instantly see the positive environmental impact you've made.</p>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="" class="log-form" id="logForm">
                    <div class="form-group">
                        <label for="item_type">Item Type</label>
                        <select id="item_type" name="item_type" required>
                            <option value="">Select item type...</option>
                            <option value="bottle" <?php echo ($item_type === 'bottle') ? 'selected' : ''; ?>>Plastic Bottle (Reusable Bottle)</option>
                            <option value="bag" <?php echo ($item_type === 'bag') ? 'selected' : ''; ?>>Plastic Bag (Reusable Bag)</option>
                            <option value="container" <?php echo ($item_type === 'container') ? 'selected' : ''; ?>>Plastic Container (Reusable Container)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" min="1" max="1000" required
                            value="<?php echo htmlspecialchars($quantity); ?>"
                            placeholder="Enter number of items">
                    </div>

                    <div class="form-group">
                        <label for="log_date">Date</label>
                        <input type="date" id="log_date" name="log_date" required
                            value="<?php echo htmlspecialchars($log_date); ?>"
                            max="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <!-- Real-time Impact Calculator -->
                    <div class="impact-calculator card" id="impactCalculator" style="display: none;">
                        <h3 class="card-title">Estimated Impact</h3>
                        <div class="impact-grid">
                            <div class="impact-item">
                                <div class="impact-value text-co2" id="co2Impact">0</div>
                                <div class="impact-label">grams CO₂ saved</div>
                            </div>
                            <div class="impact-item">
                                <div class="impact-value text-water" id="waterImpact">0</div>
                                <div class="impact-label">liters water saved</div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Submit Log Entry</button>
                </form>

                <!-- Environmental Factors Reference -->
                <div class="info-box" style="margin-top: 2rem;">
                    <h4>Environmental Factors Used</h4>
                    <p>These values represent the estimated savings per single item avoided, based on manufacturing data:</p>
                    <?php foreach ($factors as $type => $data): ?>
                        <div class="factor-item">
                            <span class="factor-type badge-<?php echo $type; ?>">
                                <?php echo ucfirst($type); ?>
                            </span>:
                            **<?php echo formatNumber($data['co2']); ?>g CO₂** and
                            **<?php echo formatDecimal($data['water']); ?>L water** saved per item.
                            <br><small>Source: <?php echo htmlspecialchars($data['source']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <div class="container">
            <p>&copy; 2024 <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Environmental factors for client-side calculation
        const factors = <?php echo json_encode($factors); ?>;

        // Helper function for formatting numbers (to match PHP's formatNumber/formatDecimal for display)
        function formatNumber(num) {
            return Math.round(num).toLocaleString('en-US');
        }

        function formatDecimal(num) {
            return num.toFixed(2);
        }

        // Real-time impact calculator
        function calculateImpact() {
            const itemType = document.getElementById('item_type').value;
            const quantityInput = document.getElementById('quantity');
            const quantity = parseInt(quantityInput.value) || 0;
            const calculator = document.getElementById('impactCalculator');

            // Basic client-side validation for minimum quantity
            if (quantity < 1) {
                quantityInput.value = '';
            }

            if (itemType && quantity > 0) {
                const co2Factor = factors[itemType].co2;
                const waterFactor = factors[itemType].water;

                const co2 = co2Factor * quantity;
                const water = waterFactor * quantity;

                document.getElementById('co2Impact').textContent = formatNumber(co2);
                document.getElementById('waterImpact').textContent = formatDecimal(water);
                calculator.style.display = 'block';
            } else {
                calculator.style.display = 'none';
                document.getElementById('co2Impact').textContent = '0';
                document.getElementById('waterImpact').textContent = '0';
            }
        }

        // Attach listeners
        document.getElementById('item_type').addEventListener('change', calculateImpact);
        document.getElementById('quantity').addEventListener('input', calculateImpact);

        // Trigger calculation on page load if values exist (e.g., after an error)
        window.addEventListener('load', calculateImpact);
    </script>
    <script src="js/script.js"></script>
</body>

</html>

<?php
if (isset($_GET['logout'])) {
    logoutUser();
}
?>