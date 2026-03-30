<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['tutor']);
$user = current_user();
$requestId = (int) ($_GET['request_id'] ?? 0);
$session = session_by_request($requestId);
if (!$session || (int)$session['tutor_id'] !== (int)$user['id']) { set_flash('error', 'Study session not found.'); redirect_to('tutor/sessions.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['send_message'])) {
        $message = multi_line_text($_POST['message'] ?? '');
        try { $attachment = upload_file($_FILES['attachment'] ?? [], 'session_files', ['pdf','doc','docx','png','jpg','jpeg','webp','zip'], ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','image/png','image/jpeg','image/webp','application/zip','application/x-zip-compressed'], 8388608); } catch (Throwable $e) { set_flash('error', $e->getMessage()); redirect_to('tutor/session_view.php?request_id=' . $requestId); }
        if ($message !== '' || $attachment) {
            $pdo->prepare('INSERT INTO session_messages (session_id, sender_id, message, attachment_path, attachment_name) VALUES (:session_id, :sender_id, :message, :attachment_path, :attachment_name)')->execute(['session_id'=>$session['id'],'sender_id'=>$user['id'],'message'=>$message !== '' ? $message : null,'attachment_path'=>$attachment,'attachment_name'=>$attachment ? basename((string)($_FILES['attachment']['name'] ?? 'attachment')) : null]);
            create_notification((int)$session['student_id'], 'New tutor message', 'Your tutor sent a new study update.', 'student/request_view.php?id=' . $requestId);
        }
        redirect_to('tutor/session_view.php?request_id=' . $requestId);
    }
}
$msgStmt = $pdo->prepare("SELECT sm.*, u.name FROM session_messages sm INNER JOIN users u ON u.id = sm.sender_id WHERE sm.session_id = :session_id ORDER BY sm.id ASC"); $msgStmt->execute(['session_id'=>$session['id']]); $messages = $msgStmt->fetchAll();
$pageTitle = 'Tutor session';
include __DIR__ . '/../includes/header.php';
?>
<div class="card card-soft p-4">
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-1"><?= e($session['tutor_name']) ?> with <?= e($session['student_name']) ?></h1><div class="text-muted">Session amount <?= money($session['final_amount']) ?></div></div><?= status_badge($session['status']) ?></div>
    <div class="chat-box mb-3"><?php foreach ($messages as $message): ?><div class="chat-bubble <?= (int)$message['sender_id']===(int)$user['id'] ? 'chat-own' : 'chat-other' ?>"><div class="small fw-semibold mb-1"><?= e($message['name']) ?></div><?php if ($message['message']): ?><div><?= nl2br(e($message['message'])) ?></div><?php endif; ?><?php if ($message['attachment_path']): ?><a class="btn btn-sm btn-outline-dark mt-2" href="<?= app_url($message['attachment_path']) ?>" target="_blank"><?= e($message['attachment_name'] ?: 'Open attachment') ?></a><?php endif; ?></div><?php endforeach; ?><?php if (!$messages): ?><div class="text-muted">No messages yet.</div><?php endif; ?></div>
    <?php if ($session['status'] !== 'completed'): ?><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="mb-2"><textarea class="form-control" name="message" rows="2" placeholder="Send guidance, steps, clarifications, or deliverables..."></textarea></div><div class="d-flex flex-wrap gap-2"><input class="form-control" style="max-width:280px" type="file" name="attachment" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.webp,.zip"><button class="btn btn-primary" name="send_message" value="1">Send</button></div></form><?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
