<?php
require_once __DIR__ . '/includes/bootstrap.php';
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$reset = $token !== '' ? find_valid_password_reset($token) : null;
if (!$reset) {
    set_flash('error', 'That password reset link is invalid or has expired.');
    redirect_to('forgot_password.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!ensure_password_strength($password)) {
        set_flash('error', strong_password_message());
        redirect_to('reset_password.php?token=' . urlencode($token));
    }
    if ($password !== $confirm) {
        set_flash('error', 'Passwords do not match.');
        redirect_to('reset_password.php?token=' . urlencode($token));
    }
    $pdo->prepare('UPDATE users SET password = :password WHERE id = :id')->execute([
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'id' => (int) $reset['user_id'],
    ]);
    mark_password_reset_used((int) $reset['id']);
    set_flash('success', 'Your password was updated successfully. You can sign in now.');
    redirect_to('login.php');
}
$pageTitle = 'Reset password';
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card card-soft p-4">
            <h1 class="h3 mb-3">Choose a new password</h1>
            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="mb-3">
                    <label class="form-label">New password</label>
                    <input class="form-control" type="password" name="password" required>
                    <div class="form-text"><?= e(strong_password_message()) ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm new password</label>
                    <input class="form-control" type="password" name="confirm_password" required>
                </div>
                <button class="btn btn-primary w-100">Reset password</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
