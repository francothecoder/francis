<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = clean_text($_POST['title'] ?? '');
    $type = clean_text($_POST['resource_type'] ?? '');
    $content = multi_line_text($_POST['content'] ?? '');
    $access = $_POST['access_level'] ?? 'free';

    if ($title === '' || $type === '' || $content === '' || !in_array($access, ['free','starter','plus','pro'], true)) {
        set_flash('error', 'Complete the resource form properly.');
        redirect_to('admin/resources.php');
    }

    $stmt = $pdo->prepare('INSERT INTO resources (title, resource_type, content, access_level, created_by) VALUES (:title, :resource_type, :content, :access_level, :created_by)');
    $stmt->execute([
        'title' => $title,
        'resource_type' => $type,
        'content' => $content,
        'access_level' => $access,
        'created_by' => current_user()['id'],
    ]);
    set_flash('success', 'Resource published.');
    redirect_to('admin/resources.php');
}

$resources = $pdo->query('SELECT * FROM resources ORDER BY id DESC')->fetchAll();
$pageTitle = 'Manage resources';
include __DIR__ . '/../includes/header.php';
?>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card card-soft p-4">
            <h1 class="h4 mb-3">Publish resource</h1>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="mb-3"><label class="form-label">Title</label><input class="form-control" type="text" name="title" required></div>
                <div class="mb-3"><label class="form-label">Type</label><input class="form-control" type="text" name="resource_type" placeholder="Guide, Template, Revision Note" required></div>
                <div class="mb-3"><label class="form-label">Access level</label><select class="form-select" name="access_level"><option value="free">Free</option><option value="starter">Starter</option><option value="plus">Study Plus</option><option value="pro">Academic Pro</option></select></div>
                <div class="mb-3"><label class="form-label">Content</label><textarea class="form-control" name="content" rows="7" required></textarea></div>
                <button class="btn btn-primary">Publish resource</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-soft p-4">
            <div class="section-title mb-3">Published resources</div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Title</th><th>Type</th><th>Access</th></tr></thead>
                    <tbody>
                        <?php foreach ($resources as $resource): ?>
                            <tr>
                                <td><?= e($resource['title']) ?></td>
                                <td><?= e($resource['resource_type']) ?></td>
                                <td><?= status_badge($resource['access_level']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$resources): ?>
                            <tr><td colspan="3" class="text-muted">No resources yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
