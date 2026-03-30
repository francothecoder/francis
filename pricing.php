<?php
require_once __DIR__ . '/includes/bootstrap.php';
$plans = $pdo->query('SELECT * FROM subscription_plans ORDER BY monthly_price ASC')->fetchAll();
$pageTitle = 'Academic plans';
include __DIR__ . '/includes/header.php';
?>
<section class="hero mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <span class="tag-pill mb-3">Flexible academic plans</span>
            <h1 class="display-6 fw-bold mb-3">Premium support plans that unlock resources, discounts, and priority tutor guidance.</h1>
            <p class="mb-0 text-white-50">Choose a study plan, pay with Lenco mobile money, and activate benefits instantly once payment clears.</p>
        </div>
        <div class="col-lg-5">
            <div class="card payment-card p-4 border-0">
                <div class="small text-uppercase text-white-50 mb-2">Included</div>
                <ul class="soft-list text-white-50 mb-0">
                    <li>Monthly help credits</li>
                    <li>Discounts on tutor-led sessions</li>
                    <li>Tiered resource access</li>
                    <li>Mobile money checkout</li>
                </ul>
            </div>
        </div>
    </div>
</section>
<div class="row g-4">
    <?php foreach ($plans as $plan): ?>
        <div class="col-lg-4">
            <div class="card pricing-card card-soft h-100 p-4">
                <span class="tag-pill mb-3"><?= e($plan['name']) ?></span>
                <div class="display-6 fw-bold mb-2"><?= money($plan['monthly_price']) ?><span class="fs-6 text-muted">/month</span></div>
                <p class="text-muted"><?= e($plan['description']) ?></p>
                <ul class="soft-list small ps-3 mt-2">
                    <li><?= (int) $plan['monthly_help_credits'] ?> help credits monthly</li>
                    <li><?= (int) $plan['help_discount_percent'] ?>% discount on help sessions</li>
                    <li><?= e($plan['feature_summary']) ?></li>
                </ul>
                <div class="mt-auto pt-3">
                    <?php if (is_logged_in() && user_role() === 'student'): ?>
                        <a class="btn btn-primary w-100" href="<?= app_url('student/subscribe.php?plan=' . (int) $plan['id']) ?>">Continue to payment</a>
                    <?php else: ?>
                        <a class="btn btn-primary w-100" href="<?= app_url('register.php') ?>">Create student account</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
