<?php
require_once __DIR__ . '/includes/bootstrap.php';

$tutors = $pdo->query("
    SELECT tp.*, u.name, u.avatar_path
    FROM tutor_profiles tp
    INNER JOIN users u ON u.id = tp.user_id
    WHERE tp.is_verified = 1
    ORDER BY tp.rating_average DESC, tp.total_sessions DESC
    LIMIT 4
")->fetchAll();

$resources = $pdo->query("SELECT * FROM resources ORDER BY id DESC LIMIT 3")->fetchAll();
$pageTitle = APP_NAME . ' - Academic support that feels real';
include __DIR__ . '/includes/header.php';
?>
<section class="hero mb-4">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <span class="badge text-bg-warning text-dark mb-3">Real tutors • Academic plans • Guided support</span>
            <h1 class="display-5 fw-bold">Academic support that feels real, human, and premium.</h1>
            <p class="lead mb-4">Help students understand difficult work, connect with verified tutors, negotiate support budgets fairly, and reward progress with XP, levels, and study credits.</p>
            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-warning btn-lg" href="<?= app_url('register.php') ?>">Create student account</a>
                <a class="btn btn-outline-light btn-lg" href="<?= app_url('pricing.php') ?>">View academic plans</a>
            </div>
        </div>
        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card card-soft p-3 text-dark">
                <div class="section-title mb-3">Live academic snapshot</div>
                <div class="d-flex justify-content-between mb-2"><span>Tutors available</span><strong><?= count_value("SELECT COUNT(*) FROM tutor_profiles WHERE is_verified = 1") ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Help requests completed</span><strong><?= count_value("SELECT COUNT(*) FROM help_requests WHERE status = 'completed'") ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Academic resources</span><strong><?= count_value("SELECT COUNT(*) FROM resources") ?></strong></div>
                <div class="d-flex justify-content-between"><span>Active subscriptions</span><strong><?= count_value("SELECT COUNT(*) FROM user_subscriptions WHERE status = 'active' AND ends_at >= NOW()") ?></strong></div>
            </div>
        </div>
    </div>
</section>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="metric-card bg-white p-4 h-100">
            <div class="section-title">Instant study assistance</div>
            <p class="text-muted mb-0">Students can request urgent help, attach files, suggest a budget, and receive offers from tutors.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metric-card bg-white p-4 h-100">
            <div class="section-title">Tutors earn with trust</div>
            <p class="text-muted mb-0">Tutors set prices, counter offers, build ratings, and get paid after supported sessions are completed.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metric-card bg-white p-4 h-100">
            <div class="section-title">Learning progress that matters</div>
            <p class="text-muted mb-0">XP, streaks, and reward credits push students to stay engaged while keeping the experience academic first.</p>
        </div>
    </div>
</div>

<section class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0">Featured tutors</h2>
        <a href="<?= app_url('tutors.php') ?>" class="small">View all</a>
    </div>
    <div class="row g-3">
        <?php foreach ($tutors as $tutor): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card card-soft p-3 h-100">
                    <img src="<?= e(avatar_url($tutor['avatar_path'])) ?>" class="avatar-md mb-3" alt="">
                    <h3 class="h6 mb-1"><?= e($tutor['name']) ?></h3>
                    <div class="small text-muted mb-2"><?= e($tutor['headline']) ?></div>
                    <div class="small mb-2"><?= status_badge($tutor['is_verified'] ? 'verified' : 'pending') ?></div>
                    <div class="small text-muted">Starting from <?= money($tutor['starting_price']) ?></div>
                    <div class="small text-muted">Rating <?= number_format((float) $tutor['rating_average'], 1) ?>/5</div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0">Fresh academic resources</h2>
        <a href="<?= app_url('resources.php') ?>" class="small">Browse resources</a>
    </div>
    <div class="row g-3">
        <?php foreach ($resources as $resource): ?>
            <div class="col-md-4">
                <div class="card card-soft p-4 h-100">
                    <span class="tag-pill mb-3 d-inline-block"><?= e($resource['resource_type']) ?></span>
                    <h3 class="h5"><?= e($resource['title']) ?></h3>
                    <p class="text-muted mb-0"><?= e(substr(strip_tags((string) $resource['content']), 0, 130)) ?>...</p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
