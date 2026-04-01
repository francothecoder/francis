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
    $phone = normalize_zambian_phone(clean_text($_POST['phone_number'] ?? ''));
    $paymentMethod = in_array($_POST['payment_method'] ?? 'lenco', ['lenco','manual'], true) ? $_POST['payment_method'] : 'lenco';
    $manualReference = clean_text($_POST['manual_reference'] ?? '');
    if ($phone === '') { set_flash('error', 'Enter the mobile money phone number.'); redirect_to('student/subscribe.php?plan=' . $planId); }
    try {
        if ($paymentMethod === 'manual') {
            $proofPath = uploaded_payment_proof_path($_FILES['manual_proof'] ?? null);
            if ($manualReference === '' && !$proofPath) {
                throw new RuntimeException('For manual approval, add the transaction reference or upload proof.');
            }
            $paymentId = create_subscription_payment_transaction(
                (int)$plan['id'],
                (int)$user['id'],
                (float)$plan['monthly_price'],
                $provider,
                $phone,
                'manual',
                'Manual subscription payment submitted for admin approval.',
                $manualReference !== '' ? $manualReference : null,
                $proofPath
            );
            create_notification((int)$user['id'], 'Manual subscription submitted', 'Your subscription payment proof was submitted and is awaiting admin approval.', 'student/payment_status.php?payment_id=' . $paymentId);
            foreach ($pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll() as $adminRow) {
                create_notification((int)$adminRow['id'], 'Manual subscription review needed', 'A student submitted a manual subscription payment for ' . $plan['name'] . '.', 'admin/payments.php');
            }
            set_flash('success', 'Manual payment submitted. An admin will review it shortly.');
            redirect_to('student/payment_status.php?payment_id=' . $paymentId);
        }

        $paymentId = create_subscription_payment_transaction(
            (int)$plan['id'],
            (int)$user['id'],
            (float)$plan['monthly_price'],
            $provider,
            $phone,
            'lenco',
            'Subscription payment for ' . $plan['name']
        );
        $paymentRowStmt = $pdo->prepare('SELECT * FROM payment_transactions WHERE id = :id LIMIT 1');
        $paymentRowStmt->execute(['id'=>$paymentId]);
        $paymentRow = $paymentRowStmt->fetch();
        if (!$paymentRow) throw new RuntimeException('Unable to initialize payment.');
        $response = initiate_lenco_collection($paymentRow, $phone, $provider, (string)$user['email'], (string)$user['name']);
        update_payment_gateway_snapshot($paymentId, $response);
        $gatewayMessage = payment_gateway_public_message($response);
        if (($response['data']['status'] ?? '') === 'failed') {
            set_flash('error', $gatewayMessage . ' You can retry with a fresh reference or switch to manual approval.');
        } else {
            set_flash('success', $gatewayMessage);
        }
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
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3"><div><h1 class="h3 mb-1">Activate <?= e($plan['name']) ?></h1><div class="text-muted">Pay with Lenco mobile money or submit proof for manual approval.</div></div><div class="display-6 fw-bold"><?= money($plan['monthly_price']) ?></div></div>
            <?php if ($existing && in_array($existing['status'], ['failed','held','pending'], true)): ?><div class="alert alert-warning border-0">A previous payment attempt exists. Any retry here will use a <strong>fresh payment reference</strong>.</div><?php endif; ?>
            <div class="alert alert-warning border-0"><strong>Security tip:</strong> Never pay tutors or subscriptions outside this platform. Keep every payment attempt, proof upload, and approval inside the platform so it can be tracked and protected.</div>
            <form method="post" enctype="multipart/form-data" id="subscriptionPaymentForm">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
                <div class="mb-3">
                    <label class="form-label">Payment method</label>
                    <div class="mobile-money-grid">
                        <div class="provider-option"><input id="sub-method-lenco" type="radio" name="payment_method" value="lenco" checked><label for="sub-method-lenco">Lenco mobile money</label></div>
                        <div class="provider-option"><input id="sub-method-manual" type="radio" name="payment_method" value="manual"><label for="sub-method-manual">Manual admin approval</label></div>
                    </div>
                </div>
                <div class="mobile-money-grid mb-3">
                    <?php foreach (['mtn'=>'MTN','airtel'=>'Airtel','zamtel'=>'Zamtel'] as $key => $label): ?>
                        <div class="provider-option"><input id="provider-<?= e($key) ?>" type="radio" name="provider" value="<?= e($key) ?>" <?= $key==='mtn'?'checked':'' ?>><label for="provider-<?= e($key) ?>"><?= e($label) ?></label></div>
                    <?php endforeach; ?>
                </div>
                <div class="mb-3"><label class="form-label">Phone number</label><input class="form-control" type="text" name="phone_number" placeholder="097XXXXXXX or 26097XXXXXXX" required></div>
                <div class="row g-3 manual-only d-none">
                    <div class="col-md-6"><label class="form-label">Manual transaction reference</label><input class="form-control" type="text" name="manual_reference" placeholder="Optional if you upload proof"></div>
                    <div class="col-md-6"><label class="form-label">Upload proof (image or PDF)</label><input class="form-control" type="file" name="manual_proof" accept=".jpg,.jpeg,.png,.pdf,.webp"></div>
                    <div class="col-12"><div class="alert alert-info border-0 mb-0">Use manual approval if the mobile money gateway fails. Admin will review and activate your plan.</div></div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3"><button class="btn btn-primary" id="subscriptionPayBtn">Continue</button><a class="btn btn-outline-secondary" href="<?= app_url('student/subscriptions.php') ?>">Back</a></div>
            </form>
        </div>
    </div>
</div>
<script>
(() => {
  const lenco = document.getElementById('sub-method-lenco');
  const manual = document.getElementById('sub-method-manual');
  const blocks = document.querySelectorAll('.manual-only');
  const btn = document.getElementById('subscriptionPayBtn');
  const form = document.getElementById('subscriptionPaymentForm');
  function sync() {
    const show = manual.checked;
    blocks.forEach(el => el.classList.toggle('d-none', !show));
    btn.textContent = show ? 'Submit for manual approval' : 'Request payment';
  }
  lenco.addEventListener('change', sync);
  manual.addEventListener('change', sync);
  form.addEventListener('submit', () => { btn.disabled = true; btn.textContent = manual.checked ? 'Submitting...' : 'Processing...'; });
  sync();
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
