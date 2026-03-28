<?php
require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)');
    $stmt->execute([
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'subject' => trim($_POST['subject'] ?? ''),
        'message' => trim($_POST['message'] ?? ''),
    ]);
    set_flash('success', 'Your message has been sent.');
    redirect_to('contact.php');
}
$pageTitle='Contact'; require_once __DIR__ . '/includes/header.php'; ?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-soft p-4">
            <h1 class="fw-bold">Contact Us</h1>
            <p>Need help with a project, website, resource, or membership issue? Reach out directly.</p>
            <p><strong>Phone:</strong> <?= e(get_setting('site_phone', '+260963884318')) ?><br><strong>Email:</strong> <?= e(get_setting('site_email', 'hello@franciskwesa.com')) ?></p>
            <a class="btn btn-success" href="https://wa.me/<?= e(get_setting('site_whatsapp', '260963884318')) ?>" target="_blank">Chat on WhatsApp</a>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-soft p-4">
            <h4>Quick Message</h4>
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
                    <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
                    <div class="col-12"><label class="form-label">Subject</label><input class="form-control" name="subject" required></div>
                    <div class="col-12"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="5" required></textarea></div>
                </div>
                <button class="btn btn-primary mt-3">Send Message</button>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
