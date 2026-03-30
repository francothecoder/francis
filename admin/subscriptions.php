<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);
$subs = $pdo->query("
    SELECT us.*, u.name AS student_name, sp.name AS plan_name
    FROM user_subscriptions us
    INNER JOIN users u ON u.id = us.user_id
    INNER JOIN subscription_plans sp ON sp.id = us.plan_id
    ORDER BY us.id DESC
")->fetchAll();
$pageTitle = 'Subscriptions';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Subscriptions</h1>
<div class="card card-soft p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Student</th><th>Plan</th><th>Starts</th><th>Ends</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($subs as $sub): ?>
                    <tr>
                        <td><?= e($sub['student_name']) ?></td>
                        <td><?= e($sub['plan_name']) ?></td>
                        <td><?= e(date('d M Y', strtotime($sub['starts_at']))) ?></td>
                        <td><?= e(date('d M Y', strtotime($sub['ends_at']))) ?></td>
                        <td><?= status_badge($sub['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$subs): ?>
                    <tr><td colspan="5" class="text-muted">No subscriptions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
