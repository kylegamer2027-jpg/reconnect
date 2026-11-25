<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

requireLogin("student");

$error = "";
$user_id = (int)$_SESSION["user_id"];

$stmt = $pdo->prepare("SELECT student_id, full_name, email FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

if (!$profile) {
    http_response_code(404);
    die("Profile not found.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCSRFToken($_POST["csrf_token"] ?? "")) {
        $error = "Invalid request. Please refresh the page.";
    } else {
        $student_id = is_string($_POST["student_id"] ?? null)
            ? trim($_POST["student_id"])
            : "";
        $full_name = trim($_POST["full_name"] ?? "");
        $email = strtolower(trim($_POST["email"] ?? ""));
        $current_password = $_POST["current_password"] ?? "";
        $new_password = $_POST["new_password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";

        if ($student_id === "" || !preg_match('/^[0-9]{1,50}$/', $student_id)) {
            $error = "Student ID is required and must contain numbers only (up to 50 digits).";
        } elseif ($full_name === "" || strlen($full_name) > 150) {
            $error = "Full name is required and must not exceed 150 characters.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $error = "Please enter a valid email address.";
        } elseif (($new_password !== "" || $confirm_password !== "") &&
                  ($new_password === "" || $new_password !== $confirm_password || strlen($new_password) < 8)) {
            $error = "New passwords must match and contain at least 8 characters.";
        } else {
            try {
                $pdo->beginTransaction();
                $account = $pdo->prepare("SELECT password FROM users WHERE id = ? FOR UPDATE");
                $account->execute([$user_id]);
                $account = $account->fetch();

                $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
                $emailCheck->execute([$email, $user_id]);
                if ($emailCheck->fetch()) {
                    throw new Exception("This email address is already in use.");
                }

                $studentCheck = $pdo->prepare("SELECT id FROM users WHERE student_id = ? AND id != ? LIMIT 1");
                $studentCheck->execute([$student_id, $user_id]);
                if ($studentCheck->fetch()) {
                    throw new Exception("This student ID is already in use.");
                }

                $passwordHash = null;
                if ($new_password !== "") {
                    if (!password_verify($current_password, $account["password"])) {
                        throw new Exception("Your current password is incorrect.");
                    }
                    $passwordHash = password_hash($new_password, PASSWORD_DEFAULT);
                }

                if ($passwordHash) {
                    $update = $pdo->prepare("UPDATE users SET student_id = ?, full_name = ?, email = ?, password = ? WHERE id = ?");
                    $update->execute([$student_id, $full_name, $email, $passwordHash, $user_id]);
                } else {
                    $update = $pdo->prepare("UPDATE users SET student_id = ?, full_name = ?, email = ? WHERE id = ?");
                    $update->execute([$student_id, $full_name, $email, $user_id]);
                }

                $pdo->commit();
                $_SESSION["full_name"] = $full_name;
                $_SESSION["email"] = $email;
                setFlash("success", "Profile updated successfully.");
                header("Location: profile.php");
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = $e->getMessage();
            }
        }
    }
}

$csrf_token = generateCSRFToken();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | Re-Connect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="header">
    <div class="header-brand"><h1>Re-Connect</h1><p>Student Document Request and Tracking System</p></div>
    <form class="logout" action="../auth/logout.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
        <button type="submit" data-confirm="Log out of Re-Connect?">Logout</button>
    </form>
</header>
<div class="dashboard-layout">
    <aside class="sidebar">
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link">Dashboard</a>
            <a href="request_document.php" class="sidebar-link">Request Document</a>
            <a href="my_requests.php" class="sidebar-link">My Requests</a>
            <a href="appointments.php" class="sidebar-link">Appointments</a>
            <a href="profile.php" class="sidebar-link active">Profile</a>
        </nav>
    </aside>
    <main class="dashboard-content">
        <section class="welcome"><h2>Account Profile</h2><p>Update your student information and password.</p></section>
        <?php if ($flash): ?><div class="alert alert-<?= e($flash["type"]) ?>"><?= e($flash["message"]) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <section class="section profile-section">
            <form method="POST" class="profile-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                <label for="student_id">Student ID</label>
                <input id="student_id" name="student_id" type="text" inputmode="numeric" pattern="[0-9]+" maxlength="50" required data-numeric-only value="<?= e($student_id ?? ($profile["student_id"] ?? "")) ?>">
                <label for="full_name">Full Name</label>
                <input id="full_name" name="full_name" maxlength="150" required value="<?= e($full_name ?? $profile["full_name"]) ?>">
                <label for="email">Email Address</label>
                <input id="email" name="email" type="email" maxlength="150" required value="<?= e($email ?? $profile["email"]) ?>">
                <hr>
                <h3>Change Password</h3>
                <label for="current_password">Current Password</label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password">
                <label for="new_password">New Password</label>
                <input id="new_password" name="new_password" type="password" minlength="8" autocomplete="new-password">
                <label for="confirm_password">Confirm New Password</label>
                <input id="confirm_password" name="confirm_password" type="password" minlength="8" autocomplete="new-password">
                <button type="submit" class="btn-primary">Save Profile</button>
            </form>
        </section>
    </main>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>