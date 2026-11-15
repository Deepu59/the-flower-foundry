<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

try {
    app_config();
    $pdo = db();
    $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
} catch (RuntimeException $error) {
    error_log('Flower Foundry admin setup configuration failed: ' . $error->getMessage());
    http_response_code(503);
    exit('Admin setup is unavailable. Check config.php and try again.');
} catch (PDOException $error) {
    error_log('Flower Foundry admin setup database failed: ' . $error->getMessage());
    http_response_code(503);
    exit('Admin setup is unavailable. Check the database settings and import database/schema.sql in phpMyAdmin first.');
}

if ($adminCount > 0) {
    http_response_code(404);
    exit('Not found.');
}

if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}

$email = '';
$errorMessage = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        verify_csrf();
        $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $confirmation = is_string($_POST['password_confirmation'] ?? null) ? $_POST['password_confirmation'] : '';
        if (!hash_equals($password, $confirmation)) {
            throw new RuntimeException('The passwords did not match.');
        }
        $pdo->beginTransaction();
        try {
            create_initial_admin($pdo, $email, $password);
            seed_empty_database($pdo);
            $pdo->commit();
        } catch (RuntimeException|PDOException $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
        session_regenerate_id(true);
        $_SESSION['flash_success'] = 'Admin account created. Log in with your new credentials.';
        redirect_to('login');
    } catch (RuntimeException $error) {
        $errorMessage = $error->getMessage();
    } catch (PDOException $error) {
        error_log('Flower Foundry admin account creation failed: ' . $error->getMessage());
        $errorMessage = 'The admin account could not be created. Please try again.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>First admin setup | <?= e(SHOP['name']) ?></title>
  <link rel="stylesheet" href="<?= e(base_path() . '/assets/css/style.css') ?>">
</head>
<body>
<main class="section">
  <div class="container" style="max-width:560px">
    <h1>Create the first admin account</h1>
    <p>This setup page is available only until the first admin account is created.</p>
    <?php if ($errorMessage !== ''): ?><div class="alert alert-error" role="alert"><?= e($errorMessage) ?></div><?php endif; ?>
    <form method="post" class="panel">
      <?= csrf_field() ?>
      <label for="email">Admin email</label>
      <input class="input" type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="email" maxlength="254" required>
      <label for="password">Password (at least 15 characters)</label>
      <input class="input" type="password" id="password" name="password" autocomplete="new-password" minlength="15" required>
      <label for="password_confirmation">Confirm password</label>
      <input class="input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" minlength="15" required>
      <button class="btn btn-primary" type="submit">Create admin account</button>
    </form>
  </div>
</main>
</body>
</html>
