
<?php

require_once "../config/database.php";
require_once "../includes/functions.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verify security token
    if (!verifyCSRFToken($_POST["csrf_token"] ?? "")) {
        $error = "Invalid request. Please refresh the page.";
    } else {

        $student_id = trim($_POST["student_id"] ?? "");
        $full_name = trim($_POST["full_name"] ?? "");
        $email = strtolower(trim($_POST["email"] ?? ""));
        $password = $_POST["password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";

        
        // Validate required fields
if ($full_name === "" || $email === "" || $password === "" || $confirm_password === "") {

    $error = "Please complete all required fields.";

} elseif (strlen($student_id) > 50) {

    $error = "Student ID is too long.";

} elseif (strlen($full_name) > 150) {

    $error = "Full name must not exceed 150 characters.";

} elseif (strlen($email) > 150) {

    $error = "Email address is too long.";

} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $error = "Please enter a valid email address.";

} elseif (strlen($password) < 8) {

    $error = "Password must be at least 8 characters.";

} elseif ($password !== $confirm_password) {

    $error = "Passwords do not match.";

} else {

            try {

                // Check if email already exists
                $check = $pdo->prepare(
                    "SELECT id FROM users WHERE email = ?"
                );

                $check->execute([$email]);

                if ($check->fetch()) {
                    $error = "This email is already registered.";

                } else {

                    // Hash the password
                    $hashed_password = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    // Create student account
                    $stmt = $pdo->prepare(
                        "INSERT INTO users
                        (student_id, full_name, email, password, role)
                        VALUES (?, ?, ?, ?, 'student')"
                    );

                    $stmt->execute([
                        $student_id !== "" ? $student_id : null,
                        $full_name,
                        $email,
                        $hashed_password
                    ]);

                    $success = "Registration successful! You can now log in.";

                }

            } catch (PDOException $e) {

                if ($e->getCode() == "23000") {
                    $error = "The student ID or email is already registered.";
                } else {
                    $error = "Something went wrong while creating your account.";
                }

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
    <title>Student Registration | Re-Connect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="auth-page">

    <h1>Re-Connect</h1>
    <h2>Student Registration</h2>

    <p>Create your student account to request academic documents.</p>

    <?php if ($error !== ""): ?>
        <p style="color: red;">
            <?= e($error) ?>
        </p>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
        <p style="color: green;">
            <?= e($success) ?>
        </p>
        <a href="login.php">Go to Login</a>
    <?php else: ?>

        
<form method="POST" action="">

    <input
        type="hidden"
        name="csrf_token"
        value="<?= e($csrf_token) ?>"
    >

    <div class="auth-form-group">
        <label for="student_id">Student ID (optional)</label>
        <input
            type="text"
            id="student_id"
            name="student_id"
            placeholder="Enter your student ID"
        >
    </div>

    <div class="auth-form-group">
        <label for="full_name">Full Name</label>
        <input
            type="text"
            id="full_name"
            name="full_name"
            required
            placeholder="Enter your full name"
        >
    </div>

    <div class="auth-form-group">
        <label for="email">Email Address</label>
        <input
            type="email"
            id="email"
            name="email"
            required
            placeholder="Enter your email"
        >
    </div>

    <div class="auth-form-group">
        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            required
            minlength="8"
            placeholder="At least 8 characters"
        >
    </div>

    <div class="auth-form-group">
        <label for="confirm_password">Confirm Password</label>
        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            required
            minlength="8"
            placeholder="Confirm your password"
        >
    </div>

    <button type="submit">Register Account</button>

</form>


        <p>
            Already have an account?
            <a href="login.php">Log in here</a>
        </p>

    <?php endif; ?>

</body>
</html>
