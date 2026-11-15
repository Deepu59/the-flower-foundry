import Link from 'next/link';
import { getDb } from '@/lib/db';
import { npr, niceDateTime, todayNP } from '@/lib/format';
import { STATUS_LABELS } from '@/lib/config';

export default function AdminDashboard() {
  const db = getDb();
  const today = todayNP();
  const one = (sql, ...a) => db.prepare(sql).get(...a);

  const pending = one("SELECT COUNT(*) c FROM orders WHERE status = 'pending'").c;
  const todayDeliveries = one("SELECT COUNT(*) c FROM orders WHERE delivery_date = ? AND status NOT IN ('cancelled','delivered')", today).c;
  const revenue = one("SELECT COALESCE(SUM(total),0) s FROM orders WHERE payment_status = 'paid' OR (payment_method = 'cod' AND status = 'delivered')").s;
  const totalOrders = one('SELECT COUNT(*) c FROM orders').c;
  const products = one("SELECT COUNT(*) c FROM products WHERE active = 1").c;
  const customers = one("SELECT COUNT(*) c FROM users WHERE role = 'customer'").c;
  const messages = one('SELECT COUNT(*) c FROM messages').c;
  const recent = db.prepare('SELECT * FROM orders ORDER BY created_at DESC LIMIT 8').all();

  return (
    <>
      <h1 style={{ fontSize: '2rem' }}>Dashboard</h1>
      <div className="stats">
        <div className="stat"><b>{pending}</b><span>Pending orders</span></div>
        <div className="stat"><b>{todayDeliveries}</b><span>Deliveries today</span></div>
        <div className="stat"><b>{npr(revenue)}</b><span>Revenue (paid / delivered)</span></div>
        <div className="stat"><b>{totalOrders}</b><span>Total orders</span></div>
        <div className="stat"><b>{products}</b><span>Active products</span></div>
        <div className="stat"><b>{customers}</b><span>Customers</span></div>
        <div className="stat"><b>{messages}</b><span>Messages</span></div>
      </div>

      <h2 style={{ fontSize: '1.4rem' }}>Recent orders</h2>
      {recent.length === 0 ? (
        <p className="muted">No orders yet.</p>
      ) : (
        <div className="table-wrap">
          <table className="table">
            <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Placed</th></tr></thead>
            <tbody>
              {recent.map((o) => (
                <tr key={o.id}>
                  <td><Link className="link-btn" href={`/admin/orders/${o.id}`}>{o.code}</Link></td>
                  <td>{o.sender_name}</td>
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
