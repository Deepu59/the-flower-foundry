import Link from 'next/link';
import { getDb } from '@/lib/db';
import { npr, niceDate, niceDateTime } from '@/lib/format';
import { ORDER_STATUSES, STATUS_LABELS } from '@/lib/config';

export default async function AdminOrders({ searchParams }) {
  const sp = await searchParams;
  const status = ORDER_STATUSES.includes(sp.status) ? sp.status : '';
  const q = String(sp.q || '').trim().slice(0, 40);
  const where = [];
  const params = {};
  if (status) { where.push('status = @status'); params.status = status; }
  if (q) { where.push('(code LIKE @q OR sender_name LIKE @q OR recipient_name LIKE @q OR sender_phone LIKE @q OR recipient_phone LIKE @q)'); params.q = `%${q}%`; }
  const orders = getDb().prepare(`SELECT * FROM orders ${where.length ? 'WHERE ' + where.join(' AND ') : ''} ORDER BY created_at DESC LIMIT 200`).all(params);

  return (
    <>
      <h1 style={{ fontSize: '2rem' }}>Orders</h1>
      <div className="chips" style={{ marginBottom: '1rem' }}>
        <Link className={`chip ${!status ? 'active' : ''}`} href="/admin/orders">All</Link>
        {ORDER_STATUSES.map((s) => (<Link key={s} className={`chip ${status === s ? 'active' : ''}`} href={`/admin/orders?status=${s}`}>{STATUS_LABELS[s]}</Link>))}
      </div>
      <form className="filters" style={{ marginBottom: '1rem' }}>
        {status && <input type="hidden" name="status" value={status} />}
        <input className="input" type="search" name="q" placeholder="Search code, name or phone" defaultValue={q} />
        <button className="btn btn-outline btn-sm" type="submit">Search</button>
      </form>
      {orders.length === 0 ? (
        <p className="muted">No orders found.</p>
      ) : (
        <div className="table-wrap">
          <table className="table">
            <thead><tr><th>Order</th><th>Customer → Recipient</th><th>Deliver</th><th>Total</th><th>Payment</th><th>Status</th><th>Placed</th></tr></thead>
            <tbody>
              {orders.map((o) => (
                <tr key={o.id}>
                  <td><Link className="link-btn" href={`/admin/orders/${o.id}`}>{o.code}</Link></td>
                  <td>{o.sender_name} → {o.recipient_name}{o.sender_country !== 'Nepal' && <> <span className="badge">{o.sender_country}</span></>}</td>
                  <td>{niceDate(o.delivery_date)}<br /><small className="muted">{o.delivery_slot}</small></td>
                  <td>{npr(o.total)}</td>
                  <td><span className={`badge ${o.payment_status}`}>{o.payment_method === 'cod' ? 'COD' : 'eSewa'} · {o.payment_status}</span></td>
                  <td><span className={`badge ${o.status}`}>{STATUS_LABELS[o.status]}</span></td>
                  <td>{niceDateTime(o.created_at)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </>
  );
}
