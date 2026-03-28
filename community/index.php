<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    $action = $_POST['action'] ?? 'create_topic';

    if ($action === 'create_topic') {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $content = trim($_POST['content'] ?? '');
        if ($title === '' || $content === '') {
            set_flash('error', 'Title and topic content are required.');
            redirect_to('community/index.php');
        }
        try {
            $attachmentPath = upload_file($_FILES['attachment'] ?? [], 'community/topics');
            $attachmentName = !empty($attachmentPath) ? ($_FILES['attachment']['name'] ?? basename($attachmentPath)) : null;
            $stmt = $pdo->prepare('INSERT INTO community_topics (user_id, title, category, content, attachment_path, attachment_name) VALUES (:user_id, :title, :category, :content, :attachment_path, :attachment_name)');
            $stmt->execute([
                'user_id' => current_user()['id'],
                'title' => $title,
                'category' => $category,
                'content' => $content,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
            ]);
            set_flash('success', 'Topic created successfully.');
        } catch (Throwable $e) {
            set_flash('error', $e->getMessage());
        }
    }
    redirect_to('community/index.php');
}

$search = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(ct.title LIKE :search OR ct.content LIKE :search OR ct.category LIKE :search)';
    $params['search'] = '%' . $search . '%';
}
if ($category !== '') {
    $where[] = 'ct.category = :category';
    $params['category'] = $category;
}
$sql = "SELECT ct.*, u.name,
        (SELECT COUNT(*) FROM community_comments cc WHERE cc.topic_id = ct.id) AS comments_count,
        (SELECT COUNT(*) FROM community_topic_views v WHERE v.topic_id = ct.id) AS views_count,
        (SELECT COUNT(*) FROM community_topic_props p WHERE p.topic_id = ct.id) AS props_count
        FROM community_topics ct
        INNER JOIN users u ON u.id = ct.user_id";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY ct.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$topics = $stmt->fetchAll();
$categories = $pdo->query('SELECT DISTINCT category FROM community_topics ORDER BY category ASC')->fetchAll(PDO::FETCH_COLUMN);
$pageTitle = 'Community';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="community-hero reveal is-visible">
    <div class="row g-4 align-items-center">
        <div class="col-lg-8">
            <div class="community-label mb-2">Collaborate • Ask • Learn • Build</div>
            <h1 class="display-6 fw-bold mb-3">Student community you’ll actually want to come back to</h1>
            <p class="lead mb-0 topic-body">Start a discussion, share screenshots or files, collect props from other students, and keep learning in public with a cleaner, more addictive interface.</p>
        </div>
        <div class="col-lg-4">
            <div class="glass-card community-sidebar-card">
                <div class="community-stats">
                    <span class="stat-chip">🧵 <?= format_count(count($topics)) ?> topics</span>
                    <span class="stat-chip">✨ Smart reactions</span>
                    <span class="stat-chip">📎 Attachments enabled</span>
                </div>
                <?php if (is_logged_in()): ?>
                    <button class="btn btn-primary btn-pill mt-3" data-bs-toggle="collapse" data-bs-target="#newTopic">Start a fresh topic</button>
                <?php else: ?>
                    <a class="btn btn-primary btn-pill mt-3" href="<?= url('auth/login.php') ?>">Login to join the vibe</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <?php if (is_logged_in()): ?>
            <div class="collapse mb-4" id="newTopic">
                <div class="community-compose reveal is-visible">
                    <h4 class="fw-bold mb-3">Post something worth discussing</h4>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create_topic">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="community-label">Title</label>
                                <input class="form-control" name="title" placeholder="What do you want help or feedback on?" required>
                            </div>
                            <div class="col-md-4">
                                <label class="community-label">Category</label>
                                <input class="form-control" name="category" placeholder="Projects, Exams, Coding..." required>
                            </div>
                            <div class="col-12">
                                <label class="community-label">Topic content</label>
                                <textarea class="form-control" rows="5" name="content" placeholder="Share context, what you've tried, and what kind of help you need..." required></textarea>
                            </div>
                            <div class="col-12">
                                <label class="community-label">Attach image or file</label>
                                <input type="file" class="form-control" name="attachment">
                            </div>
                        </div>
                        <button class="btn btn-primary btn-pill mt-3">Publish topic</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="community-grid">
            <?php foreach ($topics as $topic): ?>
                <article class="glass-card topic-card reveal">
                    <div class="topic-card-inner">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="community-meta"><?= e($topic['category']) ?> · by <?= e($topic['name']) ?> · <?= e($topic['created_at']) ?></div>
                                <a class="topic-title" href="<?= url('community/topic.php?id=' . (int)$topic['id']) ?>"><?= e($topic['title']) ?></a>
                            </div>
                            <?php if ($topic['is_locked']): ?><span class="tiny-pill">Locked</span><?php endif; ?>
                        </div>
                        <div class="topic-snippet"><?= e(mb_strimwidth((string)$topic['content'], 0, 220, '...')) ?></div>
                        <?= attachment_preview($topic['attachment_path'] ?? null, $topic['attachment_name'] ?? null) ?>
                        <div class="community-stats">
                            <span class="stat-chip">👁 <?= format_count((int)$topic['views_count']) ?> viewers</span>
                            <span class="stat-chip">⚡ <?= format_count((int)$topic['props_count']) ?> props</span>
                            <span class="stat-chip">💬 <?= format_count((int)$topic['comments_count']) ?> replies</span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (!$topics): ?>
                <div class="glass-card p-4 reveal is-visible">
                    <h4 class="fw-bold">No topics found</h4>
                    <p class="topic-body mb-0">Try a different search or be the first to post a topic.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="glass-card community-sidebar-card reveal">
            <h4 class="fw-bold mb-3">Discover topics</h4>
            <form method="get" class="d-grid gap-3">
                <input type="text" class="form-control" name="q" value="<?= e($search) ?>" placeholder="Search topic, tag, idea...">
                <select class="form-select" name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-soft">Filter discussions</button>
            </form>
        </div>

        <div class="glass-card community-sidebar-card reveal mt-4">
            <h5 class="fw-bold">Community energy</h5>
            <ul class="list-clean mt-3">
                <li>Use <strong>props</strong> to boost good posts.</li>
                <li>Attach screenshots, images, PDFs, ZIPs, or docs.</li>
                <li>Reply under any comment to keep discussions threaded.</li>
            </ul>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
