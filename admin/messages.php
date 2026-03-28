<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$messages = $pdo->query('SELECT * FROM contact_messages ORDER BY id DESC')->fetchAll();
$pageTitle='Contact Messages'; require_once __DIR__ . '/../includes/header.php'; ?>
<div class="card card-soft p-4">
    <h1 class="fw-bold mb-3">Contact Messages</h1>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Name</th><th>Email</th><th>Subject</th><th>Message</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach($messages as $message): ?>
                    <tr>
                        <td><?= e($message['name']) ?></td>
                        <td><?= e($message['email']) ?></td>
                        <td><?= e($message['subject']) ?></td>
                        <td><?= e($message['message']) ?></td>
                        <td><?= e($message['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
