
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

// Validate request ID
$request_id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$request_id || $request_id < 1) {
    die("Invalid request ID.");
}

// Get request belonging to this student only
$stmt = $pdo->prepare("
    SELECT *
    FROM document_requests
    WHERE id = ? AND user_id = ?
    LIMIT 1
");

$stmt->execute([$request_id, $user_id]);

$request = $stmt->fetch();

if (!$request) {
    http_response_code(404);
    die("Request not found or you do not have permission to view it.");
}

// Get status history
$stmt = $pdo->prepare("
    SELECT
        h.old_status,
        h.new_status,
        h.note,
        h.changed_at,
        u.full_name AS changed_by_name
    FROM request_status_history h
    INNER JOIN users u ON h.changed_by = u.id
    WHERE h.request_id = ?
    ORDER BY h.changed_at ASC, h.id ASC
");

$stmt->execute([$request_id]);

$history = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Request Details | Re-Connect</title>

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

                <h2>Request Details #<?= (int)$request["id"] ?></h2>

                <p>
                    View your document request information and track its progress.
                </p>

            </section>

            <!-- Request Information -->
            <section class="section">

                <h3>Document Information</h3>

                <p>
                    <strong>Document Type:</strong>
                    <?= e($request["document_type"]) ?>
                </p>

                <p>
                    <strong>Quantity:</strong>
                    <?= (int)$request["quantity"] ?>
                </p>

                <p>
                    <strong>Purpose:</strong>
                    <?= nl2br(e($request["purpose"])) ?>
                </p>

                <p>
                    <strong>Additional Details:</strong>
                    <?= !empty($request["additional_details"])
                        ? nl2br(e($request["additional_details"]))
                        : "None" ?>
                </p>

                <p>
                    <strong>Current Status:</strong>

                    <span class="request-current-status status <?= statusClass($request['status']) ?>">
                        <?= e($request["status"]) ?>
                    </span>
                </p>

                <p>
                    <strong>Date Submitted:</strong>
                    <?= e($request["submitted_at"]) ?>
                </p>

                <p>
                    <strong>Last Updated:</strong>
                    <?= e($request["updated_at"]) ?>
                </p>

            </section>

            <!-- Latest Registrar Note -->
            <section class="section">

                <h3>Latest Registrar Note</h3>

                <?php if (!empty($request["registrar_note"])): ?>

                    <div class="registrar-note">
                        <?= nl2br(e($request["registrar_note"])) ?>
                    </div>

                <?php else: ?>

                    <p class="empty">
                        No registrar note has been provided yet.
                    </p>

                <?php endif; ?>

            </section>

            <!-- Request Status Timeline -->
            <section class="section">

                <h3>Request Status History</h3>

                <p>
                    Follow the progress of your academic document request.
                </p>

                <div class="timeline">

                    <!-- Initial Submission -->
                    <div class="timeline-item">

                        <h4>Pending</h4>

                        <p>
                            Your document request was submitted successfully.
                        </p>

                        <p>
                            <small>
                                <?= e($request["submitted_at"]) ?>
                            </small>
                        </p>

                    </div>

                    <!-- Status Changes -->
                    <?php if (count($history) > 0): ?>

                        <?php foreach ($history as $item): ?>

                            <div class="timeline-item">

                                <h4>
                                    <?= e($item["new_status"]) ?>
                                </h4>

                                <p>
                                    <strong>Previous Status:</strong>
                                    <?= e($item["old_status"]) ?>
                                </p>

                                <p>
                                    <strong>Updated by:</strong>
                                    <?= e($item["changed_by_name"]) ?>
                                </p>

                                <?php if (!empty($item["note"])): ?>

                                    <div class="registrar-note">

                                        <strong>Registrar Note:</strong>

                                        <p>
                                            <?= nl2br(e($item["note"])) ?>
                                        </p>

                                    </div>

                                <?php endif; ?>

                                <p>
                                    <small>
                                        <?= e($item["changed_at"]) ?>
                                    </small>
                                </p>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </section>

        </main>

    </div>

</body>
</html>
