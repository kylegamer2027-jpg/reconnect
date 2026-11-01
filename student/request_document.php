
<?php

require_once "../config/database.php";
require_once "../includes/functions.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

// Only students can access this page
if ($_SESSION["role"] !== "student") {
    header("Location: ../auth/login.php");
    exit;
}

$error = "";

// Available document types
$document_types = [
    "Certificate of Enrollment",
    "Certificate of Grades",
    "Transcript of Records",
    "Other"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verify CSRF token
    if (!verifyCSRFToken($_POST["csrf_token"] ?? "")) {

        $error = "Invalid request. Please refresh the page.";

    } else {

        $document_type = trim($_POST["document_type"] ?? "");
        $quantity = filter_var(
            $_POST["quantity"] ?? null,
            FILTER_VALIDATE_INT
        );
        $purpose = trim($_POST["purpose"] ?? "");
        $additional_details = trim(
            $_POST["additional_details"] ?? ""
        );

        // Validate document type
        if (!in_array($document_type, $document_types, true)) {

            $error = "Please select a valid document type.";

        } elseif ($quantity === false || $quantity < 1) {

            $error = "Please enter a valid quantity.";

        } elseif ($purpose === "") {

            $error = "Please provide the purpose of your request.";

        } elseif (
            $document_type === "Other" &&
            $additional_details === ""
        ) {

            $error = "Please specify the document you need.";

        } else {

            try {

                // Save request to database
                $stmt = $pdo->prepare("
                    INSERT INTO document_requests
                    (
                        user_id,
                        document_type,
                        quantity,
                        purpose,
                        additional_details
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $_SESSION["user_id"],
                    $document_type,
                    $quantity,
                    $purpose,
                    $additional_details !== ""
                        ? $additional_details
                        : null
                ]);

                // Get the new request ID
                $request_id = $pdo->lastInsertId();

                // Redirect after successful submission
                header(
                    "Location: request_document.php?submitted=1&id=" .
                    $request_id
                );
                exit;

            } catch (PDOException $e) {

                $error = "Unable to submit your request. Please try again.";

            }
        }
    }
}

$csrf_token = generateCSRFToken();

$submitted = isset($_GET["submitted"]) &&
             $_GET["submitted"] === "1";

$request_id = filter_var(
    $_GET["id"] ?? null,
    FILTER_VALIDATE_INT
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Request Document | Re-Connect</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

    <header class="header">

        <div>
            <h1>Re-Connect</h1>
            <p>Student Document Request and Tracking System</p>
        </div>

        <a href="dashboard.php" style="color: white;">
            Back to Dashboard
        </a>

    </header>


    <main class="container">

        <section class="section">

            <h2>Request Academic Document</h2>

            <p style="margin: 12px 0 20px; color: #666;">
                Complete the form below to submit your document request.
            </p>


            <?php if ($submitted && $request_id): ?>

                <div style="
                    background: #e8f5e9;
                    color: #216b35;
                    padding: 15px;
                    border-radius: 6px;
                    margin-bottom: 20px;
                ">

                    <strong>Request submitted successfully!</strong>

                    <p>
                        Your Request ID is:
                        <strong>#<?= (int)$request_id ?></strong>
                    </p>

                    <p>
                        Your request status is Pending.
                    </p>

                </div>

                <a href="dashboard.php">Return to Dashboard</a>

                <br><br>

                <a href="request_document.php">
                    Submit Another Request
                </a>


            <?php else: ?>


                <?php if ($error !== ""): ?>

                    <p style="color: red; margin-bottom: 15px;">
                        <?= e($error) ?>
                    </p>

                <?php endif; ?>


                <form method="POST" action="">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($csrf_token) ?>"
                    >


                    <label>Document Type</label>

                    <br>

                    <select name="document_type" required>

                        <option value="">Select Document Type</option>

                        <?php foreach ($document_types as $type): ?>

                            <option
                                value="<?= e($type) ?>"
                                <?= (
                                    ($_POST["document_type"] ?? "") === $type
                                ) ? "selected" : "" ?>
                            >
                                <?= e($type) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <br><br>


                    <label>Quantity</label>

                    <br>

                    <input
                        type="number"
                        name="quantity"
                        min="1"
                        value="<?= e($_POST["quantity"] ?? "1") ?>"
                        required
                    >

                    <br><br>


                    <label>Purpose of Request</label>

                    <br>

                    <textarea
                        name="purpose"
                        rows="4"
                        required
                        placeholder="Explain why you need this document."
                    ><?= e($_POST["purpose"] ?? "") ?></textarea>

                    <br><br>


                    <label>Additional Details</label>

                    <br>

                    <textarea
                        name="additional_details"
                        rows="4"
                        placeholder="If you selected Other, specify the document here."
                    ><?= e($_POST["additional_details"] ?? "") ?></textarea>

                    <br><br>


                    <button type="submit">
                        Submit Document Request
                    </button>

                </form>


            <?php endif; ?>

        </section>

    </main>

</body>

</html>
