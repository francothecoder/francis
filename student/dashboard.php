<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();
$membership = fetch_membership_for_user((int)$user['id']);
$pendingRequest = get_pending_subscription_for_user((int)$user['id']);
$resources = $pdo->query('SELECT * FROM resources ORDER BY id DESC LIMIT 3')->fetchAll();
$projects = $pdo->query('SELECT * FROM projects ORDER BY id DESC LIMIT 4')->fetchAll();
$topicsStmt = $pdo->query("SELECT ct.*, (SELECT COUNT(*) FROM community_comments cc WHERE cc.topic_id = ct.id) AS comments_count
                           FROM community_topics ct ORDER BY ct.id DESC LIMIT 3");
$topics = $topicsStmt->fetchAll();
$pageTitle='Student Dashboard'; require_once __DIR__ . '/../includes/header.php'; ?>
<h1 class="fw-bold mb-4">Welcome, <?= e($user['name']) ?></h1>
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card card-soft p-4">
            <h5>Membership</h5>
            <?php if ($membership): ?>
                <p class="mb-1"><?= e($membership['plan_name']) ?></p>
                <small class="text-muted"><?= e(membership_label($membership['days_left'])) ?></small>
            <?php elseif ($pendingRequest): ?>
                <p class="mb-1">Pending <?= e($pendingRequest['plan_name']) ?> request</p>
                <small class="text-muted">Awaiting admin approval</small>
            <?php else: ?>
                <p class="mb-2">Free User</p>
                <a href="<?= url('membership.php') ?>" class="btn btn-outline-primary btn-sm">Subscribe</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4"><div class="card card-soft p-4"><h5>University</h5><p class="mb-0"><?= e($user['university'] ?? 'Not set') ?></p></div></div>
    <div class="col-md-4"><div class="card card-soft p-4"><h5>Need Help?</h5><a href="<?= url('student/support.php') ?>" class="btn btn-outline-primary btn-sm">Open Support</a></div></div>
</div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card card-soft p-4">
            <div class="d-flex justify-content-between"><h4>Recent Resources</h4><a href="<?= url('student/resources.php') ?>">View all</a></div>
            <ul class="list-clean mt-3 mb-0"><?php foreach($resources as $item): ?><li><?= e($item['title']) ?> <span class="text-muted small">(<?= e($item['access_level']) ?>)</span></li><?php endforeach; ?></ul>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-soft p-4">
            <div class="d-flex justify-content-between"><h4>Projects</h4><a href="<?= url('student/projects.php') ?>">View all</a></div>
            <ul class="list-clean mt-3 mb-0"><?php foreach($projects as $item): ?><li><?= e($item['title']) ?> <span class="text-muted small">(<?= e($item['access_level']) ?>)</span></li><?php endforeach; ?></ul>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-soft p-4">
            <div class="d-flex justify-content-between"><h4>Community</h4><a href="<?= url('community/index.php') ?>">Visit</a></div>
            <ul class="list-clean mt-3 mb-0"><?php foreach($topics as $topic): ?><li><?= e($topic['title']) ?> <span class="text-muted small">(<?= (int)$topic['comments_count'] ?> replies)</span></li><?php endforeach; ?></ul>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
