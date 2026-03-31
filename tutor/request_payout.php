<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['tutor']);
$user = current_user();
$summary = payout_summary((int) $user['id']);
$available = $summary['available'];
$minimumWithdrawal = 20.00;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $amount = round((float) ($_POST['amount'] ?? 0), 2);
    $provider = strtolower(trim((string) ($_POST['provider'] ?? 'mtn')));
    $mobile = normalize_zambian_phone((string) ($_POST['mobile_number'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));

    if (!in_array($provider, ['mtn','airtel','zamtel'], true)) {
        set_flash('error', 'Select a valid mobile money provider.');
        redirect_to('tutor/request_payout.php');
    }
    if ($amount < $minimumWithdrawal) {
        set_flash('error', 'Minimum withdrawal is ' . money($minimumWithdrawal) . '.');
        redirect_to('tutor/request_payout.php');
    }
    if ($amount <= 0 || $amount > $available) {
        set_flash('error', 'Enter a valid payout amount within your available tutor balance.');
        redirect_to('tutor/request_payout.php');
    }
    if (!valid_zambian_phone($mobile)) {
        set_flash('error', 'Enter a valid Zambia mobile money number.');
        redirect_to('tutor/request_payout.php');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO payout_requests (tutor_id, amount, provider, mobile_number, notes, status) VALUES (:tutor_id, :amount, :provider, :mobile_number, :notes, :status)');
        $stmt->execute([
            'tutor_id' => $user['id'],
            'amount' => $amount,
            'provider' => $provider,
            'mobile_number' => $mobile,
            'notes' => $notes !== '' ? $notes : null,
            'status' => 'requested',
        ]);
        $payoutId = (int) $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO tutor_wallet_transactions (tutor_id, amount, transaction_type, notes) VALUES (:tutor_id, :amount, 'debit', :notes)")
            ->execute([
                'tutor_id' => $user['id'],
                'amount' => $amount,
                'notes' => 'Withdrawal request #' . $payoutId . ' submitted and funds reserved',
            ]);
        create_notification((int) $user['id'], 'Withdrawal request submitted', 'Your withdrawal request for ' . money($amount) . ' is waiting for admin review.', 'tutor/request_payout.php');
        $adminIds = $pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($adminIds as $adminId) {
            create_notification((int) $adminId, 'New tutor withdrawal request', $user['name'] . ' requested a payout of ' . money($amount) . '.', 'admin/payouts.php');
        }
        $pdo->commit();
        set_flash('success', 'Withdrawal request submitted successfully. The amount has been reserved from your wallet pending review.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash('error', $e->getMessage());
    }
    redirect_to('tutor/request_payout.php');
}

$summary = payout_summary((int) $user['id']);
$available = $summary['available'];
$requests = $pdo->prepare('SELECT * FROM payout_requests WHERE tutor_id = :tutor_id ORDER BY id DESC');
$requests->execute(['tutor_id' => $user['id']]);
$items = $requests->fetchAll();

$pageTitle = 'Withdraw earnings';
include __DIR__ . '/../includes/header.php';
?>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card card-soft p-4 h-100">
            <h1 class="h4 mb-2">Withdraw earnings</h1>
            <p class="text-muted mb-4">Request your tutor earnings to your preferred mobile money wallet. Minimum withdrawal is <?= money($minimumWithdrawal) ?>.</p>
            <div class="row g-3 mb-4">
                <div class="col-6"><div class="border rounded-4 p-3"><div class="stat-muted">Available</div><div class="fw-bold fs-5"><?= money($summary['available']) ?></div></div></div>
                <div class="col-6"><div class="border rounded-4 p-3"><div class="stat-muted">Requested</div><div class="fw-bold fs-5"><?= money($summary['requested']) ?></div></div></div>
            </div>
            <form method="post" class="vstack gap-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div>
                    <label class="form-label">Amount</label>
                    <input class="form-control" type="number" step="0.01" min="<?= e((string) $minimumWithdrawal) ?>" max="<?= e((string) $available) ?>" name="amount" required>
                </div>
                <div>
                    <label class="form-label">Provider</label>
                    <select class="form-select" name="provider" required>
                        <option value="mtn">MTN Mobile Money</option>
                        <option value="airtel">Airtel Money</option>
                        <option value="zamtel">Zamtel Money</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Mobile number</label>
                    <input class="form-control" type="text" name="mobile_number" placeholder="097xxxxxxx or 26097xxxxxxx" required>
                </div>
                <div>
                    <label class="form-label">Optional note</label>
                    <textarea class="form-control" name="notes" rows="3" placeholder="Any payout note for admin"></textarea>
                </div>
                <button class="btn btn-primary">Submit withdrawal request</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-soft p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="section-title">Withdrawal history</div>
                    <div class="text-muted small">Track the status of each payout request.</div>
                </div>
                <a class="btn btn-outline-primary btn-sm" href="<?= app_url('tutor/dashboard.php') ?>">Back to dashboard</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Amount</th><th>Wallet</th><th>Status</th><th>Requested</th><th>Admin note</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= money($item['amount']) ?></td>
                            <td><?= e(strtoupper((string) $item['provider'])) ?><br><span class="small text-muted"><?= e($item['mobile_number']) ?></span></td>
                            <td><?= status_badge($item['status']) ?></td>
                            <td><?= e(relative_time($item['requested_at'])) ?></td>
                            <td class="small text-muted"><?= e($item['admin_notes'] ?: $item['notes'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$items): ?>
                        <tr><td colspan="5" class="text-muted">No withdrawal requests yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
