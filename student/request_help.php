<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['student']);
$user = current_user();
$reward = reward_summary((int) $user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $subject = clean_text($_POST['subject'] ?? '');
    $title = clean_text($_POST['title'] ?? '');
    $details = multi_line_text($_POST['details'] ?? '');
    $urgency = $_POST['urgency'] ?? 'normal';
    $budget = (float) ($_POST['suggested_budget'] ?? 0);
    $rewardCredits = min((int) ($_POST['reward_credits_to_use'] ?? 0), (int) $reward['reward_credits']);
    flash_old_input($_POST);

    if ($subject === '' || $title === '' || $details === '' || !in_array($urgency, ['normal', 'urgent'], true)) {
        set_flash('error', 'Complete all required request details.');
        redirect_to('student/request_help.php');
    }

    try {
        $attachment = upload_file(
            $_FILES['attachment'] ?? [],
            'help_files',
            ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'webp'],
            ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/png', 'image/jpeg', 'image/webp'],
            6291456
        );
    } catch (Throwable $e) {
        set_flash('error', $e->getMessage());
        redirect_to('student/request_help.php');
    }

    $stmt = $pdo->prepare('INSERT INTO help_requests (student_id, subject, title, details, urgency, suggested_budget, reward_credits_to_use, attachment_path, status) VALUES (:student_id, :subject, :title, :details, :urgency, :suggested_budget, :reward_credits_to_use, :attachment_path, :status)');
    $stmt->execute([
        'student_id' => $user['id'],
        'subject' => $subject,
        'title' => $title,
        'details' => $details,
        'urgency' => $urgency,
        'suggested_budget' => $budget,
        'reward_credits_to_use' => $rewardCredits,
        'attachment_path' => $attachment,
        'status' => 'open',
    ]);
    $requestId = (int) $pdo->lastInsertId();

    if ($rewardCredits > 0) {
        $pdo->prepare('UPDATE user_rewards SET reward_credits = GREATEST(reward_credits - :credits, 0) WHERE user_id = :user_id')->execute([
            'credits' => $rewardCredits,
            'user_id' => $user['id'],
        ]);
    }

    grant_xp((int) $user['id'], 'create_help_request', 5, $requestId);

    $tutorIds = $pdo->query("SELECT user_id FROM tutor_profiles WHERE is_verified = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tutorIds as $tutorId) {
        create_notification((int) $tutorId, 'New help request', 'A new student help request is open for offers.', 'tutor/help_board.php');
    }

    clear_old_input();
    set_flash('success', 'Your academic help request is now live for tutors.');
    redirect_to('student/request_view.php?id=' . $requestId);
}

$pageTitle = 'Request academic help';
include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card card-soft p-4">
            <h1 class="h3 mb-3">Request academic help</h1>
            <p class="text-muted">Describe what you need clearly so tutors can support you with the right guidance and a fair offer.</p>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Subject</label>
                        <input class="form-control" type="text" name="subject" value="<?= e(old('subject')) ?>" placeholder="ICT, Mathematics, Accounting..." required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Urgency</label>
                        <select class="form-select" name="urgency">
                            <option value="normal" <?= old('urgency','normal') === 'normal' ? 'selected' : '' ?>>Normal</option>
                            <option value="urgent" <?= old('urgency') === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Request title</label>
                        <input class="form-control" type="text" name="title" value="<?= e(old('title')) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Explain what help you need</label>
                        <textarea class="form-control" name="details" rows="6" required><?= e(old('details')) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Suggested budget (optional)</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="suggested_budget" value="<?= e(old('suggested_budget','0')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reward credits to use</label>
                        <input class="form-control" type="number" min="0" max="<?= (int) $reward['reward_credits'] ?>" name="reward_credits_to_use" value="<?= e(old('reward_credits_to_use','0')) ?>">
                        <div class="form-text">Available credits: <?= (int) $reward['reward_credits'] ?></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Attachment</label>
                        <input class="form-control" type="file" name="attachment" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.webp">
                    </div>
                </div>
                <button class="btn btn-primary mt-4">Publish help request</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
