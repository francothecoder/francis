<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'activate_plan') {
        create_membership((int)$_POST['user_id'], (int)$_POST['plan_id'], (int)current_user()['id']);
        set_flash('success', 'Membership activated successfully.');
    }
    redirect_to('admin/users.php');
}

$users = $pdo->query("SELECT u.*,
    (SELECT COUNT(*) FROM community_topics ct WHERE ct.user_id = u.id) AS topics_count
    FROM users u WHERE role = 'student' ORDER BY id DESC")->fetchAll();
$plans = $pdo->query('SELECT * FROM membership_plans WHERE is_active = 1 ORDER BY price ASC')->fetchAll();
$pageTitle='Manage Users'; require_once __DIR__ . '/../includes/header.php'; ?>
<h1 class="fw-bold mb-4">Manage Users</h1>
<div class="table-responsive card card-soft p-3">
<table class="table">
<thead><tr><th>Name</th><th>Email</th><th>University</th><th>Current Plan</th><th>Community</th><th>Activate Plan</th></tr></thead>
<tbody>
<?php foreach($users as $u): $membership = fetch_membership_for_user((int)$u['id']); ?>
<tr>
    <td><?= e($u['name']) ?></td>
    <td><?= e($u['email']) ?></td>
    <td><?= e($u['university']) ?></td>
    <td><?= e($membership['plan_name'] ?? 'Free') ?></td>
    <td><?= (int)$u['topics_count'] ?> topics</td>
    <td>
        <form method="post" class="d-flex gap-2 flex-wrap">
            <input type="hidden" name="action" value="activate_plan">
            <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
            <select name="plan_id" class="form-select form-select-sm" style="max-width:150px"><?php foreach($plans as $plan): ?><option value="<?= e($plan['id']) ?>"><?= e($plan['name']) ?></option><?php endforeach; ?></select>
            <button class="btn btn-primary btn-sm">Activate</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
