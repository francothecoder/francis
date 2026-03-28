<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$adminId = (int)current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    create_payment_record((int)$_POST['user_id'], $_POST['plan_id'] !== '' ? (int)$_POST['plan_id'] : null, (float)$_POST['amount'], trim($_POST['method'] ?? ''), trim($_POST['reference_code'] ?? ''), trim($_POST['notes'] ?? ''), null, $adminId);
    set_flash('success', 'Payment recorded.');
    redirect_to('admin/payments.php');
}
$users = $pdo->query("SELECT id, name FROM users WHERE role = 'student' ORDER BY name")->fetchAll();
$plans = $pdo->query('SELECT id, name FROM membership_plans ORDER BY price')->fetchAll();
$payments = $pdo->query("SELECT p.*, u.name as user_name, m.name as plan_name
                         FROM payment_records p
                         LEFT JOIN users u ON u.id = p.user_id
                         LEFT JOIN membership_plans m ON m.id = p.plan_id
                         ORDER BY p.id DESC")->fetchAll();
$pageTitle='Payments'; require_once __DIR__ . '/../includes/header.php'; ?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-soft p-4">
            <h1 class="fw-bold mb-3">Record Payment</h1>
            <form method="post">
                <div class="mb-3"><label class="form-label">Student</label><select class="form-select" name="user_id"><?php foreach($users as $u): ?><option value="<?= e($u['id']) ?>"><?= e($u['name']) ?></option><?php endforeach; ?></select></div>
                <div class="mb-3"><label class="form-label">Plan</label><select class="form-select" name="plan_id"><option value="">None</option><?php foreach($plans as $p): ?><option value="<?= e($p['id']) ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select></div>
                <div class="mb-3"><label class="form-label">Amount</label><input class="form-control" type="number" step="0.01" name="amount" required></div>
                <div class="mb-3"><label class="form-label">Method</label><input class="form-control" name="method" placeholder="MTN / Airtel / Cash" required></div>
                <div class="mb-3"><label class="form-label">Reference</label><input class="form-control" name="reference_code"></div>
                <div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" rows="3" name="notes"></textarea></div>
                <button class="btn btn-primary w-100">Save Payment</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-soft p-4">
            <h4>Payment Records</h4>
            <div class="table-responsive"><table class="table"><thead><tr><th>Student</th><th>Plan</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead><tbody><?php foreach($payments as $payment): ?><tr><td><?= e($payment['user_name']) ?></td><td><?= e($payment['plan_name'] ?? '-') ?></td><td>K<?= e(number_format($payment['amount'],2)) ?></td><td><?= e($payment['method']) ?></td><td><?= e($payment['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
