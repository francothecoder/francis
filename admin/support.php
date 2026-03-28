<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'reply') {
        $pdo->prepare('INSERT INTO support_replies (support_request_id, user_id, message) VALUES (:ticket_id, :user_id, :message)')
            ->execute(['ticket_id' => $ticketId, 'user_id' => current_user()['id'], 'message' => trim($_POST['message'] ?? '')]);
        $pdo->prepare("UPDATE support_requests SET status = 'in_progress', updated_at = NOW() WHERE id = :id")->execute(['id' => $ticketId]);
        set_flash('success', 'Reply posted.');
    } elseif ($action === 'status') {
        $pdo->prepare("UPDATE support_requests SET status = :status, updated_at = NOW() WHERE id = :id")
            ->execute(['status' => $_POST['status'] ?? 'open', 'id' => $ticketId]);
        set_flash('success', 'Status updated.');
    }
    redirect_to('admin/support.php');
}

$items = $pdo->query('SELECT s.*, u.name FROM support_requests s JOIN users u ON u.id = s.user_id ORDER BY s.id DESC')->fetchAll();
$pageTitle='Support Requests'; require_once __DIR__ . '/../includes/header.php'; ?>
<div class="card card-soft p-4">
    <h1 class="fw-bold mb-3">Support Requests</h1>
    <?php foreach($items as $item): ?>
        <div class="border rounded-4 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <h5 class="mb-1"><?= e($item['subject']) ?></h5>
                    <div class="small text-muted"><?= e($item['name']) ?> · <?= e($item['created_at']) ?></div>
                </div>
                <form method="post" class="d-flex gap-2">
                    <input type="hidden" name="action" value="status">
                    <input type="hidden" name="ticket_id" value="<?= (int)$item['id'] ?>">
                    <select name="status" class="form-select form-select-sm">
                        <?php foreach (['open','in_progress','closed'] as $status): ?>
                            <option value="<?= $status ?>" <?= $item['status'] === $status ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ', $status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-outline-primary">Save</button>
                </form>
            </div>
            <p class="mt-3"><?= nl2br(e($item['message'])) ?></p>
            <?php
            $replies = $pdo->prepare('SELECT sr.*, u.name, u.role FROM support_replies sr INNER JOIN users u ON u.id = sr.user_id WHERE sr.support_request_id = :id ORDER BY sr.id ASC');
            $replies->execute(['id' => $item['id']]);
            foreach ($replies->fetchAll() as $reply): ?>
                <div class="bg-light rounded-4 p-3 mb-2">
                    <div class="small text-muted mb-1"><?= e($reply['name']) ?> · <?= e($reply['created_at']) ?></div>
                    <div><?= nl2br(e($reply['message'])) ?></div>
                </div>
            <?php endforeach; ?>
            <form method="post" class="mt-3">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="ticket_id" value="<?= (int)$item['id'] ?>">
                <textarea class="form-control mb-2" name="message" rows="3" placeholder="Reply to this request..." required></textarea>
                <button class="btn btn-sm btn-primary">Post Reply</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
