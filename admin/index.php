<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$stats = [
    'students' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
    'projects' => $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
    'resources' => $pdo->query('SELECT COUNT(*) FROM resources')->fetchColumn(),
    'support' => $pdo->query("SELECT COUNT(*) FROM support_requests WHERE status != 'closed'")->fetchColumn(),
    'pending_subscriptions' => $pdo->query("SELECT COUNT(*) FROM subscription_requests WHERE status = 'pending'")->fetchColumn(),
    'topics' => $pdo->query('SELECT COUNT(*) FROM community_topics')->fetchColumn(),
];
$pageTitle='Admin Dashboard'; require_once __DIR__ . '/../includes/header.php'; ?>
<h1 class="fw-bold mb-4">Admin Dashboard</h1>
<div class="row g-4 mb-4">
    <?php foreach($stats as $label => $count): ?>
        <div class="col-md-4 col-lg-2"><div class="card card-soft p-4"><div class="text-muted text-capitalize"><?= e(str_replace('_',' ',$label)) ?></div><div class="display-6 fw-bold"><?= e($count) ?></div></div></div>
    <?php endforeach; ?>
</div>
<div class="row g-4">
    <div class="col-md-4"><a class="card card-soft p-4 text-decoration-none text-dark" href="<?= url('admin/users.php') ?>"><h4>Manage Users</h4><p class="mb-0">View students and activate plans manually.</p></a></div>
    <div class="col-md-4"><a class="card card-soft p-4 text-decoration-none text-dark" href="<?= url('admin/subscriptions.php') ?>"><h4>Subscription Requests</h4><p class="mb-0">Approve or reject subscription submissions.</p></a></div>
    <div class="col-md-4"><a class="card card-soft p-4 text-decoration-none text-dark" href="<?= url('admin/projects.php') ?>"><h4>Manage Projects</h4><p class="mb-0">Upload, edit, and delete student projects.</p></a></div>
    <div class="col-md-4"><a class="card card-soft p-4 text-decoration-none text-dark" href="<?= url('admin/resources.php') ?>"><h4>Manage Resources</h4><p class="mb-0">Post guides, templates, and premium materials.</p></a></div>
    <div class="col-md-4"><a class="card card-soft p-4 text-decoration-none text-dark" href="<?= url('admin/payments.php') ?>"><h4>Payments</h4><p class="mb-0">Record manual payments and see payment history.</p></a></div>
    <div class="col-md-4"><a class="card card-soft p-4 text-decoration-none text-dark" href="<?= url('admin/support.php') ?>"><h4>Support Requests</h4><p class="mb-0">Reply to tickets and update statuses.</p></a></div>
    <div class="col-md-4"><a class="card card-soft p-4 text-decoration-none text-dark" href="<?= url('admin/community.php') ?>"><h4>Community</h4><p class="mb-0">Moderate topics and lock discussions if needed.</p></a></div>
    <div class="col-md-4"><a class="card card-soft p-4 text-decoration-none text-dark" href="<?= url('admin/messages.php') ?>"><h4>Contact Messages</h4><p class="mb-0">Read messages sent from the contact page.</p></a></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
