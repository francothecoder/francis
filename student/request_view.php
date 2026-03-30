<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['student']);
$user = current_user();
$requestId = (int) ($_GET['id'] ?? 0);
$request = help_request_by_id($requestId);

if (!$request || (int) $request['student_id'] !== (int) $user['id']) {
    set_flash('error', 'Help request not found.');
    redirect_to('student/my_requests.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['accept_offer_id'])) {
        $offerId = (int) $_POST['accept_offer_id'];
        $pdo->beginTransaction();
        try {
            $offerStmt = $pdo->prepare('SELECT * FROM help_offers WHERE id = :id AND request_id = :request_id LIMIT 1 FOR UPDATE');
            $offerStmt->execute(['id' => $offerId, 'request_id' => $requestId]);
            $offer = $offerStmt->fetch();
            if (!$offer) {
                throw new RuntimeException('Offer not found.');
            }

            $pricing = calculate_final_help_price((float) $offer['offered_amount'], (int) $user['id'], (int) $request['reward_credits_to_use']);
            $platformPercent = platform_commission_percent();
            $platformFee = round(($pricing['final_price'] * $platformPercent) / 100, 2);
            $tutorEarnings = round($pricing['final_price'] - $platformFee, 2);

            $pdo->prepare("UPDATE help_offers SET status = 'accepted' WHERE id = :id")->execute(['id' => $offerId]);
            $pdo->prepare("UPDATE help_offers SET status = 'rejected' WHERE request_id = :request_id AND id <> :id")->execute(['request_id' => $requestId, 'id' => $offerId]);
            $pdo->prepare("UPDATE help_requests SET status = 'accepted', selected_tutor_id = :selected_tutor_id, accepted_offer_id = :accepted_offer_id WHERE id = :id")->execute([
                'selected_tutor_id' => $offer['tutor_id'],
                'accepted_offer_id' => $offerId,
                'id' => $requestId,
            ]);

            $pdo->prepare('INSERT INTO study_sessions (request_id, student_id, tutor_id, agreed_amount, final_amount, platform_fee, tutor_earnings, status, started_at) VALUES (:request_id, :student_id, :tutor_id, :agreed_amount, :final_amount, :platform_fee, :tutor_earnings, :status, NOW())')->execute([
                'request_id' => $requestId,
                'student_id' => $user['id'],
                'tutor_id' => $offer['tutor_id'],
                'agreed_amount' => $offer['offered_amount'],
                'final_amount' => $pricing['final_price'],
                'platform_fee' => $platformFee,
                'tutor_earnings' => $tutorEarnings,
                'status' => 'in_progress',
            ]);

            $sessionId = (int) $pdo->lastInsertId();

            $pdo->prepare("INSERT INTO payment_transactions (request_id, session_id, student_id, tutor_id, amount, discount_amount, reward_credit_value, platform_fee, tutor_earnings, status)
                VALUES (:request_id, :session_id, :student_id, :tutor_id, :amount, :discount_amount, :reward_credit_value, :platform_fee, :tutor_earnings, 'held')")
                ->execute([
                    'request_id' => $requestId,
                    'session_id' => $sessionId,
                    'student_id' => $user['id'],
                    'tutor_id' => $offer['tutor_id'],
                    'amount' => $pricing['final_price'],
                    'discount_amount' => $pricing['discount_amount'],
                    'reward_credit_value' => $pricing['reward_credit_value'],
                    'platform_fee' => $platformFee,
                    'tutor_earnings' => $tutorEarnings,
                ]);

            create_notification((int) $offer['tutor_id'], 'Offer accepted', 'A student accepted your offer. Start the study session now.', 'tutor/session_view.php?request_id=' . $requestId);
            grant_xp((int) $user['id'], 'accept_help_offer', 5, $requestId);

            $pdo->commit();
            set_flash('success', 'Offer accepted. Your study session is now active.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('error', $e->getMessage());
        }
        redirect_to('student/request_view.php?id=' . $requestId);
    }

    if (isset($_POST['complete_session'])) {
        $session = session_by_request($requestId);
        if ($session && (int) $session['student_id'] === (int) $user['id']) {
            $pdo->beginTransaction();
            try {
                $pdo->prepare("UPDATE study_sessions SET status = 'completed', ended_at = NOW() WHERE id = :id")->execute(['id' => $session['id']]);
                $pdo->prepare("UPDATE help_requests SET status = 'completed' WHERE id = :id")->execute(['id' => $requestId]);
                $pdo->prepare("UPDATE payment_transactions SET status = 'released' WHERE session_id = :session_id")->execute(['session_id' => $session['id']]);
                $pdo->prepare("INSERT INTO tutor_wallet_transactions (tutor_id, session_id, amount, transaction_type, notes) VALUES (:tutor_id, :session_id, :amount, 'credit', :notes)")
                    ->execute([
                        'tutor_id' => $session['tutor_id'],
                        'session_id' => $session['id'],
                        'amount' => $session['tutor_earnings'],
                        'notes' => 'Released earnings from completed study session',
                    ]);
                $pdo->prepare("UPDATE tutor_profiles SET total_sessions = total_sessions + 1 WHERE user_id = :user_id")->execute(['user_id' => $session['tutor_id']]);
                create_notification((int) $session['tutor_id'], 'Session completed', 'The student marked the session as completed. Earnings are now available in your tutor wallet.', 'tutor/dashboard.php');
                grant_xp((int) $user['id'], 'complete_session', 10, $requestId);
                grant_xp((int) $session['tutor_id'], 'complete_session_tutor', 12, $requestId);
                $pdo->commit();
                set_flash('success', 'Session marked complete. Please rate your tutor next.');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                set_flash('error', $e->getMessage());
            }
        }
        redirect_to('student/request_view.php?id=' . $requestId);
    }

    if (isset($_POST['submit_rating'])) {
        $session = session_by_request($requestId);
        $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
        $review = multi_line_text($_POST['review'] ?? '');
        if ($session && (int) $session['student_id'] === (int) $user['id'] && $session['status'] === 'completed') {
            $existing = $pdo->prepare('SELECT COUNT(*) FROM tutor_ratings WHERE session_id = :session_id');
            $existing->execute(['session_id' => $session['id']]);
            if ((int) $existing->fetchColumn() === 0) {
                $pdo->beginTransaction();
                try {
                    $pdo->prepare('INSERT INTO tutor_ratings (session_id, tutor_id, student_id, rating, review) VALUES (:session_id, :tutor_id, :student_id, :rating, :review)')
                        ->execute([
                            'session_id' => $session['id'],
                            'tutor_id' => $session['tutor_id'],
                            'student_id' => $user['id'],
                            'rating' => $rating,
                            'review' => $review,
                        ]);

                    $summaryStmt = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews FROM tutor_ratings WHERE tutor_id = :tutor_id');
                    $summaryStmt->execute(['tutor_id' => $session['tutor_id']]);
                    $summary = $summaryStmt->fetch();

                    $pdo->prepare('UPDATE tutor_profiles SET rating_average = :rating_average, total_reviews = :total_reviews WHERE user_id = :user_id')
                        ->execute([
                            'rating_average' => round((float) $summary['avg_rating'], 2),
                            'total_reviews' => (int) $summary['total_reviews'],
                            'user_id' => $session['tutor_id'],
                        ]);
                    grant_xp((int) $user['id'], 'rate_tutor', 5, $requestId);
                    create_notification((int) $session['tutor_id'], 'New tutor rating', 'A student has submitted your session review.', 'tutor/dashboard.php');
                    $pdo->commit();
                    set_flash('success', 'Thank you. Your tutor review was saved.');
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    set_flash('error', $e->getMessage());
                }
            } else {
                set_flash('error', 'This session has already been rated.');
            }
        }
        redirect_to('student/request_view.php?id=' . $requestId);
    }

    if (isset($_POST['send_message'])) {
        $session = session_by_request($requestId);
        $message = multi_line_text($_POST['message'] ?? '');
        if ($session && $message !== '') {
            $pdo->prepare('INSERT INTO session_messages (session_id, sender_id, message) VALUES (:session_id, :sender_id, :message)')
                ->execute([
                    'session_id' => $session['id'],
                    'sender_id' => $user['id'],
                    'message' => $message,
                ]);
            create_notification((int) $session['tutor_id'], 'New study message', 'A student sent a new message in your active study session.', 'tutor/session_view.php?request_id=' . $requestId);
        }
        redirect_to('student/request_view.php?id=' . $requestId);
    }
}

$offersStmt = $pdo->prepare("
    SELECT ho.*, u.name AS tutor_name, tp.headline
    FROM help_offers ho
    INNER JOIN users u ON u.id = ho.tutor_id
    INNER JOIN tutor_profiles tp ON tp.user_id = ho.tutor_id
    WHERE ho.request_id = :request_id
    ORDER BY ho.id DESC
");
$offersStmt->execute(['request_id' => $requestId]);
$offers = $offersStmt->fetchAll();

$session = session_by_request($requestId);
$messages = [];
if ($session) {
    $msgStmt = $pdo->prepare("SELECT sm.*, u.name FROM session_messages sm INNER JOIN users u ON u.id = sm.sender_id WHERE sm.session_id = :session_id ORDER BY sm.id ASC");
    $msgStmt->execute(['session_id' => $session['id']]);
    $messages = $msgStmt->fetchAll();
}
$pageTitle = 'Help request';
include __DIR__ . '/../includes/header.php';
?>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card card-soft p-4 mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h1 class="h3 mb-1"><?= e($request['title']) ?></h1>
                    <div class="text-muted mb-3"><?= e($request['subject']) ?> · <?= e(relative_time($request['created_at'])) ?></div>
                </div>
                <?= status_badge($request['status']) ?>
            </div>
            <div class="mb-3"><?= nl2br(e($request['details'])) ?></div>
            <div class="small text-muted mb-2">Urgency: <?= e(ucfirst($request['urgency'])) ?></div>
            <div class="small text-muted mb-2">Suggested budget: <?= money($request['suggested_budget']) ?></div>
            <?php if ($request['attachment_path']): ?>
                <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= app_url($request['attachment_path']) ?>">View attachment</a>
            <?php endif; ?>
        </div>

        <?php if ($session): ?>
            <div class="card card-soft p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title">Live study session</div>
                    <?= status_badge($session['status']) ?>
                </div>
                <div class="small text-muted mb-3">Tutor: <?= e($session['tutor_name']) ?> · Session amount <?= money($session['final_amount']) ?></div>
                <div class="chat-box mb-3">
                    <?php foreach ($messages as $message): ?>
                        <div class="chat-bubble <?= (int) $message['sender_id'] === (int) $user['id'] ? 'chat-own' : 'chat-other' ?>">
                            <div class="small fw-semibold mb-1"><?= e($message['name']) ?></div>
                            <div><?= nl2br(e($message['message'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$messages): ?>
                        <div class="text-muted small">No messages yet.</div>
                    <?php endif; ?>
                </div>
                <?php if ($session['status'] !== 'completed'): ?>
                    <form method="post" class="mb-3">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <div class="input-group">
                            <textarea class="form-control" name="message" rows="2" placeholder="Type your study message..." required></textarea>
                            <button class="btn btn-primary" name="send_message" value="1">Send</button>
                        </div>
                    </form>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <button class="btn btn-success" name="complete_session" value="1">Mark session completed</button>
                    </form>
                <?php else: ?>
                    <hr>
                    <h2 class="h5">Rate your tutor</h2>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <select class="form-select" name="rating">
                                    <option value="5">5 - Excellent</option>
                                    <option value="4">4 - Good</option>
                                    <option value="3">3 - Fair</option>
                                    <option value="2">2 - Weak</option>
                                    <option value="1">1 - Poor</option>
                                </select>
                            </div>
                            <div class="col-md-9">
                                <textarea class="form-control" name="review" rows="3" placeholder="Share how the tutor helped you."></textarea>
                            </div>
                        </div>
                        <button class="btn btn-primary mt-3" name="submit_rating" value="1">Save rating</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-lg-5">
        <div class="card card-soft p-4">
            <div class="section-title mb-3">Tutor offers</div>
            <?php if ($offers): ?>
                <?php foreach ($offers as $offer): ?>
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong><?= e($offer['tutor_name']) ?></strong>
                                <div class="small text-muted"><?= e($offer['headline']) ?></div>
                            </div>
                            <?= status_badge($offer['status']) ?>
                        </div>
                        <div class="small text-muted mt-2"><?= nl2br(e($offer['message'])) ?></div>
                        <div class="fw-semibold mt-2">Offer: <?= money($offer['offered_amount']) ?></div>
                        <?php if ($request['status'] === 'open' || $request['status'] === 'quoted'): ?>
                            <form method="post" class="mt-3">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <button class="btn btn-primary btn-sm" name="accept_offer_id" value="<?= (int) $offer['id'] ?>">Accept offer</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-muted">No tutor offers yet. Verified tutors have been notified.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
