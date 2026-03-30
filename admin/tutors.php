<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $userId = (int) ($_POST['user_id'] ?? 0);
    if (isset($_POST['verify'])) {
        $pdo->prepare('UPDATE tutor_profiles SET is_verified = 1 WHERE user_id = :user_id')->execute(['user_id' => $userId]);
        create_notification($userId, 'Tutor verified', 'Your tutor profile was verified by admin and is now visible to students.', 'tutor/dashboard.php');
        set_flash('success', 'Tutor verified.');
    }
    redirect_to('admin/tutors.php');
}

$tutors = $pdo->query("
    SELECT u.id, u.name, u.email, tp.headline, tp.subjects, tp.is_verified, tp.rating_average, tp.total_sessions
    FROM users u
    INNER JOIN tutor_profiles tp ON tp.user_id = u.id
    WHERE u.role = 'tutor'
    ORDER BY tp.is_verified ASC, u.id DESC
")->fetchAll();

$pageTitle = 'Manage tutors';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Tutors</h1>
<div class="card card-soft p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Name</th><th>Subjects</th><th>Rating</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($tutors as $tutor): ?>
                    <tr>
                        <td><?= e($tutor['name']) ?><div class="small text-muted"><?= e($tutor['email']) ?></div></td>
                        <td><?= e($tutor['subjects']) ?></td>
                        <td><?= number_format((float) $tutor['rating_average'], 1) ?></td>
                        <td><?= $tutor['is_verified'] ? status_badge('verified') : status_badge('pending') ?></td>
                        <td>
                            <?php if (!(int) $tutor['is_verified']): ?>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="user_id" value="<?= (int) $tutor['id'] ?>">
                                    <button class="btn btn-sm btn-primary" name="verify" value="1">Verify</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$tutors): ?>
                    <tr><td colspan="5" class="text-muted">No tutors found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
