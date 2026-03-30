<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['student']);
$user = current_user();
$reward = reward_summary((int) $user['id']);
$subscription = current_subscription((int) $user['id']);
$activeRequests = $pdo->prepare("SELECT * FROM help_requests WHERE student_id = :student_id ORDER BY id DESC LIMIT 5");
$activeRequests->execute(['student_id' => $user['id']]);
$requests = $activeRequests->fetchAll();

$pageTitle = 'Student dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Welcome back, <?= e($user['name']) ?></h1>
        <div class="text-muted">Track your study support, learning rewards, and live tutor activity.</div>
    </div>
    <a class="btn btn-primary" href="<?= app_url('student/request_help.php') ?>">Request academic help</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="metric-card bg-white p-4"><div class="text-muted">Level</div><div class="metric-value"><?= (int) $reward['current_level'] ?></div></div></div>
    <div class="col-md-3"><div class="metric-card bg-white p-4"><div class="text-muted">XP</div><div class="metric-value"><?= (int) $reward['current_xp'] ?></div></div></div>
    <div class="col-md-3"><div class="metric-card bg-white p-4"><div class="text-muted">Reward credits</div><div class="metric-value"><?= (int) $reward['reward_credits'] ?></div></div></div>
    <div class="col-md-3"><div class="metric-card bg-white p-4"><div class="text-muted">Study streak</div><div class="metric-value"><?= (int) $reward['streak_days'] ?>d</div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card card-soft p-4 h-100">
            <div class="section-title mb-3">Subscription</div>
            <?php if ($subscription): ?>
                <div class="h5"><?= e($subscription['plan_name']) ?></div>
                <div class="text-muted mb-2"><?= money($subscription['monthly_price']) ?>/month</div>
                <div class="small text-muted mb-2">Help discount: <?= (int) $subscription['help_discount_percent'] ?>%</div>
                <div class="small text-muted">Ends <?= e(date('d M Y', strtotime($subscription['ends_at']))) ?></div>
            <?php else: ?>
                <p class="text-muted">You do not have an active academic plan.</p>
                <a class="btn btn-outline-primary btn-sm" href="<?= app_url('pricing.php') ?>">Choose a plan</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card card-soft p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title">Recent help requests</div>
                <a class="small" href="<?= app_url('student/my_requests.php') ?>">View all</a>
            </div>
            <?php if ($requests): ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Subject</th><th>Urgency</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?= e($request['subject']) ?></td>
                                <td><?= e(ucfirst($request['urgency'])) ?></td>
                                <td><?= status_badge($request['status']) ?></td>
                                <td><a class="btn btn-sm btn-outline-primary" href="<?= app_url('student/request_view.php?id=' . (int) $request['id']) ?>">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">No requests yet. Start with one guided help request.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
