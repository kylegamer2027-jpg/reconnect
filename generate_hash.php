
<?php

$hash = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = $_POST["password"] ?? "";

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
    }

}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Generate Password Hash</title>
</head>
<body>

    <h2>Registrar Password Hash Generator</h2>

    <form method="POST">

        <label>Enter Registrar Password:</label>
        <br>

        <input type="password" name="password" required minlength="8">

        <br><br>

        <button type="submit">Generate Hash</button>

    </form>

    <?php if (!empty($error)): ?>
        <p><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($hash !== ""): ?>

        <h3>Generated Password Hash:</h3>

        <textarea rows="4" cols="70" readonly><?= htmlspecialchars($hash) ?></textarea>

    <?php endif; ?>

</body>
</html>
