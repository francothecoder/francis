<?php
require_once __DIR__ . '/../includes/functions.php';
if (is_logged_in()) {
    redirect_to(is_admin() ? 'admin/index.php' : 'student/dashboard.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        set_flash('success', 'Welcome back, ' . $user['name']);
        redirect_to($user['role'] === 'admin' ? 'admin/index.php' : 'student/dashboard.php');
    }
    set_flash('error', 'Invalid login details.');
}
$pageTitle='Login'; require_once __DIR__ . '/../includes/header.php'; ?>
<div class="auth-card">
    <div class="card card-soft p-4">
        <h1 class="fw-bold mb-3">Login</h1>
        <form method="post">
            <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
            <button class="btn btn-primary w-100">Login</button>
        </form>
        <div class="small text-muted mt-3">Admin: admin@franciskwesa.com / password123<br>Student: student@example.com / student123</div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
