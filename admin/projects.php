<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    try {
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            if ($row = $stmt->fetch()) {
                delete_uploaded_file($row['file_path']);
                $pdo->prepare('DELETE FROM projects WHERE id = :id')->execute(['id' => $id]);
                set_flash('success', 'Project deleted.');
            }
        } else {
            $path = upload_file($_FILES['file'] ?? [], 'projects', ['zip','rar','pdf','doc','docx']);
            if ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $id]);
                $existing = $stmt->fetch();
                if ($existing) {
                    $finalPath = $path ?: $existing['file_path'];
                    if ($path && $existing['file_path']) delete_uploaded_file($existing['file_path']);
                    $update = $pdo->prepare('UPDATE projects SET title=:title, category=:category, description=:description, access_level=:access_level, file_path=:file_path, updated_at=NOW() WHERE id=:id');
                    $update->execute([
                        'title' => trim($_POST['title'] ?? ''),
                        'category' => trim($_POST['category'] ?? ''),
                        'description' => trim($_POST['description'] ?? ''),
                        'access_level' => trim($_POST['access_level'] ?? 'free'),
                        'file_path' => $finalPath,
                        'id' => $id,
                    ]);
                    set_flash('success', 'Project updated successfully.');
                }
            } else {
                $stmt = $pdo->prepare('INSERT INTO projects (title, category, description, access_level, file_path, created_by) VALUES (:title, :category, :description, :access_level, :file_path, :created_by)');
                $stmt->execute([
                    'title' => trim($_POST['title'] ?? ''),
                    'category' => trim($_POST['category'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'access_level' => trim($_POST['access_level'] ?? 'free'),
                    'file_path' => $path,
                    'created_by' => current_user()['id'],
                ]);
                set_flash('success', 'Project added successfully.');
            }
        }
    } catch (Throwable $e) {
        set_flash('error', $e->getMessage());
    }
    redirect_to('admin/projects.php');
}

$editId = (int)($_GET['edit'] ?? 0);
$editItem = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $editItem = $stmt->fetch();
}
$projects = $pdo->query('SELECT * FROM projects ORDER BY id DESC')->fetchAll();
$pageTitle='Manage Projects'; require_once __DIR__ . '/../includes/header.php'; ?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-soft p-4">
            <h1 class="fw-bold mb-3"><?= $editItem ? 'Edit Project' : 'Add Project' ?></h1>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $editItem ? 'update' : 'create' ?>">
                <?php if ($editItem): ?><input type="hidden" name="id" value="<?= (int)$editItem['id'] ?>"><?php endif; ?>
                <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" required value="<?= e($editItem['title'] ?? '') ?>"></div>
                <div class="mb-3"><label class="form-label">Category</label><input class="form-control" name="category" required value="<?= e($editItem['category'] ?? '') ?>"></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" rows="4" name="description" required><?= e($editItem['description'] ?? '') ?></textarea></div>
                <div class="mb-3"><label class="form-label">Access Level</label><select class="form-select" name="access_level"><?php foreach(['free','basic','pro','elite'] as $level): ?><option value="<?= $level ?>" <?= (($editItem['access_level'] ?? '') === $level) ? 'selected' : '' ?>><?= ucfirst($level) ?></option><?php endforeach; ?></select></div>
                <div class="mb-3"><label class="form-label">File</label><input type="file" class="form-control" name="file"></div>
                <button class="btn btn-primary w-100"><?= $editItem ? 'Update Project' : 'Save Project' ?></button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-soft p-4">
            <h4>Existing Projects</h4>
            <div class="table-responsive"><table class="table"><thead><tr><th>Title</th><th>Category</th><th>Access</th><th></th></tr></thead><tbody><?php foreach($projects as $p): ?><tr><td><?= e($p['title']) ?></td><td><?= e($p['category']) ?></td><td><?= e($p['access_level']) ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= url('admin/projects.php?edit=' . (int)$p['id']) ?>">Edit</a> <form method="post" class="d-inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this project?')">Delete</button></form></td></tr><?php endforeach; ?></tbody></table></div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
