<?php
require_once __DIR__ . '/../includes/functions.php';
if (is_logged_in()) {
    redirect_to(is_admin() ? 'admin/index.php' : 'student/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $university = trim($_POST['university'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';

    if ($password !== $confirm) {
        set_flash('error', 'Passwords do not match.');
        redirect_to('auth/register.php');
    }

    $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $check->execute(['email' => $email]);
    if ($check->fetch()) {
        set_flash('error', 'An account with that email already exists.');
        redirect_to('auth/register.php');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, university) VALUES (:name, :email, :password, 'student', :university)");
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'password' => $hash,
        'university' => $university,
    ]);

    $id = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $_SESSION['user'] = $stmt->fetch();
    set_flash('success', 'Registration successful. Welcome to the Student Hub.');
    redirect_to('student/dashboard.php');
}
$pageTitle='Create Account'; require_once __DIR__ . '/../includes/header.php'; ?>
<div class="auth-card">
    <div class="card card-soft p-4">
        <h1 class="fw-bold mb-3">Create Account</h1>
        <form method="post">
            <div class="mb-3"><label class="form-label">Full Name</label><input class="form-control" name="name" required></div>
            <div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
            <div class="mb-3"><label class="form-label">University</label><input class="form-control" name="university" required></div>
            <div class="mb-3"><label class="form-label">Password</label><input class="form-control" type="password" name="password" required></div>
            <div class="mb-3"><label class="form-label">Confirm Password</label><input class="form-control" type="password" name="password_confirmation" required></div>
            <button class="btn btn-primary w-100">Create Account</button>
        </form>
        <div class="small text-muted mt-3">Already have an account? <a href="<?= url('auth/login.php') ?>">Login here</a></div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
