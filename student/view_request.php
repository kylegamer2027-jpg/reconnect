
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

    <header class="header">

        <div>
            <h1>Re-Connect</h1>
            <p>Student Document Request and Tracking System</p>
        </div>

        <a href="my_requests.php" style="color: white;">
            Back to My Requests
        </a>

    </header>


    <main class="container">

        <section class="section">

            <h2>Request Details #<?= (int)$request["id"] ?></h2>

            <br>

            <p><strong>Document Type:</strong>
                <?= e($request["document_type"]) ?>
            </p>

            <br>

            <p><strong>Quantity:</strong>
                <?= (int)$request["quantity"] ?>
            </p>

            <br>

            <p><strong>Purpose:</strong>
                <?= nl2br(e($request["purpose"])) ?>
            </p>

            <br>

            <p><strong>Additional Details:</strong>
                <?= $request["additional_details"] !== null
                    ? nl2br(e($request["additional_details"]))
                    : "None" ?>
            </p>

            <br>

            <p><strong>Current Status:</strong>
                <span class="status">
                    <?= e($request["status"]) ?>
                </span>
            </p>

            <br>

            <p><strong>Date Submitted:</strong>
                <?= e($request["submitted_at"]) ?>
            </p>

            <br>

            <p><strong>Last Updated:</strong>
                <?= e($request["updated_at"]) ?>
            </p>

        </section>


        <br>


        <section class="section">

            <h3>Request Status History</h3>

            <br>

            <div style="border-left: 3px solid #173b63; padding-left: 20px;">

                <!-- Initial submission -->
                <div style="margin-bottom: 25px;">

                    <strong>Pending</strong>

                    <p>
                        Your document request was submitted successfully.
                    </p>

                    <small>
                        <?= e($request["submitted_at"]) ?>
                    </small>

                </div>


                <?php foreach ($history as $item): ?>

                    <div style="margin-bottom: 25px;">

                        <strong>
                            <?= e($item["new_status"]) ?>
                        </strong>

                        <p>
                            Status updated by:
                            <?= e($item["changed_by_name"]) ?>
                        </p>

                        <?php if (!empty($item["note"])): ?>

                            <p>
                                Note: <?= e($item["note"]) ?>
                            </p>

                        <?php endif; ?>

                        <small>
                            <?= e($item["changed_at"]) ?>
                        </small>

                    </div>

                <?php endforeach; ?>

            </div>

        </section>

    </main>

</body>

</html>
