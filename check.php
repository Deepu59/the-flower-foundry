<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$root = __DIR__;
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';
$configExists = is_file($configPath);

echo 'PHP version: ' . PHP_VERSION . "\n";
echo 'PHP 8.1 or later: ' . (PHP_VERSION_ID >= 80100 ? 'yes' : 'no') . "\n";
echo 'config.php exists: ' . ($configExists ? 'yes' : 'no') . "\n";

foreach (['includes', 'assets', 'data', 'data/uploads'] as $folder) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $folder);
    echo $folder . ' exists: ' . (is_dir($path) ? 'yes' : 'no') . "\n";
}
$uploadsPath = $root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'uploads';
echo 'data writable: ' . (is_dir($root . DIRECTORY_SEPARATOR . 'data') && is_writable($root . DIRECTORY_SEPARATOR . 'data') ? 'yes' : 'no') . "\n";
echo 'data/uploads writable: ' . (is_dir($uploadsPath) && is_writable($uploadsPath) ? 'yes' : 'no') . "\n";

$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'fileinfo', 'session', 'json'];
$missingExtensions = [];
foreach ($requiredExtensions as $extension) {
    $loaded = extension_loaded($extension);
    echo 'Extension ' . $extension . ': ' . ($loaded ? 'loaded' : 'MISSING') . "\n";
    if (!$loaded) {
        $missingExtensions[] = $extension;
    }
}
echo 'Extension curl (optional for eSewa status checks): ' . (extension_loaded('curl') ? 'loaded' : 'not loaded') . "\n";

if (!$configExists) {
    echo 'Database connection: config.php is missing.' . "\n";
    exit;
}

if (PHP_VERSION_ID < 80100) {
    echo 'Database connection: not checked because PHP 8.1 or later is required.' . "\n";
    exit;
}

require_once $root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'bootstrap.php';

try {
    $config = app_config();
    if ($missingExtensions) {
        echo 'Database connection: not checked because required PHP extensions are missing.' . "\n";
        exit;
    }
    db()->query('SELECT 1');
    echo 'Database connection: OK' . "\n";
} catch (Throwable $error) {
    log_app_error('Hosting diagnostic database check failed', $error);
    $message = $error->getMessage();
    if (isset($config) && is_array($config)) {
        foreach (['db_password', 'db_user', 'db_host', 'db_name'] as $key) {
            $value = $config[$key] ?? '';
            if (is_string($value) && $value !== '') {
                $message = str_replace($value, '[redacted]', $message);
            }
        }
    }
    echo 'Database connection: ' . $message . "\n";
}
