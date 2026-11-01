
<?php

require_once "../config/database.php";
require_once "../includes/functions.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

// Check student role
if ($_SESSION["role"] !== "student") {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// Get dashboard statistics
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_requests,
        COALESCE(SUM(status = 'Pending'), 0) AS pending_requests,
        COALESCE(SUM(status = 'Ready for Release'), 0) AS ready_requests,
        COALESCE(SUM(status = 'Completed'), 0) AS completed_requests
    FROM document_requests
    WHERE user_id = ?
");

$stmt->execute([$user_id]);

$stats = $stmt->fetch();

// Get the student's recent requests
$stmt = $pdo->prepare("
    SELECT id, document_type, status, submitted_at
    FROM document_requests
    WHERE user_id = ?
    ORDER BY submitted_at DESC
    LIMIT 5
");

$stmt->execute([$user_id]);

$recent_requests = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Dashboard | Re-Connect</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

   <!-- Header -->
<header class="header">

    <div class="header-brand">
        <h1>Re-Connect</h1>
        <p>Student Document Request and Tracking System</p>
    </div>

    <form class="logout" action="../auth/logout.php" method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(generateCSRFToken()) ?>"
        >

        <button type="submit">Logout</button>

    </form>

</header>


    <div class="dashboard-layout">

    <aside class="sidebar">

        <nav class="sidebar-nav">

            <a href="dashboard.php" class="sidebar-link active">
                <span>Dashboard</span>
            </a>

            <a href="request_document.php" class="sidebar-link">
                <span>Request Document</span>
            </a>

            <a href="my_requests.php" class="sidebar-link">
                <span>My Requests</span>
            </a>

        </nav>

    </aside>

    <main class="container dashboard-content">

        <!-- Welcome Section -->
        <section class="welcome">

            <h2>
                Welcome, <?= e($_SESSION["full_name"]) ?>!
            </h2>

            <p>
                Monitor your academic document requests through your dashboard.
            </p>

        </section>


        <!-- Statistics -->
        <section class="dashboard-cards">

            <div class="card">
                <h3>Total Requests</h3>

                <div class="number">
                    <?= (int)$stats["total_requests"] ?>
                </div>
            </div>


            <div class="card">
                <h3>Pending Requests</h3>

                <div class="number">
                    <?= (int)$stats["pending_requests"] ?>
                </div>
            </div>


            <div class="card">
                <h3>Ready for Release</h3>

                <div class="number">
                    <?= (int)$stats["ready_requests"] ?>
                </div>
            </div>


            <div class="card">
                <h3>Completed Requests</h3>

                <div class="number">
                    <?= (int)$stats["completed_requests"] ?>
                </div>
            </div>

        </section>


        <!-- Recent Requests -->
        <section class="section">

            <h3>Recent Document Requests</h3>

            <table>

                <thead>

                    <tr>
                        <th>Request ID</th>
                        <th>Document Type</th>
                        <th>Status</th>
                        <th>Date Submitted</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (count($recent_requests) > 0): ?>

                        <?php foreach ($recent_requests as $request): ?>

                            <tr>

                                <td>
                                    <?= (int)$request["id"] ?>
                                </td>

                                <td>
                                    <?= e($request["document_type"]) ?>
                                </td>

                                <td>
                                    <span class="status">
                                        <?= e($request["status"]) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e($request["submitted_at"]) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="4" class="empty">
                                You have not submitted any document requests yet.
                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </section>

    </main>
    
</div>

</body>
</html>
