import Link from 'next/link';
import { getDb } from '@/lib/db';
import { requireUser } from '@/lib/session';
import { npr, niceDate, niceDateTime } from '@/lib/format';
import { STATUS_LABELS } from '@/lib/config';

export const dynamic = 'force-dynamic';
export const metadata = { title: 'My account' };

export default async function AccountPage() {
  const user = await requireUser('/account');
  const orders = getDb()
    .prepare('SELECT * FROM orders WHERE user_id = ? OR sender_email = ? ORDER BY created_at DESC')
    .all(user.id, user.email);

  return (
    <>
      <section className="page-title"><div className="container"><h1>My account</h1><p>{user.name} · {user.email}</p></div></section>
      <div className="container" style={{ padding: '2rem 0 4rem' }}>
        <h2>My orders</h2>
        {orders.length === 0 ? (
          <div className="empty">
            <div className="big">🌸</div>
            <p className="muted">You have not placed any orders yet.</p>
            <Link href="/shop" className="btn btn-primary">Start shopping</Link>
          </div>
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead><tr><th>Order</th><th>Placed</th><th>Deliver on</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead>
              <tbody>
                {orders.map((o) => (
                  <tr key={o.id}>
                    <td><Link className="link-btn" href={`/order/${o.code}`}>{o.code}</Link></td>
                    <td>{niceDateTime(o.created_at)}</td>
                    <td>{niceDate(o.delivery_date)}</td>
                    <td>{npr(o.total)}</td>
                    <td><span className={`badge ${o.payment_status}`}>{o.payment_method === 'cod' ? 'COD' : 'eSewa'} · {o.payment_status}</span></td>
                    <td><span className={`badge ${o.status}`}>{STATUS_LABELS[o.status]}</span></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </>
  );
}
