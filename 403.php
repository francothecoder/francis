<?php
$pageTitle = 'Access denied';
require_once __DIR__ . '/includes/bootstrap.php';
include __DIR__ . '/includes/header.php';
?>
<div class="card card-soft p-5 text-center">
    <h1 class="h3 mb-3">Access denied</h1>
    <p class="text-muted">You do not have permission to view this page.</p>
    <a class="btn btn-primary" href="<?= app_url() ?>">Go home</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
