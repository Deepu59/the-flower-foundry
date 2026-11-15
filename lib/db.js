import Database from 'better-sqlite3';
import bcrypt from 'bcryptjs';
import fs from 'fs';
import path from 'path';
import { PRODUCTS, ADDONS } from './seed-data';
import { slugify } from './format';

const DATA_DIR = path.join(process.cwd(), 'data');

function init() {
  fs.mkdirSync(DATA_DIR, { recursive: true });
  const db = new Database(path.join(DATA_DIR, 'shop.db'));
  db.pragma('journal_mode = WAL');
  db.pragma('foreign_keys = ON');

  db.exec(`
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      email TEXT NOT NULL UNIQUE,
      phone TEXT,
      password_hash TEXT NOT NULL,
      role TEXT NOT NULL DEFAULT 'customer',
      created_at INTEGER NOT NULL
    );
    CREATE TABLE IF NOT EXISTS sessions (
      token TEXT PRIMARY KEY,
      user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      expires_at INTEGER NOT NULL
    );
    CREATE TABLE IF NOT EXISTS products (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      slug TEXT NOT NULL UNIQUE,
      name TEXT NOT NULL,
      description TEXT NOT NULL DEFAULT '',
      category TEXT NOT NULL,
      kind TEXT NOT NULL DEFAULT 'flower',
      price INTEGER NOT NULL,
      image TEXT NOT NULL DEFAULT '',
      occasions TEXT NOT NULL DEFAULT '',
      featured INTEGER NOT NULL DEFAULT 0,
      active INTEGER NOT NULL DEFAULT 1,
      created_at INTEGER NOT NULL
    );
    CREATE TABLE IF NOT EXISTS orders (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      code TEXT NOT NULL UNIQUE,
      user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
      status TEXT NOT NULL DEFAULT 'pending',
      payment_method TEXT NOT NULL,
      payment_status TEXT NOT NULL DEFAULT 'unpaid',
      subtotal INTEGER NOT NULL,
      delivery_fee INTEGER NOT NULL,
      total INTEGER NOT NULL,
      sender_name TEXT NOT NULL,
      sender_phone TEXT NOT NULL,
      sender_email TEXT NOT NULL,
      sender_country TEXT NOT NULL DEFAULT 'Nepal',
      recipient_name TEXT NOT NULL,
      recipient_phone TEXT NOT NULL,
      address TEXT NOT NULL,
      landmark TEXT NOT NULL DEFAULT '',
      delivery_date TEXT NOT NULL,
      delivery_slot TEXT NOT NULL,
      card_message TEXT NOT NULL DEFAULT '',
      notes TEXT NOT NULL DEFAULT '',
      esewa_uuid TEXT,
      esewa_ref TEXT,
      created_at INTEGER NOT NULL
    );
    CREATE TABLE IF NOT EXISTS order_items (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
      product_id INTEGER,
      name TEXT NOT NULL,
      price INTEGER NOT NULL,
      qty INTEGER NOT NULL
    );
    CREATE TABLE IF NOT EXISTS messages (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      email TEXT NOT NULL,
      phone TEXT,
      body TEXT NOT NULL,
      created_at INTEGER NOT NULL
    );
  `);

  seed(db);
  return db;
}

function uniqueSlug(db, name) {
  const base = slugify(name) || 'item';
  let slug = base;
  let i = 2;
  while (db.prepare('SELECT 1 FROM products WHERE slug = ?').get(slug)) {
    slug = `${base}-${i++}`;
  }
  return slug;
}

function seed(db) {
  const now = Date.now();

  if (db.prepare('SELECT COUNT(*) AS c FROM products').get().c === 0) {
    const insert = db.prepare(
      `INSERT INTO products (slug, name, description, category, kind, price, image, occasions, featured, active, created_at)
       VALUES (@slug, @name, @description, @category, @kind, @price, @image, @occasions, @featured, 1, @created_at)`
    );
    const tx = db.transaction(() => {
      for (const p of PRODUCTS) {
        insert.run({ ...p, slug: uniqueSlug(db, p.name), kind: 'flower', created_at: now });
      }
      for (const a of ADDONS) {
        insert.run({ ...a, slug: uniqueSlug(db, a.name), kind: 'addon', occasions: '', featured: 0, created_at: now });
      }
    });
    tx();
  }

  if (db.prepare("SELECT COUNT(*) AS c FROM users WHERE role = 'admin'").get().c === 0) {
    const email = (process.env.ADMIN_EMAIL || 'admin@theflowershop.np').toLowerCase();
    const password = process.env.ADMIN_PASSWORD || 'ChangeMe123!';
    db.prepare(
      `INSERT OR IGNORE INTO users (name, email, phone, password_hash, role, created_at)
       VALUES ('Shop Admin', ?, '', ?, 'admin', ?)`
    ).run(email, bcrypt.hashSync(password, 10), now);
  }
}

export function getDb() {
  if (!globalThis.__flowerShopDb) {
    globalThis.__flowerShopDb = init();
  }
  return globalThis.__flowerShopDb;
}

export { uniqueSlug };
