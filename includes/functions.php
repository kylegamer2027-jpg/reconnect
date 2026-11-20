
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
           is_string($token) &&
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

function requireLogin($role = null) {
    if (!isset($_SESSION["user_id"])) {
        header("Location: /reconnect/auth/login.php");
        exit;
    }

    if ($role !== null && ($_SESSION["role"] ?? "") !== $role) {
        header("Location: /reconnect/auth/login.php");
        exit;
    }
}

function setFlash($type, $message) {
    $_SESSION["flash"] = [
        "type" => $type,
        "message" => $message
    ];
}

function getFlash() {
    if (!isset($_SESSION["flash"])) {
        return null;
    }

    $flash = $_SESSION["flash"];
    unset($_SESSION["flash"]);
    return $flash;
}
?>
