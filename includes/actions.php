<?php
declare(strict_types=1);

function action_fail(string $page, string $message, array $params = []): never
{
    $_SESSION['flash_error'] = $message;
    redirect_to($page, $params);
}

function form_value(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function safe_next_page(string $page): string
{
    return in_array($page, ['cart', 'checkout', 'account', 'admin', 'shop'], true) ? $page : '';
}

function order_code(PDO $pdo): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = 'FS-';
        for ($i = 0; $i < 8; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $check = $pdo->prepare('SELECT 1 FROM orders WHERE code = ? LIMIT 1');
        $check->execute([$code]);
    } while ($check->fetchColumn());
    return $code;
}

function save_uploaded_image(): ?string
{
    $file = $_FILES['image'] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image upload failed. Please try again.');
    }
    if (!is_uploaded_file($file['tmp_name']) || (int) $file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Choose an image smaller than 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Image must be a JPG, PNG or WebP file.');
    }

    $directory = APP_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create the image upload directory.');
    }
    $filename = bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $filename)) {
        throw new RuntimeException('Could not save the uploaded image.');
    }
    return '/uploads/' . $filename;
}

function process_post(PDO $pdo): void
{
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'login') {
        $email = strtolower(form_value('email'));
        $password = (string) ($_POST['password'] ?? '');
        $next = safe_next_page(form_value('next'));
        $query = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE email = ? LIMIT 1');
        $query->execute([$email]);
        $user = $query->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            action_fail('login', 'Incorrect email or password.', $next ? ['next' => $next] : []);
        }
        login_user((int) $user['id']);
        redirect_to($next ?: ($user['role'] === 'admin' ? 'admin' : 'account'));
    }

    if ($action === 'register') {
        $name = form_value('name');
        $email = strtolower(form_value('email'));
        $phone = form_value('phone');
        $password = (string) ($_POST['password'] ?? '');
        $next = safe_next_page(form_value('next'));
        if (mb_strlen($name) < 2) {
            action_fail('register', 'Please enter your full name.', $next ? ['next' => $next] : []);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            action_fail('register', 'Please enter a valid email address.', $next ? ['next' => $next] : []);
        }
        if (strlen($password) < 8) {
            action_fail('register', 'Password must be at least 8 characters.', $next ? ['next' => $next] : []);
        }
        $exists = $pdo->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
        $exists->execute([$email]);
        if ($exists->fetchColumn()) {
            action_fail('register', 'An account with this email already exists. Try logging in.', $next ? ['next' => $next] : []);
        }
        $insert = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role, created_at) VALUES (?, ?, ?, ?, 'customer', ?)");
        $insert->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), (int) floor(microtime(true) * 1000)]);
        login_user((int) $pdo->lastInsertId());
        redirect_to($next ?: 'account');
    }

    if ($action === 'logout') {
        logout_user();
        redirect_to('home');
    }

    if ($action === 'cart_add') {
        $id = max(0, (int) ($_POST['product_id'] ?? 0));
        $qty = min(20, max(1, (int) ($_POST['qty'] ?? 1)));
        $product = $pdo->prepare('SELECT id FROM products WHERE id = ? AND active = 1 LIMIT 1');
        $product->execute([$id]);
        if (!$product->fetchColumn()) {
            action_fail('shop', 'That item is no longer available.');
        }
        $_SESSION['cart'] ??= [];
        $_SESSION['cart'][$id] = min(20, (int) ($_SESSION['cart'][$id] ?? 0) + $qty);
        $returnPage = (string) ($_POST['return_page'] ?? 'shop');
        $returnPage = in_array($returnPage, ['home', 'shop', 'product', 'cart'], true) ? $returnPage : 'shop';
        $returnParams = $returnPage === 'product' && isset($_POST['return_slug']) ? ['slug' => (string) $_POST['return_slug']] : [];
        $_SESSION['flash_success'] = 'Added to your cart.';
        redirect_to($returnPage, $returnParams);
    }

    if ($action === 'cart_update' || $action === 'cart_remove') {
        require_user('cart');
        $id = max(0, (int) ($_POST['product_id'] ?? 0));
        if ($action === 'cart_remove' || (int) ($_POST['qty'] ?? 0) < 1) {
            unset($_SESSION['cart'][$id]);
        } else {
            $_SESSION['cart'][$id] = min(20, (int) $_POST['qty']);
        }
        redirect_to('cart');
    }

    if ($action === 'contact') {
        $name = form_value('name');
        $email = strtolower(form_value('email'));
        $phone = form_value('phone');
        $body = mb_substr(form_value('body'), 0, 2000);
        if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($body) < 5) {
            action_fail('contact', 'Please enter your name, a valid email address, and a message.');
        }
        $insert = $pdo->prepare('INSERT INTO messages (name, email, phone, body, created_at) VALUES (?, ?, ?, ?, ?)');
        $insert->execute([$name, $email, $phone, $body, (int) floor(microtime(true) * 1000)]);
        $_SESSION['flash_success'] = 'Thank you! We have received your message and will reply soon.';
        redirect_to('contact');
    }

    if ($action === 'place_order') {
        $user = require_user('checkout');
        $cart = (array) ($_SESSION['cart'] ?? []);
        $lines = [];
        $productQuery = $pdo->prepare('SELECT id, name, price FROM products WHERE id = ? AND active = 1 LIMIT 1');
        foreach ($cart as $id => $qty) {
            $productQuery->execute([(int) $id]);
            $product = $productQuery->fetch();
            if (!$product) {
                action_fail('cart', 'An item in your cart is no longer available. Please review your cart.');
            }
            $lines[] = [
                'id' => (int) $product['id'],
                'name' => $product['name'],
                'price' => (int) $product['price'],
                'qty' => min(20, max(1, (int) $qty)),
            ];
        }
        if (!$lines) {
            action_fail('checkout', 'Your cart is empty.');
        }

        $senderName = form_value('sender_name');
        $senderPhone = form_value('sender_phone');
        $senderEmail = (string) $user['email'];
        $senderCountry = 'Nepal';
        $recipientName = form_value('recipient_name');
        $recipientPhone = form_value('recipient_phone');
        $address = form_value('address');
        $landmark = form_value('landmark');
        $deliveryDate = form_value('delivery_date');
        $deliverySlot = form_value('delivery_slot');
        $cardMessage = mb_substr(form_value('card_message'), 0, 300);
        $notes = mb_substr(form_value('notes'), 0, 300);
        $paymentMethod = form_value('payment_method');
        $npMobile = '/^(\+?977)?[- ]?9[78]\d{8}$/';

        if (mb_strlen($senderName) < 2) {
            action_fail('checkout', 'Please enter your name.');
        }
        if (!preg_match($npMobile, $senderPhone)) {
            action_fail('checkout', 'Please enter a valid Nepali mobile number (98XXXXXXXX).');
        }
        if (mb_strlen($recipientName) < 2 || !preg_match($npMobile, $recipientPhone)) {
            action_fail('checkout', 'Enter the recipient’s name and a valid Nepali mobile number (98XXXXXXXX).');
        }
        if (mb_strlen($address) < 5) {
            action_fail('checkout', 'Please enter the delivery address in ' . DELIVERY['area'] . '.');
        }
        if (!in_array($deliverySlot, DELIVERY['slots'], true)) {
            action_fail('checkout', 'Please choose a delivery time slot.');
        }
        $today = nepal_today();
        $maxDate = (new DateTimeImmutable($today, new DateTimeZone('Asia/Kathmandu')))
            ->modify('+' . DELIVERY['max_days_ahead'] . ' days')->format('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $deliveryDate) || $deliveryDate < $today || $deliveryDate > $maxDate) {
            action_fail('checkout', 'Please choose a valid delivery date.');
        }
        $nepalNow = new DateTimeImmutable('now', new DateTimeZone('Asia/Kathmandu'));
        if ($deliveryDate === $today && (int) $nepalNow->format('G') >= DELIVERY['same_day_cutoff_hour']) {
            action_fail('checkout', 'Same-day delivery is closed for today. Please pick tomorrow or later.');
        }
        if ($paymentMethod !== 'cod') {
            action_fail('checkout', 'Cash on delivery is the only available payment method.');
        }

        $subtotal = array_sum(array_map(static fn (array $line): int => $line['price'] * $line['qty'], $lines));
        $deliveryFee = $subtotal >= DELIVERY['free_above'] ? 0 : DELIVERY['fee'];
        $total = $subtotal + $deliveryFee;
        $code = order_code($pdo);
        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare(
                "INSERT INTO orders (code, user_id, status, payment_method, payment_status, subtotal, delivery_fee, total,
                 sender_name, sender_phone, sender_email, sender_country, recipient_name, recipient_phone, address, landmark,
                 delivery_date, delivery_slot, card_message, notes, created_at)
                 VALUES (?, ?, 'pending', ?, 'unpaid', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $insert->execute([
                $code, $user['id'], $paymentMethod, $subtotal, $deliveryFee, $total,
                $senderName, $senderPhone, $senderEmail, $senderCountry, $recipientName, $recipientPhone,
                $address, $landmark, $deliveryDate, $deliverySlot, $cardMessage, $notes, (int) floor(microtime(true) * 1000),
            ]);
            $orderId = (int) $pdo->lastInsertId();
            $itemInsert = $pdo->prepare('INSERT INTO order_items (order_id, product_id, name, price, qty) VALUES (?, ?, ?, ?, ?)');
            foreach ($lines as $line) {
                $itemInsert->execute([$orderId, $line['id'], $line['name'], $line['price'], $line['qty']]);
            }
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
        unset($_SESSION['cart']);
        redirect_to('order', ['code' => $code, 'placed' => 1]);
    }

    if ($action === 'product_save') {
        require_admin();
        $id = max(0, (int) ($_POST['id'] ?? 0));
        $kind = form_value('kind') === 'addon' ? 'addon' : 'flower';
        $categories = $kind === 'addon' ? ADDON_CATEGORIES : FLOWER_CATEGORIES;
        $category = form_value('category');
        $name = form_value('name');
        $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_INT);
        if (mb_strlen($name) < 2 || $price === false || $price < 1 || !in_array($category, $categories, true)) {
            action_fail('admin_product', 'Enter a product name, valid price, and category.', $id ? ['id' => $id] : []);
        }
        try {
            $newImage = save_uploaded_image();
        } catch (RuntimeException $error) {
            action_fail('admin_product', $error->getMessage(), $id ? ['id' => $id] : []);
        }
        $occasions = $kind === 'flower'
            ? implode(',', array_values(array_intersect(array_keys(OCCASIONS), (array) ($_POST['occasions'] ?? []))))
            : '';
        $featured = $kind === 'flower' && isset($_POST['featured']) ? 1 : 0;
        $active = isset($_POST['active']) ? 1 : 0;
        $description = mb_substr(form_value('description'), 0, 1000);
        if ($id) {
            $existing = $pdo->prepare('SELECT id FROM products WHERE id = ? LIMIT 1');
            $existing->execute([$id]);
            if (!$existing->fetchColumn()) {
                action_fail('admin_products', 'That product no longer exists.');
            }
            $statement = $pdo->prepare(
                'UPDATE products SET name=?, description=?, category=?, kind=?, price=?, occasions=?, featured=?, active=?,
                 image=COALESCE(?, image) WHERE id=?'
            );
            $statement->execute([$name, $description, $category, $kind, $price, $occasions, $featured, $active, $newImage, $id]);
        } else {
            $statement = $pdo->prepare(
                'INSERT INTO products (slug, name, description, category, kind, price, image, occasions, featured, active, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $statement->execute([
                unique_slug($pdo, $name), $name, $description, $category, $kind, $price, $newImage ?? '',
                $occasions, $featured, $active, (int) floor(microtime(true) * 1000),
            ]);
        }
        $_SESSION['flash_success'] = 'Product saved.';
        redirect_to('admin_products');
    }

    if ($action === 'product_toggle') {
        require_admin();
        $pdo->prepare('UPDATE products SET active = 1 - active WHERE id = ?')->execute([max(0, (int) ($_POST['id'] ?? 0))]);
        redirect_to('admin_products');
    }

    if ($action === 'order_update') {
        require_admin();
        $id = max(0, (int) ($_POST['id'] ?? 0));
        $status = form_value('status');
        $paymentStatus = form_value('payment_status');
        if (!in_array($status, ORDER_STATUS_OPTIONS, true) || !in_array($paymentStatus, ['unpaid', 'paid', 'failed', 'refunded'], true)) {
            action_fail('admin_order', 'Choose valid order and payment statuses.', ['id' => $id]);
        }
        $pdo->prepare('UPDATE orders SET status = ?, payment_status = ? WHERE id = ?')->execute([$status, $paymentStatus, $id]);
        $_SESSION['flash_success'] = 'Order updated.';
        redirect_to('admin_order', ['id' => $id]);
    }

    if ($action === 'order_status_update') {
        require_admin();
        $id = max(0, (int) ($_POST['id'] ?? 0));
        $status = form_value('status');
        if (!in_array($status, ORDER_STATUS_OPTIONS, true)) {
            action_fail('admin', 'Choose a valid order status.');
        }
        $exists = $pdo->prepare('SELECT 1 FROM orders WHERE id = ? LIMIT 1');
        $exists->execute([$id]);
        if (!$exists->fetchColumn()) {
            action_fail('admin', 'That order no longer exists.');
        }
        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $id]);
        $_SESSION['flash_success'] = 'Order status updated.';
        redirect_to('admin');
    }

    action_fail('home', 'That action is not available.');
}
