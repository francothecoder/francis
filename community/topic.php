<?php
require_once __DIR__ . '/../includes/functions.php';

$topicId = (int)($_GET['id'] ?? 0);
if ($topicId <= 0) {
    die('Topic not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    $action = $_POST['action'] ?? 'add_comment';

    if ($action === 'toggle_prop') {
        $added = toggle_topic_prop($topicId, current_user()['id']);
        set_flash('success', $added ? 'You gave props to this topic.' : 'Props removed.');
        redirect_to('community/topic.php?id=' . $topicId);
    }

    $stmt = $pdo->prepare('SELECT is_locked FROM community_topics WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $topicId]);
    $lockState = $stmt->fetchColumn();
    if ($lockState === false) {
        set_flash('error', 'Topic not found.');
        redirect_to('community/index.php');
    }
    if ((int)$lockState === 1) {
        set_flash('error', 'This topic is locked.');
        redirect_to('community/topic.php?id=' . $topicId);
    }

    if (in_array($action, ['add_comment', 'reply_comment'], true)) {
        $comment = trim($_POST['comment'] ?? '');
        if ($comment === '') {
            set_flash('error', 'Comment cannot be empty.');
            redirect_to('community/topic.php?id=' . $topicId);
        }
        try {
            $attachmentPath = upload_file($_FILES['attachment'] ?? [], 'community/comments');
            $attachmentName = !empty($attachmentPath) ? ($_FILES['attachment']['name'] ?? basename($attachmentPath)) : null;
            $pdo->prepare('INSERT INTO community_comments (topic_id, user_id, parent_id, comment, attachment_path, attachment_name) VALUES (:topic_id, :user_id, :parent_id, :comment, :attachment_path, :attachment_name)')
                ->execute([
                    'topic_id' => $topicId,
                    'user_id' => current_user()['id'],
                    'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
                    'comment' => $comment,
                    'attachment_path' => $attachmentPath,
                    'attachment_name' => $attachmentName,
                ]);
            set_flash('success', 'Comment posted.');
        } catch (Throwable $e) {
            set_flash('error', $e->getMessage());
        }
    }
    redirect_to('community/topic.php?id=' . $topicId);
}

track_topic_view($topicId);
$stmt = $pdo->prepare("SELECT ct.*, u.name,
    (SELECT COUNT(*) FROM community_comments cc WHERE cc.topic_id = ct.id) AS comments_count,
    (SELECT COUNT(*) FROM community_topic_views v WHERE v.topic_id = ct.id) AS views_count,
    (SELECT COUNT(*) FROM community_topic_props p WHERE p.topic_id = ct.id) AS props_count
    FROM community_topics ct
    INNER JOIN users u ON u.id = ct.user_id
    WHERE ct.id = :id LIMIT 1");
$stmt->execute(['id' => $topicId]);
$topic = $stmt->fetch();
if (!$topic) {
    die('Topic not found.');
}

$pageTitle = $topic['title'];
$commentsTree = topic_comment_tree($topicId);
$userHasPropped = is_logged_in() ? user_has_propped($topicId, current_user()['id']) : false;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="topic-shell">
    <div>
        <article class="glass-card topic-main-card reveal is-visible">
            <div class="community-meta mb-2"><?= e($topic['category']) ?> · by <?= e($topic['name']) ?> · <?= e($topic['created_at']) ?></div>
            <h1 class="fw-bold mb-3"><?= e($topic['title']) ?></h1>
            <?php if ($topic['is_locked']): ?><div class="alert alert-warning soft-alert">This topic is locked by an admin. You can still read it, but new replies are disabled.</div><?php endif; ?>
            <div class="topic-body"><?= nl2br(e($topic['content'])) ?></div>
            <?php if (!empty($topic['attachment_path'])): ?>
                <div class="topic-cover"><?= attachment_preview($topic['attachment_path'], $topic['attachment_name']) ?></div>
            <?php endif; ?>
            <div class="community-stats mt-4">
                <span class="stat-chip">👁 <?= format_count((int)$topic['views_count']) ?> viewers</span>
                <span class="stat-chip">💬 <?= format_count((int)$topic['comments_count']) ?> replies</span>
                <span class="stat-chip">⚡ <?= format_count((int)$topic['props_count']) ?> props</span>
            </div>
            <div class="d-flex gap-2 flex-wrap mt-4">
                <?php if (is_logged_in()): ?>
                    <form method="post" class="m-0">
                        <input type="hidden" name="action" value="toggle_prop">
                        <button class="prop-button <?= $userHasPropped ? 'active' : '' ?>" type="submit">
                            <?= $userHasPropped ? '⚡ Props sent' : '⚡ Give props' ?>
                        </button>
                    </form>
                <?php else: ?>
                    <a class="prop-button text-decoration-none" href="<?= url('auth/login.php') ?>">⚡ Login to give props</a>
                <?php endif; ?>
                <a class="btn btn-soft" href="<?= url('community/index.php') ?>">← Back to community</a>
            </div>
        </article>

        <?php if (is_logged_in() && !$topic['is_locked']): ?>
            <div class="community-compose reveal mt-4">
                <h4 class="fw-bold mb-3">Drop your reply</h4>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_comment">
                    <textarea class="form-control" name="comment" rows="4" placeholder="Share your advice, answer, or follow-up question..." required></textarea>
                    <input type="file" class="form-control mt-3" name="attachment">
                    <button class="btn btn-primary btn-pill mt-3">Post reply</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <h4 class="fw-bold mb-3">Discussion</h4>
            <?php render_comment_branch($commentsTree, $topicId); ?>
            <?php if (empty($commentsTree[0])): ?>
                <div class="glass-card p-4">
                    <p class="mb-0 topic-body">No replies yet. Be the first to contribute.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <aside>
        <div class="glass-card community-sidebar-card reveal is-visible">
            <h5 class="fw-bold mb-3">Topic pulse</h5>
            <div class="metric-box mb-3">
                <div class="community-label">Live engagement</div>
                <div class="fs-3 fw-bold mt-1"><?= format_count((int)$topic['props_count']) ?> props</div>
                <div class="small-muted">Students are using props instead of boring likes.</div>
            </div>
            <div class="metric-box mb-3">
                <div class="community-label">People reached</div>
                <div class="fs-3 fw-bold mt-1"><?= format_count((int)$topic['views_count']) ?> viewers</div>
                <div class="small-muted">Unique views tracked per session.</div>
            </div>
            <div class="metric-box">
                <div class="community-label">Reply quality</div>
                <div class="fs-3 fw-bold mt-1"><?= format_count((int)$topic['comments_count']) ?> replies</div>
                <div class="small-muted">Threaded replies keep the conversation clean.</div>
            </div>
        </div>
    </aside>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
