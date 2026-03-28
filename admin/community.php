<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topicId = (int)($_POST['topic_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_lock') {
        $pdo->prepare('UPDATE community_topics SET is_locked = 1 - is_locked, updated_at = NOW() WHERE id = :id')->execute(['id' => $topicId]);
        set_flash('success', 'Topic status updated.');
    } elseif ($action === 'delete_topic') {
        $stmt = $pdo->prepare('SELECT attachment_path FROM community_topics WHERE id = :id');
        $stmt->execute(['id' => $topicId]);
        if ($path = $stmt->fetchColumn()) {
            delete_uploaded_file($path);
        }
        $stmt2 = $pdo->prepare('SELECT attachment_path FROM community_comments WHERE topic_id = :id');
        $stmt2->execute(['id' => $topicId]);
        foreach ($stmt2->fetchAll(PDO::FETCH_COLUMN) as $commentPath) {
            delete_uploaded_file($commentPath);
        }
        $pdo->prepare('DELETE FROM community_topics WHERE id = :id')->execute(['id' => $topicId]);
        set_flash('success', 'Topic deleted.');
    }
    redirect_to('admin/community.php');
}

$topics = $pdo->query("SELECT ct.*, u.name,
    (SELECT COUNT(*) FROM community_comments cc WHERE cc.topic_id = ct.id) AS comments_count,
    (SELECT COUNT(*) FROM community_topic_views v WHERE v.topic_id = ct.id) AS views_count,
    (SELECT COUNT(*) FROM community_topic_props p WHERE p.topic_id = ct.id) AS props_count
    FROM community_topics ct
    INNER JOIN users u ON u.id = ct.user_id
    ORDER BY ct.id DESC")->fetchAll();
$pageTitle = 'Community Moderation';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="glass-card p-4 table-shell reveal is-visible">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <div class="community-label">Admin moderation</div>
            <h1 class="fw-bold mb-0">Community moderation</h1>
        </div>
        <a class="btn btn-soft" href="<?= url('community/index.php') ?>">Open public community</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Topic</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Viewers</th>
                    <th>Props</th>
                    <th>Replies</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($topics as $topic): ?>
                <tr>
                    <td>
                        <a class="fw-semibold text-decoration-none" href="<?= url('community/topic.php?id=' . (int)$topic['id']) ?>"><?= e($topic['title']) ?></a>
                        <?php if (!empty($topic['attachment_path'])): ?><div class="small-muted">Has attachment</div><?php endif; ?>
                    </td>
                    <td><?= e($topic['name']) ?></td>
                    <td><?= e($topic['category']) ?></td>
                    <td><?= (int)$topic['views_count'] ?></td>
                    <td><?= (int)$topic['props_count'] ?></td>
                    <td><?= (int)$topic['comments_count'] ?></td>
                    <td><span class="tiny-pill"><?= $topic['is_locked'] ? 'Locked' : 'Open' ?></span></td>
                    <td class="text-end">
                        <form method="post" class="d-inline">
                            <input type="hidden" name="action" value="toggle_lock">
                            <input type="hidden" name="topic_id" value="<?= (int)$topic['id'] ?>">
                            <button class="btn btn-sm btn-soft"><?= $topic['is_locked'] ? 'Unlock' : 'Lock' ?></button>
                        </form>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="action" value="delete_topic">
                            <input type="hidden" name="topic_id" value="<?= (int)$topic['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this topic and all its replies?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
