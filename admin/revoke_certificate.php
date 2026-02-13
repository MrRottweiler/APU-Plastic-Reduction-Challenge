<!-- ****************************************** -->



<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Security: Ensure only admins can run this script
requireRole('admin');

if (isset($_GET['id'])) {
    $user_certificate_id = intval($_GET['id']);

    if ($user_certificate_id > 0) {
        $conn = getDBConnection();

        // Delete the specific award instance
        $stmt = $conn->prepare("DELETE FROM user_certificates WHERE user_certificate_id = ?");
        $stmt->bind_param("i", $user_certificate_id);

        if ($stmt->execute()) {
            // Success: Redirect back with message
            header("Location: certificates.php?success=" . urlencode("Certificate revoked successfully."));
            exit();
        } else {
            // Error: Redirect back with error
            header("Location: certificates.php?error=" . urlencode("Failed to revoke certificate."));
            exit();
        }

        $stmt->close();
        closeDBConnection($conn);
    }
}

// Fallback: If no ID provided, just go back
header("Location: certificates.php");
exit();
