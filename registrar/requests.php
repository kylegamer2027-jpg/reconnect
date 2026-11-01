
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

    // Search and filter values
    $search = substr(trim($_GET["search"] ?? ""), 0, 100);
    $document_type = $_GET["document_type"] ?? "";
    $status = $_GET["status"] ?? "";

    // Available filters
    $document_types = [
        "Certificate of Enrollment",
        "Certificate of Grades",
        "Transcript of Records",
        "Other"
    ];

    $statuses = [
        "Pending",
        "Under Review",
        "Processing",
        "Ready for Release",
        "Completed",
        "Rejected"
    ];

    // Build query conditions
    $sql = "
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
        WHERE 1 = 1
    ";

    $params = [];

    // Search student name or student ID
    if ($search !== "") {
        $sql .= "
            AND (
                u.full_name LIKE ?
                OR u.student_id LIKE ?
            )
        ";

        $searchValue = "%" . $search . "%";

        $params[] = $searchValue;
        $params[] = $searchValue;
    }

    // Filter document type
    if (in_array($document_type, $document_types, true)) {
        $sql .= " AND dr.document_type = ?";
        $params[] = $document_type;
    } else {
        $document_type = "";
    }

    // Filter request status
    if (in_array($status, $statuses, true)) {
        $sql .= " AND dr.status = ?";
        $params[] = $status;
    } else {
        $status = "";
    }

    // Latest requests first
    $sql .= " ORDER BY dr.submitted_at DESC, dr.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

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

                <h2>Student Document Requests</h2>

                <p>
                    Search, filter, and manage student document requests.
                </p>

            </section>

            <!-- Search and Filters -->
            <section class="section">

                <h3>Search and Filter Requests</h3>

                <form method="GET" action="requests.php">

                    <label for="search">
                        Student Name or Student ID
                    </label>

                    <br>

                    <input
                        type="text"
                        name="search"
                        id="search"
                        placeholder="Enter student name or ID"
                        value="<?= e($search) ?>"
                        style="padding: 10px; width: 100%; max-width: 400px;"
                    >

                    <br><br>

                    <label for="document_type">
                        Document Type
                    </label>

                    <br>

                    <select
                        name="document_type"
                        id="document_type"
                        style="padding: 10px; width: 100%; max-width: 400px;"
                    >

                        <option value="">All Document Types</option>

                        <?php foreach ($document_types as $type): ?>

                            <option
                                value="<?= e($type) ?>"
                                <?= $document_type === $type ? "selected" : "" ?>
                            >
                                <?= e($type) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <br><br>

                    <label for="status">
                        Request Status
                    </label>

                    <br>

                    <select
                        name="status"
                        id="status"
                        style="padding: 10px; width: 100%; max-width: 400px;"
                    >

                        <option value="">All Statuses</option>

                        <?php foreach ($statuses as $item): ?>

                            <option
                                value="<?= e($item) ?>"
                                <?= $status === $item ? "selected" : "" ?>
                            >
                                <?= e($item) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <br><br>

                    <button
                        type="submit"
                        style="
                            background: #173b63;
                            color: white;
                            padding: 11px 20px;
                            border: none;
                            border-radius: 5px;
                            cursor: pointer;
                        "
                    >
                        Apply Filters
                    </button>

                    <a
                        href="requests.php"
                        style="
                            display: inline-block;
                            margin-left: 10px;
                            padding: 11px 20px;
                            background: #e5e7eb;
                            color: #333;
                            text-decoration: none;
                            border-radius: 5px;
                        "
                    >
                        Reset Filters
                    </a>

                </form>

            </section>

            <!-- Request Results -->
            <section class="section">

                <h3>Request Results</h3>

                <p>
                    Matching Requests:
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
                                        <span class="status <?= e(statusClass($request["status"])) ?>">
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
                                    No matching requests found.
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
