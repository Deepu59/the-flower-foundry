import { getDb } from '@/lib/db';
import CartView from '@/components/CartView';

export const dynamic = 'force-dynamic';
export const metadata = { title: 'Your cart' };

export default function CartPage() {
  const addons = getDb().prepare("SELECT id, slug, name, price, image, kind FROM products WHERE active = 1 AND kind = 'addon' ORDER BY id").all();
  return (
    <>
      <section className="page-title"><div className="container"><h1>Your cart</h1></div></section>
      <CartView addons={addons} />
    </>
  );
}
