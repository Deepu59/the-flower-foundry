CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  email VARCHAR(254) NOT NULL UNIQUE,
  phone VARCHAR(50) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'customer',
  created_at BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sessions (
  token VARCHAR(128) NOT NULL PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  expires_at BIGINT NOT NULL,
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(255) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  category VARCHAR(100) NOT NULL,
  kind VARCHAR(20) NOT NULL DEFAULT 'flower',
  price INT UNSIGNED NOT NULL,
  image VARCHAR(500) NOT NULL DEFAULT '',
  occasions VARCHAR(500) NOT NULL DEFAULT '',
  featured TINYINT(1) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at BIGINT NOT NULL,
  INDEX idx_products_shop (active, kind, category),
  INDEX idx_products_featured (active, kind, featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  user_id INT UNSIGNED NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'pending',
  payment_method VARCHAR(20) NOT NULL,
  payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
  subtotal INT UNSIGNED NOT NULL,
  delivery_fee INT UNSIGNED NOT NULL,
  total INT UNSIGNED NOT NULL,
  sender_name VARCHAR(160) NOT NULL,
  sender_phone VARCHAR(50) NOT NULL,
  sender_email VARCHAR(254) NOT NULL,
  sender_country VARCHAR(120) NOT NULL DEFAULT 'Nepal',
  recipient_name VARCHAR(160) NOT NULL,
  recipient_phone VARCHAR(50) NOT NULL,
  address VARCHAR(500) NOT NULL,
  landmark VARCHAR(255) NOT NULL DEFAULT '',
  delivery_date DATE NOT NULL,
  delivery_slot VARCHAR(80) NOT NULL,
  card_message VARCHAR(300) NOT NULL DEFAULT '',
  notes VARCHAR(300) NOT NULL DEFAULT '',
  esewa_uuid VARCHAR(100) NULL,
  esewa_ref VARCHAR(100) NULL,
  created_at BIGINT NOT NULL,
  INDEX idx_orders_user (user_id, created_at),
  INDEX idx_orders_status (status, delivery_date),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  name VARCHAR(200) NOT NULL,
  price INT UNSIGNED NOT NULL,
  qty INT UNSIGNED NOT NULL,
  INDEX idx_order_items_order (order_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  email VARCHAR(254) NOT NULL,
  phone VARCHAR(50) NULL,
  body TEXT NOT NULL,
  created_at BIGINT NOT NULL,
  INDEX idx_messages_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
