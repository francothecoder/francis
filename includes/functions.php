<?php
declare(strict_types=1);

function app_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return BASE_URL . ($path !== '' ? '/' . $path : '');
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect_to(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : app_url($path)));
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function get_flash(string $type): ?string
{
    if (!isset($_SESSION['flash'][$type])) {
        return null;
    }
    $message = $_SESSION['flash'][$type];
    unset($_SESSION['flash'][$type]);
    return $message;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function refresh_current_user(): void
{
    global $pdo;
    if (empty($_SESSION['user']['id']) || !isset($pdo)) {
        return;
    }
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int) $_SESSION['user']['id']]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user'] = $user;
    } else {
        unset($_SESSION['user']);
    }
}

function is_logged_in(): bool { return !empty($_SESSION['user']); }
function user_role(): string { return $_SESSION['user']['role'] ?? ''; }

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Please sign in to continue.');
        redirect_to('login.php');
    }
}

function require_role(array $roles): void
{
    require_login();
    if (!in_array(user_role(), $roles, true)) {
        http_response_code(403);
        include BASE_PATH . '/403.php';
        exit;
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid form token. Please refresh and try again.');
    }
}

function old(string $key, string $default = ''): string { return $_SESSION['old'][$key] ?? $default; }
function flash_old_input(array $data): void { $_SESSION['old'] = $data; }
function clear_old_input(): void { unset($_SESSION['old']); }

function get_setting(string $key, string $default = ''): string
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = :setting_key LIMIT 1');
    $stmt->execute(['setting_key' => $key]);
    $row = $stmt->fetch();
    return (string) ($row['setting_value'] ?? $default);
}

function update_setting(string $key, string $value): void
{
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:setting_key, :setting_value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $stmt->execute(['setting_key' => $key, 'setting_value' => $value]);
}

function ensure_directory(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('Unable to create directory: ' . $path);
    }
}

function app_log(string $channel, string $message, array $context = []): void
{
    $logDir = BASE_PATH . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    $entry = [
        'time' => date('Y-m-d H:i:s'),
        'channel' => $channel,
        'message' => $message,
        'context' => $context,
    ];
    @file_put_contents($logDir . '/' . preg_replace('/[^a-z0-9_-]+/i', '_', $channel) . '.log', json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
}

function smtp_enabled(): bool { return get_setting('smtp_enabled', '0') === '1'; }
function smtp_host(): string { return get_setting('smtp_host', ''); }
function smtp_port(): int { return (int) get_setting('smtp_port', '587'); }
function smtp_username(): string { return get_setting('smtp_username', ''); }
function smtp_password(): string { return get_setting('smtp_password', ''); }
function smtp_encryption(): string { return get_setting('smtp_encryption', 'tls'); }
function smtp_from_email(): string { return get_setting('smtp_from_email', ''); }
function smtp_from_name(): string { return get_setting('smtp_from_name', get_setting('platform_name', APP_NAME)); }
function smtp_reply_to_email(): string { return get_setting('smtp_reply_to_email', smtp_from_email()); }
function smtp_reply_to_name(): string { return get_setting('smtp_reply_to_name', smtp_from_name()); }
function email_notifications_enabled(): bool { return get_setting('email_notifications_enabled', '1') === '1'; }

function strong_password_message(): string
{
    return 'Password must be at least 8 characters.';
}

function base_email_shell(string $title, string $bodyHtml, ?string $buttonLabel = null, ?string $buttonUrl = null): string
{
    $platform = e(get_setting('platform_name', APP_NAME));
    $button = '';
    if ($buttonLabel && $buttonUrl) {
        $button = '<p style="margin:20px 0 0"><a href="' . e($buttonUrl) . '" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:10px;font-weight:700">' . e($buttonLabel) . '</a></p>';
    }
    return '<div style="font-family:Inter,Arial,sans-serif;background:#f8fafc;padding:24px">'
        . '<div style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:18px;padding:28px;border:1px solid #e2e8f0">'
        . '<div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#64748b;margin-bottom:10px">' . $platform . '</div>'
        . '<h2 style="margin:0 0 12px;color:#0f172a">' . e($title) . '</h2>'
        . $bodyHtml
        . $button
        . '<p style="margin:20px 0 0;color:#94a3b8;font-size:13px">This email was sent by ' . $platform . '.</p>'
        . '</div></div>';
}

function admin_users(): array
{
    global $pdo;
    return $pdo->query("SELECT id, name, email FROM users WHERE role = 'admin' ORDER BY id ASC")->fetchAll();
}

function notify_admins_about_registration(array $user): void
{
    foreach (admin_users() as $admin) {
        $message = $user['name'] . ' created a new ' . $user['role'] . ' account.'
            . (!empty($user['phone_number']) ? ' Phone: ' . $user['phone_number'] . '.' : '')
            . (!empty($user['email']) ? ' Email: ' . $user['email'] . '.' : '');
        create_notification((int) $admin['id'], 'New user registration', $message, 'admin/dashboard.php');
    }
}

function create_password_reset_token(int $userId): string
{
    global $pdo;
    $token = bin2hex(random_bytes(32));
    $pdo->prepare('DELETE FROM password_resets WHERE user_id = :user_id OR expires_at < NOW()')->execute(['user_id' => $userId]);
    $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 60 MINUTE))')->execute([
        'user_id' => $userId,
        'token_hash' => password_hash($token, PASSWORD_DEFAULT),
    ]);
    return $token;
}

function find_valid_password_reset(string $token): ?array
{
    global $pdo;
    $stmt = $pdo->query('SELECT pr.*, u.email, u.name FROM password_resets pr INNER JOIN users u ON u.id = pr.user_id WHERE pr.used_at IS NULL AND pr.expires_at > NOW() ORDER BY pr.id DESC');
    foreach ($stmt->fetchAll() as $row) {
        if (password_verify($token, (string) $row['token_hash'])) {
            return $row;
        }
    }
    return null;
}

function mark_password_reset_used(int $resetId): void
{
    global $pdo;
    $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id')->execute(['id' => $resetId]);
}

function send_password_reset_email(array $user, string $token): void
{
    if (!validate_email((string) ($user['email'] ?? ''))) {
        return;
    }
    $link = app_url('reset_password.php?token=' . urlencode($token));
    $body = '<p style="margin:0 0 18px;color:#475569;line-height:1.7">We received a request to reset your password. This secure link expires in 60 minutes.</p>'
        . '<p style="margin:0 0 18px;color:#475569;line-height:1.7">If you did not request this change, you can safely ignore this email.</p>';
    $html = base_email_shell('Reset your password', $body, 'Choose a new password', $link);
    send_platform_email((string) $user['email'], (string) $user['name'], 'Password reset · ' . get_setting('platform_name', APP_NAME), $html, 'Reset your password: ' . $link);
}

function normalize_zambian_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (str_starts_with($digits, '260') && strlen($digits) === 12) {
        return $digits;
    }
    if (strlen($digits) == 10 && $digits[0] === '0') {
        return '26' . $digits;
    }
    if (strlen($digits) == 9 && in_array($digits[0], ['7', '9'], true)) {
        return '260' . $digits;
    }
    return $digits;
}

function valid_zambian_phone(string $phone): bool
{
    return (bool) preg_match('/^260(?:7|9)\d{8}$/', $phone);
}

function provider_label(string $provider): string
{
    $provider = strtolower(trim($provider));
    return ['mtn' => 'MTN', 'airtel' => 'AIRTEL', 'zamtel' => 'ZAMTEL'][$provider] ?? strtoupper($provider);
}


function generate_unique_gateway_reference(string $prefix = 'REQ-'): string
{
    global $pdo;
    $prefix = strtoupper(trim($prefix));
    if ($prefix === '') $prefix = 'REQ-';
    do {
        $reference = $prefix . strtoupper(bin2hex(random_bytes(6)));
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM payment_transactions WHERE gateway_reference = :reference');
        $stmt->execute(['reference' => $reference]);
        $exists = (int) $stmt->fetchColumn() > 0;
    } while ($exists);
    return $reference;
}

function uploaded_payment_proof_path(?array $file): ?string
{
    if (!$file) return null;
    return upload_file(
        $file,
        'payment_proofs',
        ['jpg','jpeg','png','pdf','webp'],
        ['image/jpeg','image/png','application/pdf','image/webp'],
        6 * 1024 * 1024
    );
}

function create_help_request_payment_transaction(int $requestId, int $studentId, int $tutorId, float $finalAmount, float $baseAmount, float $discountAmount, float $rewardCreditValue, float $platformFee, float $tutorEarnings, string $provider, string $phoneNumber, string $gateway = 'lenco', ?string $notes = null, ?string $manualReference = null, ?string $manualProofPath = null): int
{
    global $pdo;
    $reference = generate_unique_gateway_reference('REQ-');
    $gatewayStatus = $gateway === 'manual' ? 'submitted' : 'pending';
    $status = $gateway === 'manual' ? 'held' : 'pending';
    $stmt = $pdo->prepare('INSERT INTO payment_transactions (request_id, student_id, tutor_id, payment_type, amount, base_amount, discount_amount, reward_credit_value, platform_fee, tutor_earnings, provider, phone_number, gateway, gateway_reference, gateway_status, status, notes, manual_reference, manual_proof_path) VALUES (:request_id, :student_id, :tutor_id, "help_request", :amount, :base_amount, :discount_amount, :reward_credit_value, :platform_fee, :tutor_earnings, :provider, :phone_number, :gateway, :gateway_reference, :gateway_status, :status, :notes, :manual_reference, :manual_proof_path)');
    $stmt->execute([
        'request_id' => $requestId,
        'student_id' => $studentId,
        'tutor_id' => $tutorId,
        'amount' => round($finalAmount, 2),
        'base_amount' => round($baseAmount, 2),
        'discount_amount' => round($discountAmount, 2),
        'reward_credit_value' => round($rewardCreditValue, 2),
        'platform_fee' => round($platformFee, 2),
        'tutor_earnings' => round($tutorEarnings, 2),
        'provider' => $provider,
        'phone_number' => $phoneNumber,
        'gateway' => $gateway,
        'gateway_reference' => $reference,
        'gateway_status' => $gatewayStatus,
        'status' => $status,
        'notes' => $notes,
        'manual_reference' => $manualReference,
        'manual_proof_path' => $manualProofPath,
    ]);
    return (int) $pdo->lastInsertId();
}

function create_subscription_payment_transaction(int $planId, int $studentId, float $amount, string $provider, string $phoneNumber, string $gateway = 'lenco', ?string $notes = null, ?string $manualReference = null, ?string $manualProofPath = null): int
{
    global $pdo;
    $reference = generate_unique_gateway_reference('SUB-');
    $gatewayStatus = $gateway === 'manual' ? 'submitted' : 'pending';
    $status = $gateway === 'manual' ? 'held' : 'pending';
    $stmt = $pdo->prepare('INSERT INTO payment_transactions (plan_id, student_id, payment_type, amount, base_amount, provider, phone_number, gateway, gateway_reference, gateway_status, status, notes, manual_reference, manual_proof_path) VALUES (:plan_id, :student_id, "subscription", :amount, :base_amount, :provider, :phone_number, :gateway, :gateway_reference, :gateway_status, :status, :notes, :manual_reference, :manual_proof_path)');
    $stmt->execute([
        'plan_id' => $planId,
        'student_id' => $studentId,
        'amount' => round($amount, 2),
        'base_amount' => round($amount, 2),
        'provider' => $provider,
        'phone_number' => $phoneNumber,
        'gateway' => $gateway,
        'gateway_reference' => $reference,
        'gateway_status' => $gatewayStatus,
        'status' => $status,
        'notes' => $notes,
        'manual_reference' => $manualReference,
        'manual_proof_path' => $manualProofPath,
    ]);
    return (int) $pdo->lastInsertId();
}

function admin_finalize_payment(int $paymentId, int $adminId, bool $approve, ?string $note = null): void
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM payment_transactions WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $paymentId]);
    $payment = $stmt->fetch();
    if (!$payment) throw new RuntimeException('Payment not found.');
    if ($approve) {
        $pdo->prepare("UPDATE payment_transactions SET status='paid', gateway_status='approved', paid_at = NOW(), approved_by = :approved_by, approved_at = NOW(), approval_notes = :approval_notes WHERE id = :id")->execute([
            'approved_by' => $adminId,
            'approval_notes' => $note,
            'id' => $paymentId,
        ]);
        if (!empty($payment['plan_id'])) {
            activate_subscription_from_payment($paymentId);
            create_notification((int) $payment['student_id'], 'Payment approved', 'Your subscription payment was approved manually by the admin team.', 'student/subscriptions.php');
        } else {
            activate_session_from_payment($paymentId);
            create_notification((int) $payment['student_id'], 'Payment approved', 'Your academic help payment was approved manually. Your study session is now available.', 'student/request_view.php?id=' . (int) $payment['request_id']);
            if (!empty($payment['tutor_id'])) {
                create_notification((int) $payment['tutor_id'], 'Manual payment approved', 'A student payment was approved manually. Please proceed with the guided support session.', 'tutor/session_view.php?request_id=' . (int) $payment['request_id']);
            }
        }
    } else {
        $pdo->prepare("UPDATE payment_transactions SET status='failed', gateway_status='rejected', approved_by = :approved_by, approved_at = NOW(), approval_notes = :approval_notes WHERE id = :id")->execute([
            'approved_by' => $adminId,
            'approval_notes' => $note,
            'id' => $paymentId,
        ]);
        create_notification((int) $payment['student_id'], 'Payment rejected', 'Your manual payment submission was not approved. Please review the note from admin and retry.', !empty($payment['plan_id']) ? 'student/subscriptions.php' : 'student/payment_status.php?payment_id=' . $paymentId);
    }
}

function payment_gateway_public_message(array $gatewayResponse): string
{
    $data = $gatewayResponse['data'] ?? [];
    $status = strtolower((string) ($data['status'] ?? ''));
    $reason = trim((string) ($data['reasonForFailure'] ?? $gatewayResponse['message'] ?? ''));
    if ($status === 'successful') {
        return 'Payment confirmed successfully.';
    }
    if ($status === 'pay-offline') {
        return 'Approve the mobile money prompt on your phone, then refresh this page.';
    }
    if ($status === 'pending') {
        return 'Payment request created. Wait a moment, then refresh to check the latest gateway status.';
    }
    if ($status === 'failed') {
        return $reason !== '' ? $reason : 'The mobile money request failed before authorization reached the phone.';
    }
    return trim((string) ($gatewayResponse['message'] ?? 'Payment request sent.')) ?: 'Payment request sent.';
}

function require_phpmailer(): void
{
    require_once BASE_PATH . '/vendor/phpmailer/src/Exception.php';
    require_once BASE_PATH . '/vendor/phpmailer/src/SMTP.php';
    require_once BASE_PATH . '/vendor/phpmailer/src/PHPMailer.php';
}

function send_platform_email(string $toEmail, string $toName, string $subject, string $htmlBody, ?string $textBody = null): bool
{
    if (!smtp_enabled()) {
        return false;
    }
    require_phpmailer();
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = smtp_host();
        $mail->Port = smtp_port();
        $mail->SMTPAuth = true;
        $mail->Username = smtp_username();
        $mail->Password = smtp_password();
        $mail->CharSet = 'UTF-8';
        $encryption = strtolower(trim(smtp_encryption()));
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->setFrom(smtp_from_email(), smtp_from_name());
        if (smtp_reply_to_email() !== '') {
            $mail->addReplyTo(smtp_reply_to_email(), smtp_reply_to_name());
        }
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody ?: trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], PHP_EOL, $htmlBody)));
        $mail->send();
        app_log('mail', 'Email sent', ['to' => $toEmail, 'subject' => $subject]);
        return true;
    } catch (Throwable $e) {
        app_log('mail', 'Email failed', ['to' => $toEmail, 'subject' => $subject, 'error' => $e->getMessage()]);
        return false;
    }
}

function send_user_notification_email(int $userId, string $title, string $message, ?string $targetPath = null): void
{
    if (!email_notifications_enabled()) {
        return;
    }
    global $pdo;
    $stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
    if (!$user || !validate_email((string) ($user['email'] ?? ''))) {
        return;
    }
    $link = $targetPath ? app_url($targetPath) : app_url();
    $platform = get_setting('platform_name', APP_NAME);
    $body = '<p style="margin:0 0 18px;color:#475569;line-height:1.7">' . nl2br(e($message)) . '</p>';
    $html = base_email_shell($title, $body, 'Open ' . $platform, $link);
    send_platform_email((string) $user['email'], (string) $user['name'], $title . ' · ' . $platform, $html, $message . PHP_EOL . $link);
}

function money(float|string|int $amount): string { return 'K' . number_format((float) $amount, 2); }

function status_badge(string $status): string
{
    $map = [
        'open' => 'warning', 'quoted' => 'info', 'accepted' => 'primary', 'awaiting_payment' => 'warning',
        'payment_pending' => 'warning', 'paid' => 'success', 'in_progress' => 'primary', 'completed' => 'success',
        'cancelled' => 'secondary', 'held' => 'warning', 'released' => 'success', 'pending' => 'warning',
        'requested' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'active' => 'success',
        'expired' => 'secondary', 'verified' => 'success', 'failed' => 'danger', 'submitted' => 'info'
    ];
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge text-bg-' . $class . '">' . e(ucwords(str_replace('_', ' ', $status))) . '</span>';
}

function validate_email(string $email): bool { return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }
function clean_text(string $value): string { return trim(preg_replace('/\s+/', ' ', $value) ?? ''); }
function multi_line_text(string $value): string { return trim((string) $value); }
function ensure_password_strength(string $password): bool { return strlen($password) >= 8; }

function upload_file(array $file, string $subdir, array $allowedExtensions, array $allowedMimeTypes, int $maxSize = 5242880): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed. Please try again.');
    if (($file['size'] ?? 0) > $maxSize) throw new RuntimeException('File is too large.');
    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) throw new RuntimeException('Invalid file type.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']) ?: '';
    if (!in_array($mimeType, $allowedMimeTypes, true)) throw new RuntimeException('Invalid file content.');
    $directory = BASE_PATH . '/uploads/' . trim($subdir, '/');
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) throw new RuntimeException('Upload folder could not be created.');
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $target = $directory . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) throw new RuntimeException('Failed to store uploaded file.');
    return 'uploads/' . trim($subdir, '/') . '/' . $filename;
}

function delete_file(?string $relativePath): void
{
    if (!$relativePath) return;
    $fullPath = BASE_PATH . '/' . ltrim($relativePath, '/');
    if (is_file($fullPath)) @unlink($fullPath);
}

function scalar_value(string $sql, array $params = []): mixed
{
    global $pdo; $stmt = $pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchColumn();
}
function count_value(string $sql, array $params = []): int { return (int) scalar_value($sql, $params); }

function xp_for_level(int $level): int { return max(100, $level * 100); }

function current_subscription(int $userId): ?array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT us.*, sp.name AS plan_name, sp.monthly_price, sp.help_discount_percent, sp.monthly_help_credits FROM user_subscriptions us INNER JOIN subscription_plans sp ON sp.id = us.plan_id WHERE us.user_id = :user_id AND us.status = 'active' AND us.ends_at >= NOW() ORDER BY us.ends_at DESC LIMIT 1");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetch() ?: null;
}

function subscription_rank(?string $accessLevel): int
{
    return ['free' => 0, 'starter' => 1, 'plus' => 2, 'pro' => 3][$accessLevel ?? 'free'] ?? 0;
}

function student_access_rank(int $userId): int
{
    $subscription = current_subscription($userId);
    if (!$subscription) return 0;
    return ['Starter Plan' => 1, 'Study Plus' => 2, 'Academic Pro' => 3][$subscription['plan_name']] ?? 0;
}

function can_access_resource(array $resource, ?array $user = null): bool
{
    $required = subscription_rank($resource['access_level'] ?? 'free');
    if ($required === 0) return true;
    if (!$user || ($user['role'] ?? '') !== 'student') return false;
    return student_access_rank((int) $user['id']) >= $required;
}

function platform_commission_percent(): float { return (float) get_setting('platform_commission_percent', '20'); }
function student_discount_percent(int $userId): float { $subscription = current_subscription($userId); return $subscription ? (float) $subscription['help_discount_percent'] : 0.0; }

function tutor_profile(int $userId): ?array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT tp.*, u.name, u.email, u.phone_number, u.university, u.bio AS user_bio, u.avatar_path FROM tutor_profiles tp INNER JOIN users u ON u.id = tp.user_id WHERE tp.user_id = :user_id LIMIT 1");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetch() ?: null;
}

function avatar_url(?string $path, string $name='Student'): string
{
    if (!$path) return 'https://ui-avatars.com/api/?background=0D8ABC&color=fff&name=' . rawurlencode($name);
    return app_url($path);
}

function grant_xp(int $userId, string $reason, int $points, ?int $referenceId = null): void
{
    global $pdo;
    if ($points <= 0) return;
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO xp_transactions (user_id, reason_key, points, reference_id) VALUES (:user_id, :reason_key, :points, :reference_id)')->execute([
            'user_id'=>$userId,'reason_key'=>$reason,'points'=>$points,'reference_id'=>$referenceId
        ]);
        $summaryStmt = $pdo->prepare('SELECT * FROM user_rewards WHERE user_id = :user_id LIMIT 1 FOR UPDATE');
        $summaryStmt->execute(['user_id'=>$userId]);
        $summary = $summaryStmt->fetch();
        if (!$summary) {
            $pdo->prepare('INSERT INTO user_rewards (user_id,current_xp,current_level,reward_credits,streak_days,last_activity_date) VALUES (:user_id,0,1,0,0,NULL)')->execute(['user_id'=>$userId]);
            $summary = ['current_xp'=>0,'current_level'=>1];
        }
        $newXp = (int)$summary['current_xp'] + $points;
        $level = (int)$summary['current_level'];
        while ($newXp >= xp_for_level($level)) { $newXp -= xp_for_level($level); $level++; }
        $creditBoost = intdiv($points, 50);
        $pdo->prepare('UPDATE user_rewards SET current_xp = :current_xp, current_level = :current_level, reward_credits = reward_credits + :reward_credits WHERE user_id = :user_id')->execute([
            'current_xp'=>$newXp,'current_level'=>$level,'reward_credits'=>$creditBoost,'user_id'=>$userId
        ]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function reward_summary(int $userId): array
{
    global $pdo; $stmt = $pdo->prepare('SELECT * FROM user_rewards WHERE user_id = :user_id LIMIT 1'); $stmt->execute(['user_id'=>$userId]);
    return $stmt->fetch() ?: ['user_id'=>$userId,'current_xp'=>0,'current_level'=>1,'reward_credits'=>0,'streak_days'=>0,'last_activity_date'=>null];
}

function touch_daily_streak(int $userId): void
{
    global $pdo;
    $summary = reward_summary($userId); $today = new DateTimeImmutable('today');
    $last = !empty($summary['last_activity_date']) ? new DateTimeImmutable($summary['last_activity_date']) : null;
    $streak = (int)$summary['streak_days'];
    if ($last && $last->format('Y-m-d') === $today->format('Y-m-d')) return;
    $streak = ($last && $last->modify('+1 day')->format('Y-m-d') === $today->format('Y-m-d')) ? $streak + 1 : 1;
    $pdo->prepare('INSERT INTO user_rewards (user_id,current_xp,current_level,reward_credits,streak_days,last_activity_date) VALUES (:user_id,0,1,0,:streak_days,:last_activity_date) ON DUPLICATE KEY UPDATE streak_days = VALUES(streak_days), last_activity_date = VALUES(last_activity_date)')->execute([
        'user_id'=>$userId,'streak_days'=>$streak,'last_activity_date'=>$today->format('Y-m-d')
    ]);
}

function calculate_final_help_price(float $basePrice, int $studentId, int $rewardCreditsToUse = 0): array
{
    $discountPercent = student_discount_percent($studentId);
    $discountAmount = ($basePrice * $discountPercent) / 100;
    $creditValue = max(0, $rewardCreditsToUse) * 2.5;
    $final = max(0, $basePrice - $discountAmount - $creditValue);
    return ['base_price'=>round($basePrice,2),'discount_percent'=>round($discountPercent,2),'discount_amount'=>round($discountAmount,2),'reward_credit_value'=>round($creditValue,2),'final_price'=>round($final,2)];
}

function create_notification(int $userId, string $title, string $message, ?string $targetPath = null): void
{
    global $pdo;
    $pdo->prepare('INSERT INTO notifications (user_id,title,message,target_path) VALUES (:user_id,:title,:message,:target_path)')->execute(['user_id'=>$userId,'title'=>$title,'message'=>$message,'target_path'=>$targetPath]);
    send_user_notification_email($userId, $title, $message, $targetPath);
}
function unread_notification_count(int $userId): int { return count_value('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0',['user_id'=>$userId]); }
function recent_notifications(int $userId, int $limit = 6): array { global $pdo; $stmt=$pdo->prepare('SELECT * FROM notifications WHERE user_id = :user_id ORDER BY id DESC LIMIT '.(int)$limit); $stmt->execute(['user_id'=>$userId]); return $stmt->fetchAll(); }
function mark_notifications_read(int $userId): void { global $pdo; $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id')->execute(['user_id'=>$userId]); }

function fetch_open_help_requests_for_tutor(int $tutorId): array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT hr.*, u.name AS student_name FROM help_requests hr INNER JOIN users u ON u.id = hr.student_id WHERE hr.status IN ('open','quoted') AND NOT EXISTS (SELECT 1 FROM help_offers ho WHERE ho.request_id = hr.id AND ho.tutor_id = :tutor_id) ORDER BY hr.urgency DESC, hr.id DESC");
    $stmt->execute(['tutor_id'=>$tutorId]); return $stmt->fetchAll();
}
function help_request_by_id(int $requestId): ?array { global $pdo; $stmt=$pdo->prepare("SELECT hr.*, u.name AS student_name FROM help_requests hr INNER JOIN users u ON u.id = hr.student_id WHERE hr.id = :id LIMIT 1"); $stmt->execute(['id'=>$requestId]); return $stmt->fetch() ?: null; }
function accepted_offer_for_request(int $requestId): ?array { global $pdo; $stmt=$pdo->prepare("SELECT ho.*, u.name AS tutor_name FROM help_offers ho INNER JOIN users u ON u.id = ho.tutor_id WHERE ho.request_id = :request_id AND ho.status = 'accepted' LIMIT 1"); $stmt->execute(['request_id'=>$requestId]); return $stmt->fetch() ?: null; }
function session_by_request(int $requestId): ?array { global $pdo; $stmt=$pdo->prepare("SELECT ss.*, su.name AS student_name, tu.name AS tutor_name FROM study_sessions ss INNER JOIN users su ON su.id = ss.student_id INNER JOIN users tu ON tu.id = ss.tutor_id WHERE ss.request_id = :request_id LIMIT 1"); $stmt->execute(['request_id'=>$requestId]); return $stmt->fetch() ?: null; }
function payment_for_request(int $requestId): ?array { global $pdo; $stmt=$pdo->prepare('SELECT * FROM payment_transactions WHERE request_id = :request_id ORDER BY id DESC LIMIT 1'); $stmt->execute(['request_id'=>$requestId]); return $stmt->fetch() ?: null; }
function payment_for_subscription(int $userId, int $planId): ?array { global $pdo; $stmt=$pdo->prepare('SELECT * FROM payment_transactions WHERE student_id = :student_id AND plan_id = :plan_id ORDER BY id DESC LIMIT 1'); $stmt->execute(['student_id'=>$userId,'plan_id'=>$planId]); return $stmt->fetch() ?: null; }

function tutor_wallet_balance(int $tutorId): float
{
    return (float) scalar_value("SELECT COALESCE(SUM(CASE WHEN transaction_type='credit' THEN amount ELSE -amount END),0) FROM tutor_wallet_transactions WHERE tutor_id = :tutor_id", ['tutor_id'=>$tutorId]);
}

function pending_payout_amount(int $tutorId): float
{
    return (float) scalar_value("SELECT COALESCE(SUM(amount),0) FROM payout_requests WHERE tutor_id = :tutor_id AND status IN ('requested','approved')", ['tutor_id' => $tutorId]);
}

function payout_summary(int $tutorId): array
{
    $available = tutor_wallet_balance($tutorId);
    $requested = (float) scalar_value("SELECT COALESCE(SUM(amount),0) FROM payout_requests WHERE tutor_id = :tutor_id AND status = 'requested'", ['tutor_id' => $tutorId]);
    $approved = (float) scalar_value("SELECT COALESCE(SUM(amount),0) FROM payout_requests WHERE tutor_id = :tutor_id AND status = 'approved'", ['tutor_id' => $tutorId]);
    $paid = (float) scalar_value("SELECT COALESCE(SUM(amount),0) FROM payout_requests WHERE tutor_id = :tutor_id AND status = 'paid'", ['tutor_id' => $tutorId]);
    return [
        'available' => round($available, 2),
        'requested' => round($requested, 2),
        'approved' => round($approved, 2),
        'paid' => round($paid, 2),
    ];
}

function relative_time(?string $datetime): string
{
    if (!$datetime) return ''; $date = new DateTimeImmutable($datetime); $now = new DateTimeImmutable(); $diff = $now->getTimestamp() - $date->getTimestamp();
    if ($diff < 60) return 'just now'; if ($diff < 3600) return floor($diff/60) . ' mins ago'; if ($diff < 86400) return floor($diff/3600) . ' hrs ago';
    return $date->format('d M Y H:i');
}

function resource_excerpt(array $resource, int $limit = 160): string
{
    $excerpt = trim((string)($resource['excerpt'] ?? '')) ?: trim(substr(strip_tags((string)($resource['content'] ?? '')),0,$limit));
    return mb_strlen($excerpt) > $limit ? mb_substr($excerpt,0,$limit-3) . '...' : $excerpt;
}

function lenco_enabled(): bool { return get_setting('lenco_secret_key', '') !== ''; }
function lenco_mode(): string { return get_setting('lenco_mode', 'sandbox'); }
function lenco_base_url(): string { return lenco_mode() === 'live' ? 'https://api.lenco.co/access/v2' : 'https://sandbox.lenco.co/access/v2'; }
function lenco_callback_url(): string { return rtrim(get_setting('lenco_callback_url', ''), '/'); }
function lenco_public_key(): string { return get_setting('lenco_public_key', ''); }
function lenco_secret_key(): string { return get_setting('lenco_secret_key', ''); }

function lenco_request(string $method, string $path, ?array $payload = null): array
{
    $url = rtrim(lenco_base_url(), '/') . '/' . ltrim($path, '/');
    $headers = [
        'Authorization: Bearer ' . lenco_secret_key(),
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
    }
    $response = curl_exec($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        app_log('lenco_gateway', 'Curl error', ['method' => $method, 'path' => $path, 'payload' => $payload, 'error' => $error]);
        throw new RuntimeException('Lenco request failed: ' . $error);
    }
    curl_close($ch);
    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        app_log('lenco_gateway', 'Invalid JSON response', ['method' => $method, 'path' => $path, 'payload' => $payload, 'http_status' => $statusCode, 'response' => $response]);
        throw new RuntimeException('Invalid payment gateway response.');
    }
    $decoded['_http_status'] = $statusCode;
    app_log('lenco_gateway', 'Gateway response', ['method' => $method, 'path' => $path, 'payload' => $payload, 'http_status' => $statusCode, 'response' => $decoded]);
    return $decoded;
}

function initiate_lenco_collection(array $payment, string $phone, string $provider, string $email, string $name): array
{
    if (!lenco_enabled()) throw new RuntimeException('Lenco payment settings are not configured yet.');
    $provider = strtolower(trim($provider));
    if (!in_array($provider, ['mtn','airtel','zamtel'], true)) throw new RuntimeException('Unsupported mobile money provider.');
    $normalizedPhone = normalize_zambian_phone($phone);
    if (!valid_zambian_phone($normalizedPhone)) {
        throw new RuntimeException('Enter a valid Zambia mobile money number in the format 097XXXXXXX, 077XXXXXXX, or 26097XXXXXXX.');
    }
    $payload = [
        'reference' => $payment['gateway_reference'],
        'amount' => number_format((float) $payment['amount'], 2, '.', ''),
        'currency' => 'ZMW',
        'bearer' => 'merchant',
        'phone' => $normalizedPhone,
        'country' => 'ZM',
        'provider' => $provider,
        'operator' => provider_label($provider),
        'email' => $email,
        'name' => $name,
        'callbackUrl' => lenco_callback_url() ?: app_url('webhook/lenco.php'),
    ];
    $payload = array_filter($payload, static fn($v) => $v !== null && $v !== '');
    $response = lenco_request('POST', 'collections/mobile-money', $payload);
    $status = strtolower((string) ($response['data']['status'] ?? ''));
    if (($response['status'] ?? false) === false && $status === '') {
        throw new RuntimeException(trim((string) ($response['message'] ?? 'Unable to start mobile money request.')) ?: 'Unable to start mobile money request.');
    }
    return $response;
}

function requery_lenco_collection(string $reference): array
{
    return lenco_request('GET', 'collections/status/' . rawurlencode($reference));
}

function update_payment_gateway_snapshot(int $paymentId, array $gatewayResponse): void
{
    global $pdo;
    $data = $gatewayResponse['data'] ?? [];
    $gatewayStatus = strtolower((string) ($data['status'] ?? (($gatewayResponse['status'] ?? false) ? 'pending' : 'failed')));
    $reason = trim((string) ($data['reasonForFailure'] ?? $gatewayResponse['message'] ?? ''));
    $pdo->prepare('UPDATE payment_transactions SET gateway_status = :gateway_status, gateway_payload = :gateway_payload, lenco_reference = COALESCE(:lenco_reference, lenco_reference), notes = :notes WHERE id = :id')->execute([
        'gateway_status' => $gatewayStatus,
        'gateway_payload' => json_encode($gatewayResponse, JSON_UNESCAPED_SLASHES),
        'lenco_reference' => $data['lencoReference'] ?? null,
        'notes' => $reason !== '' ? $reason : ($gatewayStatus === 'pay-offline' ? 'Awaiting approval on customer phone.' : ($gatewayStatus === 'successful' ? 'Payment confirmed by gateway.' : null)),
        'id' => $paymentId,
    ]);
}

function sync_payment_status(int $paymentId): array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM payment_transactions WHERE id = :id LIMIT 1');
    $stmt->execute(['id'=>$paymentId]);
    $payment = $stmt->fetch();
    if (!$payment) throw new RuntimeException('Payment not found.');
    if (($payment['gateway'] ?? 'lenco') === 'manual') return $payment;
    if (!$payment['gateway_reference']) return $payment;
    $response = requery_lenco_collection((string)$payment['gateway_reference']);
    update_payment_gateway_snapshot($paymentId, $response);
    $status = strtolower((string)($response['data']['status'] ?? 'pending'));
    if ($status === 'successful') {
        $pdo->prepare("UPDATE payment_transactions SET status='paid', paid_at = NOW() WHERE id = :id")->execute(['id'=>$paymentId]);
        if (!empty($payment['plan_id'])) {
            activate_subscription_from_payment($paymentId);
            create_notification((int)$payment['student_id'], 'Subscription paid', 'Your subscription payment was confirmed successfully.', 'student/subscriptions.php');
        } else {
            activate_session_from_payment($paymentId);
            create_notification((int)$payment['student_id'], 'Payment confirmed', 'Your academic help payment was confirmed successfully.', 'student/request_view.php?id=' . (int)$payment['request_id']);
        }
    } elseif ($status === 'failed') {
        $pdo->prepare("UPDATE payment_transactions SET status='failed' WHERE id = :id")->execute(['id'=>$paymentId]);
        create_notification((int)$payment['student_id'], 'Payment failed', trim((string)($response['data']['reasonForFailure'] ?? 'Your mobile money payment failed. Please retry or use manual approval.')), 'student/payment_status.php?payment_id=' . $paymentId);
    } else {
        $pdo->prepare("UPDATE payment_transactions SET status='pending' WHERE id = :id")->execute(['id'=>$paymentId]);
    }
    $stmt->execute(['id'=>$paymentId]);
    return $stmt->fetch() ?: [];
}
function activate_subscription_from_payment(int $paymentId): void
{
    global $pdo;
    $payment = scalar_value('SELECT COUNT(*) FROM payment_transactions WHERE id = :id AND status = "paid"', ['id'=>$paymentId]);
    if (!$payment) return;
    $rowStmt = $pdo->prepare('SELECT * FROM payment_transactions WHERE id = :id LIMIT 1');
    $rowStmt->execute(['id'=>$paymentId]);
    $paymentRow = $rowStmt->fetch();
    if (!$paymentRow || empty($paymentRow['plan_id'])) return;
    $exists = count_value('SELECT COUNT(*) FROM user_subscriptions WHERE payment_transaction_id = :payment_transaction_id', ['payment_transaction_id'=>$paymentId]);
    if ($exists > 0) return;
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE user_subscriptions SET status='expired' WHERE user_id = :user_id AND status='active'")->execute(['user_id'=>$paymentRow['student_id']]);
        $pdo->prepare("INSERT INTO user_subscriptions (user_id, plan_id, payment_transaction_id, starts_at, ends_at, status) VALUES (:user_id, :plan_id, :payment_transaction_id, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active')")->execute([
            'user_id'=>$paymentRow['student_id'], 'plan_id'=>$paymentRow['plan_id'], 'payment_transaction_id'=>$paymentId
        ]);
        $plan = scalar_value('SELECT name FROM subscription_plans WHERE id = :id', ['id'=>$paymentRow['plan_id']]);
        grant_xp((int)$paymentRow['student_id'], 'buy_subscription', 20, (int)$paymentRow['plan_id']);
        create_notification((int)$paymentRow['student_id'], 'Subscription activated', 'Your ' . $plan . ' plan is active.', 'student/dashboard.php');
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack(); throw $e;
    }
}

function activate_session_from_payment(int $paymentId): void
{
    global $pdo;
    $paymentStmt = $pdo->prepare('SELECT * FROM payment_transactions WHERE id = :id LIMIT 1');
    $paymentStmt->execute(['id'=>$paymentId]);
    $payment = $paymentStmt->fetch();
    if (!$payment || $payment['status'] !== 'paid' || empty($payment['request_id'])) return;
    if (session_by_request((int)$payment['request_id'])) return;
    $offer = accepted_offer_for_request((int)$payment['request_id']);
    if (!$offer) return;
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE help_requests SET status='in_progress', selected_tutor_id = :tutor_id, accepted_offer_id = :offer_id WHERE id = :id")->execute([
            'tutor_id'=>$offer['tutor_id'], 'offer_id'=>$offer['id'], 'id'=>$payment['request_id']
        ]);
        $pdo->prepare('INSERT INTO study_sessions (request_id, student_id, tutor_id, agreed_amount, final_amount, platform_fee, tutor_earnings, status, started_at) VALUES (:request_id, :student_id, :tutor_id, :agreed_amount, :final_amount, :platform_fee, :tutor_earnings, "in_progress", NOW())')->execute([
            'request_id'=>$payment['request_id'],'student_id'=>$payment['student_id'],'tutor_id'=>$payment['tutor_id'],'agreed_amount'=>$payment['base_amount'],'final_amount'=>$payment['amount'],'platform_fee'=>$payment['platform_fee'],'tutor_earnings'=>$payment['tutor_earnings']
        ]);
        $sessionId = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE payment_transactions SET session_id = :session_id WHERE id = :id')->execute(['session_id'=>$sessionId,'id'=>$paymentId]);
        create_notification((int)$payment['tutor_id'], 'Payment confirmed', 'A student completed payment. Your guided study session is now live.', 'tutor/session_view.php?request_id=' . (int)$payment['request_id']);
        grant_xp((int)$payment['student_id'], 'accept_help_offer', 5, (int)$payment['request_id']);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}

