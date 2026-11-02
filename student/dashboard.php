
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

<!-- HEADER -->
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

<!-- DASHBOARD LAYOUT -->
<div class="dashboard-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <nav class="sidebar-nav">

            <a href="dashboard.php" class="sidebar-link active">
                Dashboard
            </a>

            <a href="request_document.php" class="sidebar-link">
                Request Document
            </a>

            <a href="my_requests.php" class="sidebar-link">
                My Requests
            </a>

        </nav>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="container dashboard-content">

        <!-- WELCOME SECTION -->
        <section class="welcome student-welcome">

            <div>
                <h2>
                    Welcome, <?= e($_SESSION["full_name"]) ?>!
                </h2>

                <p>
                    Monitor your academic document requests through your dashboard.
                </p>
            </div>

            <a href="request_document.php" class="dashboard-action">
                + Request a Document
            </a>

        </section>

        <!-- STATISTICS -->
        <section class="dashboard-cards">

            <div class="card student-stat-card">
                <div class="stat-heading">
                    <h3>Total Requests</h3>
                    <span class="stat-icon">T</span>
                </div>

                <div class="number">
                    <?= (int)$stats["total_requests"] ?>
                </div>

                <p class="stat-description">All submitted requests</p>
            </div>

            <div class="card student-stat-card">
                <div class="stat-heading">
                    <h3>Pending Requests</h3>
                    <span class="stat-icon">P</span>
                </div>

                <div class="number">
                    <?= (int)$stats["pending_requests"] ?>
                </div>

                <p class="stat-description">Waiting for review</p>
            </div>

            <div class="card student-stat-card">
                <div class="stat-heading">
                    <h3>Ready for Release</h3>
                    <span class="stat-icon">R</span>
                </div>

                <div class="number">
                    <?= (int)$stats["ready_requests"] ?>
                </div>

                <p class="stat-description">Available for claiming</p>
            </div>

            <div class="card student-stat-card">
                <div class="stat-heading">
                    <h3>Completed Requests</h3>
                    <span class="stat-icon">C</span>
                </div>

                <div class="number">
                    <?= (int)$stats["completed_requests"] ?>
                </div>

                <p class="stat-description">Successfully completed</p>
            </div>

        </section>

        <!-- RECENT REQUESTS -->
        <section class="section student-recent-section">

            <div class="section-heading">
                <div>
                    <h3>Recent Document Requests</h3>
                    <p>View the latest updates on your document requests.</p>
                </div>

                <a href="my_requests.php" class="view-all-link">
                    View All
                </a>
            </div>

            <div class="student-table-wrapper">

                <table class="student-request-table">

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
                                        <strong>
                                            #<?= (int)$request["id"] ?>
                                        </strong>
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
                                    <div class="empty-state">
                                        <h4>No Requests Yet</h4>
                                        <p>
                                            You have not submitted any document requests.
                                        </p>
                                        <a href="request_document.php">
                                            Submit Your First Request
                                        </a>
                                    </div>
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>
</html>
