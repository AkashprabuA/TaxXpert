<?php
// logout.php - Enhanced Version
session_start();

// Store company name for logout message
$company_name = isset($_SESSION['company_name']) ? $_SESSION['company_name'] : '';

// Destroy all session data
session_unset();
session_destroy();

// Clear any session cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page with personalized logout message
if (!empty($company_name)) {
    header("Location: login.php?logout=success&company=" . urlencode($company_name));
} else {
    header("Location: login.php?logout=success");
}
exit;
?>