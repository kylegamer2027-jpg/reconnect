
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

// Get all requests belonging to this student
$stmt = $pdo->prepare("
    SELECT
        id,
        document_type,
        quantity,
        purpose,
        status,
        submitted_at,
        updated_at
    FROM document_requests
    WHERE user_id = ?
    ORDER BY submitted_at DESC
");

$stmt->execute([$user_id]);

$requests = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Requests | Re-Connect</title>

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

    <!-- Dashboard Layout -->
    <div class="dashboard-layout">

        <!-- Sidebar -->
        <aside class="sidebar">

            <nav class="sidebar-nav">

                <a href="dashboard.php" class="sidebar-link">
                    Dashboard
                </a>

                <a href="request_document.php" class="sidebar-link">
                    Request Document
                </a>

                <a href="my_requests.php" class="sidebar-link active">
                    My Requests
                </a>

            </nav>

        </aside>

        <!-- Main Content -->
        <main class="container dashboard-content">

            <section class="welcome">

                <h2>My Document Requests</h2>

                <p>
                    View and monitor the status of your submitted academic documents.
                </p>

            </section>

            <section class="section">

                <h3>Request List</h3>

                <table>

                    <thead>

                        <tr>
                            <th>Request ID</th>
                            <th>Document Type</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Date Submitted</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (count($requests) > 0): ?>

                            <?php foreach ($requests as $request): ?>

                                <tr>

                                    <td>
                                        #<?= (int)$request["id"] ?>
                                    </td>

                                    <td>
                                        <?= e($request["document_type"]) ?>
                                    </td>

                                    <td>
                                        <?= (int)$request["quantity"] ?>
                                    </td>

                                    <td>
                                        <span class="status <?= statusClass($request['status']) ?>">
                                            <?= e($request["status"]) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e($request["submitted_at"]) ?>
                                    </td>

                                    <td>
                                        <a href="view_request.php?id=<?= (int)$request["id"] ?>">
                                            View Details
                                        </a>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="6" class="empty">
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
