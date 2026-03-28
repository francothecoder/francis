<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();
$membership = fetch_membership_for_user((int)$user['id']);
$level = get_user_plan_level($membership);
$projects = $pdo->query('SELECT * FROM projects ORDER BY id DESC')->fetchAll();
$pageTitle='Student Projects'; require_once __DIR__ . '/../includes/header.php'; ?>
<h1 class="fw-bold mb-4">Projects Library</h1>
<div class="row g-4">
<?php foreach($projects as $project): ?>
    <div class="col-md-6">
        <div class="card card-soft h-100 p-4">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <h5><?= e($project['title']) ?></h5>
                    <div class="small text-muted"><?= e($project['category']) ?></div>
                </div>
                <span class="badge text-bg-<?= $project['access_level']==='free' ? 'success':'primary' ?>"><?= e($project['access_level']) ?></span>
            </div>
            <p class="mt-3"><?= e($project['description']) ?></p>
            <?php if (can_access_level($project['access_level'], $level)): ?>
                <?php if (!empty($project['file_path'])): ?>
                    <a class="btn btn-outline-primary" href="<?= url('student/download.php?type=project&id=' . (int)$project['id']) ?>">Download</a>
                <?php else: ?>
                    <button class="btn btn-outline-secondary" disabled>File not uploaded yet</button>
                <?php endif; ?>
            <?php else: ?>
                <a class="btn btn-primary" href="<?= url('membership.php') ?>">Upgrade to Access</a>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
