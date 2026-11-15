import Link from 'next/link';
import { notFound } from 'next/navigation';
import { getDb } from '@/lib/db';
import { npr, niceDate, niceDateTime } from '@/lib/format';
import { ORDER_STATUSES, STATUS_LABELS } from '@/lib/config';
import { updateOrderAction } from '@/lib/admin-actions';

export default async function AdminOrderDetail({ params }) {
  const { id } = await params;
  const db = getDb();
  const o = db.prepare('SELECT * FROM orders WHERE id = ?').get(Number(id));
  if (!o) notFound();
  const items = db.prepare('SELECT * FROM order_items WHERE order_id = ?').all(o.id);

  return (
    <>
      <p><Link href="/admin/orders" className="link-btn">← All orders</Link></p>
      <h1 style={{ fontSize: '2rem' }}>Order {o.code}</h1>
      <p className="muted">Placed {niceDateTime(o.created_at)}</p>

      <form action={updateOrderAction} className="panel">
        <input type="hidden" name="id" value={o.id} />
        <h3>Update order</h3>
        <div className="grid-2">
          <div className="field">
            <label htmlFor="status">Order status</label>
            <select id="status" name="status" defaultValue={o.status}>
              {ORDER_STATUSES.map((s) => (<option key={s} value={s}>{STATUS_LABELS[s]}</option>))}
            </select>
          </div>
          <div className="field">
            <label htmlFor="payment_status">Payment status ({o.payment_method === 'cod' ? 'Cash on delivery' : 'eSewa'})</label>
            <select id="payment_status" name="payment_status" defaultValue={o.payment_status}>
              {['unpaid', 'paid', 'failed', 'refunded'].map((s) => (<option key={s} value={s}>{s}</option>))}
            </select>
          </div>
        </div>
        {o.esewa_ref && <p className="muted" style={{ fontSize: '.88rem' }}>eSewa reference: <b>{o.esewa_ref}</b></p>}
        <button className="btn btn-primary" type="submit">Save changes</button>
      </form>

      <div className="panel">
        <h3>Items</h3>
        {items.map((i) => (<div className="sum-row" key={i.id}><span>{i.name} × {i.qty}</span><span>{npr(i.price * i.qty)}</span></div>))}
        <div className="sum-row" style={{ marginTop: '.5rem' }}><span>Subtotal</span><span>{npr(o.subtotal)}</span></div>
        <div className="sum-row"><span>Delivery</span><span>{o.delivery_fee ? npr(o.delivery_fee) : 'Free'}</span></div>
        <div className="sum-row total"><span>Total</span><span>{npr(o.total)}</span></div>
      </div>

      <div className="grid-2" style={{ alignItems: 'start' }}>
        <div className="panel">
          <h3>Deliver to</h3>
          <dl className="kv">
            <dt>Recipient</dt><dd>{o.recipient_name}</dd>
            <dt>Phone</dt><dd>{o.recipient_phone}</dd>
            <dt>Address</dt><dd>{o.address}{o.landmark ? ` (${o.landmark})` : ''}</dd>
            <dt>When</dt><dd>{niceDate(o.delivery_date)}, {o.delivery_slot}</dd>
            <dt>Card message</dt><dd>{o.card_message || '—'}</dd>
            <dt>Notes</dt><dd>{o.notes || '—'}</dd>
          </dl>
        </div>
        <div className="panel">
          <h3>Ordered by</h3>
          <dl className="kv">
            <dt>Name</dt><dd>{o.sender_name}</dd>
            <dt>Phone</dt><dd>{o.sender_phone}</dd>
            <dt>Email</dt><dd>{o.sender_email}</dd>
            <dt>Country</dt><dd>{o.sender_country}</dd>
          </dl>
        </div>
      </div>
    </>
  );
}
