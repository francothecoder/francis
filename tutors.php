<?php
require_once __DIR__ . '/includes/bootstrap.php';
$tutors = $pdo->query("
    SELECT tp.*, u.name, u.avatar_path
    FROM tutor_profiles tp
    INNER JOIN users u ON u.id = tp.user_id
    WHERE tp.is_verified = 1
    ORDER BY tp.rating_average DESC, tp.total_sessions DESC, tp.id DESC
")->fetchAll();
$pageTitle = 'Tutors';
include __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4">Verified tutors</h1>
<div class="row g-3">
    <?php foreach ($tutors as $tutor): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card card-soft p-4 h-100">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="<?= e(avatar_url($tutor['avatar_path'])) ?>" class="avatar-md" alt="">
                    <div>
                        <h2 class="h5 mb-1"><?= e($tutor['name']) ?></h2>
                        <div class="small text-muted"><?= e($tutor['headline']) ?></div>
                        <div class="small">Rating <?= number_format((float) $tutor['rating_average'], 1) ?>/5</div>
                    </div>
                </div>
                <p class="text-muted"><?= e($tutor['bio']) ?></p>
                <div class="small mb-2"><strong>Subjects:</strong> <?= e($tutor['subjects']) ?></div>
                <div class="small mb-3"><strong>Starts from:</strong> <?= money($tutor['starting_price']) ?> · <strong>Minimum offer:</strong> <?= money($tutor['min_offer_price']) ?></div>
                <div class="small text-muted">Completed sessions: <?= (int) $tutor['total_sessions'] ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
