<?php
require_once __DIR__ . '/includes/functions.php';

function table_exists(PDO $pdo, string $dbName, string $table): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = :table");
    $stmt->execute(['db' => $dbName, 'table' => $table]);
    return (int)$stmt->fetchColumn() > 0;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host'] ?? '127.0.0.1');
    $port = trim($_POST['db_port'] ?? '3306');
    $name = trim($_POST['db_name'] ?? 'franciskwesa_db');
    $user = trim($_POST['db_user'] ?? 'root');
    $pass = (string)($_POST['db_pass'] ?? '');
    $charset = 'utf8mb4';

    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $name, $charset);
        $testPdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        if (table_exists($testPdo, $name, 'users')) {
            $errors[] = 'It looks like the database is already installed. If you want a fresh install, empty the database first.';
        } else {
            $sql = file_get_contents(__DIR__ . '/database.sql');
            $testPdo->exec($sql);

            $config = "<?php\nreturn [\n"
                . "    'host' => " . var_export($host, true) . ",\n"
                . "    'port' => " . var_export($port, true) . ",\n"
                . "    'name' => " . var_export($name, true) . ",\n"
                . "    'user' => " . var_export($user, true) . ",\n"
                . "    'pass' => " . var_export($pass, true) . ",\n"
                . "    'charset' => 'utf8mb4',\n"
                . "];\n";
            file_put_contents(__DIR__ . '/config/database.php', $config);
            $success = 'Installation complete. You can now open the site and login.';
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install - Francis Kwesa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 mb-3">Francis Kwesa MySQL Installer</h1>
                    <p class="text-muted">Create an empty MySQL database first, then enter its details below.</p>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?> <a href="index.php">Open site</a></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Database Host</label>
                                <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($_POST['db_host'] ?? '127.0.0.1') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Port</label>
                                <input type="text" name="db_port" class="form-control" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Database Name</label>
                                <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($_POST['db_name'] ?? 'franciskwesa_db') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Database User</label>
                                <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Database Password</label>
                                <input type="password" name="db_pass" class="form-control" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
                            </div>
                        </div>
                        <button class="btn btn-primary mt-4" type="submit">Install Now</button>
                    </form>

                    <hr>
                    <p class="mb-1"><strong>Demo admin:</strong> admin@franciskwesa.com / password123</p>
                    <p class="mb-0"><strong>Demo student:</strong> student@example.com / student123</p>
                    <p class="mt-3 small text-muted"><?= htmlspecialchars(base_path_fix_note()) ?></p><p class="small text-muted mb-0">This build includes dark/light mode, community props, viewer tracking, and file attachments for topics and replies.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
