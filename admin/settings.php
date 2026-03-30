<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    update_setting('platform_commission_percent', (string) max(0, min(90, (float) ($_POST['platform_commission_percent'] ?? 20))));
    update_setting('platform_name', clean_text($_POST['platform_name'] ?? APP_NAME));
    update_setting('lenco_mode', in_array($_POST['lenco_mode'] ?? 'sandbox', ['sandbox','live'], true) ? $_POST['lenco_mode'] : 'sandbox');
    update_setting('lenco_public_key', trim((string) ($_POST['lenco_public_key'] ?? '')));
    update_setting('lenco_secret_key', trim((string) ($_POST['lenco_secret_key'] ?? '')));
    update_setting('lenco_callback_url', trim((string) ($_POST['lenco_callback_url'] ?? '')));
    update_setting('smtp_enabled', isset($_POST['smtp_enabled']) ? '1' : '0');
    update_setting('smtp_host', trim((string) ($_POST['smtp_host'] ?? '')));
    update_setting('smtp_port', trim((string) ($_POST['smtp_port'] ?? '587')));
    update_setting('smtp_username', trim((string) ($_POST['smtp_username'] ?? '')));
    update_setting('smtp_password', trim((string) ($_POST['smtp_password'] ?? '')));
    update_setting('smtp_encryption', in_array($_POST['smtp_encryption'] ?? 'tls', ['tls','ssl','none'], true) ? $_POST['smtp_encryption'] : 'tls');
    update_setting('smtp_from_email', trim((string) ($_POST['smtp_from_email'] ?? '')));
    update_setting('smtp_from_name', trim((string) ($_POST['smtp_from_name'] ?? '')));
    update_setting('smtp_reply_to_email', trim((string) ($_POST['smtp_reply_to_email'] ?? '')));
    update_setting('smtp_reply_to_name', trim((string) ($_POST['smtp_reply_to_name'] ?? '')));
    update_setting('email_notifications_enabled', isset($_POST['email_notifications_enabled']) ? '1' : '0');
    set_flash('success', 'Platform settings updated.');
    redirect_to('admin/settings.php');
}
$pageTitle = 'Platform settings';
include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center g-4">
    <div class="col-xl-8">
        <div class="card card-soft p-4 mb-4">
            <h1 class="h3 mb-3">Platform settings</h1>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="mb-3"><label class="form-label">Platform name</label><input class="form-control" type="text" name="platform_name" value="<?= e(get_setting('platform_name', APP_NAME)) ?>"></div>
                <div class="mb-3"><label class="form-label">Platform commission percent</label><input class="form-control" type="number" min="0" max="90" step="0.01" name="platform_commission_percent" value="<?= e(get_setting('platform_commission_percent', '20')) ?>"></div>
                <hr>
                <h2 class="h5 mb-3">Lenco mobile money integration</h2>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Mode</label><select class="form-select" name="lenco_mode"><option value="sandbox" <?= get_setting('lenco_mode','sandbox')==='sandbox'?'selected':'' ?>>Sandbox</option><option value="live" <?= get_setting('lenco_mode')==='live'?'selected':'' ?>>Live</option></select></div>
                    <div class="col-md-8"><label class="form-label">Callback URL</label><input class="form-control" type="url" name="lenco_callback_url" value="<?= e(get_setting('lenco_callback_url')) ?>" placeholder="https://yourdomain.com/academic_support_hub/webhook/lenco.php"></div>
                    <div class="col-md-6"><label class="form-label">Public key</label><input class="form-control" type="text" name="lenco_public_key" value="<?= e(get_setting('lenco_public_key')) ?>"></div>
                    <div class="col-md-6"><label class="form-label">Secret key</label><input class="form-control" type="password" name="lenco_secret_key" value="<?= e(get_setting('lenco_secret_key')) ?>"></div>
                </div>
                <div class="form-text mt-2">Use the callback URL in your Lenco dashboard so successful collections can ping your server automatically.</div>
                <hr class="my-4">
                <h2 class="h5 mb-3">SMTP email notifications (PHPMailer)</h2>
                <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="smtp_enabled" name="smtp_enabled" <?= smtp_enabled() ? 'checked' : '' ?>><label class="form-check-label" for="smtp_enabled">Enable SMTP sending</label></div>
                <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="email_notifications_enabled" name="email_notifications_enabled" <?= email_notifications_enabled() ? 'checked' : '' ?>><label class="form-check-label" for="email_notifications_enabled">Send user notification emails</label></div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">SMTP host</label><input class="form-control" type="text" name="smtp_host" value="<?= e(get_setting('smtp_host')) ?>" placeholder="smtp.yourdomain.com"></div>
                    <div class="col-md-3"><label class="form-label">SMTP port</label><input class="form-control" type="number" name="smtp_port" value="<?= e(get_setting('smtp_port','587')) ?>"></div>
                    <div class="col-md-3"><label class="form-label">Encryption</label><select class="form-select" name="smtp_encryption"><option value="tls" <?= get_setting('smtp_encryption','tls')==='tls'?'selected':'' ?>>TLS</option><option value="ssl" <?= get_setting('smtp_encryption')==='ssl'?'selected':'' ?>>SSL</option><option value="none" <?= get_setting('smtp_encryption')==='none'?'selected':'' ?>>None</option></select></div>
                    <div class="col-md-6"><label class="form-label">SMTP username</label><input class="form-control" type="text" name="smtp_username" value="<?= e(get_setting('smtp_username')) ?>"></div>
                    <div class="col-md-6"><label class="form-label">SMTP password</label><input class="form-control" type="password" name="smtp_password" value="<?= e(get_setting('smtp_password')) ?>"></div>
                    <div class="col-md-6"><label class="form-label">From email</label><input class="form-control" type="email" name="smtp_from_email" value="<?= e(get_setting('smtp_from_email')) ?>"></div>
                    <div class="col-md-6"><label class="form-label">From name</label><input class="form-control" type="text" name="smtp_from_name" value="<?= e(get_setting('smtp_from_name', get_setting('platform_name', APP_NAME))) ?>"></div>
                    <div class="col-md-6"><label class="form-label">Reply-to email</label><input class="form-control" type="email" name="smtp_reply_to_email" value="<?= e(get_setting('smtp_reply_to_email')) ?>"></div>
                    <div class="col-md-6"><label class="form-label">Reply-to name</label><input class="form-control" type="text" name="smtp_reply_to_name" value="<?= e(get_setting('smtp_reply_to_name', get_setting('smtp_from_name', get_setting('platform_name', APP_NAME)))) ?>"></div>
                </div>
                <button class="btn btn-primary mt-4">Save settings</button>
            </form>
        </div>
        <div class="card card-soft p-4">
            <div class="section-title mb-2">Webhook endpoint</div>
            <code><?= e(app_url('webhook/lenco.php')) ?></code>
            <p class="text-muted small mt-2 mb-0">Configure the full public URL above in your Lenco dashboard. The endpoint stores webhook payloads and updates matching payments.</p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
