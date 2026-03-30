<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['student']);
$user = current_user();
$paymentId = (int) ($_GET['payment_id'] ?? 0);
$stmt = $pdo->prepare('SELECT pt.*, sp.name AS plan_name, hr.title AS request_title, u.name AS tutor_name FROM payment_transactions pt LEFT JOIN subscription_plans sp ON sp.id = pt.plan_id LEFT JOIN help_requests hr ON hr.id = pt.request_id LEFT JOIN users u ON u.id = pt.tutor_id WHERE pt.id = :id AND pt.student_id = :student_id LIMIT 1');
$stmt->execute(['id'=>$paymentId, 'student_id'=>$user['id']]);
$payment = $stmt->fetch();
if (!$payment) { set_flash('error', 'Payment not found.'); redirect_to('student/dashboard.php'); }
if (isset($_POST['refresh_status'])) {
    verify_csrf();
    try { $payment = sync_payment_status($paymentId); } catch (Throwable $e) { set_flash('error', $e->getMessage()); redirect_to('student/payment_status.php?payment_id=' . $paymentId); }
    set_flash('success', 'Payment status refreshed.');
    redirect_to('student/payment_status.php?payment_id=' . $paymentId);
}
$pageTitle = 'Payment status';
include __DIR__ . '/../includes/header.php';
?>
<div class="row g-4 justify-content-center">
    <div class="col-xl-8">
        <div class="card card-soft p-4">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3"><div><h1 class="h3 mb-1">Payment status</h1><div class="text-muted"><?= $payment['payment_type'] === 'subscription' ? 'Subscription for ' . e($payment['plan_name']) : 'Academic help for ' . e($payment['request_title']) ?></div></div><?= status_badge((string)$payment['status']) ?></div>
            <div class="row g-3 mb-4">
                <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="stat-muted">Reference</div><div class="fw-semibold"><?= e($payment['gateway_reference']) ?></div><div class="small text-muted mt-2">Gateway status: <?= e($payment['gateway_status'] ?: 'pending') ?></div><?php if (!empty($payment['notes'])): ?><div class="small text-muted mt-2">Reason: <?= e($payment['notes']) ?></div><?php endif; ?></div></div>
                <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="stat-muted">Amount</div><div class="fw-semibold"><?= money($payment['amount']) ?></div><div class="small text-muted mt-2">Provider: <?= e(strtoupper((string)$payment['provider'])) ?></div></div></div>
            </div>
            <div class="alert alert-info border-0">If the gateway status shows <strong>pay-offline</strong> or <strong>pending</strong>, approve the charge on your phone, wait a few seconds, then use the refresh button below until the payment switches to <strong>Paid</strong>. If it shows <strong>failed</strong>, the reason from the gateway is shown above.</div>
            <form method="post" class="d-flex flex-wrap gap-2">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button class="btn btn-primary" name="refresh_status" value="1">Refresh status</button>
                <?php if ($payment['payment_type'] === 'subscription'): ?><a class="btn btn-outline-secondary" href="<?= app_url('student/subscriptions.php') ?>">Back to subscriptions</a><?php else: ?><a class="btn btn-outline-secondary" href="<?= app_url('student/request_view.php?id=' . (int)$payment['request_id']) ?>">Back to request</a><?php endif; ?>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
