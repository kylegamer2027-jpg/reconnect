<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

requireLogin("registrar");

$error = "";
$statuses = ["Pending", "Confirmed", "Completed", "Cancelled"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCSRFToken($_POST["csrf_token"] ?? "")) {
        $error = "Invalid request. Please refresh the page.";
    } else {
        $appointment_id = filter_var($_POST["appointment_id"] ?? null, FILTER_VALIDATE_INT);
        $status = $_POST["status"] ?? "";
        if (!$appointment_id || !in_array($status, $statuses, true)) {
            $error = "Please choose a valid appointment status.";
        } else {
            $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
            $stmt->execute([$status, $appointment_id]);
            header("Location: appointments.php?updated=1");
            exit;
        }
    }
}

$stmt = $pdo->query(
    "SELECT a.id, a.appointment_date, a.purpose, a.status,
            u.full_name, u.student_id
     FROM appointments a
     JOIN users u ON u.id = a.user_id
     ORDER BY a.appointment_date DESC, a.id DESC"
);
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments | Re-Connect Registrar</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="registrar-dashboard">
<header class="header">
    <div class="header-brand"><h1>Re-Connect</h1><p>Registrar Department</p></div>
    <form class="logout" action="../auth/logout.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
        <button type="submit">Logout</button>
    </form>
</header>
<div class="dashboard-layout">
    <aside class="sidebar">
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link">Dashboard</a>
            <a href="requests.php" class="sidebar-link">Manage Requests</a>
            <a href="appointments.php" class="sidebar-link active">Appointments</a>
            <a href="students.php" class="sidebar-link">Students</a>
        </nav>
    </aside>
    <main class="dashboard-content">
        <section class="welcome">
            <h2>Student Appointments</h2>
            <p>Review appointment requests and update their status.</p>
        </section>
        <?php if (isset($_GET["updated"])): ?>
            <div class="alert alert-success" role="status">Appointment status updated.</div>
        <?php endif; ?>
        <?php if ($error !== ""): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>
        <section class="section">
            <?php if ($appointments): ?>
                <div class="registrar-table-wrapper">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Student</th><th>School ID</th><th>Reason</th><th>Status</th><th>Update</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td><?= e($appointment["appointment_date"]) ?></td>
                                    <td><?= e($appointment["full_name"]) ?></td>
                                    <td><?= e($appointment["student_id"] ?? "N/A") ?></td>
                                    <td><?= e($appointment["purpose"]) ?></td>
                                    <td><?= e($appointment["status"]) ?></td>
                                    <td>
                                        <form method="POST" class="appointment-status-form">
                                            <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                            <input type="hidden" name="appointment_id" value="<?= (int)$appointment["id"] ?>">
                                            <select name="status" aria-label="Status for appointment <?= (int)$appointment["id"] ?>">
                                                <?php foreach ($statuses as $status): ?>
                                                    <option value="<?= e($status) ?>" <?= $appointment["status"] === $status ? "selected" : "" ?>><?= e($status) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No appointments have been requested.</p>
            <?php endif; ?>
        </section>
    </main>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>
