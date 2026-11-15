'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useCart } from './CartProvider';

export default function ProductBuy({ product }) {
  const { add } = useCart();
  const [qty, setQty] = useState(1);
  const [added, setAdded] = useState(false);

  function addNow() {
    add(product, qty);
    setAdded(true);
    setTimeout(() => setAdded(false), 1600);
  }

  return (
    <div>
      <div style={{ display: 'flex', gap: '1rem', alignItems: 'center', flexWrap: 'wrap', margin: '1.2rem 0' }}>
        <div className="qty" aria-label="Quantity">
          <button type="button" onClick={() => setQty((q) => Math.max(1, q - 1))} aria-label="Decrease">−</button>
          <span>{qty}</span>
          <button type="button" onClick={() => setQty((q) => Math.min(20, q + 1))} aria-label="Increase">+</button>
        </div>
        <button type="button" className="btn btn-primary" onClick={addNow}>{added ? '✓ Added to cart' : 'Add to cart'}</button>
      </div>
      <Link href="/checkout" className="btn btn-outline" onClick={() => add(product, qty)}>Buy now</Link>
    </div>
  );
}
