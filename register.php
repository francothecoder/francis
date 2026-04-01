<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = clean_text($_POST['name'] ?? '');
    $email = strtolower(clean_text($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $phoneNumber = normalize_zambian_phone(clean_text($_POST['phone_number'] ?? ''));
    $university = clean_text($_POST['university'] ?? '');
    $bio = multi_line_text($_POST['bio'] ?? '');
    $qualificationDetails = multi_line_text($_POST['qualification_details'] ?? '');
    flash_old_input($_POST);

    if ($name === '' || !validate_email($email) || !ensure_password_strength($password) || !in_array($role, ['student', 'tutor'], true) || ($phoneNumber !== '' && !valid_zambian_phone($phoneNumber))) {
        set_flash('error', 'Complete the form correctly. ' . strong_password_message() . ' Use a valid Zambia phone number if provided.');
        redirect_to('register.php');
    }

    $exists = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
    $exists->execute(['email' => $email]);
    if ((int) $exists->fetchColumn() > 0) {
        set_flash('error', 'That email address is already registered.');
        redirect_to('register.php');
    }

    $avatarPath = null;
    try {
        if (!empty($_FILES['avatar']['name'])) {
            $avatarPath = upload_file($_FILES['avatar'], 'avatars', ['jpg', 'jpeg', 'png', 'webp'], ['image/jpeg', 'image/png', 'image/webp'], 2097152);
        }
    } catch (Throwable $e) {
        set_flash('error', $e->getMessage());
        redirect_to('register.php');
    }

    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, phone_number, university, bio, avatar_path) VALUES (:name, :email, :password, :role, :phone_number, :university, :bio, :avatar_path)');
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'phone_number' => $phoneNumber !== '' ? $phoneNumber : null,
        'university' => $university,
        'bio' => $bio,
        'avatar_path' => $avatarPath,
    ]);
    $userId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO user_rewards (user_id, current_xp, current_level, reward_credits, streak_days, last_activity_date) VALUES (:user_id, 10, 1, 0, 1, CURDATE())')
        ->execute(['user_id' => $userId]);

    if ($role === 'tutor') {
        $pdo->prepare('INSERT INTO tutor_profiles (user_id, headline, bio, subjects, qualification_details, starting_price, min_offer_price, is_verified) VALUES (:user_id, :headline, :bio, :subjects, :qualification_details, 25.00, 15.00, 0)')
            ->execute([
                'user_id' => $userId,
                'headline' => 'Academic tutor',
                'bio' => $bio ?: 'Tutor profile awaiting update.',
                'subjects' => 'General',
                'qualification_details' => $qualificationDetails !== '' ? $qualificationDetails : 'Please add your academic qualifications and teaching background.',
            ]);
        create_notification($userId, 'Tutor profile created', 'Your tutor account was created and is awaiting admin verification.', 'tutor/dashboard.php');
    } else {
        create_notification($userId, 'Welcome onboard', 'Your student account is ready. Explore tutors, plans, and guided support.', 'student/dashboard.php');
    }

    grant_xp($userId, 'register_account', 10);
    notify_admins_about_registration(['name' => $name, 'email' => $email, 'role' => $role, 'phone_number' => $phoneNumber]);

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $_SESSION['user'] = $stmt->fetch();
    clear_old_input();

    if ($role === 'tutor') {
        redirect_to('tutor/dashboard.php');
    }
    redirect_to('student/dashboard.php');
}

$pageTitle = 'Create account';
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card card-soft p-4">
            <h1 class="h3 mb-3">Create your account</h1>
            <form method="post" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full name</label>
                        <input class="form-control" type="text" name="name" value="<?= e(old('name')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email address</label>
                        <input class="form-control" type="email" name="email" value="<?= e(old('email')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input class="form-control" type="password" name="password" required>
                        <div class="form-text"><?= e(strong_password_message()) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account type</label>
                        <select class="form-select" name="role" required>
                            <option value="student" <?= old('role', 'student') === 'student' ? 'selected' : '' ?>>Student</option>
                            <option value="tutor" <?= old('role') === 'tutor' ? 'selected' : '' ?>>Tutor</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone number</label>
                        <input class="form-control" type="text" name="phone_number" value="<?= e(old('phone_number')) ?>" placeholder="097XXXXXXX or 26097XXXXXXX">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Institution</label>
                        <input class="form-control" type="text" name="university" value="<?= e(old('university')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Profile photo</label>
                        <input class="form-control" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                    <div class="col-12 tutor-only d-none">
                        <label class="form-label">Qualification details (for tutors)</label>
                        <textarea class="form-control" name="qualification_details" rows="3" placeholder="Degree, diploma, certifications, teaching background"><?= e(old('qualification_details')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Short bio</label>
                        <textarea class="form-control" name="bio" rows="4"><?= e(old('bio')) ?></textarea>
                    </div>
                </div>
                <button class="btn btn-primary mt-4">Create account</button>
            </form>
            <script>
            (() => {
              const role = document.querySelector('select[name="role"]');
              const blocks = document.querySelectorAll('.tutor-only');
              function syncTutor(){ const show = role.value === 'tutor'; blocks.forEach(el => el.classList.toggle('d-none', !show)); }
              role.addEventListener('change', syncTutor); syncTutor();
            })();
            </script>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
