<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/actions.php';
require_once __DIR__ . '/includes/pages.php';

try {
    app_config();
    start_app_session();
    $pdo = db();
    $pdo->query('SELECT 1 FROM products LIMIT 1');
} catch (PDOException $error) {
    log_app_error('Database connection or schema failed', $error);
    http_response_code(503);
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Database setup required</title><body style="font:16px system-ui;max-width:680px;margin:4rem auto;padding:0 1rem"><h1>Database setup required</h1><p>Check the database host, port, name, username and password in config.php. On a hosting account, import database/schema.sql using phpMyAdmin.</p><p>For diagnostics, temporarily open <code>check.php</code>. Detailed errors are logged privately under <code>data/</code>.</p></body></html>';
    exit;
} catch (RuntimeException $error) {
    log_app_error('Configuration failed', $error);
    http_response_code(503);
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Configuration required</title><body style="font:16px system-ui;max-width:680px;margin:4rem auto;padding:0 1rem"><h1>Configuration required</h1><p>Copy <code>config.example.php</code> to <code>config.php</code> and enter your database settings. See README.md.</p></body></html>';
    exit;
} catch (Throwable $error) {
    log_app_error('Application startup failed', $error);
    http_response_code(503);
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Application unavailable</title><body style="font:16px system-ui;max-width:680px;margin:4rem auto;padding:0 1rem"><h1>Application unavailable</h1><p>Check that the required PHP extensions are enabled and the database is configured correctly. Temporarily open <code>check.php</code> for diagnostics.</p></body></html>';
    exit;
}

$page = (string) ($_GET['page'] ?? 'home');
$titles = [
    'home' => 'Flower delivery in ' . SHOP['city'],
    'shop' => 'Shop flowers & gifts',
    'product' => 'Product',
    'cart' => 'Your cart',
    'checkout' => 'Checkout',
    'login' => 'Log in',
    'register' => 'Create account',
    'account' => 'My account',
    'order' => 'Your order',
    'contact' => 'Contact us',
    'about' => 'About us',
    'occasions' => 'Shop by occasion',
    'admin' => 'Admin dashboard',
    'admin_orders' => 'Orders',
    'admin_order' => 'Order details',
    'admin_products' => 'Products',
    'admin_product' => 'Edit product',
    'admin_messages' => 'Customer messages',
    'pay_esewa' => 'Pay with eSewa',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    process_post($pdo);
}

if ($page === 'upload') {
    $filename = (string) ($_GET['file'] ?? '');
    if (!preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', $filename)) {
        http_response_code(404);
        exit;
    }
    $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $filename;
    if (!is_file($filePath)) {
        http_response_code(404);
        exit;
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($filePath);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=3600');
    readfile($filePath);
    exit;
}

if ($page === 'esewa_failure') {
    $code = (string) ($_GET['code'] ?? '');
    if (!preg_match('/^FS-[A-Z0-9]{8}$/', $code)) {
        redirect_to('cart');
    }
    $update = $pdo->prepare("UPDATE orders SET payment_status = 'failed' WHERE code = ? AND payment_method = 'esewa' AND payment_status <> 'paid'");
    $update->execute([$code]);
    redirect_to('order', ['code' => $code, 'payment' => 'failed']);
}

if ($page === 'esewa_success') {
    $encoded = (string) ($_GET['data'] ?? '');
    $decoded = base64_decode($encoded, true);
    $data = $decoded === false ? null : json_decode($decoded, true);
    if (!is_array($data) || empty($data['signature']) || empty($data['signed_field_names'])) {
        http_response_code(400);
        exit('The eSewa response could not be verified.');
    }
    $signedNames = array_filter(explode(',', (string) $data['signed_field_names']), static fn (string $name): bool => preg_match('/^[a-zA-Z0-9_]+$/', $name) === 1);
    if (!$signedNames) {
        http_response_code(400);
        exit('The eSewa response has no valid signed fields.');
    }
    $config = app_config();
    if ($config['esewa_product_code'] === '' || $config['esewa_secret_key'] === '') {
        http_response_code(503);
        exit('Online payment is not configured.');
    }
    foreach (['status', 'total_amount', 'transaction_uuid', 'product_code'] as $requiredField) {
        if (!in_array($requiredField, $signedNames, true)) {
            http_response_code(400);
            exit('The eSewa response does not sign all required fields.');
        }
    }
    $parts = [];
    foreach ($signedNames as $name) {
        if (!array_key_exists($name, $data) || !is_scalar($data[$name])) {
            http_response_code(400);
            exit('The eSewa response is missing signed data.');
        }
        $parts[] = $name . '=' . (string) $data[$name];
    }
    $expectedSignature = base64_encode(hash_hmac('sha256', implode(',', $parts), (string) $config['esewa_secret_key'], true));
    if (!hash_equals($expectedSignature, (string) $data['signature'])) {
        http_response_code(400);
        exit('The eSewa response signature is invalid.');
    }

    $transactionUuid = (string) ($data['transaction_uuid'] ?? '');
    if (!preg_match('/^(FS-[A-Z0-9]{8})-\d+$/', $transactionUuid, $uuidMatch)) {
        http_response_code(400);
        exit('The eSewa transaction ID is invalid.');
    }
    $orderQuery = $pdo->prepare("SELECT * FROM orders WHERE code = ? AND payment_method = 'esewa' LIMIT 1");
    $orderQuery->execute([$uuidMatch[1]]);
    $order = $orderQuery->fetch();
    if (!$order) {
        http_response_code(404);
        exit('The order for this eSewa transaction was not found.');
    }
    if ($order['payment_status'] === 'paid') {
        unset($_SESSION['cart']);
        redirect_to('order', ['code' => $order['code'], 'paid' => 1]);
    }
    if (($data['status'] ?? '') !== 'COMPLETE' || (string) ($data['product_code'] ?? '') !== (string) $config['esewa_product_code'] || (float) ($data['total_amount'] ?? -1) !== (float) $order['total']) {
        http_response_code(400);
        exit('The eSewa response does not match the order.');
    }

    if (!function_exists('curl_init')) {
        error_log('eSewa status check skipped because the PHP cURL extension is unavailable.');
    } else {
        $statusUrl = (string) $config['esewa_status_url'] . '?' . http_build_query([
            'product_code' => $config['esewa_product_code'],
            'total_amount' => (int) $order['total'],
            'transaction_uuid' => $transactionUuid,
        ]);
        $curl = curl_init($statusUrl);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPGET => true,
        ]);
        $statusResponse = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        if ($statusResponse === false || $statusCode < 200 || $statusCode >= 300) {
            error_log('eSewa status check skipped: ' . ($curlError ?: 'HTTP ' . $statusCode));
        } else {
            $statusData = json_decode($statusResponse, true);
            if (is_array($statusData) && !empty($statusData['status']) && $statusData['status'] !== 'COMPLETE') {
                $pdo->prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ? AND payment_status <> 'paid'")->execute([$order['id']]);
                redirect_to('order', ['code' => $order['code'], 'payment' => 'failed']);
            }
        }
    }

    $reference = (string) ($data['transaction_code'] ?? '');
    $pdo->prepare(
        "UPDATE orders SET payment_status = 'paid', esewa_ref = ?, esewa_uuid = ?,
         status = CASE WHEN status = 'pending' THEN 'confirmed' ELSE status END WHERE id = ?"
    )->execute([$reference, $transactionUuid, $order['id']]);
    unset($_SESSION['cart']);
    redirect_to('order', ['code' => $order['code'], 'paid' => 1]);
}

$protectedPages = ['admin', 'admin_orders', 'admin_order', 'admin_products', 'admin_product', 'admin_messages'];
$user = current_user();
if ($page === 'cart') {
    require_user('cart');
    $user = current_user();
}
if ($page === 'checkout') {
    $user = require_user('checkout');
}
if ($page === 'account') {
    $user = require_user('account');
}
if (in_array($page, $protectedPages, true)) {
    $user = require_admin();
}
if (in_array($page, ['login', 'register'], true) && $user) {
    $next = safe_next_page((string) ($_GET['next'] ?? ''));
    redirect_to($next ?: ($user['role'] === 'admin' ? 'admin' : 'account'));
}
if (!isset($titles[$page])) {
    http_response_code(404);
    $page = 'not_found';
    $title = 'Page not found';
} else {
    $title = $titles[$page];
    if ($page === 'product') {
        $productTitle = $pdo->prepare('SELECT name FROM products WHERE slug = ? AND active = 1 LIMIT 1');
        $productTitle->execute([(string) ($_GET['slug'] ?? '')]);
        $title = (string) ($productTitle->fetchColumn() ?: $title);
    }
}

render_header($user, $page, $title);
if (in_array($page, $protectedPages, true)) {
    echo '<div class="container admin-wrap">';
    render_admin_nav($page);
    echo '<div>';
}

switch ($page) {
    case 'home':
        render_home($pdo);
        break;
    case 'shop':
        render_shop($pdo);
        break;
    case 'product':
        render_product($pdo);
        break;
    case 'cart':
        render_cart($pdo);
        break;
    case 'checkout':
        render_checkout($pdo, $user);
        break;
    case 'login':
    case 'register':
        render_auth($page);
        break;
    case 'account':
        render_account($pdo, $user);
        break;
    case 'order':
        render_order($pdo);
        break;
    case 'contact':
        render_contact();
        break;
    case 'about':
        render_about();
        break;
    case 'occasions':
        render_occasions();
        break;
    case 'admin':
        render_admin_dashboard($pdo);
        break;
    case 'admin_orders':
        render_admin_orders($pdo);
        break;
    case 'admin_order':
        render_admin_order($pdo);
        break;
    case 'admin_products':
        render_admin_products($pdo);
        break;
    case 'admin_product':
        render_admin_product_form($pdo);
        break;
    case 'admin_messages':
        render_admin_messages($pdo);
        break;
    case 'pay_esewa':
        render_payment_redirect($pdo);
        break;
    case 'not_found':
        echo '<div class="container empty"><h1>Page not found</h1><a class="btn btn-primary" href="' . e(url('home')) . '">Back to home</a></div>';
        break;
}

if (in_array($page, $protectedPages, true)) {
    echo '</div></div>';
}
render_footer();
