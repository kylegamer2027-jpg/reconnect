
<?php

require_once "../includes/functions.php";

// Only allow logout through POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

// Verify CSRF token
if (!verifyCSRFToken($_POST["csrf_token"] ?? "")) {
    die("Invalid logout request.");
}

// Clear session data
$_SESSION = [];

// Remove session cookie
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );

}

// Destroy session
session_destroy();

// Return to login
header("Location: login.php");
exit;

?>
