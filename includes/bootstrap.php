<?php
declare(strict_types=1);

require_once __DIR__ . '/catalog.php';

const APP_ROOT = __DIR__ . DIRECTORY_SEPARATOR . '..';

function app_config(): array
{
    static $config;
    if ($config === null) {
        $configFile = APP_ROOT . DIRECTORY_SEPARATOR . 'config.php';
        if (!is_file($configFile)) {
            throw new RuntimeException('Copy config.example.php to config.php and enter your database settings.');
        }
        $loadedConfig = require $configFile;
        if (!is_array($loadedConfig)) {
            throw new RuntimeException('config.php must return an array of settings.');
        }
        $config = $loadedConfig + [
            'base_url' => '',
            'contact_email' => '',
            'site_url' => '',
            'cookie_secure' => false,
            'esewa_product_code' => '',
            'esewa_secret_key' => '',
            'esewa_form_url' => 'https://rc-epay.esewa.com.np/api/epay/main/v2/form',
            'esewa_status_url' => 'https://rc.esewa.com.np/api/epay/transaction/status/',
        ];
        foreach (['db_host', 'db_name', 'db_user', 'db_password'] as $required) {
            if (!isset($config[$required]) || !is_string($config[$required]) || ($required !== 'db_password' && trim($config[$required]) === '')) {
                throw new RuntimeException("Set a non-empty {$required} value in config.php.");
            }
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', $config['db_name'])) {
            throw new RuntimeException('DB_NAME may contain only letters, numbers, and underscores.');
        }
        if (preg_match('/[\s;#]/', $config['db_host'])) {
            throw new RuntimeException('The database host in config.php contains invalid characters.');
        }
        $port = isset($config['db_port'])
            ? filter_var($config['db_port'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]])
            : false;
        if ($port === false) {
            throw new RuntimeException('Set a valid db_port value in config.php.');
        }
        $config['db_port'] = $port;
        $baseUrl = trim((string) $config['base_url']);
        if ($baseUrl !== '' && ($baseUrl[0] !== '/' || str_starts_with($baseUrl, '//') || str_contains($baseUrl, '\\') || str_contains($baseUrl, '?') || str_contains($baseUrl, '#') || preg_match('~(?:^|/)\.\.?(/|$)~', $baseUrl))) {
            throw new RuntimeException('base_url must be blank or a root-relative path such as /the-flower-foundry.');
        }
        $config['base_url'] = rtrim($baseUrl, '/');
    }
    return $config;
}

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('flower_foundry_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => base_path() ?: '/',
        'secure' => app_config()['cookie_secure'],
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function base_path(): string
{
    $configured = (string) app_config()['base_url'];
    if ($configured !== '') {
        return $configured;
    }
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/.');
    return $dir === '' ? '' : $dir;
}

function url(string $page = 'home', array $params = []): string
{
    return base_path() . '/index.php?' . http_build_query(['page' => $page] + $params);
}

function redirect_to(string $page = 'home', array $params = []): never
{
    header('Location: ' . url($page, $params), true, 303);
    exit;
}

function db(): PDO
{
    static $connection;
    $config = app_config();
    if ($connection instanceof PDO) {
        return $connection;
    }
    $dsn = 'mysql:host=' . $config['db_host'] . ';port=' . $config['db_port'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4';
    $connection = new PDO($dsn, $config['db_user'], $config['db_password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $connection;
}

function log_app_error(string $context, Throwable $error): void
{
    $line = sprintf("[%s] %s: %s\n", gmdate('c'), $context, $error->getMessage());
    $logFile = APP_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'flower-foundry.log';
    if (@file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX) === false) {
        error_log(rtrim($line));
    }
}

function install_schema(PDO $pdo): void
{
    $schema = file_get_contents(APP_ROOT . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Could not read database/schema.sql.');
    }
    $statements = preg_split('/;\s*(?:\r?\n|$)/', trim($schema));
    foreach ($statements as $statement) {
        if (trim($statement) !== '') {
            $pdo->exec($statement);
        }
    }
}

function slugify(string $text): string
{
    $text = strtolower(str_replace('&', ' and ', $text));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string) $slug, '-') ?: 'item';
}

function unique_slug(PDO $pdo, string $name, int $exceptId = 0): string
{
    $base = slugify($name);
    $slug = $base;
    $suffix = 2;
    $query = $pdo->prepare('SELECT id FROM products WHERE slug = ? AND id <> ? LIMIT 1');
    while (true) {
        $query->execute([$slug, $exceptId]);
        if (!$query->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $suffix++;
    }
}

function seed_empty_database(PDO $pdo): void
{
    $productCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if ($productCount === 0) {
        $insert = $pdo->prepare(
            'INSERT INTO products (slug, name, description, category, kind, price, image, occasions, featured, active, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)'
        );
        foreach (seed_products() as $product) {
            $insert->execute([
                unique_slug($pdo, $product['name']),
                $product['name'],
                $product['description'],
                $product['category'],
                $product['kind'],
                $product['price'],
                $product['image'],
                $product['occasions'],
                $product['featured'],
                (int) floor(microtime(true) * 1000),
            ]);
        }
    }
}

function create_initial_admin(PDO $pdo, string $email, string $password): void
{
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Enter a valid admin email address.');
    }
    if (strlen($password) < 15) {
        throw new RuntimeException('The admin password must contain at least 15 characters.');
    }
    if ((int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0) {
        throw new RuntimeException('An admin account already exists; setup will not replace it.');
    }
    $existing = $pdo->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
    $existing->execute([$email]);
    if ($existing->fetchColumn()) {
        throw new RuntimeException('That email already belongs to an account. Choose another admin email.');
    }
    $insert = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role, created_at) VALUES (?, ?, ?, ?, 'admin', ?)");
    $insert->execute(['Shop Admin', $email, SHOP['phone'], password_hash($password, PASSWORD_DEFAULT), (int) floor(microtime(true) * 1000)]);
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function npr(int|float|string|null $amount): string
{
    return 'Rs ' . number_format((float) ($amount ?? 0), 0, '.', ',');
}

function image_url(?string $path): string
{
    $path = (string) $path;
    if (str_starts_with($path, '/images/')) {
        return base_path() . '/public' . $path;
    }
    if (str_starts_with($path, '/uploads/')) {
        return url('upload', ['file' => basename($path)]);
    }
    return $path;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = (string) ($_POST['csrf_token'] ?? '');
    if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('The form expired. Please go back, refresh the page, and try again.');
    }
}

function current_user(): ?array
{
    start_app_session();
    $token = (string) ($_SESSION['auth_token'] ?? '');
    if ($token === '') {
        return null;
    }
    $query = db()->prepare(
        'SELECT u.id, u.name, u.email, u.phone, u.role, s.expires_at
         FROM sessions s JOIN users u ON u.id = s.user_id WHERE s.token = ? LIMIT 1'
    );
    $query->execute([$token]);
    $user = $query->fetch();
    if (!$user || (int) $user['expires_at'] < (int) floor(microtime(true) * 1000)) {
        db()->prepare('DELETE FROM sessions WHERE token = ?')->execute([$token]);
        unset($_SESSION['auth_token']);
        return null;
    }
    return $user;
}

function login_user(int $userId): void
{
    start_app_session();
    session_regenerate_id(true);
    $token = bin2hex(random_bytes(32));
    $expires = (int) floor(microtime(true) * 1000) + 7 * 24 * 60 * 60 * 1000;
    db()->prepare('INSERT INTO sessions (token, user_id, expires_at) VALUES (?, ?, ?)')->execute([$token, $userId, $expires]);
    $_SESSION['auth_token'] = $token;
}

function logout_user(): void
{
    start_app_session();
    $token = (string) ($_SESSION['auth_token'] ?? '');
    if ($token !== '') {
        db()->prepare('DELETE FROM sessions WHERE token = ?')->execute([$token]);
    }
    $_SESSION = [];
    session_regenerate_id(true);
}

function require_user(string $nextPage = 'account'): array
{
    $user = current_user();
    if (!$user) {
        redirect_to('login', ['next' => $nextPage]);
    }
    return $user;
}

function require_admin(): array
{
    $user = current_user();
    if (!$user) {
        redirect_to('login', ['next' => 'admin']);
    }
    if ($user['role'] !== 'admin') {
        redirect_to('home');
    }
    return $user;
}

function nepal_today(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Asia/Kathmandu')))->format('Y-m-d');
}

function date_label(?string $date): string
{
    if (!$date) {
        return '';
    }
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $parsed ? $parsed->format('D, j M Y') : '';
}

function datetime_label(int|string|null $milliseconds): string
{
    if (!$milliseconds) {
        return '';
    }
    return (new DateTimeImmutable('@' . (int) floor((int) $milliseconds / 1000)))
        ->setTimezone(new DateTimeZone('Asia/Kathmandu'))
        ->format('j M Y, H:i');
}
