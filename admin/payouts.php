<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $payoutId = (int) ($_POST['payout_id'] ?? 0);
    $adminNotes = trim((string) ($_POST['admin_notes'] ?? ''));
    $stmt = $pdo->prepare('SELECT pr.*, u.name, u.email FROM payout_requests pr INNER JOIN users u ON u.id = pr.tutor_id WHERE pr.id = :id LIMIT 1');
    $stmt->execute(['id' => $payoutId]);
    $payout = $stmt->fetch();

    if ($payout) {
        $pdo->beginTransaction();
        try {
            if (isset($_POST['approve']) && $payout['status'] === 'requested') {
                $pdo->prepare("UPDATE payout_requests SET status = 'approved', approved_at = NOW(), approved_by = :approved_by, admin_notes = :admin_notes WHERE id = :id")
                    ->execute(['approved_by' => current_user()['id'], 'admin_notes' => $adminNotes !== '' ? $adminNotes : null, 'id' => $payoutId]);
                create_notification((int) $payout['tutor_id'], 'Withdrawal approved', 'Your withdrawal request for ' . money((float) $payout['amount']) . ' has been approved and is awaiting transfer.', 'tutor/request_payout.php');
                set_flash('success', 'Withdrawal approved.');
            }

            if (isset($_POST['reject']) && in_array($payout['status'], ['requested','approved'], true)) {
                $pdo->prepare("UPDATE payout_requests SET status = 'rejected', approved_by = :approved_by, admin_notes = :admin_notes WHERE id = :id")
                    ->execute(['approved_by' => current_user()['id'], 'admin_notes' => $adminNotes !== '' ? $adminNotes : 'Withdrawal request rejected by admin', 'id' => $payoutId]);
                $pdo->prepare("INSERT INTO tutor_wallet_transactions (tutor_id, amount, transaction_type, notes) VALUES (:tutor_id, :amount, 'credit', :notes)")
                    ->execute([
                        'tutor_id' => $payout['tutor_id'],
                        'amount' => $payout['amount'],
                        'notes' => 'Withdrawal request #' . $payoutId . ' rejected and funds returned',
                    ]);
                create_notification((int) $payout['tutor_id'], 'Withdrawal rejected', 'Your withdrawal request was rejected. Reserved funds have been returned to your wallet.', 'tutor/request_payout.php');
                set_flash('success', 'Withdrawal rejected and funds returned to wallet.');
            }

            if (isset($_POST['mark_paid']) && in_array($payout['status'], ['approved','requested'], true)) {
                $pdo->prepare("UPDATE payout_requests SET status = 'paid', approved_at = COALESCE(approved_at, NOW()), approved_by = COALESCE(approved_by, :approved_by), paid_at = NOW(), admin_notes = :admin_notes WHERE id = :id")
                    ->execute(['approved_by' => current_user()['id'], 'admin_notes' => $adminNotes !== '' ? $adminNotes : 'Withdrawal transferred by admin', 'id' => $payoutId]);
                create_notification((int) $payout['tutor_id'], 'Withdrawal paid', 'Your withdrawal request for ' . money((float) $payout['amount']) . ' has been marked as paid.', 'tutor/request_payout.php');
                set_flash('success', 'Withdrawal marked as paid.');
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('error', $e->getMessage());
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
$pageTitle = 'Tutor withdrawals';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Tutor withdrawals</h1>
<div class="card card-soft p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Tutor</th><th>Amount</th><th>Wallet</th><th>Status</th><th>Requested</th><th>Notes</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($payouts as $payout): ?>
                    <tr>
                        <td><?= e($payout['name']) ?></td>
                        <td><?= money($payout['amount']) ?></td>
                        <td><?= e(strtoupper((string) $payout['provider'])) ?><br><span class="small text-muted"><?= e($payout['mobile_number']) ?></span></td>
                        <td><?= status_badge($payout['status']) ?></td>
                        <td><?= e(relative_time($payout['requested_at'])) ?></td>
                        <td class="small text-muted"><?= e($payout['admin_notes'] ?: $payout['notes'] ?: '-') ?></td>
                        <td style="min-width:280px;">
                            <?php if (in_array($payout['status'], ['requested','approved'], true)): ?>
                                <form method="post" class="vstack gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="payout_id" value="<?= (int) $payout['id'] ?>">
                                    <textarea class="form-control form-control-sm" name="admin_notes" rows="2" placeholder="Admin notes"></textarea>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <?php if ($payout['status'] === 'requested'): ?><button class="btn btn-sm btn-outline-primary" name="approve" value="1">Approve</button><?php endif; ?>
                                        <button class="btn btn-sm btn-primary" name="mark_paid" value="1">Mark paid</button>
                                        <button class="btn btn-sm btn-outline-danger" name="reject" value="1" onclick="return confirm('Reject this withdrawal and return funds to wallet?')">Reject</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">No action needed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$payouts): ?>
                    <tr><td colspan="7" class="text-muted">No withdrawal requests yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
