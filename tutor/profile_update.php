<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['tutor']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('tutor/dashboard.php');
}
verify_csrf();
$user = current_user();
$headline = clean_text($_POST['headline'] ?? '');
$subjects = clean_text($_POST['subjects'] ?? '');
$bio = multi_line_text($_POST['bio'] ?? '');
$qualificationDetails = multi_line_text($_POST['qualification_details'] ?? '');
$startingPrice = (float) ($_POST['starting_price'] ?? 0);
$minOfferPrice = (float) ($_POST['min_offer_price'] ?? 0);

if ($headline === '' || $subjects === '' || $bio === '' || $qualificationDetails === '' || $startingPrice <= 0 || $minOfferPrice <= 0) {
    set_flash('error', 'Complete all tutor profile fields.');
    redirect_to('tutor/dashboard.php');
}

$stmt = $pdo->prepare('UPDATE tutor_profiles SET headline = :headline, subjects = :subjects, bio = :bio, qualification_details = :qualification_details, starting_price = :starting_price, min_offer_price = :min_offer_price WHERE user_id = :user_id');
$stmt->execute([
    'headline' => $headline,
    'subjects' => $subjects,
    'bio' => $bio,
    'qualification_details' => $qualificationDetails,
    'starting_price' => $startingPrice,
    'min_offer_price' => $minOfferPrice,
    'user_id' => $user['id'],
]);

set_flash('success', 'Tutor profile updated successfully.');
redirect_to('tutor/dashboard.php');
