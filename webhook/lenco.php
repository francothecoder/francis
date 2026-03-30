<?php
require_once __DIR__ . '/../includes/bootstrap.php';
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit; }
$payload = file_get_contents('php://input') ?: '{}';
$signature = $_SERVER['HTTP_X_LENCO_SIGNATURE'] ?? '';
$secret = lenco_secret_key();
if ($secret !== '' && $signature !== '') {
    $hashKey = hash('sha256', $secret);
    if (!hash_equals(hash_hmac('sha512', $payload, $hashKey), $signature)) {
        app_log('lenco_webhook', 'Invalid signature', ['payload' => $payload]);
        http_response_code(401);
        exit;
    }
}
$data = json_decode($payload, true);
$eventName = is_array($data) ? ($data['event'] ?? $data['event_name'] ?? null) : null;
$reference = is_array($data) ? ($data['data']['reference'] ?? $data['data']['clientReference'] ?? $data['reference'] ?? null) : null;
$pdo->prepare('INSERT INTO lenco_webhook_logs (event_name, gateway_reference, payload) VALUES (:event_name, :gateway_reference, :payload)')->execute(['event_name'=>$eventName, 'gateway_reference'=>$reference, 'payload'=>$payload]);
app_log('lenco_webhook', 'Webhook received', ['event' => $eventName, 'reference' => $reference, 'payload' => $data]);
if ($reference) {
    $stmt = $pdo->prepare('SELECT id FROM payment_transactions WHERE gateway_reference = :gateway_reference LIMIT 1');
    $stmt->execute(['gateway_reference'=>$reference]);
    $paymentId = (int) ($stmt->fetchColumn() ?: 0);
    if ($paymentId > 0) {
        update_payment_gateway_snapshot($paymentId, is_array($data) ? $data : ['data'=>[]]);
        $status = strtolower((string) ($data['data']['status'] ?? ($eventName === 'transaction.successful' ? 'successful' : ($eventName === 'transaction.failed' ? 'failed' : ''))));
        if ($status === 'successful') {
            $pdo->prepare("UPDATE payment_transactions SET status='paid', paid_at = NOW() WHERE id = :id")->execute(['id'=>$paymentId]);
            $paymentRowStmt = $pdo->prepare('SELECT * FROM payment_transactions WHERE id = :id LIMIT 1');
            $paymentRowStmt->execute(['id'=>$paymentId]);
            $payment = $paymentRowStmt->fetch();
            if ($payment) {
                if (!empty($payment['plan_id'])) {
                    activate_subscription_from_payment($paymentId);
                } else {
                    activate_session_from_payment($paymentId);
                }
            }
        } elseif ($status === 'failed') {
            $pdo->prepare("UPDATE payment_transactions SET status='failed' WHERE id = :id")->execute(['id'=>$paymentId]);
        } else {
            $pdo->prepare("UPDATE payment_transactions SET status='pending' WHERE id = :id")->execute(['id'=>$paymentId]);
        }
    }
}
http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['received' => true]);
