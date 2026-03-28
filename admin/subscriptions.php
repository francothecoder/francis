<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$adminId = (int)current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM subscription_requests WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $requestId]);
    $request = $stmt->fetch();

    if ($request) {
        if ($action === 'approve') {
            create_membership((int)$request['user_id'], (int)$request['plan_id'], $adminId);
            create_payment_record((int)$request['user_id'], (int)$request['plan_id'], (float)$request['amount'], $request['payment_method'], $request['reference_code'], $request['notes'], (int)$request['id'], $adminId);
            $pdo->prepare("UPDATE subscription_requests SET status = 'approved', reviewed_by = :admin, reviewed_at = NOW() WHERE id = :id")
                ->execute(['admin' => $adminId, 'id' => $requestId]);
            set_flash('success', 'Subscription approved and membership activated.');
        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE subscription_requests SET status = 'rejected', reviewed_by = :admin, reviewed_at = NOW() WHERE id = :id")
                ->execute(['admin' => $adminId, 'id' => $requestId]);
            set_flash('success', 'Subscription request rejected.');
        }
    }
    redirect_to('admin/subscriptions.php');
}

$requests = $pdo->query("SELECT sr.*, u.name AS user_name, u.email, mp.name AS plan_name
                         FROM subscription_requests sr
                         INNER JOIN users u ON u.id = sr.user_id
                         INNER JOIN membership_plans mp ON mp.id = sr.plan_id
                         ORDER BY FIELD(sr.status,'pending','approved','rejected'), sr.id DESC")->fetchAll();
$pageTitle='Subscription Requests'; require_once __DIR__ . '/../includes/header.php'; ?>
<h1 class="fw-bold mb-4">Subscription Requests</h1>
<div class="card card-soft p-4">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Student</th><th>Plan</th><th>Amount</th><th>Method</th><th>Reference</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach($requests as $request): ?>
                <tr>
                    <td><strong><?= e($request['user_name']) ?></strong><br><small><?= e($request['email']) ?></small></td>
                    <td><?= e($request['plan_name']) ?></td>
                    <td>K<?= e(number_format($request['amount'], 2)) ?></td>
                    <td><?= e($request['payment_method']) ?></td>
                    <td><?= e($request['reference_code']) ?><br><small><?= e($request['notes']) ?></small></td>
                    <td><span class="badge text-bg-<?= $request['status']==='pending' ? 'warning' : ($request['status']==='approved' ? 'success':'danger') ?>"><?= e($request['status']) ?></span></td>
                    <td>
                        <?php if ($request['status'] === 'pending'): ?>
                            <form method="post" class="d-flex gap-2">
                                <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
                                <button class="btn btn-sm btn-success" name="action" value="approve">Approve</button>
                                <button class="btn btn-sm btn-outline-danger" name="action" value="reject">Reject</button>
                            </form>
                        <?php else: ?>
                            <small class="text-muted">Reviewed</small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
