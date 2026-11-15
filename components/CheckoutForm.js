'use client';

import Link from 'next/link';
import { useActionState, useState } from 'react';
import { useCart } from './CartProvider';
import { placeOrderAction } from '@/lib/shop-actions';
import { npr } from '@/lib/format';

export default function CheckoutForm({ user, delivery, minDate, maxDate, sameDayOpen }) {
  const { items, subtotal, ready } = useCart();
  const [state, formAction, pending] = useActionState(placeOrderAction, null);
  const [abroad, setAbroad] = useState(false);
  const [payment, setPayment] = useState('esewa');

  const fee = subtotal >= delivery.freeAbove ? 0 : delivery.fee;
  const total = subtotal + fee;
  const effectivePayment = abroad && payment === 'cod' ? 'esewa' : payment;

  if (!ready) return <div className="container empty"><p className="muted">Loading…</p></div>;
  if (items.length === 0) {
    return (
      <div className="container empty">
        <div className="big">🛍</div>
        <h2>Your cart is empty</h2>
        <Link href="/shop" className="btn btn-primary">Shop bouquets</Link>
      </div>
    );
  }

  return (
    <form action={formAction} className="container two-col">
      <input type="hidden" name="cart" value={JSON.stringify(items.map((i) => ({ id: i.id, qty: i.qty })))} />
      <div>
        {state?.error && <div className="alert alert-error" role="alert">{state.error}</div>}

        {!user && (
          <div className="alert alert-info">
            Have an account? <Link href="/login?next=/checkout" className="link-btn">Log in</Link> to track your orders — or continue as a guest.
          </div>
        )}

        <div className="panel">
          <h3>1. Your details</h3>
          <label className="check">
            <input type="checkbox" name="abroad" checked={abroad} onChange={(e) => setAbroad(e.target.checked)} />
            <span>I am ordering from outside Nepal for someone in {delivery.area.split(' ')[0]}</span>
          </label>
          <div className="grid-2">
            <div className="field"><label htmlFor="sender_name">Your name</label><input id="sender_name" name="sender_name" defaultValue={user?.name || ''} required /></div>
            <div className="field"><label htmlFor="sender_phone">Your phone {abroad && '(with country code)'}</label><input id="sender_phone" name="sender_phone" defaultValue={user?.phone || ''} placeholder={abroad ? '+971 50 123 4567' : '98XXXXXXXX'} required /></div>
          </div>
          <div className="grid-2">
            <div className="field"><label htmlFor="sender_email">Your email</label><input id="sender_email" type="email" name="sender_email" defaultValue={user?.email || ''} required /></div>
            {abroad && <div className="field"><label htmlFor="sender_country">Your country</label><input id="sender_country" name="sender_country" placeholder="e.g. United Arab Emirates" required /></div>}
          </div>
        </div>

        <div className="panel">
          <h3>2. Delivery details</h3>
          <div className="grid-2">
            <div className="field"><label htmlFor="recipient_name">Recipient name</label><input id="recipient_name" name="recipient_name" required /></div>
            <div className="field"><label htmlFor="recipient_phone">Recipient phone</label><input id="recipient_phone" name="recipient_phone" placeholder="98XXXXXXXX" required /></div>
          </div>
          <div className="field"><label htmlFor="address">Delivery address ({delivery.area})</label><textarea id="address" name="address" rows={2} placeholder="Ward no., tole / street, house name or number" required /></div>
          <div className="field"><label htmlFor="landmark">Landmark (optional)</label><input id="landmark" name="landmark" placeholder="e.g. near Bheri Hospital" /></div>
          <div className="grid-2">
            <div className="field">
              <label htmlFor="delivery_date">Delivery date</label>
              <input id="delivery_date" type="date" name="delivery_date" min={minDate} max={maxDate} defaultValue={minDate} required />
              <small>{sameDayOpen ? `Order before ${delivery.sameDayCutoffHour - 12} PM for same-day delivery.` : 'Same-day delivery has closed for today.'}</small>
            </div>
            <div className="field">
              <label htmlFor="delivery_slot">Time slot</label>
              <select id="delivery_slot" name="delivery_slot" defaultValue={delivery.slots[0]} required>
                {delivery.slots.map((s) => (<option key={s} value={s}>{s}</option>))}
              </select>
            </div>
          </div>
          <div className="field"><label htmlFor="card_message">Card message (free)</label><textarea id="card_message" name="card_message" rows={2} maxLength={300} placeholder="Happy birthday! Love, …" /></div>
          <div className="field"><label htmlFor="notes">Special instructions (optional)</label><input id="notes" name="notes" maxLength={300} placeholder="e.g. call before arriving, surprise delivery" /></div>
        </div>

        <div className="panel">
          <h3>3. Payment</h3>
          <label className="pay-opt">
            <input type="radio" name="payment_method" value="esewa" checked={effectivePayment === 'esewa'} onChange={() => setPayment('esewa')} />
            <span><b>Pay with eSewa</b><small>Secure online payment — you will be redirected to eSewa.</small></span>
          </label>
          <label className={`pay-opt ${abroad ? 'disabled' : ''}`}>
            <input type="radio" name="payment_method" value="cod" disabled={abroad} checked={effectivePayment === 'cod'} onChange={() => setPayment('cod')} />
            <span><b>Cash on delivery</b><small>{abroad ? 'Not available when ordering from outside Nepal.' : 'Pay in cash when the flowers arrive.'}</small></span>
          </label>
        </div>
      </div>

      <aside className="panel sticky">
        <h3>Order summary</h3>
        {items.map((i) => (
          <div className="sum-row" key={i.id}><span>{i.name} × {i.qty}</span><span>{npr(i.price * i.qty)}</span></div>
        ))}
        <div className="sum-row" style={{ marginTop: '.5rem' }}><span>Subtotal</span><span>{npr(subtotal)}</span></div>
        <div className="sum-row"><span>Delivery</span><span>{fee === 0 ? 'Free' : npr(fee)}</span></div>
        <div className="sum-row total"><span>Total</span><span>{npr(total)}</span></div>
        <button type="submit" className="btn btn-primary btn-block" disabled={pending} style={{ marginTop: '1rem' }}>
          {pending ? 'Placing order…' : effectivePayment === 'esewa' ? `Pay ${npr(total)} with eSewa` : 'Place order (COD)'}
        </button>
        <p className="muted" style={{ fontSize: '.8rem', marginTop: '.8rem' }}>Prices are re-checked by our system when you place the order.</p>
      </aside>
    </form>
  );
}
