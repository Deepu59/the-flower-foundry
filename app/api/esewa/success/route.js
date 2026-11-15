import { NextResponse } from 'next/server';
import { getDb } from '@/lib/db';
import { verifyResponse, checkStatus, siteUrl } from '@/lib/esewa';

export const dynamic = 'force-dynamic';

function go(path) {
  return NextResponse.redirect(`${siteUrl()}${path}`, 303);
}

export async function GET(request) {
  const data = new URL(request.url).searchParams.get('data');
  if (!data) return go('/cart');

  const check = verifyResponse(data);
  if (!check.ok) return go('/cart');

  const info = check.data;
  // transaction_uuid looks like FS-XXXXXXXX-<timestamp>; the order code is everything before the last dash
  const code = String(info.transaction_uuid || '').replace(/-\d+$/, '');
  const db = getDb();
  const order = db.prepare('SELECT * FROM orders WHERE code = ?').get(code);
  if (!order) return go('/cart');

  if (order.payment_status === 'paid') return go(`/order/${code}?paid=1`);

  const amountOk = Number(info.total_amount) === Number(order.total);
  if (info.status !== 'COMPLETE' || !amountOk || info.product_code === undefined) {
    db.prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ?").run(order.id);
    return go(`/order/${code}?payment=failed`);
  }

  // Second check: ask eSewa directly. If eSewa is unreachable we rely on the signed response above.
  try {
    const remote = await checkStatus({ total: order.total, transactionUuid: info.transaction_uuid });
    if (remote?.status && remote.status !== 'COMPLETE') {
      db.prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ?").run(order.id);
      return go(`/order/${code}?payment=failed`);
    }
  } catch (e) {
    console.warn('eSewa status check skipped:', e.message);
  }

  db.prepare(
    `UPDATE orders SET payment_status = 'paid', esewa_ref = ?, esewa_uuid = ?,
       status = CASE WHEN status = 'pending' THEN 'confirmed' ELSE status END WHERE id = ?`
  ).run(info.transaction_code || '', info.transaction_uuid, order.id);

  return go(`/order/${code}?paid=1`);
}
