<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = current_user();
$resources = $pdo->query("SELECT r.*, u.name AS author_name FROM resources r LEFT JOIN users u ON u.id = r.created_by ORDER BY r.id DESC")->fetchAll();
$pageTitle = 'Resources';
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Academic resources</h1>
        <div class="text-muted">Study packs, downloadable templates, revision notes, and guided materials.</div>
    </div>
    <?php if ($user && $user['role'] === 'student' && ($sub = current_subscription((int)$user['id']))): ?>
        <span class="info-chip">Current plan: <?= e($sub['plan_name']) ?></span>
    <?php endif; ?>
</div>
<div class="row g-4">
    <?php foreach ($resources as $resource): $allowed = can_access_resource($resource, $user); ?>
        <div class="col-lg-6">
            <div class="card card-soft p-4 h-100">
                <div class="d-flex justify-content-between gap-3 align-items-start mb-3">
                    <div>
                        <span class="tag-pill mb-2"><?= e($resource['resource_type']) ?></span>
                        <h2 class="h5 mb-1"><?= e($resource['title']) ?></h2>
                        <div class="small text-muted">By <?= e($resource['author_name'] ?: 'Admin') ?></div>
                    </div>
                    <?= status_badge($resource['access_level']) ?>
                </div>
                <p class="text-muted"><?= e(resource_excerpt($resource, 180)) ?></p>
                <div class="resource-content small mb-3"><?= nl2br(e(substr((string)$resource['content'], 0, 420))) ?><?= strlen((string)$resource['content']) > 420 ? '...' : '' ?></div>
                <div class="d-flex flex-wrap gap-2 mt-auto">
                    <?php if ($allowed && $resource['attachment_path']): ?><a class="btn btn-primary btn-sm" href="<?= app_url($resource['attachment_path']) ?>" target="_blank"><?= e($resource['attachment_name'] ?: 'Download file') ?></a><?php elseif ($resource['attachment_path']): ?><a class="btn btn-outline-secondary btn-sm" href="<?= app_url('pricing.php') ?>">Upgrade to access file</a><?php endif; ?>
                    <?php if ($resource['external_url']): ?><a class="btn btn-outline-primary btn-sm" href="<?= e($resource['external_url']) ?>" target="_blank">Open reference</a><?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$resources): ?><div class="col-12"><div class="empty-state text-muted">No resources published yet.</div></div><?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
