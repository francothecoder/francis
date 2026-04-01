<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['tutor']);
$user = current_user();
$profile = tutor_profile((int) $user['id']);
$summary = payout_summary((int) $user['id']);
$walletBalance = $summary['available'];
$sessionCount = count_value("SELECT COUNT(*) FROM study_sessions WHERE tutor_id = :tutor_id", ['tutor_id' => $user['id']]);
$activeSessions = $pdo->prepare("SELECT ss.*, hr.title, hr.subject, u.name AS student_name
    FROM study_sessions ss
    INNER JOIN help_requests hr ON hr.id = ss.request_id
    INNER JOIN users u ON u.id = ss.student_id
    WHERE ss.tutor_id = :tutor_id
    ORDER BY ss.id DESC
    LIMIT 5");
$activeSessions->execute(['tutor_id' => $user['id']]);
$sessions = $activeSessions->fetchAll();
$pageTitle = 'Tutor dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Tutor dashboard</h1>
        <div class="text-muted">Manage your offers, study sessions, earnings, and tutoring profile.</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="<?= app_url('tutor/request_payout.php') ?>">Withdraw earnings</a>
        <a class="btn btn-outline-secondary" href="<?= app_url('profile.php') ?>">Edit full profile</a>
        <a class="btn btn-primary" href="<?= app_url('tutor/help_board.php') ?>">Open help board</a>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="metric-card bg-white p-4"><div class="text-muted">Wallet balance</div><div class="metric-value"><?= money($walletBalance) ?></div></div></div>
    <div class="col-md-3"><div class="metric-card bg-white p-4"><div class="text-muted">Awaiting payout</div><div class="metric-value"><?= money($summary['requested'] + $summary['approved']) ?></div></div></div>
    <div class="col-md-3"><div class="metric-card bg-white p-4"><div class="text-muted">Total withdrawn</div><div class="metric-value"><?= money($summary['paid']) ?></div></div></div>
    <div class="col-md-3"><div class="metric-card bg-white p-4"><div class="text-muted">Status</div><div class="metric-value"><?= !empty($profile['is_verified']) ? 'Verified' : 'Pending' ?></div></div></div>
</div>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card card-soft p-4 h-100">
            <div class="section-title mb-3">Tutor profile</div>
            <form method="post" action="<?= app_url('tutor/profile_update.php') ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label">Headline</label>
                    <input class="form-control" type="text" name="headline" value="<?= e($profile['headline'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Subjects</label>
                    <input class="form-control" type="text" name="subjects" value="<?= e($profile['subjects'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Starting price</label>
                    <input class="form-control" type="number" step="0.01" min="1" name="starting_price" value="<?= e((string) ($profile['starting_price'] ?? '25')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Minimum offer price</label>
                    <input class="form-control" type="number" step="0.01" min="1" name="min_offer_price" value="<?= e((string) ($profile['min_offer_price'] ?? '15')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Qualification details</label>
                    <textarea class="form-control" name="qualification_details" rows="3" required><?= e($profile['qualification_details'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Bio</label>
                    <textarea class="form-control" name="bio" rows="5" required><?= e($profile['bio'] ?? '') ?></textarea>
                </div>
                <button class="btn btn-primary">Save profile</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-soft p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title">Recent study sessions</div>
                <a class="small" href="<?= app_url('tutor/sessions.php') ?>">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Student</th><th>Request</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td><?= e($session['student_name']) ?></td>
                                <td><?= e($session['title']) ?></td>
                                <td><?= status_badge($session['status']) ?></td>
                                <td><a class="btn btn-sm btn-outline-primary" href="<?= app_url('tutor/session_view.php?request_id=' . (int) $session['request_id']) ?>">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$sessions): ?>
                            <tr><td colspan="4" class="text-muted">No sessions yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
