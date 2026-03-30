<?php
require_once __DIR__ . '/includes/bootstrap.php';
$plans = $pdo->query('SELECT * FROM subscription_plans ORDER BY monthly_price ASC')->fetchAll();
$pageTitle = 'Academic plans';
include __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4">Academic plans</h1>
<div class="row g-3">
    <?php foreach ($plans as $plan): ?>
        <div class="col-md-4">
            <div class="card pricing-card card-soft h-100">
                <div class="p-4">
                    <h2 class="h4"><?= e($plan['name']) ?></h2>
                    <div class="display-6 fw-bold mb-2"><?= money($plan['monthly_price']) ?><span class="fs-6 text-muted">/month</span></div>
                    <p class="text-muted"><?= e($plan['description']) ?></p>
                    <ul class="small text-muted ps-3">
                        <li><?= (int) $plan['monthly_help_credits'] ?> help credits monthly</li>
                        <li><?= (int) $plan['help_discount_percent'] ?>% discount on help sessions</li>
                        <li><?= e($plan['feature_summary']) ?></li>
                    </ul>
                    <?php if (is_logged_in() && user_role() === 'student'): ?>
                        <a class="btn btn-primary" href="<?= app_url('student/subscriptions.php?plan=' . (int) $plan['id']) ?>">Subscribe</a>
                    <?php else: ?>
                        <a class="btn btn-primary" href="<?= app_url('register.php') ?>">Get started</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
