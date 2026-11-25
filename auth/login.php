<?php

require_once "../config/database.php";
require_once "../includes/functions.php";

$error = "";
$selected_role = $_POST["role"] ?? "student";
if (!in_array($selected_role, ["student", "registrar"], true)) {
    $selected_role = "student";
}
$login_id_value = is_string($_POST["login_id"] ?? null)
    ? $_POST["login_id"]
    : "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCSRFToken($_POST["csrf_token"] ?? "")) {
        $error = "Invalid request. Please refresh the page.";
    } else {
        $login_id = trim($login_id_value);
        $password = is_string($_POST["password"] ?? null)
            ? $_POST["password"]
            : "";

        if ($login_id === "" || $password === "") {
            $error = "Please enter your ID and password.";
        } elseif (!preg_match('/^[0-9]+$/', $login_id)) {
            $error = "Your ID must contain numbers only.";
        } else {
            $id_column = $selected_role === "registrar" ? "registrar_id" : "student_id";
            $stmt = $pdo->prepare(
                "SELECT id, full_name, email, password, role
                 FROM users
                 WHERE {$id_column} = ? AND role = ?
                 LIMIT 1"
            );
            $stmt->execute([$login_id, $selected_role]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user["password"])) {
                session_regenerate_id(true);
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];

                $destination = $user["role"] === "registrar"
                    ? "../registrar/dashboard.php"
                    : "../student/dashboard.php";
                header("Location: " . $destination);
                exit;
            }

            $error = "Incorrect ID, password, or account type.";
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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
    <h1>Re-Connect</h1>
    <h2>Account Login</h2>
    <p>Choose your account type and sign in with your numeric ID.</p>

    <?php if ($error !== ""): ?>
        <p class="auth-error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

        <div class="auth-form-group">
            <label for="role">Log in as</label>
            <select id="role" name="role" required>
                <option value="student" <?= $selected_role === "student" ? "selected" : "" ?>>Student</option>
                <option value="registrar" <?= $selected_role === "registrar" ? "selected" : "" ?>>Registrar</option>
            </select>
        </div>

        <div class="auth-form-group">
            <label for="login_id" id="login-id-label">
                <?= $selected_role === "registrar" ? "Registrar ID" : "School ID" ?>
            </label>
            <input
                type="text"
                id="login_id"
                name="login_id"
                inputmode="numeric"
                pattern="[0-9]+"
                maxlength="50"
                autocomplete="username"
                required
                value="<?= e($login_id_value) ?>"
                placeholder="<?= $selected_role === "registrar" ? "Enter your registrar ID" : "Enter your school ID" ?>"
                data-numeric-only
            >
            <small class="auth-hint">Numbers only.</small>
        </div>

        <div class="auth-form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
                placeholder="Enter your password"
            >
        </div>

        <button type="submit">Log in</button>
    </form>

    <section class="demo-account" aria-label="Demo account">
        <h3>Try the demo student account</h3>
        <p><strong>School ID:</strong> 99999999</p>
        <p><strong>Password:</strong> DemoStudent123!</p>
        <p class="auth-hint">Demo account is for testing student features only.</p>
    </section>

    <p>Don't have an account? <a href="register.php">Register here</a></p>

    <script src="../assets/js/script.js"></script>
</body>
</html>
