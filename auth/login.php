
<?php

require_once "../config/database.php";
require_once "../includes/functions.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verifyCSRFToken($_POST["csrf_token"] ?? "")) {

        $error = "Invalid request. Please refresh the page.";

    } else {

        $email = strtolower(trim($_POST["email"] ?? ""));
        $password = $_POST["password"] ?? "";

        if ($email === "" || $password === "") {

            $error = "Please enter your email and password.";

        } else {

            // Find account using email
            $stmt = $pdo->prepare(
                "SELECT id, full_name, email, password, role
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );

            $stmt->execute([$email]);

            $user = $stmt->fetch();

            // Verify account password
            if ($user && password_verify($password, $user["password"])) {

                // Prevent session fixation
                session_regenerate_id(true);

                // Store user information in session
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];

                // Redirect according to user role
                if ($user["role"] === "registrar") {

                    header("Location: ../registrar/dashboard.php");
                    exit;

                } else {

                    header("Location: ../student/dashboard.php");
                    exit;

                }

            } else {

                $error = "Incorrect email or password.";

            }
        }
    }
}

$csrf_token = generateCSRFToken();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Re-Connect</title>
</head>

<body>

    <h1>Re-Connect</h1>

    <h2>Account Login</h2>

    <p>Log in to access your Re-Connect account.</p>

    <?php if ($error !== ""): ?>

        <p style="color: red;">
            <?= e($error) ?>
        </p>

    <?php endif; ?>

    <form method="POST" action="">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($csrf_token) ?>"
        >

        <label>Email Address</label>
        <br>

        <input
            type="email"
            name="email"
            required
            placeholder="Enter your email"
        >

        <br><br>

        <label>Password</label>
        <br>

        <input
            type="password"
            name="password"
            required
            placeholder="Enter your password"
        >

        <br><br>

        <button type="submit">Login</button>

    </form>

    <p>
        Don't have an account?
        <a href="register.php">Register here</a>
    </p>

</body>
</html>
