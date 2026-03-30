<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['student']);
$user = current_user();
$plans = $pdo->query('SELECT * FROM subscription_plans ORDER BY monthly_price ASC')->fetchAll();
$current = current_subscription((int) $user['id']);
$pageTitle = 'Student subscriptions';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3 mb-0">Academic subscriptions</h1><a class="btn btn-primary" href="<?= app_url('pricing.php') ?>">Browse plans</a></div>
<?php if ($current): ?><div class="alert alert-success border-0 shadow-sm">Current plan: <strong><?= e($current['plan_name']) ?></strong> · expires <?= e(date('d M Y', strtotime($current['ends_at']))) ?></div><?php endif; ?>
<div class="row g-4">
    <?php foreach ($plans as $plan): ?>
        <?php $payment = payment_for_subscription((int)$user['id'], (int)$plan['id']); ?>
        <div class="col-md-4"><div class="card card-soft p-4 h-100"><h2 class="h4"><?= e($plan['name']) ?></h2><div class="display-6 fw-bold"><?= money($plan['monthly_price']) ?></div><div class="small text-muted mb-3"><?= e($plan['description']) ?></div><ul class="soft-list small ps-3"><li><?= (int)$plan['help_discount_percent'] ?>% help discount</li><li><?= (int)$plan['monthly_help_credits'] ?> credits</li><li><?= e($plan['feature_summary']) ?></li></ul><div class="d-grid gap-2 mt-auto pt-2"><a class="btn btn-primary" href="<?= app_url('student/subscribe.php?plan=' . (int)$plan['id']) ?>">Continue to payment</a><?php if ($payment): ?><a class="btn btn-outline-secondary" href="<?= app_url('student/payment_status.php?payment_id=' . (int)$payment['id']) ?>">Check last payment</a><?php endif; ?></div></div></div>
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
