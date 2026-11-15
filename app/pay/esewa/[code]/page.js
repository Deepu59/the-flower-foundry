import { redirect, notFound } from 'next/navigation';
import { getDb } from '@/lib/db';
import { ESEWA, buildPaymentFields } from '@/lib/esewa';
import { npr } from '@/lib/format';
import AutoSubmit from '@/components/AutoSubmit';

export const dynamic = 'force-dynamic';
export const metadata = { title: 'Pay with eSewa' };

export default async function PayWithEsewa({ params }) {
  const { code } = await params;
  const db = getDb();
  const order = db.prepare('SELECT * FROM orders WHERE code = ?').get(code);
  if (!order) notFound();
  if (order.payment_method !== 'esewa' || order.payment_status === 'paid' || order.status === 'cancelled') {
    redirect(`/order/${code}`);
  }

  // A fresh transaction id for every attempt (eSewa rejects re-used ids)
  const transactionUuid = `${order.code}-${Date.now()}`;
  db.prepare('UPDATE orders SET esewa_uuid = ? WHERE id = ?').run(transactionUuid, order.id);
  const fields = buildPaymentFields({ total: order.total, transactionUuid, orderCode: order.code });

  return (
    <div className="container center" style={{ padding: '4rem 0' }}>
      <div className="panel" style={{ maxWidth: 480, margin: '0 auto' }}>
        <div style={{ fontSize: '2.5rem' }}>🔒</div>
        <h2>Redirecting to eSewa…</h2>
        <p className="muted">
          Order <b>{order.code}</b> · Amount <b>{npr(order.total)}</b>
        </p>
        <p className="muted" style={{ fontSize: '.9rem' }}>Please do not close this page. You will return to {`THE FLOWER SHOP`} after paying.</p>
        {ESEWA.productCode === 'EPAYTEST' && (
          <p className="alert alert-info" style={{ fontSize: '.82rem' }}>Test mode: this uses the eSewa sandbox, no real money is charged.</p>
        )}
        <AutoSubmit action={ESEWA.formUrl} fields={fields} />
      </div>
    </div>
  );
}
