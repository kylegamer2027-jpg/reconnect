<?php
require_once "includes/functions.php";

$dashboard = null;
if (isset($_SESSION["user_id"])) {
    $dashboard = $_SESSION["role"] === "registrar"
        ? "registrar/dashboard.php"
        : "student/dashboard.php";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Re-Connect | Academic Document Requests</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="landing-page">
    <header class="landing-nav">
        <a class="landing-brand" href="index.php" aria-label="Re-Connect home">
            <strong>Re-Connect</strong>
        </a>
        <nav aria-label="Primary navigation">
            <?php if ($dashboard): ?>
                <a class="landing-link" href="<?= e($dashboard) ?>">Open Dashboard</a>
            <?php else: ?>
                <a class="landing-link" href="auth/login.php">Log in</a>
                <a class="landing-link landing-link-primary" href="auth/register.php">Create account</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="landing-main">
        <section class="landing-hero">
            <p class="landing-eyebrow">Academic services, made simple</p>
            <h1>Request, track, and receive your documents.</h1>
            <p class="landing-copy">Re-Connect gives students a clear view of every academic document request while helping the registrar manage requests efficiently.</p>
            <div class="landing-actions">
                <?php if ($dashboard): ?>
                    <a class="btn-primary" href="<?= e($dashboard) ?>">Go to dashboard</a>
                <?php else: ?>
                    <a class="btn-primary" href="auth/register.php">Request a document</a>
                    <a class="btn-secondary" href="auth/login.php">Sign in</a>
                <?php endif; ?>
            </div>
        </section>

        <section class="landing-features" aria-label="System features">
            <article>
                <span>01</span>
                <h2>Submit</h2>
                <p>Choose a document, quantity, purpose, and supporting details in one form.</p>
            </article>
            <article>
                <span>02</span>
                <h2>Track</h2>
                <p>Follow each status update and registrar note from submission to completion.</p>
            </article>
            <article>
                <span>03</span>
                <h2>Manage</h2>
                <p>Registrars can search, filter, review, update, and report on all requests.</p>
            </article>
        </section>
    </main>
    <script src="assets/js/script.js"></script>
</body>
</html>