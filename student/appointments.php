<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

requireLogin("student");

$error = "";
// Keep appointment dates aligned with the school's UTC+08 calendar day.
$appointment_timezone = new DateTimeZone("+08:00");
$today = (new DateTimeImmutable("now", $appointment_timezone))->format("Y-m-d");
$user_id = (int)$_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCSRFToken($_POST["csrf_token"] ?? "")) {
        $error = "Invalid request. Please refresh the page.";
    } else {
        $appointment_date = is_string($_POST["appointment_date"] ?? null)
            ? trim($_POST["appointment_date"])
            : "";
        $purpose = is_string($_POST["purpose"] ?? null)
            ? trim($_POST["purpose"])
            : "";
        $parsed_date = DateTimeImmutable::createFromFormat("!Y-m-d", $appointment_date);

        if (
            !$parsed_date ||
            $parsed_date->format("Y-m-d") !== $appointment_date ||
            $appointment_date < $today
        ) {
            $error = "Please choose today or a future appointment date.";
        } elseif ($purpose === "" || strlen($purpose) > 500) {
            $error = "Please provide a reason (up to 500 characters).";
        } else {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO appointments (user_id, appointment_date, purpose)
                     VALUES (?, ?, ?)"
                );
                $stmt->execute([$user_id, $appointment_date, $purpose]);
                header("Location: appointments.php?booked=1");
                exit;
            } catch (PDOException $e) {
                error_log("Appointment booking failed: " . $e->getMessage());
                $error = "Unable to book your appointment. Please try again.";
            }
        }
    }
}

$stmt = $pdo->prepare(
    "SELECT id, appointment_date, purpose, status
     FROM appointments
     WHERE user_id = ?
     ORDER BY appointment_date DESC, id DESC"
);
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll();
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments | Re-Connect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="header">
    <div class="header-brand"><h1>Re-Connect</h1><p>Student Document Request and Tracking System</p></div>
    <form class="logout" action="../auth/logout.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
        <button type="submit">Logout</button>
    </form>
</header>
<div class="dashboard-layout">
    <aside class="sidebar">
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link">Dashboard</a>
            <a href="request_document.php" class="sidebar-link">Request Document</a>
            <a href="my_requests.php" class="sidebar-link">My Requests</a>
            <a href="appointments.php" class="sidebar-link active">Appointments</a>
            <a href="profile.php" class="sidebar-link">Profile</a>
        </nav>
    </aside>
    <main class="dashboard-content">
        <section class="welcome">
            <h2>Book an Appointment</h2>
            <p>Choose a date to visit the registrar. Past dates cannot be selected.</p>
        </section>

        <?php if (isset($_GET["booked"])): ?>
            <div class="alert alert-success" role="status">Your appointment request has been submitted.</div>
        <?php endif; ?>
        <?php if ($error !== ""): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <section class="section">
            <form method="POST" class="profile-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                <label for="appointment_date">Appointment date</label>
                <input
                    id="appointment_date"
                    name="appointment_date"
                    type="date"
                    min="<?= e($today) ?>"
                    value="<?= e($_POST["appointment_date"] ?? "") ?>"
                    required
                >
                <label for="purpose">Reason for visit</label>
                <textarea id="purpose" name="purpose" rows="4" maxlength="500" required><?= e($_POST["purpose"] ?? "") ?></textarea>
                <button type="submit" class="btn-primary">Request Appointment</button>
            </form>
        </section>

        <section class="section">
            <h3>Your Appointments</h3>
            <?php if ($appointments): ?>
                <div class="student-table-wrapper">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Reason</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td><?= e($appointment["appointment_date"]) ?></td>
                                    <td><?= e($appointment["purpose"]) ?></td>
                                    <td><?= e($appointment["status"]) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>You have not requested an appointment yet.</p>
            <?php endif; ?>
        </section>
    </main>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>
