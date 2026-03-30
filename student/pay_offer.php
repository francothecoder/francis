<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['student']);
$user = current_user();
$requestId = (int) ($_GET['request_id'] ?? $_POST['request_id'] ?? 0);
$request = help_request_by_id($requestId);
if (!$request || (int)$request['student_id'] !== (int)$user['id']) { set_flash('error', 'Request not found.'); redirect_to('student/my_requests.php'); }
$offer = accepted_offer_for_request($requestId);
if (!$offer) { set_flash('error', 'Accept a tutor offer first.'); redirect_to('student/request_view.php?id=' . $requestId); }
$payment = payment_for_request($requestId);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $provider = strtolower(clean_text($_POST['provider'] ?? 'mtn'));
    $phone = clean_text($_POST['phone_number'] ?? '');
    if ($phone === '') { set_flash('error', 'Enter the mobile money phone number.'); redirect_to('student/pay_offer.php?request_id=' . $requestId); }
    try {
        if (!$payment) {
            $pricing = calculate_final_help_price((float)$offer['offered_amount'], (int)$user['id'], (int)$request['reward_credits_to_use']);
            $platformFee = round(($pricing['final_price'] * platform_commission_percent()) / 100, 2);
            $tutorEarnings = round($pricing['final_price'] - $platformFee, 2);
            $reference = 'REQ-' . strtoupper(bin2hex(random_bytes(6)));
            $pdo->prepare('INSERT INTO payment_transactions (request_id, student_id, tutor_id, payment_type, amount, base_amount, discount_amount, reward_credit_value, platform_fee, tutor_earnings, provider, phone_number, gateway_reference, gateway_status, status, notes) VALUES (:request_id, :student_id, :tutor_id, "help_request", :amount, :base_amount, :discount_amount, :reward_credit_value, :platform_fee, :tutor_earnings, :provider, :phone_number, :gateway_reference, "pending", "pending", :notes)')->execute([
                'request_id'=>$requestId,'student_id'=>$user['id'],'tutor_id'=>$offer['tutor_id'],'amount'=>$pricing['final_price'],'base_amount'=>$offer['offered_amount'],'discount_amount'=>$pricing['discount_amount'],'reward_credit_value'=>$pricing['reward_credit_value'],'platform_fee'=>$platformFee,'tutor_earnings'=>$tutorEarnings,'provider'=>$provider,'phone_number'=>$phone,'gateway_reference'=>$reference,'notes'=>'Academic help payment for request #' . $requestId
            ]);
            $paymentId = (int)$pdo->lastInsertId();
            $pdo->prepare("UPDATE help_requests SET status='awaiting_payment' WHERE id = :id")->execute(['id'=>$requestId]);
        } else {
            $paymentId = (int)$payment['id'];
            $pdo->prepare('UPDATE payment_transactions SET provider = :provider, phone_number = :phone_number WHERE id = :id')->execute(['provider'=>$provider, 'phone_number'=>$phone, 'id'=>$paymentId]);
            $pdo->prepare("UPDATE help_requests SET status='awaiting_payment' WHERE id = :id")->execute(['id'=>$requestId]);
        }
        $stmt = $pdo->prepare('SELECT * FROM payment_transactions WHERE id = :id LIMIT 1'); $stmt->execute(['id'=>$paymentId]); $paymentRow = $stmt->fetch();
        $response = initiate_lenco_collection($paymentRow, $phone, $provider, (string)$user['email'], (string)$user['name']);
        update_payment_gateway_snapshot($paymentId, $response);
        $pdo->prepare("UPDATE help_requests SET status='payment_pending' WHERE id = :id")->execute(['id'=>$requestId]);
        set_flash('success', 'Payment prompt sent. Approve it on your phone, then refresh payment status.');
        redirect_to('student/payment_status.php?payment_id=' . $paymentId);
    } catch (Throwable $e) {
        set_flash('error', $e->getMessage());
        redirect_to('student/pay_offer.php?request_id=' . $requestId);
    }
}
$pricing = calculate_final_help_price((float)$offer['offered_amount'], (int)$user['id'], (int)$request['reward_credits_to_use']);
$pageTitle = 'Pay for session';
include __DIR__ . '/../includes/header.php';
?>
<div class="row g-4 justify-content-center">
    <div class="col-xl-8">
        <div class="card card-soft p-4">
            <div class="row g-4 align-items-start">
                <div class="col-lg-6">
                    <h1 class="h3 mb-1">Confirm guided session payment</h1>
                    <div class="text-muted mb-3">Tutor: <?= e($offer['tutor_name']) ?> · Request: <?= e($request['title']) ?></div>
                    <div class="border rounded-4 p-3 bg-light-subtle">
                        <div class="d-flex justify-content-between mb-2"><span>Agreed tutor fee</span><strong><?= money($offer['offered_amount']) ?></strong></div>
                        <div class="d-flex justify-content-between mb-2"><span>Subscription discount</span><strong>-<?= money($pricing['discount_amount']) ?></strong></div>
                        <div class="d-flex justify-content-between mb-2"><span>Reward credit value</span><strong>-<?= money($pricing['reward_credit_value']) ?></strong></div>
                        <hr><div class="d-flex justify-content-between"><span class="fw-semibold">Amount to pay</span><strong class="fs-4"><?= money($pricing['final_price']) ?></strong></div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <?php if (!lenco_enabled()): ?><div class="alert alert-warning">Lenco is not configured yet. Add credentials in admin settings before accepting live payments.</div><?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="request_id" value="<?= (int)$requestId ?>">
                        <div class="mobile-money-grid mb-3"><?php foreach (['mtn'=>'MTN','airtel'=>'Airtel','zamtel'=>'Zamtel'] as $key => $label): ?><div class="provider-option"><input id="provider-<?= e($key) ?>" type="radio" name="provider" value="<?= e($key) ?>" <?= $key==='mtn'?'checked':'' ?>><label for="provider-<?= e($key) ?>"><?= e($label) ?></label></div><?php endforeach; ?></div>
                        <div class="mb-3"><label class="form-label">Phone number</label><input class="form-control" type="text" name="phone_number" placeholder="2609XXXXXXXX" required></div>
                        <div class="d-flex gap-2"><button class="btn btn-primary" <?= lenco_enabled() ? '' : 'disabled' ?>>Request payment</button><a class="btn btn-outline-secondary" href="<?= app_url('student/request_view.php?id=' . $requestId) ?>">Back</a></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
