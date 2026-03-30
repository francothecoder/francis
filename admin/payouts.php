<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['mark_paid'])) {
        $payoutId = (int) ($_POST['payout_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM payout_requests WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $payoutId]);
        $payout = $stmt->fetch();
        if ($payout) {
            $pdo->beginTransaction();
            try {
                $pdo->prepare("UPDATE payout_requests SET status = 'paid', paid_at = NOW() WHERE id = :id")->execute(['id' => $payoutId]);
                $pdo->prepare("INSERT INTO tutor_wallet_transactions (tutor_id, amount, transaction_type, notes) VALUES (:tutor_id, :amount, 'debit', :notes)")
                    ->execute([
                        'tutor_id' => $payout['tutor_id'],
                        'amount' => $payout['amount'],
                        'notes' => 'Tutor payout marked paid by admin',
                    ]);
                create_notification((int) $payout['tutor_id'], 'Payout completed', 'Your tutor payout request has been marked as paid.', 'tutor/dashboard.php');
                $pdo->commit();
                set_flash('success', 'Payout marked as paid.');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                set_flash('error', $e->getMessage());
            }
        }
    }
    redirect_to('admin/payouts.php');
}

$payouts = $pdo->query("
    SELECT pr.*, u.name
    FROM payout_requests pr
    INNER JOIN users u ON u.id = pr.tutor_id
    ORDER BY pr.id DESC
")->fetchAll();
$pageTitle = 'Tutor payouts';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Tutor payouts</h1>
<div class="card card-soft p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Tutor</th><th>Amount</th><th>Status</th><th>Requested</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($payouts as $payout): ?>
                    <tr>
                        <td><?= e($payout['name']) ?></td>
                        <td><?= money($payout['amount']) ?></td>
                        <td><?= status_badge($payout['status']) ?></td>
                        <td><?= e(relative_time($payout['requested_at'])) ?></td>
                        <td>
                            <?php if ($payout['status'] === 'requested'): ?>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="payout_id" value="<?= (int) $payout['id'] ?>">
                                    <button class="btn btn-sm btn-primary" name="mark_paid" value="1">Mark paid</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$payouts): ?>
                    <tr><td colspan="5" class="text-muted">No payout requests yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
