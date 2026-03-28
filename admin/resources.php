<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    try {
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM resources WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            if ($row = $stmt->fetch()) {
                delete_uploaded_file($row['file_path']);
                $pdo->prepare('DELETE FROM resources WHERE id = :id')->execute(['id' => $id]);
                set_flash('success', 'Resource deleted.');
            }
        } else {
            $path = upload_file($_FILES['file'] ?? [], 'resources', ['pdf','doc','docx','zip','rar']);
            if ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $pdo->prepare('SELECT * FROM resources WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $id]);
                $existing = $stmt->fetch();
                if ($existing) {
                    $finalPath = $path ?: $existing['file_path'];
                    if ($path && $existing['file_path']) delete_uploaded_file($existing['file_path']);
                    $update = $pdo->prepare('UPDATE resources SET title=:title, type=:type, content=:content, file_path=:file_path, access_level=:access_level, updated_at=NOW() WHERE id=:id');
                    $update->execute([
                        'title' => trim($_POST['title'] ?? ''),
                        'type' => trim($_POST['type'] ?? ''),
                        'content' => trim($_POST['content'] ?? ''),
                        'file_path' => $finalPath,
                        'access_level' => trim($_POST['access_level'] ?? 'free'),
                        'id' => $id,
                    ]);
                    set_flash('success', 'Resource updated successfully.');
                }
            } else {
                $stmt = $pdo->prepare('INSERT INTO resources (title, type, content, file_path, access_level, created_by) VALUES (:title, :type, :content, :file_path, :access_level, :created_by)');
                $stmt->execute([
                    'title' => trim($_POST['title'] ?? ''),
                    'type' => trim($_POST['type'] ?? ''),
                    'content' => trim($_POST['content'] ?? ''),
                    'file_path' => $path,
                    'access_level' => trim($_POST['access_level'] ?? 'free'),
                    'created_by' => current_user()['id'],
                ]);
                set_flash('success', 'Resource added successfully.');
            }
        }
    } catch (Throwable $e) {
        set_flash('error', $e->getMessage());
    }
    redirect_to('admin/resources.php');
}

$editId = (int)($_GET['edit'] ?? 0);
$editItem = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM resources WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $editItem = $stmt->fetch();
}
$items = $pdo->query('SELECT * FROM resources ORDER BY id DESC')->fetchAll();
$pageTitle='Manage Resources'; require_once __DIR__ . '/../includes/header.php'; ?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-soft p-4">
            <h1 class="fw-bold mb-3"><?= $editItem ? 'Edit Resource' : 'Add Resource' ?></h1>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $editItem ? 'update' : 'create' ?>">
                <?php if ($editItem): ?><input type="hidden" name="id" value="<?= (int)$editItem['id'] ?>"><?php endif; ?>
                <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" required value="<?= e($editItem['title'] ?? '') ?>"></div>
                <div class="mb-3"><label class="form-label">Type</label><input class="form-control" name="type" placeholder="Guide / Template / Tutorial" required value="<?= e($editItem['type'] ?? '') ?>"></div>
                <div class="mb-3"><label class="form-label">Content</label><textarea class="form-control" rows="5" name="content"><?= e($editItem['content'] ?? '') ?></textarea></div>
                <div class="mb-3"><label class="form-label">Access Level</label><select class="form-select" name="access_level"><?php foreach(['free','basic','pro','elite'] as $level): ?><option value="<?= $level ?>" <?= (($editItem['access_level'] ?? '') === $level) ? 'selected' : '' ?>><?= ucfirst($level) ?></option><?php endforeach; ?></select></div>
                <div class="mb-3"><label class="form-label">Attachment</label><input type="file" class="form-control" name="file"></div>
                <button class="btn btn-primary w-100"><?= $editItem ? 'Update Resource' : 'Save Resource' ?></button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-soft p-4">
            <h4>Existing Resources</h4>
            <div class="table-responsive"><table class="table"><thead><tr><th>Title</th><th>Type</th><th>Access</th><th></th></tr></thead><tbody><?php foreach($items as $item): ?><tr><td><?= e($item['title']) ?></td><td><?= e($item['type']) ?></td><td><?= e($item['access_level']) ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= url('admin/resources.php?edit=' . (int)$item['id']) ?>">Edit</a> <form method="post" class="d-inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this resource?')">Delete</button></form></td></tr><?php endforeach; ?></tbody></table></div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
