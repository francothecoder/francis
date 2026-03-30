<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['tutor']);
$user = current_user();
$profile = tutor_profile((int) $user['id']);
$requests = fetch_open_help_requests_for_tutor((int) $user['id']);
$pageTitle = 'Help board';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Help board</h1>
        <div class="text-muted">Browse student requests and send academic support offers.</div>
    </div>
    <?php if (empty($profile['is_verified'])): ?>
        <span class="badge text-bg-warning">Awaiting admin verification</span>
    <?php endif; ?>
</div>

<?php if (empty($profile['is_verified'])): ?>
    <div class="alert alert-warning">Your tutor account is pending verification. You can view requests, but students will trust offers more once verification is approved by admin.</div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($requests as $request): ?>
        <div class="col-lg-6">
            <div class="card card-soft p-4 h-100">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <h2 class="h5"><?= e($request['title']) ?></h2>
                        <div class="text-muted small"><?= e($request['student_name']) ?> · <?= e($request['subject']) ?></div>
                    </div>
                    <?= status_badge($request['status']) ?>
                </div>
                <p class="text-muted mt-3"><?= e(substr($request['details'], 0, 160)) ?>...</p>
                <div class="small text-muted mb-3">Suggested budget: <?= money($request['suggested_budget']) ?> · <?= e(ucfirst($request['urgency'])) ?></div>
                <a class="btn btn-primary btn-sm" href="<?= app_url('tutor/request_view.php?id=' . (int) $request['id']) ?>">Open request</a>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$requests): ?>
        <div class="col-12"><div class="card card-soft p-4 text-muted">No open help requests match your board right now.</div></div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
