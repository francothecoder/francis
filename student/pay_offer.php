<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['student']);
$user = current_user();
$requestId = (int) ($_GET['request_id'] ?? $_POST['request_id'] ?? 0);
$request = help_request_by_id($requestId);
if (!$request || (int) $request['student_id'] !== (int) $user['id']) { set_flash('error', 'Help request not found.'); redirect_to('student/my_requests.php'); }
$offer = accepted_offer_for_request($requestId);
if (!$offer) { set_flash('error', 'Accept a tutor offer before paying.'); redirect_to('student/request_view.php?id=' . $requestId); }
$latestPayment = payment_for_request($requestId);
$pricing = calculate_final_help_price((float)$offer['offered_amount'], (int)$user['id'], 0);
$platformFee = round(($pricing['final_price'] * platform_commission_percent()) / 100, 2);
$tutorEarnings = round($pricing['final_price'] - $platformFee, 2);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $provider = strtolower(clean_text($_POST['provider'] ?? 'mtn'));
    $phone = normalize_zambian_phone(clean_text($_POST['phone_number'] ?? ''));
    $paymentMethod = in_array($_POST['payment_method'] ?? 'lenco', ['lenco','manual'], true) ? $_POST['payment_method'] : 'lenco';
    $manualReference = clean_text($_POST['manual_reference'] ?? '');
    if ($phone === '') { set_flash('error', 'Enter the mobile money phone number.'); redirect_to('student/pay_offer.php?request_id=' . $requestId); }

    try {
        if ($paymentMethod === 'manual') {
            $proofPath = uploaded_payment_proof_path($_FILES['manual_proof'] ?? null);
            if ($manualReference === '' && !$proofPath) {
                throw new RuntimeException('For manual approval, add the transaction reference or upload proof.');
            }
            $paymentId = create_help_request_payment_transaction(
                $requestId,
                (int)$user['id'],
                (int)$offer['tutor_id'],
                (float)$pricing['final_price'],
                (float)$offer['offered_amount'],
                (float)$pricing['discount_amount'],
                (float)$pricing['reward_credit_value'],
                (float)$platformFee,
                (float)$tutorEarnings,
                $provider,
                $phone,
                'manual',
                'Manual payment submitted for admin approval.',
                $manualReference !== '' ? $manualReference : null,
                $proofPath
            );
            $pdo->prepare("UPDATE help_requests SET status='awaiting_payment' WHERE id = :id")->execute(['id'=>$requestId]);
            create_notification((int)$user['id'], 'Manual payment submitted', 'Your payment proof was submitted and is awaiting admin approval.', 'student/payment_status.php?payment_id=' . $paymentId);
            foreach ($pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll() as $adminRow) {
                create_notification((int)$adminRow['id'], 'Manual payment needs review', 'A student submitted a manual payment for request #' . $requestId . '.', 'admin/payments.php');
            }
            set_flash('success', 'Manual payment submitted. An admin will review it shortly.');
            redirect_to('student/payment_status.php?payment_id=' . $paymentId);
        }

        $paymentId = create_help_request_payment_transaction(
            $requestId,
            (int)$user['id'],
            (int)$offer['tutor_id'],
            (float)$pricing['final_price'],
            (float)$offer['offered_amount'],
            (float)$pricing['discount_amount'],
            (float)$pricing['reward_credit_value'],
            (float)$platformFee,
            (float)$tutorEarnings,
            $provider,
            $phone,
            'lenco',
            'Academic help payment for request #' . $requestId
        );
        $pdo->prepare("UPDATE help_requests SET status='awaiting_payment' WHERE id = :id")->execute(['id'=>$requestId]);

        $paymentRowStmt = $pdo->prepare('SELECT * FROM payment_transactions WHERE id = :id LIMIT 1');
        $paymentRowStmt->execute(['id'=>$paymentId]);
        $paymentRow = $paymentRowStmt->fetch();
        if (!$paymentRow) throw new RuntimeException('Unable to initialize payment.');
        $response = initiate_lenco_collection($paymentRow, $phone, $provider, (string)$user['email'], (string)$user['name']);
        update_payment_gateway_snapshot($paymentId, $response);
        $gatewayStatus = strtolower((string)($response['data']['status'] ?? 'pending'));
        $pdo->prepare("UPDATE help_requests SET status='payment_pending' WHERE id = :id")->execute(['id'=>$requestId]);
        $gatewayMessage = payment_gateway_public_message($response);
        if ($gatewayStatus === 'failed') {
            set_flash('error', $gatewayMessage . ' You can retry with a fresh reference or switch to manual approval.');
        } else {
            set_flash('success', $gatewayMessage);
        }
        redirect_to('student/payment_status.php?payment_id=' . $paymentId);
    } catch (Throwable $e) {
        set_flash('error', $e->getMessage());
        redirect_to('student/pay_offer.php?request_id=' . $requestId);
    }
}
$pageTitle = 'Pay for academic help';
include __DIR__ . '/../includes/header.php';
?>
<div class="row g-4 justify-content-center">
    <div class="col-xl-8">
        <div class="card card-soft p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div><h1 class="h3 mb-1">Complete your academic help payment</h1><div class="text-muted">Secure your selected tutor and unlock the guided study session.</div></div>
                <div class="display-6 fw-bold"><?= money($pricing['final_price']) ?></div>
            </div>
            <?php if ($latestPayment && in_array($latestPayment['status'], ['failed','held','pending'], true)): ?>
                <div class="alert alert-warning border-0">A previous payment attempt exists. Any new retry from this page will create a <strong>fresh payment reference</strong> automatically.</div>
            <?php endif; ?>
            <div class="row g-3 mb-4">
                <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="stat-muted">Tutor</div><div class="fw-semibold"><?= e($offer['tutor_name']) ?></div><div class="small text-muted mt-2"><?= e($request['title']) ?></div></div></div>
                <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="stat-muted">Pricing breakdown</div><div class="small text-muted">Base price <?= money($offer['offered_amount']) ?></div><div class="small text-muted">Plan discount <?= money($pricing['discount_amount']) ?></div><div class="small text-muted">Reward credits <?= money($pricing['reward_credit_value']) ?></div><div class="fw-semibold mt-2">Final amount <?= money($pricing['final_price']) ?></div></div></div>
            </div>

            <form method="post" enctype="multipart/form-data" class="row g-3" id="paymentForm">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="request_id" value="<?= (int)$requestId ?>">

                <div class="col-12">
                    <label class="form-label">Payment method</label>
                    <div class="mobile-money-grid">
                        <div class="provider-option"><input id="method-lenco" type="radio" name="payment_method" value="lenco" checked><label for="method-lenco">Lenco mobile money</label></div>
                        <div class="provider-option"><input id="method-manual" type="radio" name="payment_method" value="manual"><label for="method-manual">Manual admin approval</label></div>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Mobile money provider</label>
                    <div class="mobile-money-grid"><?php foreach (['mtn'=>'MTN','airtel'=>'Airtel','zamtel'=>'Zamtel'] as $key => $label): ?><div class="provider-option"><input id="provider-<?= e($key) ?>" type="radio" name="provider" value="<?= e($key) ?>" <?= $key==='mtn'?'checked':'' ?>><label for="provider-<?= e($key) ?>"><?= e($label) ?></label></div><?php endforeach; ?></div>
                </div>

                <div class="col-md-6"><label class="form-label">Phone number</label><input class="form-control" type="text" name="phone_number" placeholder="097XXXXXXX or 26097XXXXXXX" required></div>

                <div class="col-md-6 manual-only d-none"><label class="form-label">Manual transaction reference</label><input class="form-control" type="text" name="manual_reference" placeholder="Optional if you upload proof"></div>
                <div class="col-12 manual-only d-none"><label class="form-label">Upload proof (image or PDF)</label><input class="form-control" type="file" name="manual_proof" accept=".jpg,.jpeg,.png,.pdf,.webp"></div>
                <div class="col-12 manual-only d-none"><div class="alert alert-info border-0 mb-0">Use this if Lenco fails or you already paid outside the gateway. Admin will review and approve manually.</div></div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" id="payBtn" <?= lenco_enabled() ? '' : 'disabled' ?>>Continue</button>
                    <a class="btn btn-outline-secondary" href="<?= app_url('student/request_view.php?id=' . $requestId) ?>">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(() => {
  const lencoRadio = document.getElementById('method-lenco');
  const manualRadio = document.getElementById('method-manual');
  const manualBlocks = document.querySelectorAll('.manual-only');
  const btn = document.getElementById('payBtn');
  const form = document.getElementById('paymentForm');
  function syncMethod() {
    const manual = manualRadio.checked;
    manualBlocks.forEach(el => el.classList.toggle('d-none', !manual));
    btn.textContent = manual ? 'Submit for manual approval' : 'Request payment';
  }
  lencoRadio.addEventListener('change', syncMethod);
  manualRadio.addEventListener('change', syncMethod);
  form.addEventListener('submit', () => {
    btn.disabled = true;
    btn.textContent = manualRadio.checked ? 'Submitting...' : 'Processing...';
  });
  syncMethod();
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
