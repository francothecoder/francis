<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();
$membership = fetch_membership_for_user((int)$user['id']);
$level = get_user_plan_level($membership);
$resources = $pdo->query('SELECT * FROM resources ORDER BY id DESC')->fetchAll();
$pageTitle='Resources'; require_once __DIR__ . '/../includes/header.php'; ?>
<h1 class="fw-bold mb-4">Resources</h1>
<div class="row g-4">
<?php foreach($resources as $item): ?>
    <div class="col-md-6">
        <div class="card card-soft h-100 p-4">
            <span class="badge text-bg-<?= $item['access_level']==='free' ? 'success':'primary' ?> mb-2 text-uppercase"><?= e($item['access_level']) ?></span>
            <h5><?= e($item['title']) ?></h5>
            <p class="text-muted small"><?= e($item['type']) ?></p>
            <?php if (can_access_level($item['access_level'], $level)): ?>
                <?php if (!empty($item['content'])): ?><p class="resource-content"><?= e($item['content']) ?></p><?php endif; ?>
                <?php if (!empty($item['file_path'])): ?><a class="btn btn-outline-primary" href="<?= url('student/download.php?type=resource&id=' . (int)$item['id']) ?>">Download Attachment</a><?php endif; ?>
            <?php else: ?>
                <p>This resource is locked for your current plan.</p>
                <a class="btn btn-primary" href="<?= url('membership.php') ?>">Upgrade Membership</a>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
