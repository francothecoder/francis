<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['student']);
$user = current_user();
$requestId = (int) ($_GET['id'] ?? 0);
$request = help_request_by_id($requestId);
if (!$request || (int) $request['student_id'] !== (int) $user['id']) { set_flash('error', 'Help request not found.'); redirect_to('student/my_requests.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['accept_offer_id'])) {
        $offerId = (int) $_POST['accept_offer_id'];
        try {
            $pdo->beginTransaction();
            $offerStmt = $pdo->prepare('SELECT * FROM help_offers WHERE id = :id AND request_id = :request_id LIMIT 1 FOR UPDATE');
            $offerStmt->execute(['id'=>$offerId,'request_id'=>$requestId]);
            $offer = $offerStmt->fetch();
            if (!$offer) throw new RuntimeException('Offer not found.');
            $pdo->prepare("UPDATE help_offers SET status='accepted' WHERE id = :id")->execute(['id'=>$offerId]);
            $pdo->prepare("UPDATE help_offers SET status='rejected' WHERE request_id = :request_id AND id <> :id")->execute(['request_id'=>$requestId,'id'=>$offerId]);
            $pdo->prepare("UPDATE help_requests SET status='accepted', selected_tutor_id = :selected_tutor_id, accepted_offer_id = :accepted_offer_id WHERE id = :id")->execute(['selected_tutor_id'=>$offer['tutor_id'],'accepted_offer_id'=>$offerId,'id'=>$requestId]);
            create_notification((int)$offer['tutor_id'], 'Offer accepted', 'The student accepted your offer and can now proceed to payment.', 'tutor/request_view.php?id=' . $requestId);
            $pdo->commit();
            set_flash('success', 'Offer accepted. Complete payment to unlock the live study session.');
            redirect_to('student/pay_offer.php?request_id=' . $requestId);
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); set_flash('error', $e->getMessage()); redirect_to('student/request_view.php?id=' . $requestId); }
    }

    if (isset($_POST['complete_session'])) {
        $session = session_by_request($requestId);
        if ($session && (int)$session['student_id'] === (int)$user['id']) {
            try {
                $pdo->beginTransaction();
                $pdo->prepare("UPDATE study_sessions SET status='completed', ended_at = NOW() WHERE id = :id")->execute(['id'=>$session['id']]);
                $pdo->prepare("UPDATE help_requests SET status='completed' WHERE id = :id")->execute(['id'=>$requestId]);
                $pdo->prepare("UPDATE payment_transactions SET status='released' WHERE session_id = :session_id")->execute(['session_id'=>$session['id']]);
                $pdo->prepare("INSERT INTO tutor_wallet_transactions (tutor_id, session_id, amount, transaction_type, notes) VALUES (:tutor_id, :session_id, :amount, 'credit', :notes)")->execute(['tutor_id'=>$session['tutor_id'], 'session_id'=>$session['id'], 'amount'=>$session['tutor_earnings'], 'notes'=>'Released earnings from completed study session']);
                $pdo->prepare("UPDATE tutor_profiles SET total_sessions = total_sessions + 1 WHERE user_id = :user_id")->execute(['user_id'=>$session['tutor_id']]);
                create_notification((int)$session['tutor_id'], 'Session completed', 'The student marked the session as completed. Earnings are now available in your tutor wallet.', 'tutor/dashboard.php');
                grant_xp((int)$user['id'], 'complete_session', 10, $requestId); grant_xp((int)$session['tutor_id'], 'complete_session_tutor', 12, $requestId);
                $pdo->commit();
                set_flash('success', 'Session marked complete. Please rate your tutor next.');
            } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); set_flash('error', $e->getMessage()); }
        }
        redirect_to('student/request_view.php?id=' . $requestId);
    }

    if (isset($_POST['submit_rating'])) {
        $session = session_by_request($requestId); $rating = max(1, min(5, (int)($_POST['rating'] ?? 5))); $review = multi_line_text($_POST['review'] ?? '');
        if ($session && (int)$session['student_id'] === (int)$user['id'] && $session['status'] === 'completed') {
            $existing = count_value('SELECT COUNT(*) FROM tutor_ratings WHERE session_id = :session_id', ['session_id'=>$session['id']]);
            if ($existing === 0) {
                try {
                    $pdo->beginTransaction();
                    $pdo->prepare('INSERT INTO tutor_ratings (session_id, tutor_id, student_id, rating, review) VALUES (:session_id, :tutor_id, :student_id, :rating, :review)')->execute(['session_id'=>$session['id'],'tutor_id'=>$session['tutor_id'],'student_id'=>$user['id'],'rating'=>$rating,'review'=>$review]);
                    $summaryStmt = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews FROM tutor_ratings WHERE tutor_id = :tutor_id'); $summaryStmt->execute(['tutor_id'=>$session['tutor_id']]); $summary = $summaryStmt->fetch();
                    $pdo->prepare('UPDATE tutor_profiles SET rating_average = :rating_average, total_reviews = :total_reviews WHERE user_id = :user_id')->execute(['rating_average'=>round((float)$summary['avg_rating'],2),'total_reviews'=>(int)$summary['total_reviews'],'user_id'=>$session['tutor_id']]);
                    grant_xp((int)$user['id'], 'rate_tutor', 5, $requestId); create_notification((int)$session['tutor_id'], 'New tutor rating', 'A student has submitted your session review.', 'tutor/dashboard.php'); $pdo->commit();
                    set_flash('success', 'Thank you. Your tutor review was saved.');
                } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); set_flash('error', $e->getMessage()); }
            } else set_flash('error', 'This session has already been rated.');
        }
        redirect_to('student/request_view.php?id=' . $requestId);
    }

    if (isset($_POST['send_message'])) {
        $session = session_by_request($requestId); $message = multi_line_text($_POST['message'] ?? '');
        if ($session) {
            try { $attachment = upload_file($_FILES['attachment'] ?? [], 'session_files', ['pdf','doc','docx','png','jpg','jpeg','webp','zip'], ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','image/png','image/jpeg','image/webp','application/zip','application/x-zip-compressed'], 8388608); } catch (Throwable $e) { set_flash('error', $e->getMessage()); redirect_to('student/request_view.php?id=' . $requestId); }
            if ($message !== '' || $attachment) {
                $pdo->prepare('INSERT INTO session_messages (session_id, sender_id, message, attachment_path, attachment_name) VALUES (:session_id, :sender_id, :message, :attachment_path, :attachment_name)')->execute(['session_id'=>$session['id'],'sender_id'=>$user['id'],'message'=>$message !== '' ? $message : null,'attachment_path'=>$attachment,'attachment_name'=>$attachment ? basename((string)($_FILES['attachment']['name'] ?? 'attachment')) : null]);
                create_notification((int)$session['tutor_id'], 'New study message', 'A student sent a new update in your active study session.', 'tutor/session_view.php?request_id=' . $requestId);
            }
        }
        redirect_to('student/request_view.php?id=' . $requestId);
    }
}
$offersStmt = $pdo->prepare("SELECT ho.*, u.name AS tutor_name, tp.headline, tp.rating_average FROM help_offers ho INNER JOIN users u ON u.id = ho.tutor_id INNER JOIN tutor_profiles tp ON tp.user_id = ho.tutor_id WHERE ho.request_id = :request_id ORDER BY ho.id DESC"); $offersStmt->execute(['request_id'=>$requestId]); $offers = $offersStmt->fetchAll();
$session = session_by_request($requestId); $payment = payment_for_request($requestId); $messages=[]; $existingRating=0;
if ($session) { $msgStmt = $pdo->prepare("SELECT sm.*, u.name FROM session_messages sm INNER JOIN users u ON u.id = sm.sender_id WHERE sm.session_id = :session_id ORDER BY sm.id ASC"); $msgStmt->execute(['session_id'=>$session['id']]); $messages = $msgStmt->fetchAll(); $existingRating = count_value('SELECT COUNT(*) FROM tutor_ratings WHERE session_id = :session_id', ['session_id'=>$session['id']]); }
$pageTitle = 'Help request';
include __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card card-soft p-4 mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3"><div><h1 class="h3 mb-1"><?= e($request['title']) ?></h1><div class="text-muted mb-3"><?= e($request['subject']) ?> · <?= e(relative_time($request['created_at'])) ?></div></div><?= status_badge($request['status']) ?></div>
            <div class="mb-3"><?= nl2br(e($request['details'])) ?></div>
            <div class="d-flex flex-wrap gap-2 small text-muted mb-3"><span class="info-chip">Urgency: <?= e(ucfirst($request['urgency'])) ?></span><span class="info-chip">Suggested budget: <?= money($request['suggested_budget']) ?></span><span class="info-chip">Reward credits used: <?= (int)$request['reward_credits_to_use'] ?></span></div>
            <?php if ($request['attachment_path']): ?><a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= app_url($request['attachment_path']) ?>">Open attachment</a><?php endif; ?>
            <?php if ($payment): ?><a class="btn btn-sm btn-outline-primary" href="<?= app_url('student/payment_status.php?payment_id=' . (int)$payment['id']) ?>">Payment status</a><?php endif; ?>
        </div>

        <?php if ($session): ?>
            <div class="card card-soft p-4">
                <div class="d-flex justify-content-between align-items-center mb-3"><div class="section-title">Live study session</div><?= status_badge($session['status']) ?></div>
                <div class="small text-muted mb-3">Tutor: <?= e($session['tutor_name']) ?> · Session amount <?= money($session['final_amount']) ?></div>
                <div class="chat-box mb-3"><?php foreach ($messages as $message): ?><div class="chat-bubble <?= (int)$message['sender_id']===(int)$user['id'] ? 'chat-own' : 'chat-other' ?>"><div class="small fw-semibold mb-1"><?= e($message['name']) ?></div><?php if ($message['message']): ?><div><?= nl2br(e($message['message'])) ?></div><?php endif; ?><?php if ($message['attachment_path']): ?><a class="btn btn-sm btn-outline-dark mt-2" href="<?= app_url($message['attachment_path']) ?>" target="_blank"><?= e($message['attachment_name'] ?: 'Open attachment') ?></a><?php endif; ?></div><?php endforeach; ?><?php if (!$messages): ?><div class="text-muted small">No messages yet.</div><?php endif; ?></div>
                <?php if ($session['status'] !== 'completed'): ?>
                    <form method="post" enctype="multipart/form-data" class="mb-3"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="mb-2"><textarea class="form-control" name="message" rows="2" placeholder="Type your study message..."></textarea></div><div class="d-flex flex-wrap gap-2"><input class="form-control" style="max-width:280px" type="file" name="attachment" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.webp,.zip"><button class="btn btn-primary" name="send_message" value="1">Send update</button></div></form>
                    <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="btn btn-success" name="complete_session" value="1">Mark session completed</button></form>
                <?php elseif (!$existingRating): ?>
                    <hr><h2 class="h5">Rate your tutor</h2><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="row g-3"><div class="col-md-3"><select class="form-select" name="rating"><option value="5">5 - Excellent</option><option value="4">4 - Good</option><option value="3">3 - Fair</option><option value="2">2 - Weak</option><option value="1">1 - Poor</option></select></div><div class="col-md-9"><textarea class="form-control" name="review" rows="3" placeholder="Share how the tutor helped you."></textarea></div></div><button class="btn btn-primary mt-3" name="submit_rating" value="1">Save rating</button></form>
                <?php else: ?><div class="alert alert-success border-0">Tutor already rated for this session.</div><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-lg-5">
        <div class="card card-soft p-4">
            <div class="section-title mb-3">Tutor offers</div>
            <?php if ($offers): foreach ($offers as $offer): $pricing = calculate_final_help_price((float)$offer['offered_amount'], (int)$user['id'], (int)$request['reward_credits_to_use']); ?>
                <div class="border rounded-4 p-3 mb-3">
                    <div class="d-flex justify-content-between"><div><strong><?= e($offer['tutor_name']) ?></strong><div class="small text-muted"><?= e($offer['headline']) ?></div></div><?= status_badge($offer['status']) ?></div>
                    <div class="small text-muted mt-2"><?= nl2br(e($offer['message'])) ?></div>
                    <div class="small text-muted mt-2">Tutor rating <?= number_format((float)$offer['rating_average'], 1) ?>/5</div>
                    <div class="fw-semibold mt-2">Tutor fee: <?= money($offer['offered_amount']) ?></div>
                    <div class="small text-success">After discount and credits: <?= money($pricing['final_price']) ?></div>
                    <?php if (in_array($request['status'], ['open','quoted'], true)): ?><form method="post" class="mt-3"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="btn btn-primary btn-sm" name="accept_offer_id" value="<?= (int)$offer['id'] ?>">Accept and pay</button></form><?php elseif ((int)$request['accepted_offer_id'] === (int)$offer['id'] && !$session): ?><a class="btn btn-primary btn-sm mt-3" href="<?= app_url('student/pay_offer.php?request_id=' . $requestId) ?>">Continue payment</a><?php endif; ?>
                </div>
            <?php endforeach; else: ?><div class="text-muted">No tutor offers yet. Verified tutors have been notified.</div><?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
