
<?php

require_once "../config/database.php";
require_once "../includes/functions.php";

// Check registrar login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SESSION["role"] !== "registrar") {
    header("Location: ../auth/login.php");
    exit;
}

// Validate request ID
$request_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$request_id || $request_id < 1) {
    die("Invalid request ID.");
}

$error = "";

// Available request statuses
$allowed_statuses = [
    "Pending",
    "Under Review",
    "Processing",
    "Ready for Release",
    "Completed",
    "Rejected"
];

// Process status update
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verifyCSRFToken($_POST["csrf_token"] ?? "")) {
        die("Invalid request. Please refresh the page and try again.");
    }

    $new_status = $_POST["status"] ?? "";
    $registrar_note = trim($_POST["registrar_note"] ?? "");
    if (strlen($registrar_note) > 2000) {
    $error = "Registrar note must not exceed 2000 characters.";
}

    if ($error !== "") {

    // Keep the validation error.

} elseif (!in_array($new_status, $allowed_statuses, true)) {

        $error = "Please select a valid request status.";

    } else {

        try {

            $pdo->beginTransaction();

            // Lock the request while updating it
            $checkStmt = $pdo->prepare("
                SELECT status
                FROM document_requests
                WHERE id = ?
                FOR UPDATE
            ");

            $checkStmt->execute([$request_id]);
            $currentRequest = $checkStmt->fetch();

            if (!$currentRequest) {
                throw new Exception("Request not found.");
            }

            $old_status = $currentRequest["status"];

            // Update request information
            $updateStmt = $pdo->prepare("
                UPDATE document_requests
                SET status = ?,
                    registrar_note = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            $updateStmt->execute([
                $new_status,
                $registrar_note,
                $request_id
            ]);

            // Record history only when the status changes
            if ($old_status !== $new_status) {

                $historyStmt = $pdo->prepare("
                    INSERT INTO request_status_history
                    (
                        request_id,
                        changed_by,
                        old_status,
                        new_status,
                        note
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");

                $historyStmt->execute([
                    $request_id,
                    $_SESSION["user_id"],
                    $old_status,
                    $new_status,
                    $registrar_note
                ]);
            }

            $pdo->commit();

            header("Location: view_request.php?id=" . $request_id . "&updated=1");
            exit;

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
             $pdo->rollBack();
            }

            error_log("Request update database error: " . $e->getMessage());

             $error = "A database error occurred. Please try again.";

        } catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Request update error: " . $e->getMessage());

    $error = "Unable to update the request. Please try again.";

        }
    }
}

// Retrieve request and student information
$stmt = $pdo->prepare("
    SELECT
        dr.*,
        u.full_name,
        u.student_id,
        u.email
    FROM document_requests dr
    INNER JOIN users u ON dr.user_id = u.id
    WHERE dr.id = ?
    LIMIT 1
");

$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request) {
    http_response_code(404);
    die("Document request not found.");
}

// Retrieve request status history
$historyStmt = $pdo->prepare("
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

$historyStmt->execute([$request_id]);
$history = $historyStmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>View Request | Re-Connect</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

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

    <!-- Registrar Sidebar -->
    <aside class="sidebar">

        <nav class="sidebar-nav">

            <a href="dashboard.php" class="sidebar-link">
                Dashboard
            </a>

            <a href="requests.php" class="sidebar-link active">
                Manage Requests
            </a>

        </nav>

    </aside>

    <!-- Main Content -->
    <main class="container dashboard-content">

        <section class="welcome">

            <h2>Request Details #<?= (int)$request["id"] ?></h2>

            <p>
                Review student information and manage the document request.
            </p>

        </section>

        <?php if (($_GET["updated"] ?? "") === "1"): ?>

            <section class="section">
                <p style="color: green;">
                    Request updated successfully!
                </p>
            </section>

        <?php endif; ?>

        <?php if ($error !== ""): ?>

            <section class="section">
                <p style="color: red;">
                    <?= e($error) ?>
                </p>
            </section>

        <?php endif; ?>

        <!-- Student Information -->
        <section class="section">

            <h3>Student Information</h3>

            <p>
                <strong>Student Name:</strong>
                <?= e($request["full_name"]) ?>
            </p>

            <p>
                <strong>Student ID:</strong>
                <?= e($request["student_id"] ?? "N/A") ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?= e($request["email"]) ?>
            </p>

        </section>

        <!-- Document Request Information -->
        <section class="section">

            <h3>Document Request Information</h3>

            <p>
                <strong>Request ID:</strong>
                #<?= (int)$request["id"] ?>
            </p>

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
                <?= e($request["purpose"]) ?>
            </p>

            <p>
                <strong>Additional Details:</strong>
                <?= e($request["additional_details"] ?: "None") ?>
            </p>

            <p>
                <strong>Date Submitted:</strong>
                <?= e($request["submitted_at"]) ?>
            </p>

            <p>
                <strong>Last Updated:</strong>
                <?= e($request["updated_at"]) ?>
            </p>

            <p>
                <strong>Current Status:</strong>

                <span class="status <?= e(statusClass($request["status"])) ?>">
                    <?= e($request["status"]) ?>
                </span>
            </p>

        </section>

        <!-- Update Request Status -->
        <section class="section">

            <h3>Update Request Status</h3>

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(generateCSRFToken()) ?>"
                >

                <label for="status">
                    <strong>Select New Status:</strong>
                </label>

                <br><br>

                <select
                    name="status"
                    id="status"
                    required
                    style="padding: 10px; width: 100%; max-width: 400px;"
                >

                    <?php foreach ($allowed_statuses as $status): ?>

                        <option
                            value="<?= e($status) ?>"
                            <?= $request["status"] === $status ? "selected" : "" ?>
                        >
                            <?= e($status) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <br><br>

                <label for="registrar_note">
                    <strong>Registrar Note:</strong>
                </label>

                <br><br>

                <textarea
                    name="registrar_note"
                    id="registrar_note"
                    rows="4"
                    maxlength="2000"
                    style="width: 100%; max-width: 600px; padding: 10px;"
                    placeholder="Enter a note for this request..."
                ><?= e($request["registrar_note"] ?? "") ?></textarea>

                <br><br>

                <button
                    type="submit"
                    style="
                        background: #173b63;
                        color: white;
                        padding: 12px 20px;
                        border: none;
                        border-radius: 5px;
                        cursor: pointer;
                    "
                >
                    Save Changes
                </button>

            </form>

        </section>

        <!-- Request Status History -->
        <section class="section">

            <h3>Request Status History</h3>

            <p>
                <strong>Initial Status:</strong> Pending
                <br>
                <small><?= e($request["submitted_at"]) ?></small>
            </p>

            <?php if (count($history) > 0): ?>

                <?php foreach ($history as $item): ?>

                    <hr>

                    <p>
                        <strong>
                            <?= e($item["old_status"]) ?>
                            &rarr;
                            <?= e($item["new_status"]) ?>
                        </strong>
                    </p>

                    <p>
                        Updated by:
                        <?= e($item["changed_by_name"]) ?>
                    </p>

                    <p>
                        Note:
                        <?= e($item["note"] ?: "No note provided") ?>
                    </p>

                    <small>
                        <?= e($item["changed_at"]) ?>
                    </small>

                <?php endforeach; ?>

            <?php else: ?>

                <p class="empty">
                    No status changes have been recorded yet.
                </p>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>
