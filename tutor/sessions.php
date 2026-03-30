<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['tutor']);
$user = current_user();
$stmt = $pdo->prepare("SELECT ss.*, hr.title, hr.subject, u.name AS student_name
    FROM study_sessions ss
    INNER JOIN help_requests hr ON hr.id = ss.request_id
    INNER JOIN users u ON u.id = ss.student_id
    WHERE ss.tutor_id = :tutor_id
    ORDER BY ss.id DESC");
$stmt->execute(['tutor_id' => $user['id']]);
$sessions = $stmt->fetchAll();
$pageTitle = 'Tutor sessions';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Study sessions</h1>
<div class="card card-soft p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Student</th><th>Request</th><th>Amount</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($sessions as $session): ?>
                <tr>
                    <td><?= e($session['student_name']) ?></td>
                    <td><?= e($session['title']) ?></td>
                    <td><?= money($session['final_amount']) ?></td>
                    <td><?= status_badge($session['status']) ?></td>
                    <td><a class="btn btn-sm btn-outline-primary" href="<?= app_url('tutor/session_view.php?request_id=' . (int) $session['request_id']) ?>">Open</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$sessions): ?>
                <tr><td colspan="5" class="text-muted">No sessions yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
