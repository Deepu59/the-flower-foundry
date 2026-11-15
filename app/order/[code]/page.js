import Link from 'next/link';
import { notFound } from 'next/navigation';
import { getDb } from '@/lib/db';
import { npr, niceDate, niceDateTime } from '@/lib/format';
import { SHOP, STATUS_LABELS } from '@/lib/config';
import ClearCart from '@/components/ClearCart';

export const dynamic = 'force-dynamic';
export const metadata = { title: 'Your order' };

const FLOW = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered'];

export default async function OrderPage({ params, searchParams }) {
  const { code } = await params;
  const sp = await searchParams;
  const db = getDb();
  const o = db.prepare('SELECT * FROM orders WHERE code = ?').get(code);
  if (!o) notFound();
  const items = db.prepare('SELECT * FROM order_items WHERE order_id = ?').all(o.id);

  const failed = sp.payment === 'failed';
  const justPaid = sp.paid === '1' || o.payment_status === 'paid';
  const needsPay = o.payment_method === 'esewa' && o.payment_status !== 'paid' && o.status !== 'cancelled';
  const clear = sp.placed === '1' || sp.paid === '1';
  const stepIndex = FLOW.indexOf(o.status);

  return (
    <div className="container" style={{ maxWidth: 860, paddingBottom: '4rem' }}>
      {clear && <ClearCart />}
      <div className="order-hero">
        <div className="tick">{needsPay ? '⏳' : '✓'}</div>
        <h1 style={{ fontSize: '2rem' }}>
          {o.status === 'cancelled' ? 'Order cancelled' : needsPay ? 'Payment pending' : 'Thank you! Your order is placed'}
        </h1>
        <p className="muted">Order number <b>{o.code}</b> · placed {niceDateTime(o.created_at)}</p>
        <p className="muted" style={{ fontSize: '.88rem' }}>Save this number — you can use it to follow up with us on {SHOP.phone}.</p>
      </div>

      {failed && <div className="alert alert-error">Your eSewa payment was not completed. No money was taken. You can try again below.</div>}
      {sp.paid === '1' && o.payment_status === 'paid' && <div className="alert alert-ok">Payment received via eSewa{o.esewa_ref ? ` (ref ${o.esewa_ref})` : ''}. Thank you!</div>}
      {needsPay && (
        <div className="alert alert-info">
          This order is waiting for payment. <a className="link-btn" href={`/pay/esewa/${o.code}`}>Pay {npr(o.total)} with eSewa</a>
        </div>
      )}

      {o.status !== 'cancelled' && (
        <div className="timeline" aria-label="Order progress">
          {FLOW.map((s, i) => (<span key={s} className={i <= stepIndex ? 'done' : ''}>{STATUS_LABELS[s]}</span>))}
        </div>
      )}

      <div className="panel">
        <h3>Items</h3>
        {items.map((i) => (
          <div className="sum-row" key={i.id}><span>{i.name} × {i.qty}</span><span>{npr(i.price * i.qty)}</span></div>
        ))}
        <div className="sum-row" style={{ marginTop: '.5rem' }}><span>Subtotal</span><span>{npr(o.subtotal)}</span></div>
        <div className="sum-row"><span>Delivery</span><span>{o.delivery_fee ? npr(o.delivery_fee) : 'Free'}</span></div>
        <div className="sum-row total"><span>Total</span><span>{npr(o.total)}</span></div>
        <p style={{ marginTop: '.6rem' }}>
          <span className={`badge ${o.payment_status}`}>{justPaid ? 'Paid' : o.payment_status === 'unpaid' ? (o.payment_method === 'cod' ? 'Pay on delivery' : 'Unpaid') : o.payment_status}</span>{' '}
          <span className="muted" style={{ fontSize: '.85rem' }}>via {o.payment_method === 'cod' ? 'Cash on delivery' : 'eSewa'}</span>
        </p>
      </div>

      <div className="two-col" style={{ padding: 0 }}>
        <div className="panel">
          <h3>Delivery</h3>
          <dl className="kv">
            <dt>Recipient</dt><dd>{o.recipient_name} · {o.recipient_phone}</dd>
            <dt>Address</dt><dd>{o.address}{o.landmark ? ` (${o.landmark})` : ''}</dd>
            <dt>When</dt><dd>{niceDate(o.delivery_date)}, {o.delivery_slot}</dd>
            {o.card_message && (<><dt>Card message</dt><dd>{o.card_message}</dd></>)}
            {o.notes && (<><dt>Notes</dt><dd>{o.notes}</dd></>)}
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

      <p className="center"><Link href="/shop" className="btn btn-outline">Continue shopping</Link></p>
    </div>
  );
}
