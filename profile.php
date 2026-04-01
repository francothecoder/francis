<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$user = current_user();
$profile = user_role() === 'tutor' ? tutor_profile((int) $user['id']) : null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = clean_text($_POST['name'] ?? '');
    $email = strtolower(clean_text($_POST['email'] ?? ''));
    $phoneNumber = normalize_zambian_phone(clean_text($_POST['phone_number'] ?? ''));
    $university = clean_text($_POST['university'] ?? '');
    $bio = multi_line_text($_POST['bio'] ?? '');
    if ($name === '' || !validate_email($email) || ($phoneNumber !== '' && !valid_zambian_phone($phoneNumber))) {
        set_flash('error', 'Complete the profile with a valid email and Zambia phone number.');
        redirect_to('profile.php');
    }
    $exists = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id != :id');
    $exists->execute(['email' => $email, 'id' => $user['id']]);
    if ((int) $exists->fetchColumn() > 0) {
        set_flash('error', 'That email is already in use by another account.');
        redirect_to('profile.php');
    }
    $avatarPath = $user['avatar_path'] ?? null;
    try {
        if (!empty($_FILES['avatar']['name'])) {
            $avatarPath = upload_file($_FILES['avatar'], 'avatars', ['jpg','jpeg','png','webp'], ['image/jpeg','image/png','image/webp'], 2097152);
        }
    } catch (Throwable $e) {
        set_flash('error', $e->getMessage());
        redirect_to('profile.php');
    }
    $pdo->prepare('UPDATE users SET name = :name, email = :email, phone_number = :phone_number, university = :university, bio = :bio, avatar_path = :avatar_path WHERE id = :id')->execute([
        'name' => $name,
        'email' => $email,
        'phone_number' => $phoneNumber !== '' ? $phoneNumber : null,
        'university' => $university !== '' ? $university : null,
        'bio' => $bio !== '' ? $bio : null,
        'avatar_path' => $avatarPath,
        'id' => $user['id'],
    ]);
    if (user_role() === 'tutor') {
        $headline = clean_text($_POST['headline'] ?? '');
        $subjects = clean_text($_POST['subjects'] ?? '');
        $qualificationDetails = multi_line_text($_POST['qualification_details'] ?? '');
        $startingPrice = (float) ($_POST['starting_price'] ?? 0);
        $minOfferPrice = (float) ($_POST['min_offer_price'] ?? 0);
        if ($headline === '' || $subjects === '' || $qualificationDetails === '' || $startingPrice <= 0 || $minOfferPrice <= 0) {
            set_flash('error', 'Complete all tutor-specific profile fields, including qualification details.');
            redirect_to('profile.php');
        }
        $pdo->prepare('UPDATE tutor_profiles SET headline = :headline, subjects = :subjects, bio = :bio, qualification_details = :qualification_details, starting_price = :starting_price, min_offer_price = :min_offer_price WHERE user_id = :user_id')->execute([
            'headline' => $headline,
            'subjects' => $subjects,
            'bio' => $bio,
            'qualification_details' => $qualificationDetails,
            'starting_price' => $startingPrice,
            'min_offer_price' => $minOfferPrice,
            'user_id' => $user['id'],
        ]);
    }
    refresh_current_user();
    set_flash('success', 'Your profile was updated successfully.');
    redirect_to('profile.php');
}
$pageTitle = 'My profile';
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card card-soft p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">My profile</h1>
                    <div class="text-muted">Keep your contact details and academic profile up to date.</div>
                </div>
                <img src="<?= e(avatar_url($user['avatar_path'] ?? null, $user['name'] ?? 'User')) ?>" alt="" class="avatar-md rounded-circle">
            </div>
            <form method="post" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="col-md-6"><label class="form-label">Full name</label><input class="form-control" type="text" name="name" value="<?= e($user['name'] ?? '') ?>" required></div>
                <div class="col-md-6"><label class="form-label">Email address</label><input class="form-control" type="email" name="email" value="<?= e($user['email'] ?? '') ?>" required></div>
                <div class="col-md-6"><label class="form-label">Phone number</label><input class="form-control" type="text" name="phone_number" value="<?= e($user['phone_number'] ?? '') ?>" placeholder="097XXXXXXX or 26097XXXXXXX"></div>
                <div class="col-md-6"><label class="form-label">Institution</label><input class="form-control" type="text" name="university" value="<?= e($user['university'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">Profile photo</label><input class="form-control" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp"></div>
                <div class="col-12"><label class="form-label">Bio</label><textarea class="form-control" name="bio" rows="4"><?= e($user['bio'] ?? '') ?></textarea></div>
                <?php if (user_role() === 'tutor' && $profile): ?>
                    <div class="col-12"><hr><h2 class="h5 mb-0">Tutor details</h2><div class="text-muted small">Students will see these details when deciding who to learn with.</div></div>
                    <div class="col-md-6"><label class="form-label">Headline</label><input class="form-control" type="text" name="headline" value="<?= e($profile['headline'] ?? '') ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Subjects</label><input class="form-control" type="text" name="subjects" value="<?= e($profile['subjects'] ?? '') ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Starting price</label><input class="form-control" type="number" step="0.01" min="1" name="starting_price" value="<?= e((string) ($profile['starting_price'] ?? '25')) ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Minimum offer price</label><input class="form-control" type="number" step="0.01" min="1" name="min_offer_price" value="<?= e((string) ($profile['min_offer_price'] ?? '15')) ?>" required></div>
                    <div class="col-12"><label class="form-label">Qualification details</label><textarea class="form-control" name="qualification_details" rows="4" required><?= e($profile['qualification_details'] ?? '') ?></textarea></div>
                <?php endif; ?>
                <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Save profile</button><a class="btn btn-outline-secondary" href="<?= app_url() ?>">Back</a></div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
