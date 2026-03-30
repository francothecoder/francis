<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['tutor']);
$user = current_user();
$requestId = (int) ($_GET['id'] ?? 0);
$request = help_request_by_id($requestId);

if (!$request) {
    set_flash('error', 'Request not found.');
    redirect_to('tutor/help_board.php');
}

$profile = tutor_profile((int) $user['id']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $offeredAmount = (float) ($_POST['offered_amount'] ?? 0);
    $message = multi_line_text($_POST['message'] ?? '');
    $counter = (float) ($_POST['counter_amount'] ?? 0);

    if (isset($_POST['submit_offer'])) {
        if ($offeredAmount < (float) ($profile['min_offer_price'] ?? 1) || $message === '') {
            set_flash('error', 'Enter a valid offer amount and support message.');
            redirect_to('tutor/request_view.php?id=' . $requestId);
        }

        $stmt = $pdo->prepare('INSERT INTO help_offers (request_id, tutor_id, offered_amount, message, status) VALUES (:request_id, :tutor_id, :offered_amount, :message, :status)');
        $stmt->execute([
            'request_id' => $requestId,
            'tutor_id' => $user['id'],
            'offered_amount' => $offeredAmount,
            'message' => $message,
            'status' => 'submitted',
        ]);
        $pdo->prepare("UPDATE help_requests SET status = 'quoted' WHERE id = :id AND status = 'open'")->execute(['id' => $requestId]);
        create_notification((int) $request['student_id'], 'New tutor offer', 'A tutor submitted an offer on your academic help request.', 'student/request_view.php?id=' . $requestId);
        set_flash('success', 'Your tutor offer has been submitted.');
        redirect_to('tutor/request_view.php?id=' . $requestId);
    }
}

$offersStmt = $pdo->prepare('SELECT * FROM help_offers WHERE request_id = :request_id AND tutor_id = :tutor_id ORDER BY id DESC');
$offersStmt->execute(['request_id' => $requestId, 'tutor_id' => $user['id']]);
$myOffers = $offersStmt->fetchAll();
$pageTitle = 'Request details';
include __DIR__ . '/../includes/header.php';
?>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card card-soft p-4">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1 class="h3 mb-1"><?= e($request['title']) ?></h1>
                    <div class="text-muted"><?= e($request['student_name']) ?> · <?= e($request['subject']) ?></div>
                </div>
                <?= status_badge($request['status']) ?>
            </div>
            <div class="mt-3"><?= nl2br(e($request['details'])) ?></div>
            <div class="small text-muted mt-3">Suggested budget: <?= money($request['suggested_budget']) ?> · Reward credits: <?= (int) $request['reward_credits_to_use'] ?></div>
            <?php if ($request['attachment_path']): ?>
                <a class="btn btn-outline-secondary btn-sm mt-3" target="_blank" href="<?= app_url($request['attachment_path']) ?>">View attachment</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card card-soft p-4">
            <div class="section-title mb-3">Send your offer</div>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label">Offer amount</label>
                    <input class="form-control" type="number" step="0.01" min="<?= e((string) ($profile['min_offer_price'] ?? 1)) ?>" name="offered_amount" value="<?= e((string) ($profile['starting_price'] ?? 25)) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Academic support note</label>
                    <textarea class="form-control" name="message" rows="5" required>Here is how I would guide the student through this request, what deliverables I will provide, and the likely turnaround.</textarea>
                </div>
                <button class="btn btn-primary" name="submit_offer" value="1">Submit offer</button>
            </form>
        </div>

        <div class="card card-soft p-4 mt-3">
            <div class="section-title mb-3">Your previous offers</div>
            <?php foreach ($myOffers as $offer): ?>
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between">
                        <strong><?= money($offer['offered_amount']) ?></strong>
                        <?= status_badge($offer['status']) ?>
                    </div>
                    <div class="small text-muted mt-2"><?= nl2br(e($offer['message'])) ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$myOffers): ?>
                <div class="text-muted">No offers submitted yet on this request.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
