
<?php

require_once "../includes/functions.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

// Only registrar accounts can access this page
if ($_SESSION["role"] !== "registrar") {
    header("Location: ../student/dashboard.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrar Dashboard | Re-Connect</title>
</head>

<body>

    <h1>Re-Connect</h1>

    <h2>Registrar Dashboard</h2>

    <p>
        Welcome,
        <strong><?= e($_SESSION["full_name"]) ?></strong>!
    </p>

    <p>You have successfully logged in as a Registrar.</p>

    <hr>

    <h3>Registrar Management</h3>

    <p>Manage student document requests here.</p>

    <ul>
        <li>View Document Requests</li>
        <li>Review Student Requests</li>
        <li>Update Request Status</li>
        <li>View Request History</li>
    </ul>

    <form action="../auth/logout.php" method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(generateCSRFToken()) ?>"
        >

        <button type="submit">Logout</button>

    </form>
<a href="requests.php"
   style="
       display: inline-block;
       background: #173b63;
       color: white;
       padding: 12px 20px;
       text-decoration: none;
       border-radius: 5px;
   ">
    Manage Student Requests
</a>
</body>
</html>
