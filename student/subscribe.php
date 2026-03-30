<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['student']);
$user = current_user();
$planId = (int) ($_GET['plan'] ?? $_POST['plan_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM subscription_plans WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $planId]);
$plan = $stmt->fetch();
if (!$plan) { set_flash('error', 'Plan not found.'); redirect_to('student/subscriptions.php'); }

$existing = payment_for_subscription((int)$user['id'], (int)$plan['id']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $provider = strtolower(clean_text($_POST['provider'] ?? 'mtn'));
    $phone = clean_text($_POST['phone_number'] ?? '');
    if ($phone === '') { set_flash('error', 'Enter the mobile money phone number.'); redirect_to('student/subscribe.php?plan=' . $planId); }
    try {
        if ($existing && in_array($existing['status'], ['pending','failed'], true)) {
            $paymentId = (int) $existing['id'];
            $reference = $existing['gateway_reference'];
            $pdo->prepare('UPDATE payment_transactions SET provider = :provider, phone_number = :phone_number WHERE id = :id')->execute(['provider'=>$provider, 'phone_number'=>$phone, 'id'=>$paymentId]);
        } else {
            $reference = 'SUB-' . strtoupper(bin2hex(random_bytes(6)));
            $pdo->prepare('INSERT INTO payment_transactions (plan_id, student_id, payment_type, amount, base_amount, provider, phone_number, gateway_reference, gateway_status, status, notes) VALUES (:plan_id, :student_id, "subscription", :amount, :base_amount, :provider, :phone_number, :gateway_reference, "pending", "pending", :notes)')->execute([
                'plan_id'=>$plan['id'],'student_id'=>$user['id'],'amount'=>$plan['monthly_price'],'base_amount'=>$plan['monthly_price'],'provider'=>$provider,'phone_number'=>$phone,'gateway_reference'=>$reference,'notes'=>'Subscription payment for ' . $plan['name']
            ]);
            $paymentId = (int) $pdo->lastInsertId();
        }
        $payment = scalar_value('SELECT COUNT(*) FROM payment_transactions WHERE id = :id', ['id'=>$paymentId]);
        if (!$payment) throw new RuntimeException('Unable to initialize payment.');
        $paymentRowStmt = $pdo->prepare('SELECT * FROM payment_transactions WHERE id = :id LIMIT 1'); $paymentRowStmt->execute(['id'=>$paymentId]); $paymentRow = $paymentRowStmt->fetch();
        $response = initiate_lenco_collection($paymentRow, $phone, $provider, (string)$user['email'], (string)$user['name']);
        update_payment_gateway_snapshot($paymentId, $response);
        set_flash('success', 'Payment prompt sent. Approve it on your phone, then refresh the payment status page.');
        redirect_to('student/payment_status.php?payment_id=' . $paymentId);
    } catch (Throwable $e) {
        set_flash('error', $e->getMessage());
        redirect_to('student/subscribe.php?plan=' . $planId);
    }
}
$pageTitle = 'Subscribe';
include __DIR__ . '/../includes/header.php';
?>
<div class="row g-4 justify-content-center">
    <div class="col-xl-7">
        <div class="card card-soft p-4">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3"><div><h1 class="h3 mb-1">Activate <?= e($plan['name']) ?></h1><div class="text-muted">Secure monthly plan payment via Lenco mobile money.</div></div><div class="display-6 fw-bold"><?= money($plan['monthly_price']) ?></div></div>
            <?php if (!lenco_enabled()): ?><div class="alert alert-warning">Lenco is not configured yet. Add your public key, secret key, and callback URL in admin settings.</div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
                <div class="mobile-money-grid mb-3">
                    <?php foreach (['mtn'=>'MTN','airtel'=>'Airtel','zamtel'=>'Zamtel'] as $key => $label): ?>
                        <div class="provider-option"><input id="provider-<?= e($key) ?>" type="radio" name="provider" value="<?= e($key) ?>" <?= $key==='mtn'?'checked':'' ?>><label for="provider-<?= e($key) ?>"><?= e($label) ?></label></div>
                    <?php endforeach; ?>
                </div>
                <div class="mb-3"><label class="form-label">Phone number</label><input class="form-control" type="text" name="phone_number" placeholder="2609XXXXXXXX" required></div>
                <div class="d-flex flex-wrap gap-2"><button class="btn btn-primary" <?= lenco_enabled() ? '' : 'disabled' ?>>Request payment</button><a class="btn btn-outline-secondary" href="<?= app_url('student/subscriptions.php') ?>">Back</a></div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
