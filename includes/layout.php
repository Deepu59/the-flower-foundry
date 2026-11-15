<?php
declare(strict_types=1);

function render_header(?array $user, string $page, string $title): void
{
    $cartCount = 0;
    foreach ((array) ($_SESSION['cart'] ?? []) as $qty) {
        $cartCount += (int) $qty;
    }
    $links = [
        'home' => ['home', 'Home'],
        'shop' => ['shop', 'Shop'],
        'occasions' => ['occasions', 'Occasions'],
        'about' => ['about', 'About'],
        'contact' => ['contact', 'Contact'],
    ];
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="description" content="Order fresh flower bouquets, cakes, chocolates and gifts for delivery in Nepalgunj.">
      <title><?= e($title) ?> | <?= e(SHOP['name']) ?></title>
      <link rel="icon" href="<?= e(base_path() . '/public/images/flower-foundry-mark.png') ?>">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="<?= e(base_path() . '/assets/css/style.css') ?>">
    </head>
    <body>
    <div class="topbar">🌸 Same-day flower delivery in <?= e(SHOP['city']) ?> — order before 3 PM · Delivery from <?= e(npr(DELIVERY['fee'])) ?></div>
    <header class="site-header">
      <div class="container header-inner">
        <a href="<?= e(url('home')) ?>" class="brand">
          <img src="<?= e(base_path() . '/public/images/flower-foundry-mark.png') ?>" alt="" aria-hidden="true">
          <?= e(SHOP['name']) ?>
        </a>
        <nav class="nav" id="main-nav" aria-label="Main">
          <?php foreach ($links as $key => [$target, $label]): ?>
            <a href="<?= e(url($target)) ?>" class="<?= $page === $key ? 'active' : '' ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
          <?php if (($user['role'] ?? '') === 'admin'): ?><a href="<?= e(url('admin')) ?>">Admin</a><?php endif; ?>
        </nav>
        <div class="header-actions">
          <?php if ($user): ?>
            <a href="<?= e(url('account')) ?>" class="icon-btn hide-sm">Hi, <?= e(explode(' ', $user['name'])[0]) ?></a>
            <form method="post" action="<?= e(url($page)) ?>" class="hide-sm">
              <?= csrf_field() ?><input type="hidden" name="action" value="logout">
              <button class="icon-btn" type="submit">Logout</button>
            </form>
          <?php else: ?>
            <a href="<?= e(url('login')) ?>" class="icon-btn hide-sm">Login</a>
          <?php endif; ?>
          <a href="<?= e(url('cart')) ?>" class="icon-btn" aria-label="Cart">🛍 Cart <span class="cart-count"><?= $cartCount ?></span></a>
          <button class="icon-btn menu-toggle" type="button" aria-label="Menu" aria-expanded="false" aria-controls="main-nav">☰</button>
        </div>
      </div>
    </header>
    <main>
    <?php
}

function render_footer(): void
{
    $contactEmail = (string) app_config()['contact_email'];
    ?>
    </main>
    <footer class="site-footer">
      <div class="container">
        <div class="footer-grid">
          <div>
            <h4><?= e(SHOP['name']) ?></h4>
            <p style="font-size:.92rem"><?= e(SHOP['tagline']) ?>. Hand-tied bouquets, cakes, chocolates and gifts for every occasion.</p>
          </div>
          <div><h4>Shop</h4><ul>
            <li><a href="<?= e(url('shop')) ?>">All bouquets</a></li>
            <li><a href="<?= e(url('shop', ['category' => 'Boutique Bouquets'])) ?>">Boutique bouquets</a></li>
            <li><a href="<?= e(url('shop', ['category' => 'Roses'])) ?>">Roses</a></li>
            <li><a href="<?= e(url('shop', ['kind' => 'addon'])) ?>">Cakes &amp; gifts</a></li>
          </ul></div>
          <div><h4>Help</h4><ul>
            <li><a href="<?= e(url('occasions')) ?>">Occasions</a></li>
            <li><a href="<?= e(url('about')) ?>">About us</a></li>
            <li><a href="<?= e(url('contact')) ?>">Contact</a></li>
            <li><a href="<?= e(url('account')) ?>">My orders</a></li>
          </ul></div>
          <div><h4>Visit &amp; call</h4><ul>
            <li>📍 <?= e(SHOP['address']) ?></li>
            <li>📞 <?= e(SHOP['phone']) ?></li>
            <?php if ($contactEmail !== ''): ?><li>✉️ <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a></li><?php endif; ?>
            <li>🕘 <?= e(SHOP['hours']) ?></li>
          </ul></div>
        </div>
        <div class="footer-bottom">
          <span>© <?= date('Y') ?> <?= e(SHOP['name']) ?>. All rights reserved.</span>
          <span>Delivering to <?= e(DELIVERY['area']) ?> · Cash on delivery</span>
        </div>
      </div>
    </footer>
    <a class="wa-float" href="https://wa.me/<?= e(SHOP['whatsapp']) ?>" target="_blank" rel="noopener noreferrer">💬 Chat on WhatsApp</a>
    <script src="<?= e(base_path() . '/assets/js/app.js') ?>" defer></script>
    </body>
    </html>
    <?php
}

function render_page_title(string $title, string $description = ''): void
{
    ?>
    <section class="page-title"><div class="container">
      <h1><?= e($title) ?></h1>
      <?php if ($description !== ''): ?><p><?= e($description) ?></p><?php endif; ?>
    </div></section>
    <?php
}

function render_flash(): void
{
    $error = $_SESSION['flash_error'] ?? '';
    $success = $_SESSION['flash_success'] ?? '';
    unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    if ($error !== '') {
        echo '<div class="alert alert-error">' . e($error) . '</div>';
    }
    if ($success !== '') {
        echo '<div class="alert alert-ok">' . e($success) . '</div>';
    }
}

function render_product_card(array $product, string $returnPage = 'shop', array $returnParams = []): void
{
    ?>
    <article class="card">
      <a href="<?= e(url('product', ['slug' => $product['slug']])) ?>" class="card-img" aria-label="<?= e($product['name']) ?>">
        <?php if ($product['image'] !== ''): ?><img src="<?= e(image_url($product['image'])) ?>" alt="<?= e($product['name']) ?>" loading="lazy"><?php endif; ?>
        <?php if ((int) $product['featured'] === 1): ?><span class="tag">Bestseller</span><?php endif; ?>
      </a>
      <div class="card-body">
        <span class="card-cat"><?= e($product['category']) ?></span>
        <h3><a href="<?= e(url('product', ['slug' => $product['slug']])) ?>"><?= e($product['name']) ?></a></h3>
        <div class="card-foot">
          <span class="price"><?= e(npr($product['price'])) ?></span>
          <form method="post" action="<?= e(url($returnPage, $returnParams)) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="cart_add">
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <input type="hidden" name="qty" value="1">
            <input type="hidden" name="return_page" value="<?= e($returnPage) ?>">
            <?php if ($returnPage === 'product' && isset($returnParams['slug'])): ?><input type="hidden" name="return_slug" value="<?= e($returnParams['slug']) ?>"><?php endif; ?>
            <button type="submit" class="btn btn-outline btn-sm">Add</button>
          </form>
        </div>
      </div>
    </article>
    <?php
}

function render_admin_nav(string $page): void
{
    $links = [
        'admin' => ['admin', 'Dashboard'],
        'admin_orders' => ['admin_orders', 'Orders'],
        'admin_products' => ['admin_products', 'Products'],
        'admin_messages' => ['admin_messages', 'Messages'],
    ];
    ?>
    <nav class="admin-nav" aria-label="Admin">
      <?php foreach ($links as $key => [$target, $label]): ?>
        <a href="<?= e(url($target)) ?>" class="<?= $page === $key ? 'active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php
}
