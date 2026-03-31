<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $paymentId = (int) ($_POST['payment_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $note = clean_text($_POST['approval_notes'] ?? '');
    try {
        if ($action === 'approve') {
            admin_finalize_payment($paymentId, (int)$user['id'], true, $note !== '' ? $note : 'Approved by admin.');
            set_flash('success', 'Payment approved successfully.');
        } elseif ($action === 'reject') {
            admin_finalize_payment($paymentId, (int)$user['id'], false, $note !== '' ? $note : 'Rejected by admin.');
            set_flash('success', 'Payment rejected.');
        } else {
            throw new RuntimeException('Invalid payment action.');
        }
    } catch (Throwable $e) {
        set_flash('error', $e->getMessage());
    }
    redirect_to('admin/payments.php');
}

$payments = $pdo->query("
    SELECT pt.*, u.name AS student_name, u.email AS student_email, tu.name AS tutor_name, sp.name AS plan_name, hr.title AS request_title, au.name AS approved_by_name
    FROM payment_transactions pt
    INNER JOIN users u ON u.id = pt.student_id
    LEFT JOIN users tu ON tu.id = pt.tutor_id
    LEFT JOIN subscription_plans sp ON sp.id = pt.plan_id
    LEFT JOIN help_requests hr ON hr.id = pt.request_id
    LEFT JOIN users au ON au.id = pt.approved_by
    ORDER BY pt.id DESC
    LIMIT 100
")->fetchAll();

$pageTitle = 'Payments';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Payments</h1>
        <div class="text-muted">Review live gateway attempts and approve manual fallback payments when needed.</div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="metric-card bg-white p-4"><div class="text-muted">Pending review</div><div class="metric-value"><?= count_value("SELECT COUNT(*) FROM payment_transactions WHERE gateway = 'manual' AND status = 'held'") ?></div></div></div>
    <div class="col-md-3"><div class="metric-card bg-white p-4"><div class="text-muted">Paid today</div><div class="metric-value"><?= count_value("SELECT COUNT(*) FROM payment_transactions WHERE status = 'paid' AND DATE(paid_at) = CURDATE()") ?></div></div></div>
    <div class="col-md-3"><div class="metric-card bg-white p-4"><div class="text-muted">Failed attempts</div><div class="metric-value"><?= count_value("SELECT COUNT(*) FROM payment_transactions WHERE status = 'failed'") ?></div></div></div>
    <div class="col-md-3"><div class="metric-card bg-white p-4"><div class="text-muted">Held volume</div><div class="metric-value"><?= money(scalar_value("SELECT COALESCE(SUM(amount),0) FROM payment_transactions WHERE status = 'held'")) ?></div></div></div>
</div>

<div class="card card-soft p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>ID</th><th>Student</th><th>Type</th><th>Amount</th><th>Gateway</th><th>Reference</th><th>Status</th><th>Reason / note</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td>#<?= (int)$payment['id'] ?></td>
                        <td>
                            <div class="fw-semibold"><?= e($payment['student_name']) ?></div>
                            <div class="small text-muted"><?= e($payment['student_email']) ?></div>
                            <?php if (!empty($payment['request_title'])): ?><div class="small text-muted"><?= e($payment['request_title']) ?></div><?php endif; ?>
                            <?php if (!empty($payment['plan_name'])): ?><div class="small text-muted"><?= e($payment['plan_name']) ?></div><?php endif; ?>
                        </td>
                        <td><?= e($payment['payment_type']) ?></td>
                        <td><?= money($payment['amount']) ?></td>
                        <td><div class="fw-semibold"><?= e(strtoupper((string)$payment['gateway'])) ?></div><div class="small text-muted"><?= e(strtoupper((string)$payment['provider'])) ?></div></td>
                        <td>
                            <div class="fw-semibold small"><?= e($payment['gateway_reference']) ?></div>
                            <?php if (!empty($payment['manual_reference'])): ?><div class="small text-muted">Manual ref: <?= e($payment['manual_reference']) ?></div><?php endif; ?>
                            <?php if (!empty($payment['manual_proof_path'])): ?><a class="small" target="_blank" href="<?= app_url($payment['manual_proof_path']) ?>">View proof</a><?php endif; ?>
                        </td>
                        <td>
                            <?= status_badge((string)$payment['status']) ?>
                            <div class="small text-muted mt-1">Gateway: <?= e($payment['gateway_status'] ?: '-') ?></div>
                            <?php if (!empty($payment['approved_by_name'])): ?><div class="small text-muted">By <?= e($payment['approved_by_name']) ?></div><?php endif; ?>
                        </td>
                        <td class="small text-muted" style="min-width:240px">
                            <?= e($payment['approval_notes'] ?: $payment['notes'] ?: '—') ?>
                        </td>
                        <td style="min-width:260px">
                            <?php if (in_array($payment['status'], ['held','pending','failed'], true)): ?>
                                <form method="post" class="d-grid gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="payment_id" value="<?= (int)$payment['id'] ?>">
                                    <textarea class="form-control form-control-sm" name="approval_notes" rows="2" placeholder="Admin note (optional)"></textarea>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-success btn-sm" name="action" value="approve">Approve</button>
                                        <button class="btn btn-outline-danger btn-sm" name="action" value="reject">Reject</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">No action needed.</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$payments): ?>
                    <tr><td colspan="9" class="text-muted">No payments found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
