<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['student']);
$user = current_user();
$plans = $pdo->query('SELECT * FROM subscription_plans ORDER BY monthly_price ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $planId = (int) ($_POST['plan_id'] ?? 0);
    $planStmt = $pdo->prepare('SELECT * FROM subscription_plans WHERE id = :id LIMIT 1');
    $planStmt->execute(['id' => $planId]);
    $plan = $planStmt->fetch();

    if (!$plan) {
        set_flash('error', 'Plan not found.');
        redirect_to('student/subscriptions.php');
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE user_subscriptions SET status = 'expired' WHERE user_id = :user_id AND status = 'active'")
            ->execute(['user_id' => $user['id']]);

        $pdo->prepare("INSERT INTO user_subscriptions (user_id, plan_id, starts_at, ends_at, status)
            VALUES (:user_id, :plan_id, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active')")
            ->execute([
                'user_id' => $user['id'],
                'plan_id' => $planId,
            ]);

        $pdo->prepare("INSERT INTO payment_transactions (student_id, amount, status, notes) VALUES (:student_id, :amount, 'paid', :notes)")
            ->execute([
                'student_id' => $user['id'],
                'amount' => $plan['monthly_price'],
                'notes' => 'Subscription payment for ' . $plan['name'],
            ]);

        grant_xp((int) $user['id'], 'buy_subscription', 20, $planId);
        create_notification((int) $user['id'], 'Subscription activated', 'Your ' . $plan['name'] . ' plan is active.', 'student/dashboard.php');
        $pdo->commit();
        set_flash('success', 'Subscription activated successfully.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash('error', $e->getMessage());
    }
    redirect_to('student/subscriptions.php');
}

$current = current_subscription((int) $user['id']);
$pageTitle = 'Student subscriptions';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Academic subscriptions</h1>
<?php if ($current): ?>
    <div class="alert alert-success">Current plan: <strong><?= e($current['plan_name']) ?></strong> · expires <?= e(date('d M Y', strtotime($current['ends_at']))) ?></div>
<?php endif; ?>
<div class="row g-3">
    <?php foreach ($plans as $plan): ?>
        <div class="col-md-4">
            <div class="card card-soft p-4 h-100">
                <h2 class="h4"><?= e($plan['name']) ?></h2>
                <div class="display-6 fw-bold"><?= money($plan['monthly_price']) ?></div>
                <div class="small text-muted mb-3"><?= e($plan['description']) ?></div>
                <ul class="small text-muted ps-3">
                    <li><?= (int) $plan['help_discount_percent'] ?>% help discount</li>
                    <li><?= (int) $plan['monthly_help_credits'] ?> credits</li>
                    <li><?= e($plan['feature_summary']) ?></li>
                </ul>
                <form method="post" class="mt-auto">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
                    <button class="btn btn-primary">Activate plan</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
