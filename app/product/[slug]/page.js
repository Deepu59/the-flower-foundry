import Link from 'next/link';
import { notFound } from 'next/navigation';
import { getDb } from '@/lib/db';
import { npr } from '@/lib/format';
import { DELIVERY, OCCASIONS } from '@/lib/config';
import ProductBuy from '@/components/ProductBuy';
import ProductCard from '@/components/ProductCard';
import AddToCartButton from '@/components/AddToCartButton';

export const dynamic = 'force-dynamic';

export async function generateMetadata({ params }) {
  const { slug } = await params;
  const p = getDb().prepare('SELECT name, description FROM products WHERE slug = ? AND active = 1').get(slug);
  return p ? { title: p.name, description: p.description } : {};
}

export default async function ProductPage({ params }) {
  const { slug } = await params;
  const db = getDb();
  const p = db.prepare('SELECT * FROM products WHERE slug = ? AND active = 1').get(slug);
  if (!p) notFound();

  const addons = p.kind === 'flower' ? db.prepare("SELECT * FROM products WHERE active = 1 AND kind = 'addon' ORDER BY id").all() : [];
  const related = db
    .prepare('SELECT * FROM products WHERE active = 1 AND kind = ? AND id != ? ORDER BY (category = ?) DESC, featured DESC, id LIMIT 4')
    .all(p.kind, p.id, p.category);
  const product = { id: p.id, slug: p.slug, name: p.name, price: p.price, image: p.image, kind: p.kind };
  const occ = p.occasions.split(',').filter(Boolean).map((k) => OCCASIONS.find((o) => o.key === k)?.label).filter(Boolean);

  return (
    <div className="container">
      <nav className="crumbs" aria-label="Breadcrumb">
        <Link href="/">Home</Link> / <Link href={p.kind === 'addon' ? '/shop?kind=addon' : '/shop'}>Shop</Link> /{' '}
        <Link href={`/shop?${p.kind === 'addon' ? 'kind=addon&' : ''}category=${encodeURIComponent(p.category)}`}>{p.category}</Link>
      </nav>

      <div className="product">
        <div className="product-img">{p.image ? <img src={p.image} alt={p.name} /> : null}</div>
        <div>
          <span className="card-cat">{p.category}</span>
          <h1 style={{ fontSize: 'clamp(1.8rem,4vw,2.6rem)' }}>{p.name}</h1>
          <p className="price" style={{ fontSize: '1.6rem' }}>{npr(p.price)}</p>
          <p className="muted">{p.description}</p>
          {occ.length > 0 && <p className="muted" style={{ fontSize: '.9rem' }}>Perfect for: {occ.join(' · ')}</p>}
          <ProductBuy product={product} />
          <ul className="info-list">
            <li>Hand-arranged by our florists and delivered fresh</li>
            <li>Delivery in {DELIVERY.area} from Rs {DELIVERY.fee} · free above {npr(DELIVERY.freeAbove)}</li>
            <li>Same-day delivery if you order before {DELIVERY.sameDayCutoffHour - 12} PM</li>
            <li>Pay with eSewa or cash on delivery</li>
          </ul>
        </div>
      </div>

      {addons.length > 0 && (
        <section style={{ padding: '1rem 0 2rem' }}>
          <h2>Make it extra special</h2>
          <div className="addon-strip">
            {addons.map((a) => (
              <div className="addon" key={a.id}>
                <img src={a.image} alt={a.name} loading="lazy" />
                <b>{a.name}</b>
                <div className="price" style={{ margin: '.2rem 0 .5rem' }}>{npr(a.price)}</div>
                <AddToCartButton product={{ id: a.id, slug: a.slug, name: a.name, price: a.price, image: a.image, kind: a.kind }} label="+ Add" />
              </div>
            ))}
          </div>
        </section>
      )}

      {related.length > 0 && (
        <section style={{ padding: '1rem 0 4rem' }}>
          <h2>You may also like</h2>
          <div className="grid-products">{related.map((r) => (<ProductCard key={r.id} p={r} />))}</div>
        </section>
      )}
    </div>
  );
}
