<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['student']);
$user = current_user();
$stmt = $pdo->prepare('SELECT * FROM help_requests WHERE student_id = :student_id ORDER BY id DESC');
$stmt->execute(['student_id' => $user['id']]);
$requests = $stmt->fetchAll();
$pageTitle = 'My help requests';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">My help requests</h1>
    <a class="btn btn-primary" href="<?= app_url('student/request_help.php') ?>">New request</a>
</div>
<div class="card card-soft p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Title</th><th>Subject</th><th>Status</th><th>Created</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?= e($request['title']) ?></td>
                        <td><?= e($request['subject']) ?></td>
                        <td><?= status_badge($request['status']) ?></td>
                        <td><?= e(relative_time($request['created_at'])) ?></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="<?= app_url('student/request_view.php?id=' . (int) $request['id']) ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$requests): ?>
                    <tr><td colspan="5" class="text-muted">No requests yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
