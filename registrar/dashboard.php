
<?php

require_once "../config/database.php";
require_once "../includes/functions.php";

// Check registrar access
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SESSION["role"] !== "registrar") {
    header("Location: ../auth/login.php");
    exit;
}

// Get request statistics
$statsStmt = $pdo->query("
    SELECT
        COUNT(*) AS total_requests,
        COALESCE(SUM(status = 'Pending'), 0) AS pending_requests,
        COALESCE(SUM(status = 'Under Review'), 0) AS under_review,
        COALESCE(SUM(status = 'Processing'), 0) AS processing,
        COALESCE(SUM(status = 'Ready for Release'), 0) AS ready_requests,
        COALESCE(SUM(status = 'Completed'), 0) AS completed_requests,
        COALESCE(SUM(status = 'Rejected'), 0) AS rejected_requests
    FROM document_requests
");

$stats = $statsStmt->fetch();

// Prepare status report
$statusLabels = [
    "Pending" => "pending_requests",
    "Under Review" => "under_review",
    "Processing" => "processing",
    "Ready for Release" => "ready_requests",
    "Completed" => "completed_requests",
    "Rejected" => "rejected_requests"
];

$statusCounts = [];

foreach ($statusLabels as $label => $column) {
    $statusCounts[$label] = (int)($stats[$column] ?? 0);
}

// Get document type report
$typeStmt = $pdo->query("
    SELECT document_type, COUNT(*) AS total
    FROM document_requests
    GROUP BY document_type
    ORDER BY total DESC
");

$typeReports = $typeStmt->fetchAll();

// Calculate chart widths
$maxStatusCount = max(1, ...array_values($statusCounts));

$typeCounts = array_map(
    fn($item) => (int)$item["total"],
    $typeReports
);

$maxTypeCount = max(1, ...$typeCounts);

// Get five most recent requests
$recentStmt = $pdo->query("
    SELECT
        dr.id,
        dr.document_type,
        dr.status,
        dr.submitted_at,
        u.full_name
    FROM document_requests dr
    INNER JOIN users u ON dr.user_id = u.id
    ORDER BY dr.submitted_at DESC, dr.id DESC
    LIMIT 5
");

$recentRequests = $recentStmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrar Dashboard | Re-Connect</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="registrar-dashboard">

<!-- HEADER -->
<header class="header">

    <div class="header-brand">
        <h1>Re-Connect</h1>
        <p>Registrar Department</p>
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

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <nav class="sidebar-nav">

            <a href="dashboard.php" class="sidebar-link active">
                Dashboard
            </a>

            <a href="requests.php" class="sidebar-link">
                Manage Requests
            </a>

        </nav>

    </aside>

    <!-- MAIN DASHBOARD -->
    <main class="dashboard-content">

        <!-- WELCOME SECTION -->
        <section class="welcome registrar-welcome">

            <div>
                <h2>
                    Welcome, <?= e($_SESSION["full_name"]) ?>!
                </h2>

                <p>
                    Manage student document requests and monitor their progress.
                </p>
            </div>

            <a href="requests.php" class="registrar-action-btn">
                Manage Student Requests
            </a>

        </section>

        <!-- REQUEST STATISTICS -->
        <section class="section registrar-statistics">

            <h3>Request Statistics</h3>

            <div class="registrar-stats-grid">

                <div class="card">
                    <h3>Total Requests</h3>
                    <p><?= (int)$stats["total_requests"] ?></p>
                </div>

                <div class="card">
                    <h3>Pending</h3>
                    <p><?= (int)$stats["pending_requests"] ?></p>
                </div>

                <div class="card">
                    <h3>Under Review</h3>
                    <p><?= (int)$stats["under_review"] ?></p>
                </div>

                <div class="card">
                    <h3>Processing</h3>
                    <p><?= (int)$stats["processing"] ?></p>
                </div>

                <div class="card">
                    <h3>Ready for Release</h3>
                    <p><?= (int)$stats["ready_requests"] ?></p>
                </div>

                <div class="card">
                    <h3>Completed</h3>
                    <p><?= (int)$stats["completed_requests"] ?></p>
                </div>

                <div class="card">
                    <h3>Rejected</h3>
                    <p><?= (int)$stats["rejected_requests"] ?></p>
                </div>

            </div>

        </section>

        <!-- REPORTS AND ANALYTICS -->
        <section class="section registrar-analytics">

            <h3>Request Reports and Analytics</h3>

            <div class="report-total">

                <h3>Total Student Requests</h3>

                <p><?= (int)$stats["total_requests"] ?></p>

            </div>

            <div class="report-grid">

                <!-- STATUS REPORT -->
                <div class="report-card">

                    <h3>Requests by Status</h3>

                    <?php foreach ($statusCounts as $label => $count): ?>

                        <?php
                        $percentage = ($count / $maxStatusCount) * 100;
                        ?>

                        <div class="chart-item">

                            <div class="chart-label">
                                <span><?= e($label) ?></span>
                                <strong><?= $count ?></strong>
                            </div>

                            <div class="chart-track">

                                <div
                                    class="chart-bar"
                                    style="width: <?= $percentage ?>%;"
                                ></div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <!-- DOCUMENT TYPE REPORT -->
                <div class="report-card">

                    <h3>Requests by Document Type</h3>

                    <?php if (count($typeReports) > 0): ?>

                        <?php foreach ($typeReports as $item): ?>

                            <?php
                            $count = (int)$item["total"];
                            $percentage = ($count / $maxTypeCount) * 100;
                            ?>

                            <div class="chart-item">

                                <div class="chart-label">
                                    <span>
                                        <?= e($item["document_type"]) ?>
                                    </span>

                                    <strong><?= $count ?></strong>
                                </div>

                                <div class="chart-track">

                                    <div
                                        class="chart-bar"
                                        style="width: <?= $percentage ?>%;"
                                    ></div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <p class="empty">
                            No document requests available for reporting.
                        </p>

                    <?php endif; ?>

                </div>

            </div>

        </section>

        <!-- RECENT REQUESTS -->
        <section class="section registrar-recent">

            <h3>Recently Submitted Requests</h3>

            <div class="registrar-table-wrapper">

                <table>

                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Student Name</th>
                            <th>Document Type</th>
                            <th>Status</th>
                            <th>Date Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (count($recentRequests) > 0): ?>

                            <?php foreach ($recentRequests as $request): ?>

                                <tr>

                                    <td>
                                        #<?= (int)$request["id"] ?>
                                    </td>

                                    <td>
                                        <?= e($request["full_name"]) ?>
                                    </td>

                                    <td>
                                        <?= e($request["document_type"]) ?>
                                    </td>

                                    <td>
                                        <span class="status <?= e(statusClass($request["status"])) ?>">
                                            <?= e($request["status"]) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e($request["submitted_at"]) ?>
                                    </td>

                                    <td>
                                        <a
                                            href="view_request.php?id=<?= (int)$request["id"] ?>"
                                            class="registrar-view-link"
                                        >
                                            View Request
                                        </a>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6" class="empty">
                                    No student requests available.
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
