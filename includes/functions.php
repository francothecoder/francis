<?php
require_once __DIR__ . '/../config/config.php';

function url(string $path = ''): string {
    $path = ltrim($path, '/');
    return BASE_URL . ($path ? '/' . $path : '');
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect_to(string $path): void {
    header('Location: ' . url($path));
    exit;
}

function set_flash(string $key, string $message): void {
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string {
    if (!empty($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    return null;
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function refresh_current_user(): void {
    global $pdo;
    if (empty($_SESSION['user']['id'])) return;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $_SESSION['user']['id']]);
    if ($user = $stmt->fetch()) {
        $_SESSION['user'] = $user;
    }
}

function is_logged_in(): bool {
    return !empty($_SESSION['user']);
}

function is_admin(): bool {
    return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin');
}

function require_login(): void {
    if (!is_logged_in()) {
        set_flash('error', 'Please login to continue.');
        redirect_to('auth/login.php');
    }
}

function require_admin(): void {
    if (!is_admin()) {
        set_flash('error', 'Access denied.');
        redirect_to('auth/login.php');
    }
}

function get_setting(string $key, string $default = ''): string {
    global $pdo;
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE key_name = :key LIMIT 1');
    $stmt->execute(['key' => $key]);
    $row = $stmt->fetch();
    return $row['value'] ?? $default;
}

function rank_for_level(string $level): int {
    $rank = ['free' => 0, 'basic' => 1, 'pro' => 2, 'elite' => 3];
    return $rank[strtolower($level)] ?? 0;
}

function can_access_level(string $need, string $current): bool {
    return rank_for_level($current) >= rank_for_level($need);
}

function get_user_plan_level(?array $membership): string {
    return strtolower($membership['plan_name'] ?? 'free');
}

function membership_label(?int $daysLeft): string {
    if ($daysLeft === null) return 'No active plan';
    if ($daysLeft < 0) return 'Expired';
    return $daysLeft . ' days left';
}

function fetch_membership_for_user(int $userId): ?array {
    global $pdo;
    $sql = "SELECT um.*, mp.name AS plan_name, mp.price, mp.duration_days
            FROM user_memberships um
            INNER JOIN membership_plans mp ON mp.id = um.plan_id
            WHERE um.user_id = :user_id AND um.status = 'active'
            ORDER BY um.expires_at DESC, um.id DESC
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch();
    if (!$row) return null;

    $expires = new DateTime($row['expires_at']);
    $today = new DateTime(date('Y-m-d H:i:s'));
    $row['days_left'] = (int)$today->diff($expires)->format('%r%a');
    if ($row['days_left'] < 0) {
        $pdo->prepare("UPDATE user_memberships SET status = 'expired' WHERE id = :id")->execute(['id' => $row['id']]);
        return null;
    }
    return $row;
}

function get_pending_subscription_for_user(int $userId): ?array {
    global $pdo;
    $sql = "SELECT sr.*, mp.name AS plan_name
            FROM subscription_requests sr
            INNER JOIN membership_plans mp ON mp.id = sr.plan_id
            WHERE sr.user_id = :user_id AND sr.status = 'pending'
            ORDER BY sr.id DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetch() ?: null;
}

function upload_file(array $file, string $subDir, array $allowedExt = ['zip', 'pdf', 'doc', 'docx', 'rar', 'png', 'jpg', 'jpeg', 'gif', 'webp']): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed. Please try again.');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('Invalid file type.');
    }
    $safe = time() . '_' . bin2hex(random_bytes(4)) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
    $targetDir = BASE_PATH . '/uploads/' . trim($subDir, '/');
    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        throw new RuntimeException('Could not create upload directory.');
    }
    $target = $targetDir . '/' . $safe;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Failed to save uploaded file.');
    }
    return 'uploads/' . trim($subDir, '/') . '/' . $safe;
}

function delete_uploaded_file(?string $path): void {
    if (!$path) return;
    $full = BASE_PATH . '/' . ltrim($path, '/');
    if (is_file($full)) @unlink($full);
}

function create_membership(int $userId, int $planId, int $approvedBy): void {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM membership_plans WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $planId]);
    $plan = $stmt->fetch();
    if (!$plan) {
        throw new RuntimeException('Plan not found.');
    }

    $pdo->prepare("UPDATE user_memberships SET status = 'expired' WHERE user_id = :user_id AND status = 'active'")
        ->execute(['user_id' => $userId]);

    $startsAt = new DateTime();
    $expiresAt = (clone $startsAt)->modify('+' . (int)$plan['duration_days'] . ' days');

    $insert = $pdo->prepare("INSERT INTO user_memberships (user_id, plan_id, starts_at, expires_at, status, approved_by)
                             VALUES (:user_id, :plan_id, :starts_at, :expires_at, 'active', :approved_by)");
    $insert->execute([
        'user_id' => $userId,
        'plan_id' => $planId,
        'starts_at' => $startsAt->format('Y-m-d H:i:s'),
        'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        'approved_by' => $approvedBy,
    ]);
}

function create_payment_record(int $userId, ?int $planId, float $amount, string $method, ?string $reference, ?string $notes, ?int $requestId, ?int $recordedBy): void {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO payment_records (user_id, plan_id, request_id, amount, method, reference_code, notes, recorded_by)
                           VALUES (:user_id, :plan_id, :request_id, :amount, :method, :reference_code, :notes, :recorded_by)");
    $stmt->execute([
        'user_id' => $userId,
        'plan_id' => $planId,
        'request_id' => $requestId,
        'amount' => $amount,
        'method' => $method,
        'reference_code' => $reference,
        'notes' => $notes,
        'recorded_by' => $recordedBy,
    ]);
}

function community_session_key(): string {
    if (empty($_SESSION['community_viewer_key'])) {
        $_SESSION['community_viewer_key'] = session_id() ?: bin2hex(random_bytes(12));
    }
    return (string)$_SESSION['community_viewer_key'];
}

function track_topic_view(int $topicId): void {
    global $pdo;
    $sessionKey = community_session_key();
    $userId = current_user()['id'] ?? null;
    $stmt = $pdo->prepare('INSERT IGNORE INTO community_topic_views (topic_id, user_id, session_key) VALUES (:topic_id, :user_id, :session_key)');
    $stmt->execute([
        'topic_id' => $topicId,
        'user_id' => $userId,
        'session_key' => $sessionKey,
    ]);
}

function user_has_propped(int $topicId, int $userId): bool {
    global $pdo;
    $stmt = $pdo->prepare('SELECT id FROM community_topic_props WHERE topic_id = :topic_id AND user_id = :user_id LIMIT 1');
    $stmt->execute(['topic_id' => $topicId, 'user_id' => $userId]);
    return (bool)$stmt->fetchColumn();
}

function toggle_topic_prop(int $topicId, int $userId): bool {
    global $pdo;
    if (user_has_propped($topicId, $userId)) {
        $pdo->prepare('DELETE FROM community_topic_props WHERE topic_id = :topic_id AND user_id = :user_id')
            ->execute(['topic_id' => $topicId, 'user_id' => $userId]);
        return false;
    }
    $pdo->prepare('INSERT INTO community_topic_props (topic_id, user_id) VALUES (:topic_id, :user_id)')
        ->execute(['topic_id' => $topicId, 'user_id' => $userId]);
    return true;
}

function format_count(int $count): string {
    if ($count >= 1000000) return round($count / 1000000, 1) . 'M';
    if ($count >= 1000) return round($count / 1000, 1) . 'K';
    return (string)$count;
}

function is_image_path(?string $path): bool {
    if (!$path) return false;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true);
}

function attachment_preview(?string $path, ?string $name = null, string $class = ''): string {
    if (!$path) return '';
    $safeName = e($name ?: basename($path));
    $href = url($path);
    if (is_image_path($path)) {
        return '<div class="attachment-preview ' . e($class) . '"><a href="' . $href . '" target="_blank"><img src="' . $href . '" alt="Attachment" class="community-image"></a><div class="attachment-caption">📎 ' . $safeName . '</div></div>';
    }
    return '<a class="attachment-file ' . e($class) . '" href="' . $href . '" target="_blank">📎 ' . $safeName . '</a>';
}

function topic_comment_tree(int $topicId): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT c.*, u.name, u.role
                           FROM community_comments c
                           INNER JOIN users u ON u.id = c.user_id
                           WHERE c.topic_id = :topic_id
                           ORDER BY c.created_at ASC, c.id ASC");
    $stmt->execute(['topic_id' => $topicId]);
    $comments = $stmt->fetchAll();
    $byParent = [];
    foreach ($comments as $comment) {
        $byParent[$comment['parent_id'] ?? 0][] = $comment;
    }
    return $byParent;
}

function render_comment_branch(array $byParent, int $topicId, int $parentId = 0, int $depth = 0): void {
    if (empty($byParent[$parentId])) return;
    foreach ($byParent[$parentId] as $comment) {
        $replyClass = $depth > 0 ? ' comment-reply' : '';
        echo '<div class="community-comment glass-card reveal' . $replyClass . '">';
        echo '<div class="d-flex justify-content-between align-items-start gap-3">';
        echo '<div><div class="community-author">' . e($comment['name']) . '</div><div class="community-meta">' . e($comment['created_at']) . '</div></div>';
        echo '<span class="tiny-pill">' . e(ucfirst($comment['role'])) . '</span>';
        echo '</div>';
        echo '<div class="comment-body mt-3">' . nl2br(e($comment['comment'])) . '</div>';
        echo attachment_preview($comment['attachment_path'] ?? null, $comment['attachment_name'] ?? null, 'mt-3');
        if (is_logged_in()) {
            echo '<button class="btn btn-sm btn-soft mt-3" type="button" data-bs-toggle="collapse" data-bs-target="#replyForm' . (int)$comment['id'] . '">Reply</button>';
            echo '<div class="collapse mt-3" id="replyForm' . (int)$comment['id'] . '">';
            echo '<form method="post" action="' . url('community/topic.php?id=' . $topicId) . '" enctype="multipart/form-data" class="reply-form">';
            echo '<input type="hidden" name="action" value="reply_comment">';
            echo '<input type="hidden" name="parent_id" value="' . (int)$comment['id'] . '">';
            echo '<textarea class="form-control mb-2" name="comment" rows="2" placeholder="Add your reply..." required></textarea>';
            echo '<input type="file" class="form-control mb-2" name="attachment">';
            echo '<button class="btn btn-sm btn-primary">Send reply</button>';
            echo '</form>';
            echo '</div>';
        }
        render_comment_branch($byParent, $topicId, (int)$comment['id'], $depth + 1);
        echo '</div>';
    }
}

function base_path_fix_note(): string {
    return "If links break on XAMPP, open config/config.php and set \$baseUrl to your actual folder name inside htdocs.";
}
