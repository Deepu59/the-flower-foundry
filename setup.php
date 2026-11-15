<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Run setup.php from a trusted command-line terminal.');
}

try {
    app_config();
    $pdo = db();
} catch (RuntimeException $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
install_schema($pdo);

if (in_array('--schema-only', $argv, true)) {
    fwrite(STDOUT, "MySQL schema created. No records were seeded.\n");
    exit(0);
}

seed_empty_database($pdo);
$adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
if ($adminCount === 0) {
    try {
        fwrite(STDOUT, "Create the first admin account.\n");
        fwrite(STDOUT, "Admin email: ");
        $email = fgets(STDIN);
        if ($email === false) {
            throw new RuntimeException('Could not read the admin email from the terminal.');
        }
        fwrite(STDOUT, "Admin password (15 characters minimum): ");
        $password = fgets(STDIN);
        if ($password === false) {
            throw new RuntimeException('Could not read the admin password from the terminal.');
        }
        fwrite(STDOUT, "Confirm admin password: ");
        $confirmation = fgets(STDIN);
        if ($confirmation === false) {
            throw new RuntimeException('Could not read the password confirmation from the terminal.');
        }
        $password = rtrim($password, "\r\n");
        $confirmation = rtrim($confirmation, "\r\n");
        if (!hash_equals($password, $confirmation)) {
            throw new RuntimeException('The passwords did not match. Run setup again to retry.');
        }
        create_initial_admin($pdo, trim($email), $password);
        fwrite(STDOUT, "Admin account created.\n");
    } catch (RuntimeException $error) {
        fwrite(STDERR, $error->getMessage() . "\n");
        exit(1);
    }
} else {
    fwrite(STDOUT, "An admin account already exists; it was left unchanged.\n");
}
fwrite(STDOUT, "Database ready. Existing records were left unchanged; empty product tables were seeded.\n");
