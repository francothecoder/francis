<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Lusaka');

define('APP_NAME', 'Academic Support Hub');
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/francis');

$db = require __DIR__ . '/database.php';

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $db['host'],
    $db['port'],
    $db['name'],
    $db['charset']
);

try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($current !== 'install.php') {
        http_response_code(500);
        die('Database connection failed. Update config/database.php and import database.sql. Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
}
