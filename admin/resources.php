<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = clean_text($_POST['title'] ?? '');
    $type = clean_text($_POST['resource_type'] ?? '');
    $excerpt = clean_text($_POST['excerpt'] ?? '');
    $content = multi_line_text($_POST['content'] ?? '');
    $access = $_POST['access_level'] ?? 'free';
    $externalUrl = clean_text($_POST['external_url'] ?? '');

    if ($title === '' || $type === '' || $content === '' || !in_array($access, ['free','starter','plus','pro'], true)) {
        set_flash('error', 'Complete the resource form properly.');
        redirect_to('admin/resources.php');
    }

    try {
        $attachment = upload_file($_FILES['attachment'] ?? [], 'resource_files', ['pdf','doc','docx','ppt','pptx','xls','xlsx','zip'], ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/zip','application/x-zip-compressed'], 12582912);
    } catch (Throwable $e) {
        set_flash('error', $e->getMessage());
        redirect_to('admin/resources.php');
    }

    $stmt = $pdo->prepare('INSERT INTO resources (title, resource_type, excerpt, content, access_level, attachment_path, attachment_name, external_url, created_by) VALUES (:title, :resource_type, :excerpt, :content, :access_level, :attachment_path, :attachment_name, :external_url, :created_by)');
    $stmt->execute([
        'title' => $title,
        'resource_type' => $type,
        'excerpt' => $excerpt ?: null,
        'content' => $content,
        'access_level' => $access,
        'attachment_path' => $attachment,
        'attachment_name' => $attachment ? basename((string) ($_FILES['attachment']['name'] ?? 'resource')) : null,
        'external_url' => $externalUrl !== '' ? $externalUrl : null,
        'created_by' => current_user()['id'],
    ]);
    set_flash('success', 'Resource published with full academic attachment support.');
    redirect_to('admin/resources.php');
}

$resources = $pdo->query('SELECT r.*, u.name AS author_name FROM resources r LEFT JOIN users u ON u.id = r.created_by ORDER BY r.id DESC')->fetchAll();
$pageTitle = 'Manage resources';
include __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-xl-5">
        <div class="card card-soft p-4">
            <h1 class="h4 mb-1">Publish resource</h1>
            <p class="text-muted mb-4">Post premium notes, templates, revision packs, and downloadable files.</p>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="mb-3"><label class="form-label">Title</label><input class="form-control" type="text" name="title" required></div>
                <div class="mb-3"><label class="form-label">Type</label><input class="form-control" type="text" name="resource_type" placeholder="Guide, Template, Revision Note" required></div>
                <div class="mb-3"><label class="form-label">Short excerpt</label><input class="form-control" type="text" name="excerpt" placeholder="Optional short teaser shown on cards"></div>
                <div class="mb-3"><label class="form-label">Access level</label><select class="form-select" name="access_level"><option value="free">Free</option><option value="starter">Starter</option><option value="plus">Study Plus</option><option value="pro">Academic Pro</option></select></div>
                <div class="mb-3"><label class="form-label">Content</label><textarea class="form-control" name="content" rows="8" required></textarea></div>
                <div class="mb-3"><label class="form-label">Attachment</label><input class="form-control" type="file" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip"><div class="form-text">Attach notes, slides, spreadsheets, or revision packs.</div></div>
                <div class="mb-3"><label class="form-label">External link</label><input class="form-control" type="url" name="external_url" placeholder="Optional reference link"></div>
                <button class="btn btn-primary">Publish resource</button>
            </form>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card card-soft p-4">
            <div class="d-flex justify-content-between align-items-center mb-3"><div class="section-title">Published resources</div><span class="info-chip"><?= count($resources) ?> items</span></div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Title</th><th>Type</th><th>Access</th><th>Attachment</th></tr></thead>
                    <tbody>
                        <?php foreach ($resources as $resource): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= e($resource['title']) ?></div>
                                    <div class="small text-muted"><?= e($resource['author_name'] ?: 'Admin') ?></div>
                                </td>
                                <td><?= e($resource['resource_type']) ?></td>
                                <td><?= status_badge($resource['access_level']) ?></td>
                                <td>
                                    <?php if ($resource['attachment_path']): ?><a class="btn btn-sm btn-outline-primary" href="<?= app_url($resource['attachment_path']) ?>" target="_blank">Open file</a><?php else: ?><span class="text-muted small">No file</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$resources): ?><tr><td colspan="4" class="text-muted">No resources yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
