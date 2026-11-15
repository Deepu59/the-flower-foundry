<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Run this migration from the XAMPP Shell or command prompt.');
}
if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, "PDO_SQLITE is not enabled. Enable pdo_sqlite in C:\\xampp\\php\\php.ini and retry.\n");
    exit(1);
}

$sourcePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'shop.db';
if (!is_file($sourcePath)) {
    fwrite(STDERR, "The existing SQLite database was not found at data/shop.db.\n");
    exit(1);
}

$target = db();
install_schema($target);

$tables = ['users', 'sessions', 'products', 'orders', 'order_items', 'messages'];
foreach ($tables as $table) {
    $count = (int) $target->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    if ($count !== 0) {
        fwrite(STDERR, "Migration stopped: MySQL table {$table} is not empty. Use a fresh database to avoid overwriting data.\n");
        exit(1);
    }
}

$source = new PDO('sqlite:' . $sourcePath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$columns = [
    'users' => ['id', 'name', 'email', 'phone', 'password_hash', 'role', 'created_at'],
    'sessions' => ['token', 'user_id', 'expires_at'],
    'products' => ['id', 'slug', 'name', 'description', 'category', 'kind', 'price', 'image', 'occasions', 'featured', 'active', 'created_at'],
    'orders' => ['id', 'code', 'user_id', 'status', 'payment_method', 'payment_status', 'subtotal', 'delivery_fee', 'total', 'sender_name', 'sender_phone', 'sender_email', 'sender_country', 'recipient_name', 'recipient_phone', 'address', 'landmark', 'delivery_date', 'delivery_slot', 'card_message', 'notes', 'esewa_uuid', 'esewa_ref', 'created_at'],
    'order_items' => ['id', 'order_id', 'product_id', 'name', 'price', 'qty'],
    'messages' => ['id', 'name', 'email', 'phone', 'body', 'created_at'],
];

$counts = [];
$target->beginTransaction();
try {
    foreach ($tables as $table) {
        $sourceExists = $source->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = " . $source->quote($table))->fetchColumn();
        if (!$sourceExists) {
            $counts[$table] = 0;
            continue;
        }

        $fieldList = implode(', ', array_map(static fn (string $field): string => "`{$field}`", $columns[$table]));
        $placeholders = implode(', ', array_fill(0, count($columns[$table]), '?'));
        $insert = $target->prepare("INSERT INTO `{$table}` ({$fieldList}) VALUES ({$placeholders})");
        $rows = $source->query("SELECT {$fieldList} FROM `{$table}`");
        $count = 0;
        while ($row = $rows->fetch()) {
            $insert->execute(array_values($row));
            $count++;
        }
        $counts[$table] = $count;
    }
    $target->commit();
} catch (Throwable $error) {
    if ($target->inTransaction()) {
        $target->rollBack();
    }
    throw $error;
}

foreach ($counts as $table => $count) {
    fwrite(STDOUT, sprintf("%-12s %d rows\n", $table, $count));
}
fwrite(STDOUT, "Migration complete. The SQLite database was not modified.\n");
