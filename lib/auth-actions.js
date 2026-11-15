'use server';

import bcrypt from 'bcryptjs';
import { redirect } from 'next/navigation';
import { getDb } from './db';
import { createSession, destroySession } from './session';

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function safeNext(next) {
  return typeof next === 'string' && next.startsWith('/') && !next.startsWith('//') ? next : '/account';
}

export async function registerAction(prev, formData) {
  const name = String(formData.get('name') || '').trim();
  const email = String(formData.get('email') || '').trim().toLowerCase();
  const phone = String(formData.get('phone') || '').trim();
  const password = String(formData.get('password') || '');
  const next = safeNext(formData.get('next'));

  if (name.length < 2) return { error: 'Please enter your full name.' };
  if (!EMAIL_RE.test(email)) return { error: 'Please enter a valid email address.' };
  if (password.length < 8) return { error: 'Password must be at least 8 characters.' };

  const db = getDb();
  if (db.prepare('SELECT 1 FROM users WHERE email = ?').get(email)) {
    return { error: 'An account with this email already exists. Try logging in.' };
  }
  const info = db
    .prepare('INSERT INTO users (name, email, phone, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?, ?)')
    .run(name, email, phone, bcrypt.hashSync(password, 10), 'customer', Date.now());
  await createSession(info.lastInsertRowid);
  redirect(next);
}

export async function loginAction(prev, formData) {
  const email = String(formData.get('email') || '').trim().toLowerCase();
  const password = String(formData.get('password') || '');
  const next = safeNext(formData.get('next'));

  const user = getDb().prepare('SELECT * FROM users WHERE email = ?').get(email);
  if (!user || !bcrypt.compareSync(password, user.password_hash)) {
    return { error: 'Incorrect email or password.' };
  }
  await createSession(user.id);
  redirect(user.role === 'admin' && next === '/account' ? '/admin' : next);
}

export async function logoutAction() {
  await destroySession();
  redirect('/');
}
