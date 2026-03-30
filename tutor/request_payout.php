<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['tutor']);
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $amount = (float) ($_POST['amount'] ?? 0);
    $available = scalar_value("SELECT COALESCE(SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END),0) FROM tutor_wallet_transactions WHERE tutor_id = :tutor_id", ['tutor_id' => $user['id']]);

    if ($amount <= 0 || $amount > $available) {
        set_flash('error', 'Enter a valid payout amount within your available tutor balance.');
        redirect_to('tutor/request_payout.php');
    }

    $stmt = $pdo->prepare('INSERT INTO payout_requests (tutor_id, amount, status) VALUES (:tutor_id, :amount, :status)');
    $stmt->execute([
        'tutor_id' => $user['id'],
        'amount' => $amount,
        'status' => 'requested',
    ]);
    create_notification((int) $user['id'], 'Payout request submitted', 'Your payout request is waiting for admin review.', 'tutor/dashboard.php');
    set_flash('success', 'Payout request submitted successfully.');
    redirect_to('tutor/request_payout.php');
}

$available = scalar_value("SELECT COALESCE(SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END),0) FROM tutor_wallet_transactions WHERE tutor_id = :tutor_id", ['tutor_id' => $user['id']]);
$requests = $pdo->prepare('SELECT * FROM payout_requests WHERE tutor_id = :tutor_id ORDER BY id DESC');
$requests->execute(['tutor_id' => $user['id']]);
$items = $requests->fetchAll();

$pageTitle = 'Request payout';
include __DIR__ . '/../includes/header.php';
?>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card card-soft p-4">
            <h1 class="h4 mb-3">Request payout</h1>
            <div class="text-muted mb-3">Available balance: <?= money($available) ?></div>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label">Amount</label>
                    <input class="form-control" type="number" step="0.01" min="1" max="<?= e((string) $available) ?>" name="amount" required>
                </div>
                <button class="btn btn-primary">Submit payout request</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-soft p-4">
            <div class="section-title mb-3">Payout history</div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Amount</th><th>Status</th><th>Requested</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= money($item['amount']) ?></td>
                            <td><?= status_badge($item['status']) ?></td>
                            <td><?= e(relative_time($item['requested_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$items): ?>
                        <tr><td colspan="3" class="text-muted">No payout requests yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
