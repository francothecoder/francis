<?php $pageTitle='Student Hub'; require_once __DIR__ . '/includes/header.php'; ?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-soft p-4">
            <h1 class="fw-bold">Student Hub</h1>
            <p>The Student Hub is the core of the platform. It gives students access to practical resources, project support, templates, downloads, subscriptions, and a community where members can interact and learn from one another.</p>
            <h4>What students get</h4>
            <ul>
                <li>Ready-made projects and systems with access control</li>
                <li>Resources and documentation templates</li>
                <li>Community topics, comments, and replies</li>
                <li>Support requests with responses from the admin side</li>
                <li>Membership plans for deeper access</li>
            </ul>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-soft p-4">
            <h4>Start here</h4>
            <p>Create an account to access the dashboard and explore available student resources.</p>
            <a class="btn btn-primary w-100 mb-2" href="<?= url('auth/register.php') ?>">Create Account</a>
            <a class="btn btn-outline-primary w-100 mb-2" href="<?= url('membership.php') ?>">See Plans</a>
            <a class="btn btn-outline-dark w-100" href="<?= url('community/index.php') ?>">Visit Community</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
