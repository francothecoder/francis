<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);
$commission = platform_commission_percent();
$pageTitle = 'Admin dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Admin dashboard</h1>
        <div class="text-muted">Control tutors, requests, subscriptions, commission, and payouts.</div>
    </div>
    <a class="btn btn-primary" href="<?= app_url('admin/settings.php') ?>">Platform settings</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="metric-card bg-white p-4"><div class="text-muted">Students</div><div class="metric-value"><?= count_value("SELECT COUNT(*) FROM users WHERE role = 'student'") ?></div></div></div>
    <div class="col-md-3"><div class="metric-card bg-white p-4"><div class="text-muted">Tutors</div><div class="metric-value"><?= count_value("SELECT COUNT(*) FROM users WHERE role = 'tutor'") ?></div></div></div>
    <div class="col-md-3"><div class="metric-card bg-white p-4"><div class="text-muted">Active requests</div><div class="metric-value"><?= count_value("SELECT COUNT(*) FROM help_requests WHERE status IN ('open','quoted','accepted','in_progress')") ?></div></div></div>
    <div class="col-md-3"><div class="metric-card bg-white p-4"><div class="text-muted">Commission %</div><div class="metric-value"><?= number_format($commission, 0) ?>%</div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card card-soft p-4 h-100">
            <div class="section-title mb-3">Quick links</div>
            <div class="d-grid gap-2">
                <a class="btn btn-outline-primary" href="<?= app_url('admin/tutors.php') ?>">Manage tutors</a>
                <a class="btn btn-outline-primary" href="<?= app_url('admin/help_requests.php') ?>">View help requests</a>
                <a class="btn btn-outline-primary" href="<?= app_url('admin/subscriptions.php') ?>">Subscriptions</a>
                <a class="btn btn-outline-primary" href="<?= app_url('admin/payouts.php') ?>">Tutor payouts</a>
                <a class="btn btn-outline-primary" href="<?= app_url('admin/resources.php') ?>">Resources</a>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card card-soft p-4 h-100">
            <div class="section-title mb-3">Platform earnings snapshot</div>
            <div class="table-responsive">
                <table class="table">
                    <tr><th>Held payment volume</th><td><?= money(scalar_value("SELECT COALESCE(SUM(amount),0) FROM payment_transactions WHERE status = 'held'")) ?></td></tr>
                    <tr><th>Released platform earnings</th><td><?= money(scalar_value("SELECT COALESCE(SUM(platform_fee),0) FROM payment_transactions WHERE status = 'released'")) ?></td></tr>
                    <tr><th>Active subscriptions</th><td><?= count_value("SELECT COUNT(*) FROM user_subscriptions WHERE status = 'active' AND ends_at >= NOW()") ?></td></tr>
                    <tr><th>Pending tutor verification</th><td><?= count_value("SELECT COUNT(*) FROM tutor_profiles WHERE is_verified = 0") ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
