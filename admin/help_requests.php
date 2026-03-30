<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);
$requests = $pdo->query("
    SELECT hr.*, u.name AS student_name, tu.name AS tutor_name
    FROM help_requests hr
    INNER JOIN users u ON u.id = hr.student_id
    LEFT JOIN users tu ON tu.id = hr.selected_tutor_id
    ORDER BY hr.id DESC
")->fetchAll();
$pageTitle = 'Help requests';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Help requests</h1>
<div class="card card-soft p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Student</th><th>Title</th><th>Urgency</th><th>Status</th><th>Tutor</th></tr></thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?= e($request['student_name']) ?></td>
                        <td><?= e($request['title']) ?><div class="small text-muted"><?= e($request['subject']) ?></div></td>
                        <td><?= e(ucfirst($request['urgency'])) ?></td>
                        <td><?= status_badge($request['status']) ?></td>
                        <td><?= e($request['tutor_name'] ?: 'Not selected') ?></td>
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
