
<?php

// Start session if it has not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate a CSRF security token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Verify submitted CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $token);
}

// Safely display text in HTML
function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
function statusClass($status) {
    $classes = [
        "Pending" => "status-pending",
        "Under Review" => "status-review",
        "Processing" => "status-processing",
        "Ready for Release" => "status-ready",
        "Completed" => "status-completed",
        "Rejected" => "status-rejected"
    ];

    return $classes[$status] ?? "status-default";
}
?>
