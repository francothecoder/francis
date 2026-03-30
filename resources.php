<?php
require_once __DIR__ . '/includes/bootstrap.php';
$resources = $pdo->query("
    SELECT r.*, u.name AS author_name
    FROM resources r
    LEFT JOIN users u ON u.id = r.created_by
    ORDER BY r.id DESC
")->fetchAll();
$pageTitle = 'Resources';
include __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4">Academic resources</h1>
<div class="row g-3">
    <?php foreach ($resources as $resource): ?>
        <div class="col-lg-6">
            <div class="card card-soft p-4 h-100">
                <div class="d-flex justify-content-between gap-3 align-items-start">
                    <div>
                        <span class="tag-pill d-inline-block mb-3"><?= e($resource['resource_type']) ?></span>
                        <h2 class="h5"><?= e($resource['title']) ?></h2>
                    </div>
                    <?= status_badge($resource['access_level']) ?>
                </div>
                <div class="resource-content text-muted mb-3"><?= e(substr((string) $resource['content'], 0, 500)) ?><?= strlen((string) $resource['content']) > 500 ? '...' : '' ?></div>
                <div class="small text-muted">By <?= e($resource['author_name'] ?: 'Admin') ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
