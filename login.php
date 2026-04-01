<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    if (user_role() === 'admin') {
        redirect_to('admin/dashboard.php');
    } elseif (user_role() === 'tutor') {
        redirect_to('tutor/dashboard.php');
    }
    redirect_to('student/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(clean_text($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    flash_old_input(['email' => $email]);

    if (!validate_email($email) || $password === '') {
        set_flash('error', 'Enter a valid email and password.');
        redirect_to('login.php');
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        set_flash('error', 'Invalid login details.');
        redirect_to('login.php');
    }

    $_SESSION['user'] = $user;
    clear_old_input();
    touch_daily_streak((int) $user['id']);
    grant_xp((int) $user['id'], 'daily_login', 2);
    mark_notifications_read((int) $user['id']);

    if ($user['role'] === 'admin') {
        redirect_to('admin/dashboard.php');
    } elseif ($user['role'] === 'tutor') {
        redirect_to('tutor/dashboard.php');
    }
    redirect_to('student/dashboard.php');
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card card-soft p-4">
            <h1 class="h3 mb-3">Sign in</h1>
            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input class="form-control" type="email" name="email" value="<?= e(old('email')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input class="form-control" type="password" name="password" required>
                </div>
                <button class="btn btn-primary w-100">Login</button>
            </form>
            <div class="d-flex justify-content-between small text-muted mt-3"><span>New here? <a href="<?= app_url('register.php') ?>">Create an account</a></span><a href="<?= app_url('forgot_password.php') ?>">Forgot password?</a></div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
