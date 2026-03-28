<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();
$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if (!in_array($type, ['project', 'resource'], true) || $id < 1) {
    die('Invalid download request.');
}

$table = $type === 'project' ? 'projects' : 'resources';
$stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $id]);
$item = $stmt->fetch();

if (!$item || empty($item['file_path'])) {
    die('File not found.');
}

$membership = fetch_membership_for_user((int)$user['id']);
$level = get_user_plan_level($membership);

if (!can_access_level($item['access_level'], $level)) {
    set_flash('error', 'Your current plan cannot access this file.');
    redirect_to('membership.php');
}

$full = BASE_PATH . '/' . ltrim($item['file_path'], '/');
if (!is_file($full)) {
    die('Stored file was not found on the server.');
}

$pdo->prepare('INSERT INTO downloads (user_id, item_type, item_id) VALUES (:user_id, :item_type, :item_id)')
    ->execute(['user_id' => $user['id'], 'item_type' => $type, 'item_id' => $id]);

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($full) . '"');
header('Content-Length: ' . filesize($full));
readfile($full);
exit;
