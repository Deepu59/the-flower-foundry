'use client';

import { useState } from 'react';
import { useCart } from './CartProvider';

export default function AddToCartButton({ product, qty = 1, big = false, label = 'Add to cart' }) {
  const { add } = useCart();
  const [done, setDone] = useState(false);

  function onClick() {
    add(product, qty);
    setDone(true);
    setTimeout(() => setDone(false), 1400);
  }

  return (
    <button type="button" className={`btn btn-primary ${big ? '' : 'btn-sm'}`} onClick={onClick}>
      {done ? '✓ Added' : label}
    </button>
  );
}
