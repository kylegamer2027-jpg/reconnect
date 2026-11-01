
<?php

require_once "../config/database.php";
require_once "../includes/functions.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

// Allow registrar accounts only
if ($_SESSION["role"] !== "registrar") {
    header("Location: ../auth/login.php");
    exit;
}

// Retrieve all student document requests
$stmt = $pdo->prepare("
    SELECT
        dr.id,
        dr.document_type,
        dr.quantity,
        dr.purpose,
        dr.status,
        dr.submitted_at,
        u.full_name,
        u.student_id,
        u.email
    FROM document_requests dr
    INNER JOIN users u ON dr.user_id = u.id
    ORDER BY dr.submitted_at DESC
");

$stmt->execute();
$requests = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Requests | Re-Connect</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<header class="header">

    <div>
        <h1>Re-Connect</h1>
        <p>Registrar Request Management</p>
    </div>

    <a href="dashboard.php" style="color: white;">
        Back to Dashboard
    </a>

</header>

<main class="container">

    <section class="welcome">

        <h2>Student Document Requests</h2>

        <p>
            Review and manage document requests submitted by students.
        </p>

    </section>

    <section class="section">

        <h3>All Requests</h3>

        <p>
            Total Requests:
            <strong><?= count($requests) ?></strong>
        </p>

        <table>

            <thead>

                <tr>
                    <th>Request ID</th>
                    <th>Student Name</th>
                    <th>Student ID</th>
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
                                <?= e($request["full_name"]) ?>
                            </td>

                            <td>
                                <?= e($request["student_id"] ?? "N/A") ?>
                            </td>

                            <td>
                                <?= e($request["document_type"]) ?>
                            </td>

                            <td>
                                <?= (int)$request["quantity"] ?>
                            </td>

                            <td>
                                <span class="status">
                                    <?= e($request["status"]) ?>
                                </span>
                            </td>

                            <td>
                                <?= e($request["submitted_at"]) ?>
                            </td>

                            <td>
                                <a href="view_request.php?id=<?= (int)$request["id"] ?>">
                                    View Request
                                </a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" class="empty">
                            No student document requests have been submitted yet.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </section>

</main>

</body>
</html>
