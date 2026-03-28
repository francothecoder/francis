<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'new_ticket') {
        $stmt = $pdo->prepare('INSERT INTO support_requests (user_id, subject, message) VALUES (:user_id, :subject, :message)');
        $stmt->execute([
            'user_id' => $user['id'],
            'subject' => trim($_POST['subject'] ?? ''),
            'message' => trim($_POST['message'] ?? ''),
        ]);
        set_flash('success', 'Support request submitted.');
    } elseif (($_POST['action'] ?? '') === 'reply_ticket') {
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM support_requests WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute(['id' => $ticketId, 'user_id' => $user['id']]);
        if ($stmt->fetch()) {
            $pdo->prepare('INSERT INTO support_replies (support_request_id, user_id, message) VALUES (:ticket_id, :user_id, :message)')
                ->execute(['ticket_id' => $ticketId, 'user_id' => $user['id'], 'message' => trim($_POST['message'] ?? '')]);
            $pdo->prepare("UPDATE support_requests SET status = 'open', updated_at = NOW() WHERE id = :id")->execute(['id' => $ticketId]);
            set_flash('success', 'Reply added.');
        }
    }
    redirect_to('student/support.php');
}

$stmt = $pdo->prepare('SELECT * FROM support_requests WHERE user_id = :user_id ORDER BY id DESC');
$stmt->execute(['user_id' => $user['id']]);
$tickets = $stmt->fetchAll();
$pageTitle='Support'; require_once __DIR__ . '/../includes/header.php'; ?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-soft p-4">
            <h1 class="fw-bold mb-3">Request Support</h1>
            <form method="post">
                <input type="hidden" name="action" value="new_ticket">
                <div class="mb-3"><label class="form-label">Subject</label><input class="form-control" name="subject" required></div>
                <div class="mb-3"><label class="form-label">Message</label><textarea class="form-control" rows="5" name="message" required></textarea></div>
                <button class="btn btn-primary w-100">Submit Request</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-soft p-4">
            <h4>Your Requests</h4>
            <?php foreach($tickets as $ticket): ?>
                <div class="border rounded-4 p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <h6 class="mb-1"><?= e($ticket['subject']) ?></h6>
                            <div class="small text-muted"><?= e($ticket['created_at']) ?></div>
                        </div>
                        <span class="badge text-bg-secondary"><?= e($ticket['status']) ?></span>
                    </div>
                    <p class="mt-3 mb-3"><?= nl2br(e($ticket['message'])) ?></p>
                    <?php
                    $replies = $pdo->prepare('SELECT sr.*, u.name, u.role FROM support_replies sr INNER JOIN users u ON u.id = sr.user_id WHERE sr.support_request_id = :id ORDER BY sr.id ASC');
                    $replies->execute(['id' => $ticket['id']]);
                    foreach ($replies->fetchAll() as $reply): ?>
                        <div class="bg-light rounded-4 p-3 mb-2">
                            <div class="small text-muted mb-1"><?= e($reply['name']) ?> · <?= e($reply['created_at']) ?></div>
                            <div><?= nl2br(e($reply['message'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <form method="post" class="mt-3">
                        <input type="hidden" name="action" value="reply_ticket">
                        <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
                        <textarea class="form-control mb-2" name="message" rows="3" placeholder="Add a reply..." required></textarea>
                        <button class="btn btn-sm btn-outline-primary">Reply</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
