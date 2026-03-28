<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Projects';
$projects = $pdo->query('SELECT * FROM projects ORDER BY id DESC')->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="fw-bold mb-0">Projects Library</h1>
    <a class="btn btn-primary" href="<?= url('membership.php') ?>">Unlock Premium Access</a>
</div>
<div class="row g-4">
    <?php foreach ($projects as $project): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card card-soft h-100 p-4">
            <span class="badge text-bg-<?= $project['access_level']==='free' ? 'success':'primary' ?> mb-2 text-uppercase"><?= e($project['access_level']) ?></span>
            <h5><?= e($project['title']) ?></h5>
            <p class="text-muted small mb-2"><?= e($project['category']) ?></p>
            <p><?= e($project['description']) ?></p>
            <div class="mt-auto">
                <?php if ($project['access_level'] === 'free'): ?>
                    <a class="btn btn-outline-primary" href="<?= url('auth/login.php') ?>">Login to Access</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="<?= url('membership.php') ?>">Upgrade to Access</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
