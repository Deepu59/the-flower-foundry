<?php
declare(strict_types=1);

function cart_lines(PDO $pdo): array
{
    $cart = (array) ($_SESSION['cart'] ?? []);
    if (!$cart) {
        return [];
    }
    $query = $pdo->prepare('SELECT id, slug, name, price, image, kind FROM products WHERE id = ? AND active = 1 LIMIT 1');
    $lines = [];
    foreach ($cart as $id => $qty) {
        $query->execute([(int) $id]);
        $product = $query->fetch();
        if ($product) {
            $product['qty'] = min(20, max(1, (int) $qty));
            $lines[] = $product;
        }
    }
    return $lines;
}

function cart_subtotal(array $lines): int
{
    return array_sum(array_map(static fn (array $line): int => (int) $line['price'] * (int) $line['qty'], $lines));
}

function render_order_status_options(string $currentStatus): void
{
    if (!in_array($currentStatus, ORDER_STATUS_OPTIONS, true)) {
        $label = STATUS_LABELS[$currentStatus] ?? ucfirst(str_replace('_', ' ', $currentStatus));
        echo '<option value="" selected disabled>Current: ' . e($label) . '</option>';
    }
    foreach (ORDER_STATUS_OPTIONS as $status) {
        echo '<option value="' . e($status) . '"' . ($currentStatus === $status ? ' selected' : '') . '>' . e(STATUS_LABELS[$status]) . '</option>';
    }
}

function render_home(PDO $pdo): void
{
    $featured = $pdo->query("SELECT * FROM products WHERE active = 1 AND kind = 'flower' AND featured = 1 ORDER BY id LIMIT 6")->fetchAll();
    $addons = $pdo->query("SELECT * FROM products WHERE active = 1 AND kind = 'addon' ORDER BY id LIMIT 4")->fetchAll();
    $flowerEmoji = ['Boutique Bouquets' => '💐', 'Roses' => '🌹', 'Carnations' => '🌸', 'Lilies' => '🌷', 'Gerberas' => '🌼', 'Sunflowers' => '🌻', 'Mixed Bouquets' => '💮'];
    $addonEmoji = ['Cakes' => '🎂', 'Chocolates' => '🍫', 'Teddy Bears' => '🧸', 'Greeting Cards' => '💌'];
    ?>
    <section class="hero"><div class="container hero-grid">
      <div>
        <p class="eyebrow">Online flower shop · <?= e(SHOP['city']) ?></p>
        <h1>Fresh flowers for every feeling, delivered with love.</h1>
        <p class="lead">Hand-tied bouquets, boutique wraps, cakes and gifts — ordered online and delivered to your loved ones in <?= e(DELIVERY['area']) ?>.</p>
        <div class="hero-actions"><a href="<?= e(url('shop')) ?>" class="btn btn-primary">Shop bouquets</a><a href="<?= e(url('occasions')) ?>" class="btn btn-outline">Shop by occasion</a></div>
        <div class="hero-badges"><span>Same-day delivery</span><span>Cash on delivery</span><span>Free card message</span></div>
      </div>
      <div class="hero-photos">
        <img src="<?= e(image_url('/images/blush-crimson-boutique.png')) ?>" alt="Blush and crimson rose boutique bouquet">
        <img src="<?= e(image_url('/images/peach-garden-boutique.png')) ?>" alt="Peach garden boutique bouquet with pink roses">
      </div>
    </div></section>

    <section class="section"><div class="container">
      <div class="section-head"><p class="eyebrow">Browse</p><h2>Shop by flower</h2></div>
      <div class="cat-grid"><?php foreach (FLOWER_CATEGORIES as $category): ?>
        <a href="<?= e(url('shop', ['category' => $category])) ?>" class="cat-pill"><span class="emoji"><?= e($flowerEmoji[$category] ?? '🌸') ?></span><?= e($category) ?></a>
      <?php endforeach; ?></div>
    </div></section>

    <section class="section alt"><div class="container">
      <div class="section-head"><p class="eyebrow">Our favourites</p><h2>Bestselling bouquets</h2><p>Hand-arranged by our florists and wrapped fresh every morning.</p></div>
      <div class="grid-products"><?php foreach ($featured as $product) { render_product_card($product, 'home'); } ?></div>
      <p class="center" style="margin-top:2rem"><a href="<?= e(url('shop')) ?>" class="btn btn-outline">View all bouquets</a></p>
    </div></section>

    <section class="section"><div class="container">
      <div class="section-head"><p class="eyebrow">Make it extra special</p><h2>Add a gift with your flowers</h2></div>
      <div class="cat-grid" style="margin-bottom:2rem"><?php foreach (ADDON_CATEGORIES as $category): ?>
        <a href="<?= e(url('shop', ['kind' => 'addon', 'category' => $category])) ?>" class="cat-pill"><span class="emoji"><?= e($addonEmoji[$category] ?? '🎁') ?></span><?= e($category) ?></a>
      <?php endforeach; ?></div>
      <div class="grid-products"><?php foreach ($addons as $product) { render_product_card($product, 'home'); } ?></div>
    </div></section>

    <section class="section sage"><div class="container">
      <div class="section-head"><p class="eyebrow">Simple &amp; quick</p><h2>How it works</h2></div>
      <div class="steps">
        <div class="step"><div class="num">1</div><h3>Choose</h3><p class="muted">Pick a bouquet and add a cake, chocolates, teddy or card.</p></div>
        <div class="step"><div class="num">2</div><h3>Tell us where</h3><p class="muted">Enter the recipient’s address in <?= e(SHOP['city']) ?>, pick a date and time slot.</p></div>
        <div class="step"><div class="num">3</div><h3>Pay on delivery</h3><p class="muted">Pay in cash when your flowers arrive.</p></div>
        <div class="step"><div class="num">4</div><h3>We deliver</h3><p class="muted">Fresh flowers arrive on time, with your personal message.</p></div>
      </div>
    </div></section>

    <section class="section"><div class="container">
      <div class="section-head"><p class="eyebrow">Every moment</p><h2>Shop by occasion</h2></div>
      <div class="cat-grid"><?php foreach (OCCASIONS as $key => $occasion): ?>
        <a href="<?= e(url('shop', ['occasion' => $key])) ?>" class="cat-pill"><b><?= e($occasion['label']) ?></b><small><?= e($occasion['blurb']) ?></small></a>
      <?php endforeach; ?></div>
    </div></section>

    <section class="section alt"><div class="container">
      <div class="section-head"><p class="eyebrow">Kind words</p><h2>Loved by our customers</h2></div>
      <div class="quotes">
        <div class="quote"><div class="stars">★★★★★</div><p>“The bouquet arrived within two hours and looked exactly like the photo. My sister loved it!”</p><strong>Sample review · Anita S.</strong></div>
        <div class="quote"><div class="stars">★★★★★</div><p>“The delivery was right on time, and the flowers looked even more beautiful in person.”</p><strong>Sample review · Rohan K.</strong></div>
        <div class="quote"><div class="stars">★★★★★</div><p>“Beautiful wrapping and the roses stayed fresh for a week. Will order again for our anniversary.”</p><strong>Sample review · Pratima G.</strong></div>
      </div>
    </div></section>

    <section class="section"><div class="container" style="max-width:820px">
      <h2>Online flower delivery in <?= e(SHOP['city']) ?></h2>
      <p class="muted"><?= e(SHOP['name']) ?> is your local online flower shop in <?= e(SHOP['city']) ?>, Banke. Whether it is a birthday, anniversary, Valentine’s Day or a wedding, flowers say what words cannot.</p>
      <p class="muted">Bouquet prices start from Rs 1,250. Delivery inside <?= e(SHOP['city']) ?> costs <?= e(npr(DELIVERY['fee'])) ?> (free above <?= e(npr(DELIVERY['free_above'])) ?>). Create an account to order online and pay cash on delivery.</p>
    </div></section>
    <?php
}

function render_shop(PDO $pdo): void
{
    $kind = ($_GET['kind'] ?? '') === 'addon' ? 'addon' : 'flower';
    $categories = $kind === 'addon' ? ADDON_CATEGORIES : FLOWER_CATEGORIES;
    $category = in_array((string) ($_GET['category'] ?? ''), $categories, true) ? (string) $_GET['category'] : '';
    $occasion = array_key_exists((string) ($_GET['occasion'] ?? ''), OCCASIONS) ? (string) $_GET['occasion'] : '';
    $queryText = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 60);
    $sorts = ['featured' => 'featured DESC, id', 'price_asc' => 'price ASC', 'price_desc' => 'price DESC', 'newest' => 'created_at DESC, id DESC'];
    $sort = isset($sorts[$_GET['sort'] ?? '']) ? (string) $_GET['sort'] : 'featured';
    $where = ['active = 1', 'kind = ?'];
    $params = [$kind];
    if ($category !== '') {
        $where[] = 'category = ?';
        $params[] = $category;
    }
    if ($occasion !== '') {
        $where[] = 'FIND_IN_SET(?, occasions) > 0';
        $params[] = $occasion;
    }
    if ($queryText !== '') {
        $where[] = '(name LIKE ? OR description LIKE ?)';
        $params[] = '%' . $queryText . '%';
        $params[] = '%' . $queryText . '%';
    }
    $statement = $pdo->prepare('SELECT * FROM products WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $sorts[$sort]);
    $statement->execute($params);
    $products = $statement->fetchAll();
    $title = $kind === 'addon' ? 'Cakes, chocolates & gifts' : ($occasion ? OCCASIONS[$occasion]['label'] . ' flowers' : ($category ?: 'All flower bouquets'));
    render_page_title($title, $kind === 'addon' ? 'Pair your flowers with something sweet or cuddly.' : 'Fresh, hand-tied bouquets delivered across Nepalgunj.');
    $preserve = ['kind' => $kind === 'addon' ? 'addon' : null, 'category' => $category ?: null];
    ?>
    <section class="section" style="padding-top:1.5rem"><div class="container">
      <?php render_flash(); ?>
      <div class="shop-bar"><div class="chips">
        <a class="chip <?= $kind === 'flower' && !$category && !$occasion ? 'active' : '' ?>" href="<?= e(url('shop')) ?>">All flowers</a>
        <?php foreach (FLOWER_CATEGORIES as $item): ?><a class="chip <?= $kind === 'flower' && $category === $item ? 'active' : '' ?>" href="<?= e(url('shop', ['category' => $item])) ?>"><?= e($item) ?></a><?php endforeach; ?>
        <a class="chip <?= $kind === 'addon' ? 'active' : '' ?>" href="<?= e(url('shop', ['kind' => 'addon'])) ?>">Cakes &amp; Gifts</a>
      </div></div>
      <form class="shop-bar" method="get" action="<?= e(base_path() . '/index.php') ?>">
        <input type="hidden" name="page" value="shop">
        <?php if ($kind === 'addon'): ?><input type="hidden" name="kind" value="addon"><?php endif; ?>
        <?php if ($category !== ''): ?><input type="hidden" name="category" value="<?= e($category) ?>"><?php endif; ?>
        <div class="filters">
          <input class="input" type="search" name="q" placeholder="Search…" value="<?= e($queryText) ?>" aria-label="Search">
          <?php if ($kind === 'flower'): ?><select class="input" name="occasion" aria-label="Occasion"><option value="">Any occasion</option>
            <?php foreach (OCCASIONS as $key => $item): ?><option value="<?= e($key) ?>" <?= $occasion === $key ? 'selected' : '' ?>><?= e($item['label']) ?></option><?php endforeach; ?>
          </select><?php endif; ?>
          <select class="input" name="sort" aria-label="Sort">
            <option value="featured" <?= $sort === 'featured' ? 'selected' : '' ?>>Featured</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
          </select>
          <button class="btn btn-outline btn-sm" type="submit">Apply</button>
          <?php if ($queryText || $occasion || $category || $sort !== 'featured'): ?><a href="<?= e(url('shop', $preserve)) ?>" class="link-btn">Clear</a><?php endif; ?>
        </div><span class="muted" style="font-size:.9rem"><?= count($products) ?> item<?= count($products) === 1 ? '' : 's' ?></span>
      </form>
      <?php if ($kind === 'addon'): ?><div class="chips" style="margin-bottom:1.5rem">
        <?php foreach (ADDON_CATEGORIES as $item): ?><a class="chip <?= $category === $item ? 'active' : '' ?>" href="<?= e(url('shop', ['kind' => 'addon', 'category' => $item])) ?>"><?= e($item) ?></a><?php endforeach; ?>
      </div><?php endif; ?>
      <?php if (!$products): ?><div class="empty"><div class="big">🥀</div><h3>Nothing found</h3><p class="muted">Try a different search or filter.</p><a href="<?= e(url('shop')) ?>" class="btn btn-primary">See all bouquets</a></div>
      <?php else: ?><div class="grid-products"><?php foreach ($products as $product) { render_product_card($product, 'shop', $_GET); } ?></div><?php endif; ?>
    </div></section>
    <?php
}

function render_product(PDO $pdo): void
{
    $statement = $pdo->prepare('SELECT * FROM products WHERE slug = ? AND active = 1 LIMIT 1');
    $statement->execute([(string) ($_GET['slug'] ?? '')]);
    $product = $statement->fetch();
    if (!$product) {
        http_response_code(404);
        echo '<div class="container empty"><h1>Product not found</h1><a class="btn btn-primary" href="' . e(url('shop')) . '">Back to shop</a></div>';
        return;
    }
    $addons = $product['kind'] === 'flower'
        ? $pdo->query("SELECT * FROM products WHERE active = 1 AND kind = 'addon' ORDER BY id")->fetchAll()
        : [];
    $relatedQuery = $pdo->prepare('SELECT * FROM products WHERE active = 1 AND kind = ? AND id <> ? ORDER BY (category = ?) DESC, featured DESC, id LIMIT 4');
    $relatedQuery->execute([$product['kind'], $product['id'], $product['category']]);
    $related = $relatedQuery->fetchAll();
    $occasionLabels = [];
    foreach (explode(',', $product['occasions']) as $key) {
        if (isset(OCCASIONS[$key])) {
            $occasionLabels[] = OCCASIONS[$key]['label'];
        }
    }
    ?>
    <div class="container">
      <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(url('home')) ?>">Home</a> / <a href="<?= e(url('shop', $product['kind'] === 'addon' ? ['kind' => 'addon'] : [])) ?>">Shop</a> / <?= e($product['category']) ?></nav>
      <div class="product">
        <div class="product-img"><img src="<?= e(image_url($product['image'])) ?>" alt="<?= e($product['name']) ?>"></div>
        <div>
          <span class="card-cat"><?= e($product['category']) ?></span>
          <h1 style="font-size:clamp(1.8rem,4vw,2.6rem)"><?= e($product['name']) ?></h1>
          <p class="price" style="font-size:1.6rem"><?= e(npr($product['price'])) ?></p>
          <p class="muted"><?= e($product['description']) ?></p>
          <?php if ($occasionLabels): ?><p class="muted" style="font-size:.9rem">Perfect for: <?= e(implode(' · ', $occasionLabels)) ?></p><?php endif; ?>
          <form method="post" action="<?= e(url('product', ['slug' => $product['slug']])) ?>">
            <?= csrf_field() ?><input type="hidden" name="action" value="cart_add"><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <input type="hidden" name="return_page" value="product"><input type="hidden" name="return_slug" value="<?= e($product['slug']) ?>">
            <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;margin:1.2rem 0">
              <div class="qty" aria-label="Quantity"><button type="button" data-qty-step="-1" aria-label="Decrease">−</button><input class="qty-input" type="number" name="qty" value="1" min="1" max="20" aria-label="Quantity"><button type="button" data-qty-step="1" aria-label="Increase">+</button></div>
              <button type="submit" class="btn btn-primary">Add to cart</button>
            </div>
          </form>
          <form method="post" action="<?= e(url('product', ['slug' => $product['slug']])) ?>">
            <?= csrf_field() ?><input type="hidden" name="action" value="cart_add"><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>"><input type="hidden" name="qty" value="1">
            <input type="hidden" name="return_page" value="checkout"><button type="submit" class="btn btn-outline">Buy now</button>
          </form>
          <ul class="info-list">
            <li>Hand-arranged by our florists and delivered fresh</li>
            <li>Delivery in <?= e(DELIVERY['area']) ?> from <?= e(npr(DELIVERY['fee'])) ?> · free above <?= e(npr(DELIVERY['free_above'])) ?></li>
            <li>Same-day delivery if you order before 3 PM</li><li>Pay cash on delivery</li>
          </ul>
        </div>
      </div>
      <?php if ($addons): ?><section style="padding:1rem 0 2rem"><h2>Make it extra special</h2><div class="addon-strip">
        <?php foreach ($addons as $addon): ?><div class="addon"><img src="<?= e(image_url($addon['image'])) ?>" alt="<?= e($addon['name']) ?>" loading="lazy"><b><?= e($addon['name']) ?></b><div class="price" style="margin:.2rem 0 .5rem"><?= e(npr($addon['price'])) ?></div>
          <form method="post" action="<?= e(url('product', ['slug' => $product['slug']])) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="cart_add"><input type="hidden" name="product_id" value="<?= (int) $addon['id'] ?>"><input type="hidden" name="qty" value="1"><input type="hidden" name="return_page" value="product"><input type="hidden" name="return_slug" value="<?= e($product['slug']) ?>"><button class="btn btn-outline btn-sm" type="submit">+ Add</button></form>
        </div><?php endforeach; ?>
      </div></section><?php endif; ?>
      <?php if ($related): ?><section style="padding:1rem 0 4rem"><h2>You may also like</h2><div class="grid-products"><?php foreach ($related as $item) { render_product_card($item, 'product', ['slug' => $product['slug']]); } ?></div></section><?php endif; ?>
    </div>
    <?php
}

function render_cart(PDO $pdo): void
{
    $lines = cart_lines($pdo);
    $subtotal = cart_subtotal($lines);
    $fee = $subtotal === 0 || $subtotal >= DELIVERY['free_above'] ? 0 : DELIVERY['fee'];
    $addons = $pdo->query("SELECT * FROM products WHERE active = 1 AND kind = 'addon' ORDER BY id")->fetchAll();
    $inCart = array_fill_keys(array_map(static fn (array $line): int => (int) $line['id'], $lines), true);
    render_page_title('Your cart', 'Review your flowers and gifts before checkout.');
    ?>
    <div class="container two-col">
      <div>
        <?php render_flash(); ?>
        <?php if (!$lines): ?><div class="empty"><div class="big">🛍</div><h2>Your cart is empty</h2><p class="muted">Browse our fresh bouquets and add your favourites.</p><a href="<?= e(url('shop')) ?>" class="btn btn-primary">Shop bouquets</a></div>
        <?php else: ?><div class="panel"><h3>Your items</h3>
          <?php foreach ($lines as $line): ?><div class="cart-line">
            <img src="<?= e(image_url($line['image'])) ?>" alt="<?= e($line['name']) ?>">
            <div><h4><a href="<?= e(url('product', ['slug' => $line['slug']])) ?>"><?= e($line['name']) ?></a></h4><div class="muted" style="font-size:.9rem"><?= e(npr($line['price'])) ?> each</div>
              <form method="post" action="<?= e(url('cart')) ?>" style="display:flex;gap:1rem;align-items:center;margin-top:.5rem">
                <?= csrf_field() ?><input type="hidden" name="product_id" value="<?= (int) $line['id'] ?>"><input type="hidden" name="action" value="cart_update">
                <div class="qty"><button type="button" data-qty-step="-1" aria-label="Decrease">−</button><input class="qty-input" type="number" name="qty" value="<?= (int) $line['qty'] ?>" min="0" max="20" aria-label="Quantity"><button type="button" data-qty-step="1" aria-label="Increase">+</button></div>
                <button class="link-btn" type="submit">Update</button>
              </form>
              <form method="post" action="<?= e(url('cart')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="cart_remove"><input type="hidden" name="product_id" value="<?= (int) $line['id'] ?>"><button type="submit" class="link-btn">Remove</button></form>
            </div><strong><?= e(npr((int) $line['price'] * (int) $line['qty'])) ?></strong>
          </div><?php endforeach; ?>
        </div><?php endif; ?>
        <?php $suggestions = array_values(array_filter($addons, static fn (array $addon): bool => !isset($inCart[(int) $addon['id']]))); ?>
        <?php if ($suggestions): ?><div class="panel"><h3>Add a gift 🎁</h3><div class="addon-strip">
          <?php foreach (array_slice($suggestions, 0, 6) as $addon): ?><div class="addon"><img src="<?= e(image_url($addon['image'])) ?>" alt="<?= e($addon['name']) ?>" loading="lazy"><b><?= e($addon['name']) ?></b><div class="price" style="margin:.2rem 0 .5rem"><?= e(npr($addon['price'])) ?></div>
            <form method="post" action="<?= e(url('cart')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="cart_add"><input type="hidden" name="product_id" value="<?= (int) $addon['id'] ?>"><input type="hidden" name="qty" value="1"><input type="hidden" name="return_page" value="cart"><button type="submit" class="btn btn-outline btn-sm">+ Add</button></form>
          </div><?php endforeach; ?>
        </div></div><?php endif; ?>
      </div>
      <aside class="panel sticky"><h3>Order summary</h3>
        <div class="sum-row"><span>Subtotal</span><span><?= e(npr($subtotal)) ?></span></div>
        <div class="sum-row"><span>Delivery (Nepalgunj)</span><span><?= $fee === 0 ? 'Free' : e(npr($fee)) ?></span></div>
        <?php if ($fee > 0): ?><p class="muted" style="font-size:.82rem">Add <?= e(npr(DELIVERY['free_above'] - $subtotal)) ?> more for free delivery.</p><?php endif; ?>
        <div class="sum-row total"><span>Total</span><span><?= e(npr($subtotal + $fee)) ?></span></div>
        <?php if ($lines): ?><a href="<?= e(url('checkout')) ?>" class="btn btn-primary btn-block" style="margin-top:1rem">Proceed to checkout</a><?php endif; ?>
        <a href="<?= e(url('shop')) ?>" class="btn btn-outline btn-block" style="margin-top:.6rem">Continue shopping</a>
      </aside>
    </div>
    <?php
}

function render_checkout(PDO $pdo, array $user): void
{
    $lines = cart_lines($pdo);
    $subtotal = cart_subtotal($lines);
    $fee = $subtotal >= DELIVERY['free_above'] ? 0 : DELIVERY['fee'];
    $total = $subtotal + $fee;
    $today = nepal_today();
    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Kathmandu'));
    $sameDayOpen = (int) $now->format('G') < DELIVERY['same_day_cutoff_hour'];
    $minDate = $sameDayOpen ? $today : $now->modify('+1 day')->format('Y-m-d');
    $maxDate = (new DateTimeImmutable($today, new DateTimeZone('Asia/Kathmandu')))->modify('+30 days')->format('Y-m-d');
    render_page_title('Checkout', 'Tell us where to deliver your order.');
    ?>
    <?php if (!$lines): ?><div class="container empty"><div class="big">🛍</div><h2>Your cart is empty</h2><a href="<?= e(url('shop')) ?>" class="btn btn-primary">Shop bouquets</a></div>
    <?php else: ?><form method="post" action="<?= e(url('checkout')) ?>" class="container two-col">
      <input type="hidden" name="action" value="place_order"><?= csrf_field() ?>
      <div>
        <?php render_flash(); ?>
        <div class="panel"><h3>1. Your details</h3>
          <div class="grid-2">
            <div class="field"><label for="sender_name">Your name</label><input id="sender_name" name="sender_name" value="<?= e($user['name'] ?? '') ?>" required></div>
            <div class="field"><label for="sender_phone">Your phone</label><input id="sender_phone" name="sender_phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="98XXXXXXXX" required></div>
          </div>
          <div class="field"><label for="account_email">Account email</label><input id="account_email" type="email" value="<?= e($user['email']) ?>" readonly></div>
        </div>
        <div class="panel"><h3>2. Delivery details</h3>
          <div class="grid-2"><div class="field"><label for="recipient_name">Recipient name</label><input id="recipient_name" name="recipient_name" required></div><div class="field"><label for="recipient_phone">Recipient phone</label><input id="recipient_phone" name="recipient_phone" placeholder="98XXXXXXXX" required></div></div>
          <div class="field"><label for="address">Delivery address (<?= e(DELIVERY['area']) ?>)</label><textarea id="address" name="address" rows="2" placeholder="Ward no., tole / street, house name or number" required></textarea></div>
          <div class="field"><label for="landmark">Landmark (optional)</label><input id="landmark" name="landmark" placeholder="e.g. near Bheri Hospital"></div>
          <div class="grid-2">
            <div class="field"><label for="delivery_date">Delivery date</label><input id="delivery_date" type="date" name="delivery_date" min="<?= e($minDate) ?>" max="<?= e($maxDate) ?>" value="<?= e($minDate) ?>" required><small><?= $sameDayOpen ? 'Order before 3 PM for same-day delivery.' : 'Same-day delivery has closed for today.' ?></small></div>
            <div class="field"><label for="delivery_slot">Time slot</label><select id="delivery_slot" name="delivery_slot" required><?php foreach (DELIVERY['slots'] as $slot): ?><option value="<?= e($slot) ?>"><?= e($slot) ?></option><?php endforeach; ?></select></div>
          </div>
          <div class="field"><label for="card_message">Card message (free)</label><textarea id="card_message" name="card_message" rows="2" maxlength="300"></textarea></div>
          <div class="field"><label for="notes">Special instructions (optional)</label><input id="notes" name="notes" maxlength="300" placeholder="e.g. call before arriving, surprise delivery"></div>
        </div>
        <div class="panel"><h3>3. Payment</h3>
          <input type="hidden" name="payment_method" value="cod">
          <label class="pay-opt"><input type="radio" checked disabled><span><b>Cash on delivery</b><small>Pay in cash when the flowers arrive.</small></span></label>
        </div>
      </div>
      <aside class="panel sticky"><h3>Order summary</h3>
        <?php foreach ($lines as $line): ?><div class="sum-row"><span><?= e($line['name']) ?> × <?= (int) $line['qty'] ?></span><span><?= e(npr((int) $line['price'] * (int) $line['qty'])) ?></span></div><?php endforeach; ?>
        <div class="sum-row" style="margin-top:.5rem"><span>Subtotal</span><span><?= e(npr($subtotal)) ?></span></div>
        <div class="sum-row"><span>Delivery</span><span><?= $fee === 0 ? 'Free' : e(npr($fee)) ?></span></div>
        <div class="sum-row total"><span>Total</span><span><?= e(npr($total)) ?></span></div>
        <button type="submit" class="btn btn-primary btn-block" style="margin-top:1rem">Place order · cash on delivery</button>
        <p class="muted" style="font-size:.8rem;margin-top:.8rem">Prices are re-checked from the database when you place your order.</p>
      </aside>
    </form><?php endif; ?>
    <?php
}

function render_auth(string $mode): void
{
    $isLogin = $mode === 'login';
    $next = safe_next_page((string) ($_GET['next'] ?? ''));
    render_page_title($isLogin ? 'Log in' : 'Create account', $isLogin ? 'Log in to track your orders.' : 'Save your details and track every order.');
    ?>
    <div class="container"><?php render_flash(); ?>
      <form method="post" action="<?= e(url($mode, $next ? ['next' => $next] : [])) ?>" class="panel auth">
        <?= csrf_field() ?><input type="hidden" name="action" value="<?= $isLogin ? 'login' : 'register' ?>"><input type="hidden" name="next" value="<?= e($next) ?>">
        <h2 class="center"><?= $isLogin ? 'Welcome back' : 'Create your account' ?></h2>
        <?php if (!$isLogin): ?><div class="field"><label for="name">Full name</label><input id="name" name="name" autocomplete="name" required></div><?php endif; ?>
        <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" autocomplete="email" required></div>
        <?php if (!$isLogin): ?><div class="field"><label for="phone">Phone (optional)</label><input id="phone" name="phone" autocomplete="tel" placeholder="98XXXXXXXX"></div><?php endif; ?>
        <div class="field"><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="<?= $isLogin ? 'current-password' : 'new-password' ?>" <?= $isLogin ? '' : 'minlength="8"' ?> required><?php if (!$isLogin): ?><small>At least 8 characters.</small><?php endif; ?></div>
        <button class="btn btn-primary btn-block" type="submit"><?= $isLogin ? 'Log in' : 'Create account' ?></button>
        <p class="center muted" style="margin-top:1rem;font-size:.92rem">
          <?php if ($isLogin): ?>New here? <a class="link-btn" href="<?= e(url('register', $next ? ['next' => $next] : [])) ?>">Create an account</a>
          <?php else: ?>Already have an account? <a class="link-btn" href="<?= e(url('login', $next ? ['next' => $next] : [])) ?>">Log in</a><?php endif; ?>
        </p>
      </form>
    </div>
    <?php
}

function render_account(PDO $pdo, array $user): void
{
    $query = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? OR sender_email = ? ORDER BY created_at DESC');
    $query->execute([$user['id'], $user['email']]);
    $orders = $query->fetchAll();
    render_page_title('My account', $user['name'] . ' · ' . $user['email']);
    ?>
    <div class="container" style="padding:2rem 0 4rem"><h2>My orders</h2>
      <?php if (!$orders): ?><div class="empty"><div class="big">🌸</div><p class="muted">You have not placed any orders yet.</p><a href="<?= e(url('shop')) ?>" class="btn btn-primary">Start shopping</a></div>
      <?php else: ?><div class="table-wrap"><table class="table"><thead><tr><th>Order</th><th>Placed</th><th>Deliver on</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($orders as $order): ?><tr>
          <td><a class="link-btn" href="<?= e(url('order', ['code' => $order['code']])) ?>"><?= e($order['code']) ?></a></td>
          <td><?= e(datetime_label($order['created_at'])) ?></td><td><?= e(date_label($order['delivery_date'])) ?></td><td><?= e(npr($order['total'])) ?></td>
          <td><span class="badge <?= e($order['payment_status']) ?>"><?= $order['payment_method'] === 'cod' ? 'COD' : 'eSewa' ?> · <?= e($order['payment_status']) ?></span></td>
          <td><span class="badge <?= e($order['status']) ?>"><?= e(STATUS_LABELS[$order['status']] ?? $order['status']) ?></span></td>
        </tr><?php endforeach; ?>
      </tbody></table></div><?php endif; ?>
    </div>
    <?php
}

function render_order(PDO $pdo): void
{
    $query = $pdo->prepare('SELECT * FROM orders WHERE code = ? LIMIT 1');
    $query->execute([(string) ($_GET['code'] ?? '')]);
    $order = $query->fetch();
    if (!$order) {
        http_response_code(404);
        echo '<div class="container empty"><h1>Order not found</h1><a class="btn btn-primary" href="' . e(url('shop')) . '">Back to shop</a></div>';
        return;
    }
    $itemsQuery = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
    $itemsQuery->execute([$order['id']]);
    $items = $itemsQuery->fetchAll();
    $flow = ['pending', 'shipped', 'delivered'];
    $timelineStatus = in_array($order['status'], ['confirmed', 'preparing', 'out_for_delivery'], true) ? 'shipped' : $order['status'];
    $stepIndex = array_search($timelineStatus, $flow, true);
    $needsPay = $order['payment_method'] === 'esewa' && $order['payment_status'] !== 'paid' && $order['status'] !== 'cancelled';
    ?>
    <div class="container" style="max-width:860px;padding-bottom:4rem">
      <?php render_flash(); ?>
      <div class="order-hero"><div class="tick"><?= $needsPay ? '⏳' : '✓' ?></div>
        <h1 style="font-size:2rem"><?= $order['status'] === 'cancelled' ? 'Order cancelled' : ($needsPay ? 'Payment pending' : 'Thank you! Your order is placed') ?></h1>
        <p class="muted">Order number <b><?= e($order['code']) ?></b> · placed <?= e(datetime_label($order['created_at'])) ?></p>
        <p class="muted" style="font-size:.88rem">Save this number — you can use it to follow up with us on <?= e(SHOP['phone']) ?>.</p>
      </div>
      <?php if (($_GET['payment'] ?? '') === 'failed'): ?><div class="alert alert-error">Your eSewa payment was not completed. No money was taken. You can try again below.</div><?php endif; ?>
      <?php if (($_GET['paid'] ?? '') === '1' && $order['payment_status'] === 'paid'): ?><div class="alert alert-ok">Payment received via eSewa<?= $order['esewa_ref'] ? ' (ref ' . e($order['esewa_ref']) . ')' : '' ?>. Thank you!</div><?php endif; ?>
      <?php if ($needsPay): ?><div class="alert alert-info">This order is waiting for payment. <a class="link-btn" href="<?= e(url('pay_esewa', ['code' => $order['code']])) ?>">Pay <?= e(npr($order['total'])) ?> with eSewa</a></div><?php endif; ?>
      <?php if ($order['status'] !== 'cancelled'): ?><div class="timeline" aria-label="Order progress"><?php foreach ($flow as $index => $status): ?><span class="<?= $stepIndex !== false && $index <= $stepIndex ? 'done' : '' ?>"><?= e(STATUS_LABELS[$status]) ?></span><?php endforeach; ?></div><?php endif; ?>
      <div class="panel"><h3>Items</h3>
        <?php foreach ($items as $item): ?><div class="sum-row"><span><?= e($item['name']) ?> × <?= (int) $item['qty'] ?></span><span><?= e(npr((int) $item['price'] * (int) $item['qty'])) ?></span></div><?php endforeach; ?>
        <div class="sum-row" style="margin-top:.5rem"><span>Subtotal</span><span><?= e(npr($order['subtotal'])) ?></span></div>
        <div class="sum-row"><span>Delivery</span><span><?= $order['delivery_fee'] ? e(npr($order['delivery_fee'])) : 'Free' ?></span></div>
        <div class="sum-row total"><span>Total</span><span><?= e(npr($order['total'])) ?></span></div>
        <p style="margin-top:.6rem"><span class="badge <?= e($order['payment_status']) ?>"><?= $order['payment_status'] === 'unpaid' && $order['payment_method'] === 'cod' ? 'Pay on delivery' : e($order['payment_status']) ?></span> <span class="muted" style="font-size:.85rem">via <?= $order['payment_method'] === 'cod' ? 'Cash on delivery' : 'eSewa' ?></span></p>
      </div>
      <div class="two-col" style="padding:0">
        <div class="panel"><h3>Delivery</h3><dl class="kv">
          <dt>Recipient</dt><dd><?= e($order['recipient_name']) ?> · <?= e($order['recipient_phone']) ?></dd>
          <dt>Address</dt><dd><?= e($order['address']) ?><?= $order['landmark'] ? ' (' . e($order['landmark']) . ')' : '' ?></dd>
          <dt>When</dt><dd><?= e(date_label($order['delivery_date'])) ?>, <?= e($order['delivery_slot']) ?></dd>
          <?php if ($order['card_message']): ?><dt>Card message</dt><dd><?= nl2br(e($order['card_message'])) ?></dd><?php endif; ?>
          <?php if ($order['notes']): ?><dt>Notes</dt><dd><?= nl2br(e($order['notes'])) ?></dd><?php endif; ?>
        </dl></div>
        <div class="panel"><h3>Ordered by</h3><dl class="kv"><dt>Name</dt><dd><?= e($order['sender_name']) ?></dd><dt>Phone</dt><dd><?= e($order['sender_phone']) ?></dd><dt>Email</dt><dd><?= e($order['sender_email']) ?></dd><dt>Country</dt><dd><?= e($order['sender_country']) ?></dd></dl></div>
      </div>
      <p class="center"><a href="<?= e(url('shop')) ?>" class="btn btn-outline">Continue shopping</a></p>
    </div>
    <?php
}

function render_contact(): void
{
    $contactEmail = (string) app_config()['contact_email'];
    render_page_title('Contact us', 'Questions about an order, a custom bouquet or a wedding? We would love to help.');
    ?>
    <div class="container two-col">
      <form method="post" action="<?= e(url('contact')) ?>" class="panel">
        <?= csrf_field() ?><input type="hidden" name="action" value="contact">
        <h3>Send us a message</h3><?php render_flash(); ?>
        <div class="grid-2"><div class="field"><label for="c-name">Name</label><input id="c-name" name="name" required></div><div class="field"><label for="c-email">Email</label><input id="c-email" type="email" name="email" required></div></div>
        <div class="field"><label for="c-phone">Phone (optional)</label><input id="c-phone" name="phone"></div>
        <div class="field"><label for="c-body">Message</label><textarea id="c-body" name="body" rows="5" required></textarea></div>
        <button class="btn btn-primary" type="submit">Send message</button>
      </form>
      <div class="panel"><h3>Visit or call</h3><dl class="kv" style="grid-template-columns:6rem 1fr">
        <dt>Address</dt><dd><?= e(SHOP['address']) ?></dd><dt>Phone</dt><dd><?= e(SHOP['phone']) ?></dd><?php if ($contactEmail !== ''): ?><dt>Email</dt><dd><a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a></dd><?php endif; ?><dt>Hours</dt><dd><?= e(SHOP['hours']) ?></dd>
      </dl><a class="btn btn-sage btn-block" style="margin-top:1rem" href="https://wa.me/<?= e(SHOP['whatsapp']) ?>" target="_blank" rel="noopener noreferrer">Chat on WhatsApp</a></div>
    </div>
    <?php
}

function render_about(): void
{
    render_page_title('About us', 'A little flower shop with a lot of heart.');
    ?>
    <section class="section"><div class="container" style="max-width:820px">
      <p class="eyebrow">Our story</p><h2>Fresh flowers, thoughtfully delivered.</h2>
      <p class="muted"><?= e(SHOP['name']) ?> brings hand-arranged bouquets and thoughtful gifts to moments big and small in <?= e(DELIVERY['area']) ?>. We believe the right flowers can make a birthday brighter, an anniversary more memorable, or a difficult day a little gentler.</p>
      <p class="muted">Choose from fresh bouquets, boutique wraps, cakes, chocolates and gifts. Add a personal card message, pick a delivery time, and pay cash on delivery.</p>
      <a href="<?= e(url('shop')) ?>" class="btn btn-primary">Explore the shop</a>
    </div></section>
    <?php
}

function render_occasions(): void
{
    render_page_title('Shop by occasion', 'Find the right flowers and gifts for every special moment.');
    ?>
    <section class="section"><div class="container"><div class="cat-grid">
      <?php foreach (OCCASIONS as $key => $occasion): ?><a href="<?= e(url('shop', ['occasion' => $key])) ?>" class="cat-pill"><b><?= e($occasion['label']) ?></b><small><?= e($occasion['blurb']) ?></small></a><?php endforeach; ?>
    </div></div></section>
    <?php
}

function render_admin_dashboard(PDO $pdo): void
{
    $today = nepal_today();
    $stats = [
        ['SELECT COUNT(*) FROM orders WHERE status = ?', ['pending'], 'Pending orders'],
        ['SELECT COUNT(*) FROM orders WHERE delivery_date = ? AND status NOT IN (?, ?)', [$today, 'cancelled', 'delivered'], 'Deliveries today'],
        ["SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status = 'paid' OR (payment_method = 'cod' AND status = 'delivered')", [], 'Revenue (paid / delivered)'],
        ['SELECT COUNT(*) FROM orders', [], 'Total orders'],
        ["SELECT COUNT(*) FROM products WHERE active = 1", [], 'Active products'],
        ["SELECT COUNT(*) FROM users WHERE role = 'customer'", [], 'Customers'],
        ['SELECT COUNT(*) FROM messages', [], 'Messages'],
    ];
    ?>
    <h1 style="font-size:2rem">Dashboard</h1><?php render_flash(); ?>
    <div class="stats"><?php foreach ($stats as [$sql, $params, $label]): $query = $pdo->prepare($sql); $query->execute($params); $value = $query->fetchColumn(); ?>
      <div class="stat"><b><?= str_contains($label, 'Revenue') ? e(npr($value)) : (int) $value ?></b><span><?= e($label) ?></span></div>
    <?php endforeach; ?></div>
    <h2 style="font-size:1.4rem">Recent orders</h2>
    <?php $recent = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 8')->fetchAll(); ?>
    <?php if (!$recent): ?><p class="muted">No orders yet.</p><?php else: ?><div class="table-wrap"><table class="table"><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Placed</th></tr></thead><tbody>
      <?php foreach ($recent as $order): ?><tr><td><a class="link-btn" href="<?= e(url('admin_order', ['id' => $order['id']])) ?>"><?= e($order['code']) ?></a></td><td><?= e($order['sender_name']) ?></td><td><?= e(npr($order['total'])) ?></td><td><span class="badge <?= e($order['payment_status']) ?>"><?= $order['payment_method'] === 'cod' ? 'COD' : 'eSewa' ?> · <?= e($order['payment_status']) ?></span></td><td>
        <form method="post" action="<?= e(url('admin')) ?>" class="row-actions">
          <?= csrf_field() ?><input type="hidden" name="action" value="order_status_update"><input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
          <select class="input" name="status" aria-label="Status for order <?= e($order['code']) ?>" required><?php render_order_status_options((string) $order['status']); ?></select>
          <button class="btn btn-outline btn-sm" type="submit">Save</button>
        </form>
      </td><td><?= e(datetime_label($order['created_at'])) ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
    <?php
}

function render_admin_orders(PDO $pdo): void
{
    $status = in_array((string) ($_GET['status'] ?? ''), ORDER_STATUSES, true) ? (string) $_GET['status'] : '';
    $search = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 40);
    $where = [];
    $params = [];
    if ($status !== '') {
        $where[] = 'status = ?';
        $params[] = $status;
    }
    if ($search !== '') {
        $where[] = '(code LIKE ? OR sender_name LIKE ? OR recipient_name LIKE ? OR sender_phone LIKE ? OR recipient_phone LIKE ?)';
        for ($i = 0; $i < 5; $i++) {
            $params[] = '%' . $search . '%';
        }
    }
    $sql = 'SELECT * FROM orders' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC LIMIT 200';
    $query = $pdo->prepare($sql);
    $query->execute($params);
    $orders = $query->fetchAll();
    ?>
    <h1 style="font-size:2rem">Orders</h1>
    <div class="chips" style="margin-bottom:1rem"><a class="chip <?= !$status ? 'active' : '' ?>" href="<?= e(url('admin_orders')) ?>">All</a>
      <?php foreach (ORDER_STATUSES as $item): ?><a class="chip <?= $status === $item ? 'active' : '' ?>" href="<?= e(url('admin_orders', ['status' => $item])) ?>"><?= e(STATUS_LABELS[$item]) ?></a><?php endforeach; ?>
    </div>
    <form class="filters" method="get" action="<?= e(base_path() . '/index.php') ?>" style="margin-bottom:1rem"><input type="hidden" name="page" value="admin_orders"><?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?><input class="input" type="search" name="q" placeholder="Search code, name or phone" value="<?= e($search) ?>"><button class="btn btn-outline btn-sm" type="submit">Search</button></form>
    <?php if (!$orders): ?><p class="muted">No orders found.</p><?php else: ?><div class="table-wrap"><table class="table"><thead><tr><th>Order</th><th>Customer → Recipient</th><th>Deliver</th><th>Total</th><th>Payment</th><th>Status</th><th>Placed</th></tr></thead><tbody>
      <?php foreach ($orders as $order): ?><tr><td><a class="link-btn" href="<?= e(url('admin_order', ['id' => $order['id']])) ?>"><?= e($order['code']) ?></a></td><td><?= e($order['sender_name']) ?> → <?= e($order['recipient_name']) ?><?= $order['sender_country'] !== 'Nepal' ? ' · ' . e($order['sender_country']) : '' ?></td><td><?= e(date_label($order['delivery_date'])) ?><br><small class="muted"><?= e($order['delivery_slot']) ?></small></td><td><?= e(npr($order['total'])) ?></td><td><span class="badge <?= e($order['payment_status']) ?>"><?= $order['payment_method'] === 'cod' ? 'COD' : 'eSewa' ?> · <?= e($order['payment_status']) ?></span></td><td><span class="badge <?= e($order['status']) ?>"><?= e(STATUS_LABELS[$order['status']] ?? $order['status']) ?></span></td><td><?= e(datetime_label($order['created_at'])) ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
    <?php
}

function render_admin_order(PDO $pdo): void
{
    $id = max(0, (int) ($_GET['id'] ?? 0));
    $query = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    $query->execute([$id]);
    $order = $query->fetch();
    if (!$order) {
        http_response_code(404);
        echo '<h1>Order not found</h1>';
        return;
    }
    $itemsQuery = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
    $itemsQuery->execute([$id]);
    $items = $itemsQuery->fetchAll();
    ?>
    <p><a href="<?= e(url('admin_orders')) ?>" class="link-btn">← All orders</a></p>
    <h1 style="font-size:2rem">Order <?= e($order['code']) ?></h1><p class="muted">Placed <?= e(datetime_label($order['created_at'])) ?></p>
    <form method="post" action="<?= e(url('admin_order', ['id' => $id])) ?>" class="panel">
      <?= csrf_field() ?><input type="hidden" name="action" value="order_update"><input type="hidden" name="id" value="<?= $id ?>">
      <h3>Update order</h3><div class="grid-2">
        <div class="field"><label for="status">Order status</label><select id="status" name="status" required><?php render_order_status_options((string) $order['status']); ?></select></div>
        <div class="field"><label for="payment_status">Payment status</label><select id="payment_status" name="payment_status"><?php foreach (['unpaid', 'paid', 'failed', 'refunded'] as $status): ?><option value="<?= e($status) ?>" <?= $order['payment_status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
      </div>
      <?php if ($order['esewa_ref']): ?><p class="muted" style="font-size:.88rem">eSewa reference: <b><?= e($order['esewa_ref']) ?></b></p><?php endif; ?>
      <button class="btn btn-primary" type="submit">Save changes</button><?php render_flash(); ?>
    </form>
    <div class="panel"><h3>Items</h3><?php foreach ($items as $item): ?><div class="sum-row"><span><?= e($item['name']) ?> × <?= (int) $item['qty'] ?></span><span><?= e(npr((int) $item['price'] * (int) $item['qty'])) ?></span></div><?php endforeach; ?><div class="sum-row total"><span>Total</span><span><?= e(npr($order['total'])) ?></span></div></div>
    <div class="grid-2" style="align-items:start">
      <div class="panel"><h3>Deliver to</h3><dl class="kv"><dt>Recipient</dt><dd><?= e($order['recipient_name']) ?></dd><dt>Phone</dt><dd><?= e($order['recipient_phone']) ?></dd><dt>Address</dt><dd><?= e($order['address']) ?><?= $order['landmark'] ? ' (' . e($order['landmark']) . ')' : '' ?></dd><dt>When</dt><dd><?= e(date_label($order['delivery_date'])) ?>, <?= e($order['delivery_slot']) ?></dd><dt>Card message</dt><dd><?= e($order['card_message'] ?: '—') ?></dd><dt>Notes</dt><dd><?= e($order['notes'] ?: '—') ?></dd></dl></div>
      <div class="panel"><h3>Ordered by</h3><dl class="kv"><dt>Name</dt><dd><?= e($order['sender_name']) ?></dd><dt>Phone</dt><dd><?= e($order['sender_phone']) ?></dd><dt>Email</dt><dd><?= e($order['sender_email']) ?></dd><dt>Country</dt><dd><?= e($order['sender_country']) ?></dd></dl></div>
    </div>
    <?php
}

function render_admin_products(PDO $pdo): void
{
    $products = $pdo->query('SELECT * FROM products ORDER BY kind, category, id')->fetchAll();
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem"><h1 style="font-size:2rem;margin:0">Products</h1><a href="<?= e(url('admin_product')) ?>" class="btn btn-primary">+ Add product</a></div>
    <?php render_flash(); ?>
    <div class="table-wrap" style="margin-top:1rem"><table class="table"><thead><tr><th></th><th>Name</th><th>Type</th><th>Category</th><th>Price</th><th>Status</th><th></th></tr></thead><tbody>
      <?php foreach ($products as $product): ?><tr style="<?= $product['active'] ? '' : 'opacity:.55' ?>">
        <td><?php if ($product['image']): ?><img class="thumb" src="<?= e(image_url($product['image'])) ?>" alt=""><?php endif; ?></td><td><b><?= e($product['name']) ?></b><?= $product['featured'] ? ' ★' : '' ?></td><td><?= $product['kind'] === 'addon' ? 'Add-on' : 'Flower' ?></td><td><?= e($product['category']) ?></td><td><?= e(npr($product['price'])) ?></td><td><span class="badge <?= $product['active'] ? 'paid' : 'cancelled' ?>"><?= $product['active'] ? 'Visible' : 'Hidden' ?></span></td>
        <td><div class="row-actions"><a href="<?= e(url('admin_product', ['id' => $product['id']])) ?>" class="btn btn-outline btn-sm">Edit</a><form method="post" action="<?= e(url('admin_products')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="product_toggle"><input type="hidden" name="id" value="<?= (int) $product['id'] ?>"><button class="btn btn-outline btn-sm" type="submit"><?= $product['active'] ? 'Hide' : 'Show' ?></button></form></div></td>
      </tr><?php endforeach; ?>
    </tbody></table></div>
    <?php
}

function render_admin_product_form(PDO $pdo): void
{
    $id = max(0, (int) ($_GET['id'] ?? 0));
    $product = null;
    if ($id) {
        $query = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
        $query->execute([$id]);
        $product = $query->fetch();
        if (!$product) {
            http_response_code(404);
            echo '<h1>Product not found</h1>';
            return;
        }
    }
    ?>
    <p><a href="<?= e(url('admin_products')) ?>" class="link-btn">← Products</a></p><h1 style="font-size:2rem"><?= $product ? 'Edit product' : 'Add product' ?></h1>
    <?php render_flash(); ?>
    <form method="post" action="<?= e(url('admin_product', $id ? ['id' => $id] : [])) ?>" class="panel" enctype="multipart/form-data" style="max-width:720px">
      <?= csrf_field() ?><input type="hidden" name="action" value="product_save"><input type="hidden" name="id" value="<?= $id ?>">
      <div class="grid-2">
        <div class="field"><label for="kind">Type</label><select id="kind" name="kind"><option value="flower" <?= ($product['kind'] ?? 'flower') === 'flower' ? 'selected' : '' ?>>Flower / bouquet</option><option value="addon" <?= ($product['kind'] ?? '') === 'addon' ? 'selected' : '' ?>>Add-on</option></select></div>
        <div class="field"><label for="category">Category</label><select id="category" name="category" required>
          <optgroup label="Flowers"><?php foreach (FLOWER_CATEGORIES as $category): ?><option data-kind="flower" value="<?= e($category) ?>" <?= ($product['category'] ?? '') === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></optgroup>
          <optgroup label="Add-ons"><?php foreach (ADDON_CATEGORIES as $category): ?><option data-kind="addon" value="<?= e($category) ?>" <?= ($product['category'] ?? '') === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></optgroup>
        </select></div>
      </div>
      <div class="grid-2"><div class="field"><label for="name">Name</label><input id="name" name="name" value="<?= e($product['name'] ?? '') ?>" required></div><div class="field"><label for="price">Price (NPR)</label><input id="price" name="price" type="number" min="1" step="1" value="<?= e($product['price'] ?? '') ?>" required></div></div>
      <div class="field"><label for="description">Description</label><textarea id="description" name="description" rows="4"><?= e($product['description'] ?? '') ?></textarea></div>
      <div class="field"><label for="image">Photo (JPG, PNG or WebP, max 5 MB)</label>
        <?php if (!empty($product['image'])): ?><img src="<?= e(image_url($product['image'])) ?>" alt="Current photo" style="width:120px;border-radius:12px;margin-bottom:.4rem"><small>Leave empty to keep the current photo.</small><?php endif; ?>
        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
      </div>
      <div class="field"><label>Occasions</label><div style="display:flex;flex-wrap:wrap;gap:.4rem 1.2rem">
        <?php $selected = explode(',', (string) ($product['occasions'] ?? '')); foreach (OCCASIONS as $key => $occasion): ?><label class="check" style="margin-bottom:0"><input type="checkbox" name="occasions[]" value="<?= e($key) ?>" <?= in_array($key, $selected, true) ? 'checked' : '' ?>> <?= e($occasion['label']) ?></label><?php endforeach; ?>
      </div></div>
      <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-bottom:1rem">
        <label class="check" style="margin-bottom:0"><input type="checkbox" name="featured" <?= !empty($product['featured']) ? 'checked' : '' ?>> Show as bestseller on home page</label>
        <label class="check" style="margin-bottom:0"><input type="checkbox" name="active" <?= !$product || !empty($product['active']) ? 'checked' : '' ?>> Visible in shop</label>
      </div>
      <button class="btn btn-primary" type="submit"><?= $product ? 'Save changes' : 'Create product' ?></button>
    </form>
    <?php
}

function render_admin_messages(PDO $pdo): void
{
    $messages = $pdo->query('SELECT * FROM messages ORDER BY created_at DESC LIMIT 500')->fetchAll();
    ?>
    <h1 style="font-size:2rem">Customer messages</h1>
    <?php if (!$messages): ?><p class="muted">No messages yet.</p><?php else: ?><div class="table-wrap"><table class="table"><thead><tr><th>Received</th><th>Customer</th><th>Contact</th><th>Message</th></tr></thead><tbody>
      <?php foreach ($messages as $message): ?><tr><td><?= e(datetime_label($message['created_at'])) ?></td><td><?= e($message['name']) ?></td><td><a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a><br><?= e($message['phone']) ?></td><td><?= nl2br(e($message['body'])) ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
    <?php
}

function render_payment_redirect(PDO $pdo): void
{
    $query = $pdo->prepare('SELECT * FROM orders WHERE code = ? LIMIT 1');
    $query->execute([(string) ($_GET['code'] ?? '')]);
    $order = $query->fetch();
    if (!$order) {
        http_response_code(404);
        echo '<div class="container empty"><h1>Order not found</h1></div>';
        return;
    }
    if ($order['payment_method'] !== 'esewa' || $order['payment_status'] === 'paid' || $order['status'] === 'cancelled') {
        redirect_to('order', ['code' => $order['code']]);
    }
    $config = app_config();
    if ($config['esewa_product_code'] === '' || $config['esewa_secret_key'] === '') {
        http_response_code(503);
        echo '<div class="container empty"><h1>Online payment is not configured</h1><p>Contact the shop to arrange payment for this order.</p></div>';
        return;
    }
    $uuid = $order['code'] . '-' . (string) floor(microtime(true) * 1000);
    $pdo->prepare('UPDATE orders SET esewa_uuid = ? WHERE id = ?')->execute([$uuid, $order['id']]);
    $origin = $config['site_url'];
    if ($origin === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $origin = $scheme . '://' . (string) ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_path();
    }
    $productCode = $config['esewa_product_code'];
    $total = (string) (int) $order['total'];
    $signedNames = 'total_amount,transaction_uuid,product_code';
    $message = "total_amount={$total},transaction_uuid={$uuid},product_code={$productCode}";
    $signature = base64_encode(hash_hmac('sha256', $message, (string) $config['esewa_secret_key'], true));
    $fields = [
        'amount' => $total,
        'tax_amount' => '0',
        'total_amount' => $total,
        'transaction_uuid' => $uuid,
        'product_code' => $productCode,
        'product_service_charge' => '0',
        'product_delivery_charge' => '0',
        'success_url' => $origin . '/index.php?page=esewa_success',
        'failure_url' => $origin . '/index.php?page=esewa_failure&code=' . rawurlencode($order['code']),
        'signed_field_names' => $signedNames,
        'signature' => $signature,
    ];
    ?>
    <div class="container center" style="padding:4rem 0"><div class="panel" style="max-width:480px;margin:0 auto">
      <div style="font-size:2.5rem">🔒</div><h2>Redirecting to eSewa…</h2>
      <p class="muted">Order <b><?= e($order['code']) ?></b> · Amount <b><?= e(npr($order['total'])) ?></b></p>
      <p class="muted" style="font-size:.9rem">Please do not close this page. You will return to <?= e(SHOP['name']) ?> after paying.</p>
      <?php if ($productCode === 'EPAYTEST'): ?><p class="alert alert-info" style="font-size:.82rem">Test mode: this uses the eSewa sandbox, no real money is charged.</p><?php endif; ?>
      <form id="esewa-payment" action="<?= e($config['esewa_form_url']) ?>" method="post">
        <?php foreach ($fields as $name => $value): ?><input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>"><?php endforeach; ?>
        <button class="btn btn-primary" type="submit">Continue to eSewa</button>
      </form>
    </div></div>
    <script>document.getElementById('esewa-payment').submit();</script>
    <?php
}
