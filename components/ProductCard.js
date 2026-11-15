import Link from 'next/link';
import { npr } from '@/lib/format';
import AddToCartButton from './AddToCartButton';

export default function ProductCard({ p }) {
  const product = { id: p.id, slug: p.slug, name: p.name, price: p.price, image: p.image, kind: p.kind };
  return (
    <article className="card">
      <Link href={`/product/${p.slug}`} className="card-img" aria-label={p.name}>
        {p.image ? <img src={p.image} alt={p.name} loading="lazy" /> : null}
        {p.featured ? <span className="tag">Bestseller</span> : null}
      </Link>
      <div className="card-body">
        <span className="card-cat">{p.category}</span>
        <h3>
          <Link href={`/product/${p.slug}`}>{p.name}</Link>
        </h3>
        <div className="card-foot">
          <span className="price">{npr(p.price)}</span>
          <AddToCartButton product={product} />
        </div>
      </div>
    </article>
  );
}
