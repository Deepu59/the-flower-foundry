'use server';

import fs from 'fs';
import path from 'path';
import crypto from 'crypto';
import { redirect } from 'next/navigation';
import { revalidatePath } from 'next/cache';
import { getDb, uniqueSlug } from './db';
import { requireAdmin } from './session';
import { ORDER_STATUSES, FLOWER_CATEGORIES, ADDON_CATEGORIES } from './config';

const UPLOAD_DIR = path.join(process.cwd(), 'data', 'uploads');
const EXT = { 'image/jpeg': 'jpg', 'image/png': 'png', 'image/webp': 'webp' };

async function saveImage(file) {
  if (!file || typeof file === 'string' || file.size === 0) return null;
  const ext = EXT[file.type];
  if (!ext) throw new Error('Image must be a JPG, PNG or WebP file.');
  if (file.size > 5 * 1024 * 1024) throw new Error('Image must be smaller than 5 MB.');
  fs.mkdirSync(UPLOAD_DIR, { recursive: true });
  const name = `${crypto.randomBytes(8).toString('hex')}.${ext}`;
  fs.writeFileSync(path.join(UPLOAD_DIR, name), Buffer.from(await file.arrayBuffer()));
  return `/uploads/${name}`;
}

function readProduct(formData) {
  const v = (k) => String(formData.get(k) || '').trim();
  const kind = v('kind') === 'addon' ? 'addon' : 'flower';
  const allowed = kind === 'addon' ? ADDON_CATEGORIES : FLOWER_CATEGORIES;
  const name = v('name');
  const price = Math.round(Number(v('price')));
  const category = allowed.includes(v('category')) ? v('category') : allowed[0];
  if (name.length < 2) throw new Error('Please enter a product name.');
  if (!Number.isFinite(price) || price < 1) throw new Error('Please enter a valid price.');
  return {
    name,
    price,
    kind,
    category,
    description: v('description').slice(0, 1000),
    occasions: kind === 'addon' ? '' : formData.getAll('occasions').map(String).join(','),
    featured: formData.get('featured') ? 1 : 0,
    active: formData.get('active') ? 1 : 0,
  };
}

export async function saveProductAction(prev, formData) {
  await requireAdmin();
  const db = getDb();
  const id = Number(formData.get('id')) || 0;
  let p;
  let image;
  try {
    p = readProduct(formData);
    image = await saveImage(formData.get('image'));
  } catch (e) {
    return { error: e.message };
  }

  if (id) {
    db.prepare(
      `UPDATE products SET name=?, description=?, category=?, kind=?, price=?, occasions=?, featured=?, active=?,
        image = COALESCE(?, image) WHERE id=?`
    ).run(p.name, p.description, p.category, p.kind, p.price, p.occasions, p.featured, p.active, image, id);
  } else {
    db.prepare(
      `INSERT INTO products (slug, name, description, category, kind, price, image, occasions, featured, active, created_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`
    ).run(uniqueSlug(db, p.name), p.name, p.description, p.category, p.kind, p.price, image || '', p.occasions, p.featured, p.active, Date.now());
  }
  revalidatePath('/', 'layout');
  redirect('/admin/products?saved=1');
}

export async function toggleProductAction(formData) {
  await requireAdmin();
  const id = Number(formData.get('id'));
  getDb().prepare('UPDATE products SET active = 1 - active WHERE id = ?').run(id);
  revalidatePath('/', 'layout');
}

export async function updateOrderAction(formData) {
  await requireAdmin();
  const id = Number(formData.get('id'));
  const status = String(formData.get('status') || '');
  const paymentStatus = String(formData.get('payment_status') || '');
  const db = getDb();
  if (ORDER_STATUSES.includes(status)) db.prepare('UPDATE orders SET status = ? WHERE id = ?').run(status, id);
  if (['unpaid', 'paid', 'failed', 'refunded'].includes(paymentStatus)) {
    db.prepare('UPDATE orders SET payment_status = ? WHERE id = ?').run(paymentStatus, id);
  }
  revalidatePath('/admin/orders');
  revalidatePath(`/admin/orders/${id}`);
}
