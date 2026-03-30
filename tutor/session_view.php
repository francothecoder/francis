<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['tutor']);
$user = current_user();
$requestId = (int) ($_GET['request_id'] ?? 0);
$session = session_by_request($requestId);

if (!$session || (int) $session['tutor_id'] !== (int) $user['id']) {
    set_flash('error', 'Study session not found.');
    redirect_to('tutor/sessions.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['send_message'])) {
        $message = multi_line_text($_POST['message'] ?? '');
        if ($message !== '') {
            $pdo->prepare('INSERT INTO session_messages (session_id, sender_id, message) VALUES (:session_id, :sender_id, :message)')
                ->execute([
                    'session_id' => $session['id'],
                    'sender_id' => $user['id'],
                    'message' => $message,
                ]);
            create_notification((int) $session['student_id'], 'New tutor message', 'Your tutor sent a new study message.', 'student/request_view.php?id=' . $requestId);
        }
        redirect_to('tutor/session_view.php?request_id=' . $requestId);
    }
}

$msgStmt = $pdo->prepare("SELECT sm.*, u.name FROM session_messages sm INNER JOIN users u ON u.id = sm.sender_id WHERE sm.session_id = :session_id ORDER BY sm.id ASC");
$msgStmt->execute(['session_id' => $session['id']]);
$messages = $msgStmt->fetchAll();
$pageTitle = 'Tutor session';
include __DIR__ . '/../includes/header.php';
?>
<div class="card card-soft p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1"><?= e($session['tutor_name']) ?> with <?= e($session['student_name']) ?></h1>
            <div class="text-muted">Session amount <?= money($session['final_amount']) ?></div>
        </div>
        <?= status_badge($session['status']) ?>
    </div>
    <div class="chat-box mb-3">
        <?php foreach ($messages as $message): ?>
            <div class="chat-bubble <?= (int) $message['sender_id'] === (int) $user['id'] ? 'chat-own' : 'chat-other' ?>">
                <div class="small fw-semibold mb-1"><?= e($message['name']) ?></div>
                <div><?= nl2br(e($message['message'])) ?></div>
            </div>
        <?php endforeach; ?>
        <?php if (!$messages): ?>
            <div class="text-muted">No messages yet.</div>
        <?php endif; ?>
    </div>
    <?php if ($session['status'] !== 'completed'): ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="input-group">
                <textarea class="form-control" name="message" rows="2" placeholder="Send guidance, steps, or clarifications..." required></textarea>
                <button class="btn btn-primary" name="send_message" value="1">Send</button>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
