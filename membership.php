<?php
require_once __DIR__ . '/includes/functions.php';
$user = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    $user = current_user();
    $planId = (int)($_POST['plan_id'] ?? 0);

    $stmt = $pdo->prepare('SELECT * FROM membership_plans WHERE id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute(['id' => $planId]);
    $plan = $stmt->fetch();

    if (!$plan) {
        set_flash('error', 'Selected plan was not found.');
        redirect_to('membership.php');
    }

    $existing = fetch_membership_for_user((int)$user['id']);
    if ($existing && strtolower($existing['plan_name']) === strtolower($plan['name'])) {
        set_flash('error', 'You already have this plan active.');
        redirect_to('membership.php');
    }

    if (get_pending_subscription_for_user((int)$user['id'])) {
        set_flash('error', 'You already have a pending subscription request.');
        redirect_to('membership.php');
    }

    $insert = $pdo->prepare("INSERT INTO subscription_requests (user_id, plan_id, amount, payment_method, reference_code, notes)
                             VALUES (:user_id, :plan_id, :amount, :payment_method, :reference_code, :notes)");
    $insert->execute([
        'user_id' => $user['id'],
        'plan_id' => $plan['id'],
        'amount' => $plan['price'],
        'payment_method' => trim($_POST['payment_method'] ?? ''),
        'reference_code' => trim($_POST['reference_code'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
    ]);

    set_flash('success', 'Subscription request submitted. Admin will review and activate it after payment confirmation.');
    redirect_to('membership.php');
}

$pageTitle='Membership';
$plans = $pdo->query('SELECT * FROM membership_plans WHERE is_active = 1 ORDER BY price ASC')->fetchAll();
$currentMembership = $user ? fetch_membership_for_user((int)$user['id']) : null;
$pendingRequest = $user ? get_pending_subscription_for_user((int)$user['id']) : null;
require_once __DIR__ . '/includes/header.php';
?>
<h1 class="fw-bold mb-4">Membership Plans</h1>
<div class="row g-4">
    <?php foreach ($plans as $plan): ?>
    <div class="col-md-4">
        <div class="card card-soft h-100 p-4">
            <h3><?= e($plan['name']) ?></h3>
            <div class="display-6 fw-bold">K<?= e(number_format($plan['price'], 0)) ?><span class="fs-6 text-muted">/<?= e((int)$plan['duration_days']) ?> days</span></div>
            <p><?= e($plan['description']) ?></p>
            <ul class="mb-4">
                <?php foreach (explode(',', $plan['features']) as $feature): ?>
                    <li><?= e(trim($feature)) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php if (!$user): ?>
                <a class="btn btn-primary mt-auto" href="<?= url('auth/register.php') ?>">Create Account to Subscribe</a>
            <?php else: ?>
                <button class="btn btn-primary mt-auto" data-bs-toggle="collapse" data-bs-target="#plan-<?= (int)$plan['id'] ?>">Choose <?= e($plan['name']) ?></button>
                <div class="collapse mt-3" id="plan-<?= (int)$plan['id'] ?>">
                    <form method="post">
                        <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
                        <div class="mb-2"><label class="form-label">Payment Method</label><select class="form-select" name="payment_method" required><option value="MTN MoMo">MTN MoMo</option><option value="Airtel Money">Airtel Money</option><option value="Cash">Cash</option><option value="Bank Transfer">Bank Transfer</option></select></div>
                        <div class="mb-2"><label class="form-label">Reference / Transaction ID</label><input class="form-control" name="reference_code" required></div>
                        <div class="mb-2"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="3" placeholder="Any extra information or proof note"></textarea></div>
                        <button class="btn btn-outline-primary w-100">Submit Subscription Request</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card card-soft p-4">
            <h4>Payment Instructions</h4>
            <p>Submit your payment details from the selected plan. The admin can review, approve, and activate your subscription from the dashboard.</p>
            <a class="btn btn-success" target="_blank" href="https://wa.me/<?= e(get_setting('site_whatsapp', '260963884318')) ?>">Send Proof on WhatsApp</a>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-soft p-4">
            <h4>Your Subscription Status</h4>
            <?php if ($currentMembership): ?>
                <p class="mb-1"><strong>Active Plan:</strong> <?= e($currentMembership['plan_name']) ?></p>
                <p class="mb-1"><strong>Expires:</strong> <?= e($currentMembership['expires_at']) ?></p>
                <p class="mb-0"><strong>Status:</strong> <?= e(membership_label($currentMembership['days_left'])) ?></p>
            <?php elseif ($pendingRequest): ?>
                <p class="mb-1"><strong>Pending Plan:</strong> <?= e($pendingRequest['plan_name']) ?></p>
                <p class="mb-1"><strong>Reference:</strong> <?= e($pendingRequest['reference_code']) ?></p>
                <p class="mb-0"><strong>Status:</strong> Pending admin approval</p>
            <?php else: ?>
                <p class="mb-0">No active or pending subscription yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
