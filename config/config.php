<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Lusaka');

function env_value(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return (string) $value;
}

define('APP_NAME', env_value('APP_NAME', 'Academic Support Hub'));
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', rtrim(env_value('BASE_URL', '/academic_support_hub'), '/'));
define('APP_ENV', env_value('APP_ENV', 'production'));
define('APP_DEBUG', APP_ENV !== 'production');

if (!APP_DEBUG) {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

$db = require __DIR__ . '/database.php';
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    env_value('DB_HOST', $db['host']),
    env_value('DB_PORT', (string) $db['port']),
    env_value('DB_NAME', $db['name']),
    env_value('DB_CHARSET', $db['charset'])
);

try {
    $pdo = new PDO($dsn, env_value('DB_USER', $db['user']), env_value('DB_PASS', $db['pass']), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    die('Database connection failed. Update config/database.php or environment variables, then import database.sql. Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
