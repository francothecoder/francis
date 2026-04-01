<?php
require_once __DIR__ . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(clean_text($_POST['email'] ?? ''));
    if (validate_email($email)) {
        $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        if ($user) {
            $token = create_password_reset_token((int) $user['id']);
            send_password_reset_email($user, $token);
        }
    }
    set_flash('success', 'If that email exists in the system, a password reset link has been sent.');
    redirect_to('forgot_password.php');
}
$pageTitle = 'Forgot password';
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card card-soft p-4">
            <h1 class="h3 mb-3">Forgot your password?</h1>
            <p class="text-muted">Enter your email address and we will send you a secure password reset link.</p>
            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input class="form-control" type="email" name="email" required>
                </div>
                <button class="btn btn-primary w-100">Send reset link</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
