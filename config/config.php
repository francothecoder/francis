<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Lusaka');

$basePath = dirname(__DIR__);
/*
|--------------------------------------------------------------------------
| Base URL
|--------------------------------------------------------------------------
| Change this to the folder name inside htdocs.
| Example: /francis or /franciskwesa
*/
$baseUrl = '/francis';

define('APP_NAME', 'Francis Kwesa');
define('BASE_PATH', $basePath);
define('BASE_URL', $baseUrl);

$dbConfigFile = __DIR__ . '/database.php';
$db = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'name' => 'franciskwesa_db',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
];

if (file_exists($dbConfigFile)) {
    $loaded = require $dbConfigFile;
    if (is_array($loaded)) {
        $db = array_merge($db, $loaded);
    }
}

define('DB_HOST', $db['host']);
define('DB_PORT', $db['port']);
define('DB_NAME', $db['name']);
define('DB_USER', $db['user']);
define('DB_PASS', $db['pass']);
define('DB_CHARSET', $db['charset']);

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($current !== 'install.php') {
        die('Database connection failed. Please check config/database.php or run install.php again. Error: ' . htmlspecialchars($e->getMessage()));
    }
}
