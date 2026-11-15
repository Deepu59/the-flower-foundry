'use server';

import crypto from 'crypto';
import { redirect } from 'next/navigation';
import { getDb } from './db';
import { getUser } from './session';
import { DELIVERY } from './config';
import { addDaysISO, nepalNow, todayNP } from './format';

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const NP_MOBILE = /^(\+?977)?[- ]?9[78]\d{8}$/;
const INTL_PHONE = /^\+?[0-9][0-9 \-]{6,17}$/;

function orderCode() {
  const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  const bytes = crypto.randomBytes(8);
  let s = '';
  for (const b of bytes) s += alphabet[b % alphabet.length];
  return `FS-${s}`;
}

export async function placeOrderAction(prev, formData) {
  const v = (k) => String(formData.get(k) || '').trim();

  // --- cart (re-priced from the database, never trusted from the browser) ---
  let cart;
  try {
    cart = JSON.parse(v('cart'));
  } catch {
    cart = [];
  }
  if (!Array.isArray(cart) || cart.length === 0) return { error: 'Your cart is empty.' };

  const db = getDb();
  const lines = [];
  for (const entry of cart) {
    const id = Number(entry?.id);
    const qty = Math.min(20, Math.max(1, Math.floor(Number(entry?.qty) || 1)));
    const p = db.prepare('SELECT id, name, price FROM products WHERE id = ? AND active = 1').get(id);
    if (!p) return { error: 'One of the items in your cart is no longer available. Please review your cart.' };
    lines.push({ product_id: p.id, name: p.name, price: p.price, qty });
  }
  const subtotal = lines.reduce((sum, l) => sum + l.price * l.qty, 0);
  const deliveryFee = subtotal >= DELIVERY.freeAbove ? 0 : DELIVERY.fee;
  const total = subtotal + deliveryFee;

  // --- fields ---
  const abroad = v('abroad') === 'on';
  const sender_name = v('sender_name');
  const sender_phone = v('sender_phone');
  const sender_email = v('sender_email').toLowerCase();
  const sender_country = abroad ? v('sender_country') || 'Outside Nepal' : 'Nepal';
  const recipient_name = v('recipient_name');
  const recipient_phone = v('recipient_phone');
  const address = v('address');
  const landmark = v('landmark');
  const delivery_date = v('delivery_date');
  const delivery_slot = v('delivery_slot');
  const card_message = v('card_message').slice(0, 300);
  const notes = v('notes').slice(0, 300);
  const payment_method = v('payment_method');

  if (sender_name.length < 2) return { error: 'Please enter your name.' };
  if (!EMAIL_RE.test(sender_email)) return { error: 'Please enter a valid email so we can send your order details.' };
  if (!(abroad ? INTL_PHONE : NP_MOBILE).test(sender_phone)) {
    return { error: abroad ? 'Please enter your phone number with country code.' : 'Please enter a valid Nepali mobile number (98XXXXXXXX).' };
  }
  if (recipient_name.length < 2) return { error: "Please enter the recipient's name." };
  if (!NP_MOBILE.test(recipient_phone)) return { error: "Please enter the recipient's Nepali mobile number (98XXXXXXXX)." };
  if (address.length < 5) return { error: `Please enter the delivery address in ${DELIVERY.area}.` };
  if (!DELIVERY.slots.includes(delivery_slot)) return { error: 'Please choose a delivery time slot.' };

  const today = todayNP();
  const last = addDaysISO(today, DELIVERY.maxDaysAhead);
  if (!/^\d{4}-\d{2}-\d{2}$/.test(delivery_date) || delivery_date < today || delivery_date > last) {
    return { error: 'Please choose a valid delivery date.' };
  }
  if (delivery_date === today && nepalNow().getUTCHours() >= DELIVERY.sameDayCutoffHour) {
    return { error: 'Same-day delivery is closed for today. Please pick tomorrow or later.' };
  }

  if (!['cod', 'esewa'].includes(payment_method)) return { error: 'Please choose a payment method.' };
  if (abroad && payment_method === 'cod') {
    return { error: 'Cash on delivery is not available when ordering from outside Nepal. Please pay with eSewa.' };
  }

  const user = await getUser();
  let code = orderCode();
  while (db.prepare('SELECT 1 FROM orders WHERE code = ?').get(code)) code = orderCode();

  const insertOrder = db.prepare(
    `INSERT INTO orders (code, user_id, status, payment_method, payment_status, subtotal, delivery_fee, total,
      sender_name, sender_phone, sender_email, sender_country, recipient_name, recipient_phone, address, landmark,
      delivery_date, delivery_slot, card_message, notes, created_at)
     VALUES (?, ?, 'pending', ?, 'unpaid', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`
  );
  const insertItem = db.prepare('INSERT INTO order_items (order_id, product_id, name, price, qty) VALUES (?, ?, ?, ?, ?)');

  db.transaction(() => {
    const info = insertOrder.run(
      code, user?.id ?? null, payment_method, subtotal, deliveryFee, total,
      sender_name, sender_phone, sender_email, sender_country, recipient_name, recipient_phone, address, landmark,
      delivery_date, delivery_slot, card_message, notes, Date.now()
    );
    for (const l of lines) insertItem.run(info.lastInsertRowid, l.product_id, l.name, l.price, l.qty);
  })();

  if (payment_method === 'esewa') redirect(`/pay/esewa/${code}`);
  redirect(`/order/${code}?placed=1`);
}

export async function contactAction(prev, formData) {
  const v = (k) => String(formData.get(k) || '').trim();
  const name = v('name');
  const email = v('email').toLowerCase();
  const phone = v('phone');
  const body = v('body').slice(0, 2000);
  if (name.length < 2) return { error: 'Please enter your name.' };
  if (!EMAIL_RE.test(email)) return { error: 'Please enter a valid email.' };
  if (body.length < 5) return { error: 'Please write a short message.' };
  getDb()
    .prepare('INSERT INTO messages (name, email, phone, body, created_at) VALUES (?, ?, ?, ?, ?)')
    .run(name, email, phone, body, Date.now());
  return { ok: true };
}
