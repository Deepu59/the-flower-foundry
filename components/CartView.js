'use client';

import Link from 'next/link';
import { useCart } from './CartProvider';
import { npr } from '@/lib/format';
import { DELIVERY } from '@/lib/config';

export default function CartView({ addons }) {
  const { items, subtotal, setQty, remove, add, ready } = useCart();
  const fee = subtotal >= DELIVERY.freeAbove || subtotal === 0 ? 0 : DELIVERY.fee;
  const inCart = new Set(items.map((i) => i.id));
  const suggestions = addons.filter((a) => !inCart.has(a.id));

  if (!ready) return <div className="container empty"><p className="muted">Loading your cart…</p></div>;

  if (items.length === 0) {
    return (
      <div className="container empty">
        <div className="big">🛍</div>
        <h2>Your cart is empty</h2>
        <p className="muted">Browse our fresh bouquets and add your favourites.</p>
        <Link href="/shop" className="btn btn-primary">Shop bouquets</Link>
      </div>
    );
  }

  return (
    <div className="container two-col">
      <div>
        <div className="panel">
          <h3>Your items</h3>
          {items.map((i) => (
            <div className="cart-line" key={i.id}>
              {i.image ? <img src={i.image} alt={i.name} /> : <div />}
              <div>
                <h4><Link href={`/product/${i.slug}`}>{i.name}</Link></h4>
                <div className="muted" style={{ fontSize: '.9rem' }}>{npr(i.price)} each</div>
                <div style={{ display: 'flex', gap: '1rem', alignItems: 'center', marginTop: '.5rem' }}>
                  <div className="qty">
                    <button type="button" onClick={() => setQty(i.id, i.qty - 1)} aria-label="Decrease">−</button>
                    <span>{i.qty}</span>
                    <button type="button" onClick={() => setQty(i.id, i.qty + 1)} aria-label="Increase">+</button>
                  </div>
                  <button type="button" className="link-btn" onClick={() => remove(i.id)}>Remove</button>
                </div>
              </div>
              <strong>{npr(i.price * i.qty)}</strong>
            </div>
          ))}
        </div>

        {suggestions.length > 0 && (
          <div className="panel">
            <h3>Add a gift 🎁</h3>
            <div className="addon-strip">
              {suggestions.map((a) => (
                <div className="addon" key={a.id}>
                  <img src={a.image} alt={a.name} loading="lazy" />
                  <b>{a.name}</b>
                  <div className="price" style={{ margin: '.2rem 0 .5rem' }}>{npr(a.price)}</div>
                  <button type="button" className="btn btn-outline btn-sm" onClick={() => add(a, 1)}>+ Add</button>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>

      <aside className="panel sticky">
        <h3>Order summary</h3>
        <div className="sum-row"><span>Subtotal</span><span>{npr(subtotal)}</span></div>
        <div className="sum-row"><span>Delivery ({DELIVERY.area.split(' ')[0]})</span><span>{fee === 0 ? 'Free' : npr(fee)}</span></div>
        {fee > 0 && <p className="muted" style={{ fontSize: '.82rem' }}>Add {npr(DELIVERY.freeAbove - subtotal)} more for free delivery.</p>}
        <div className="sum-row total"><span>Total</span><span>{npr(subtotal + fee)}</span></div>
        <Link href="/checkout" className="btn btn-primary btn-block" style={{ marginTop: '1rem' }}>Proceed to checkout</Link>
        <Link href="/shop" className="btn btn-outline btn-block" style={{ marginTop: '.6rem' }}>Continue shopping</Link>
      </aside>
    </div>
  );
}
