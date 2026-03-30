<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    update_setting('platform_commission_percent', (string) max(0, min(90, (float) ($_POST['platform_commission_percent'] ?? 20))));
    update_setting('platform_name', clean_text($_POST['platform_name'] ?? APP_NAME));
    set_flash('success', 'Platform settings updated.');
    redirect_to('admin/settings.php');
}

$pageTitle = 'Platform settings';
include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card card-soft p-4">
            <h1 class="h3 mb-3">Platform settings</h1>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label">Platform name</label>
                    <input class="form-control" type="text" name="platform_name" value="<?= e(get_setting('platform_name', APP_NAME)) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Platform commission percent</label>
                    <input class="form-control" type="number" min="0" max="90" step="0.01" name="platform_commission_percent" value="<?= e(get_setting('platform_commission_percent', '20')) ?>">
                </div>
                <button class="btn btn-primary">Save settings</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
