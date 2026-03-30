<?php
require_once __DIR__ . '/includes/bootstrap.php';
$featuredTutors = $pdo->query("SELECT tp.*, u.name, u.avatar_path FROM tutor_profiles tp INNER JOIN users u ON u.id = tp.user_id WHERE tp.is_verified = 1 ORDER BY tp.rating_average DESC, tp.total_sessions DESC LIMIT 3")->fetchAll();
$resources = $pdo->query("SELECT * FROM resources ORDER BY id DESC LIMIT 3")->fetchAll();
$pageTitle = 'Academic Support Hub';
include __DIR__ . '/includes/header.php';
?>
<section class="hero mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <span class="tag-pill mb-3">Academic help that feels real</span>
            <h1 class="display-5 fw-bold mb-3">Learn with real tutors, pay securely with mobile money, and access premium academic resources in one place.</h1>
            <p class="lead text-white-50 mb-4">Built for guided support, not hype. Students get structured help, tutors teach with accountability, and payments stay tracked and professional.</p>
            <div class="d-flex flex-wrap gap-3">
                <a class="btn btn-warning btn-lg" href="<?= app_url(is_logged_in() && user_role()==='student' ? 'student/request_help.php' : 'register.php') ?>">Get academic help</a>
                <a class="btn btn-outline-light btn-lg" href="<?= app_url('pricing.php') ?>">View plans</a>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card payment-card p-4 border-0">
                <div class="section-title text-white mb-3">Platform snapshot</div>
                <div class="d-flex justify-content-between mb-2"><span>Verified tutors</span><strong><?= count_value("SELECT COUNT(*) FROM tutor_profiles WHERE is_verified = 1") ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Academic resources</span><strong><?= count_value("SELECT COUNT(*) FROM resources") ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Study sessions completed</span><strong><?= count_value("SELECT COUNT(*) FROM study_sessions WHERE status = 'completed'") ?></strong></div>
                <div class="d-flex justify-content-between"><span>Secure mobile money</span><strong><?= lenco_enabled() ? 'Enabled' : 'Setup pending' ?></strong></div>
            </div>
        </div>
    </div>
</section>
<section class="mb-4">
    <div class="row g-3">
        <div class="col-md-4"><div class="metric-card"><div class="stat-muted">Guided learning</div><div class="metric-value">1-on-1</div><div class="text-muted">Structured tutor sessions and progress-aware study support.</div></div></div>
        <div class="col-md-4"><div class="metric-card"><div class="stat-muted">Payments</div><div class="metric-value">Mobile Money</div><div class="text-muted">MTN, Airtel, and Zamtel mobile money workflow built in.</div></div></div>
        <div class="col-md-4"><div class="metric-card"><div class="stat-muted">Engagement</div><div class="metric-value">XP + rewards</div><div class="text-muted">Students and tutors earn progress and incentive-based rewards.</div></div></div>
    </div>
</section>
<section class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0">Top tutors</h2>
        <a href="<?= app_url('tutors.php') ?>" class="small">Browse all tutors</a>
    </div>
    <div class="row g-3">
        <?php foreach ($featuredTutors as $tutor): ?>
            <div class="col-md-4"><div class="card card-soft p-4 h-100"><img src="<?= e(avatar_url($tutor['avatar_path'], $tutor['name'])) ?>" class="avatar-md mb-3" alt=""><h3 class="h6 mb-1"><?= e($tutor['name']) ?></h3><div class="small text-muted mb-2"><?= e($tutor['headline']) ?></div><div class="small text-muted">Starting from <?= money($tutor['starting_price']) ?></div><div class="small text-muted">Rating <?= number_format((float)$tutor['rating_average'], 1) ?>/5</div></div></div>
        <?php endforeach; ?>
    </div>
</section>
<section>
    <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="section-title mb-0">Fresh resources</h2><a href="<?= app_url('resources.php') ?>" class="small">Browse resources</a></div>
    <div class="row g-3">
        <?php foreach ($resources as $resource): ?>
            <div class="col-md-4"><div class="card card-soft p-4 h-100"><span class="tag-pill mb-3"><?= e($resource['resource_type']) ?></span><h3 class="h5"><?= e($resource['title']) ?></h3><p class="text-muted mb-0"><?= e(resource_excerpt($resource, 130)) ?></p></div></div>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
