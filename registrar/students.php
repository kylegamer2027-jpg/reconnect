<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

requireLogin("registrar");

$search = substr(trim($_GET["search"] ?? ""), 0, 100);
$sql = "
    SELECT
        u.id,
        u.student_id,
        u.full_name,
        u.email,
        u.created_at,
        COUNT(dr.id) AS total_requests,
        COALESCE(SUM(dr.status = 'Completed'), 0) AS completed_requests,
        COALESCE(SUM(dr.status = 'Pending'), 0) AS pending_requests
    FROM users u
    LEFT JOIN document_requests dr ON dr.user_id = u.id
    WHERE u.role = 'student'
";
$params = [];

if ($search !== "") {
    $sql .= " AND (u.full_name LIKE ? OR u.student_id LIKE ? OR u.email LIKE ?) ";
    $value = "%" . $search . "%";
    $params = [$value, $value, $value];
}

$sql .= " GROUP BY u.id, u.student_id, u.full_name, u.email, u.created_at ORDER BY u.full_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students | Re-Connect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="registrar-dashboard">
<header class="header">
    <div class="header-brand"><h1>Re-Connect</h1><p>Registrar Department</p></div>
    <form class="logout" action="../auth/logout.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
        <button type="submit" data-confirm="Log out of Re-Connect?">Logout</button>
    </form>
</header>
<div class="dashboard-layout">
    <aside class="sidebar">
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link">Dashboard</a>
            <a href="requests.php" class="sidebar-link">Manage Requests</a>
            <a href="students.php" class="sidebar-link active">Students</a>
        </nav>
    </aside>
    <main class="dashboard-content">
        <section class="welcome"><h2>Student Accounts</h2><p>Review registered students and their request activity.</p></section>
        <section class="section registrar-filter-section">
            <form method="GET" action="students.php" class="registrar-filter-form">
                <div class="filter-field">
                    <label for="search">Name, Student ID, or Email</label>
                    <input type="text" id="search" name="search" maxlength="100" value="<?= e($search) ?>" placeholder="Search students">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-primary">Search</button>
                    <a href="students.php" class="btn-secondary">Reset</a>
                </div>
            </form>
        </section>
        <section class="section registrar-results-section">
            <div class="results-heading"><div><h3>Registered Students</h3><p><strong><?= count($students) ?></strong> matching accounts</p></div></div>
            <div class="registrar-table-wrapper">
                <table>
                    <thead><tr><th>Student ID</th><th>Name</th><th>Email</th><th>Pending</th><th>Completed</th><th>Total Requests</th><th>Registered</th></tr></thead>
                    <tbody>
                    <?php if ($students): ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= e($student["student_id"] ?: "N/A") ?></td>
                                <td><strong><?= e($student["full_name"]) ?></strong></td>
                                <td><?= e($student["email"]) ?></td>
                                <td><?= (int)$student["pending_requests"] ?></td>
                                <td><?= (int)$student["completed_requests"] ?></td>
                                <td><?= (int)$student["total_requests"] ?></td>
                                <td><?= e($student["created_at"]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="empty">No matching student accounts found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>