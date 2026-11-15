import Link from 'next/link';
import { getDb } from '@/lib/db';
import { FLOWER_CATEGORIES, ADDON_CATEGORIES, OCCASIONS } from '@/lib/config';
import ProductCard from '@/components/ProductCard';

export const dynamic = 'force-dynamic';
export const metadata = { title: 'Shop flowers & gifts' };

const SORTS = {
  featured: 'featured DESC, id',
  price_asc: 'price ASC',
  price_desc: 'price DESC',
  newest: 'created_at DESC, id DESC',
};

export default async function Shop({ searchParams }) {
  const sp = await searchParams;
  const kind = sp.kind === 'addon' ? 'addon' : 'flower';
  const categories = kind === 'addon' ? ADDON_CATEGORIES : FLOWER_CATEGORIES;
  const category = categories.includes(sp.category) ? sp.category : '';
  const occasion = OCCASIONS.find((o) => o.key === sp.occasion)?.key || '';
  const q = String(sp.q || '').trim().slice(0, 60);
  const sort = SORTS[sp.sort] ? sp.sort : 'featured';

  const where = ['active = 1', 'kind = @kind'];
  const params = { kind };
  if (category) { where.push('category = @category'); params.category = category; }
  if (occasion) { where.push("(',' || occasions || ',') LIKE @occasion"); params.occasion = `%,${occasion},%`; }
  if (q) { where.push('(name LIKE @q OR description LIKE @q)'); params.q = `%${q}%`; }

  const products = getDb().prepare(`SELECT * FROM products WHERE ${where.join(' AND ')} ORDER BY ${SORTS[sort]}`).all(params);

  const href = (over) => {
    const u = new URLSearchParams();
    const merged = { kind: kind === 'addon' ? 'addon' : '', category, occasion, q, sort: sort === 'featured' ? '' : sort, ...over };
    for (const [k, v] of Object.entries(merged)) if (v) u.set(k, v);
    const s = u.toString();
    return s ? `/shop?${s}` : '/shop';
  };

  const title = kind === 'addon' ? 'Cakes, chocolates & gifts' : occasion ? `${OCCASIONS.find((o) => o.key === occasion).label} flowers` : category || 'All flower bouquets';

  return (
    <>
      <section className="page-title">
        <div className="container">
          <h1>{title}</h1>
          <p>{kind === 'addon' ? 'Pair your flowers with something sweet or cuddly.' : 'Fresh, hand-tied bouquets delivered across Nepalgunj.'}</p>
        </div>
      </section>

      <section className="section" style={{ paddingTop: '1.5rem' }}>
        <div className="container">
          <div className="shop-bar">
            <div className="chips">
              <Link className={`chip ${kind === 'flower' && !category && !occasion ? 'active' : ''}`} href="/shop">All flowers</Link>
              {FLOWER_CATEGORIES.map((c) => (
                <Link key={c} className={`chip ${kind === 'flower' && category === c ? 'active' : ''}`} href={`/shop?category=${encodeURIComponent(c)}`}>{c}</Link>
              ))}
              <Link className={`chip ${kind === 'addon' ? 'active' : ''}`} href="/shop?kind=addon">Cakes &amp; Gifts</Link>
            </div>
          </div>

          <form className="shop-bar" action="/shop">
            {kind === 'addon' && <input type="hidden" name="kind" value="addon" />}
            {category && <input type="hidden" name="category" value={category} />}
            <div className="filters">
              <input className="input" type="search" name="q" placeholder="Search…" defaultValue={q} aria-label="Search" />
              {kind === 'flower' && (
                <select className="input" name="occasion" defaultValue={occasion} aria-label="Occasion">
                  <option value="">Any occasion</option>
                  {OCCASIONS.map((o) => (<option key={o.key} value={o.key}>{o.label}</option>))}
                </select>
              )}
              <select className="input" name="sort" defaultValue={sort} aria-label="Sort">
                <option value="featured">Featured</option>
                <option value="price_asc">Price: low to high</option>
                <option value="price_desc">Price: high to low</option>
                <option value="newest">Newest</option>
              </select>
              <button className="btn btn-outline btn-sm" type="submit">Apply</button>
              {(q || occasion || category || sort !== 'featured') && <Link href={href({ q: '', occasion: '', category: '', sort: '' })} className="link-btn" style={{ alignSelf: 'center' }}>Clear</Link>}
            </div>
            <span className="muted" style={{ fontSize: '.9rem' }}>{products.length} item{products.length === 1 ? '' : 's'}</span>
          </form>

          {kind === 'addon' && (
            <div className="chips" style={{ marginBottom: '1.5rem' }}>
              {ADDON_CATEGORIES.map((c) => (
                <Link key={c} className={`chip ${category === c ? 'active' : ''}`} href={`/shop?kind=addon&category=${encodeURIComponent(c)}`}>{c}</Link>
              ))}
            </div>
          )}

          {products.length === 0 ? (
            <div className="empty">
              <div className="big">🥀</div>
              <h3>Nothing found</h3>
              <p className="muted">Try a different search or filter.</p>
              <Link href="/shop" className="btn btn-primary">See all bouquets</Link>
            </div>
          ) : (
            <div className="grid-products">
              {products.map((p) => (<ProductCard key={p.id} p={p} />))}
            </div>
          )}
        </div>
      </section>
    </>
  );
}
