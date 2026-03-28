<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle='Resources';
$resources = $pdo->query('SELECT * FROM resources ORDER BY id DESC')->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<h1 class="fw-bold mb-4">Resources</h1>
<div class="row g-4">
    <?php foreach ($resources as $item): ?>
    <div class="col-md-6">
        <div class="card card-soft h-100 p-4">
            <span class="badge text-bg-<?= $item['access_level']==='free' ? 'success':'primary' ?> mb-2 text-uppercase"><?= e($item['access_level']) ?></span>
            <h5><?= e($item['title']) ?></h5>
            <p class="small text-muted"><?= e($item['type']) ?></p>
            <p class="resource-content"><?= e(mb_strimwidth((string)$item['content'], 0, 180, '...')) ?></p>
            <?php if ($item['access_level'] === 'free'): ?>
                <a class="btn btn-outline-primary" href="<?= url('auth/login.php') ?>">Login to Read</a>
            <?php else: ?>
                <a class="btn btn-primary" href="<?= url('membership.php') ?>">Upgrade to Read</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
