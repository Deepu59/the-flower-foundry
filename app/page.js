import Link from 'next/link';
import { getDb } from '@/lib/db';
import { SHOP, DELIVERY, FLOWER_CATEGORIES, ADDON_CATEGORIES, OCCASIONS } from '@/lib/config';
import ProductCard from '@/components/ProductCard';

export const dynamic = 'force-dynamic';

const CAT_EMOJI = {
  'Boutique Bouquets': '💐',
  Roses: '🌹',
  Carnations: '🌸',
  Lilies: '🌷',
  Gerberas: '🌼',
  Sunflowers: '🌻',
  'Mixed Bouquets': '💮',
};
const ADDON_EMOJI = { Cakes: '🎂', Chocolates: '🍫', 'Teddy Bears': '🧸', 'Greeting Cards': '💌' };

const REVIEWS = [
  { text: 'The bouquet arrived within two hours and looked exactly like the photo. My sister loved it!', who: 'Sample review · Anita S.' },
  { text: 'I ordered from abroad for my mother\'s birthday. Easy to pay and they even sent a photo before delivery.', who: 'Sample review · Rohan K.' },
  { text: 'Beautiful wrapping and the roses stayed fresh for a week. Will order again for our anniversary.', who: 'Sample review · Pratima G.' },
];

export default function Home() {
  const db = getDb();
  const featured = db.prepare("SELECT * FROM products WHERE active = 1 AND kind = 'flower' AND featured = 1 ORDER BY id LIMIT 6").all();
  const addons = db.prepare("SELECT * FROM products WHERE active = 1 AND kind = 'addon' ORDER BY id LIMIT 4").all();

  return (
    <>
      <section className="hero">
        <div className="container hero-grid">
          <div>
            <p className="eyebrow">Online flower shop · {SHOP.city}</p>
            <h1>Fresh flowers for every feeling, delivered with love.</h1>
            <p className="lead">
              Hand-tied bouquets, boutique wraps, cakes and gifts — ordered online and delivered to your loved ones in {DELIVERY.area}. Living abroad? Order from anywhere and we will deliver for you.
            </p>
            <div className="hero-actions">
              <Link href="/shop" className="btn btn-primary">Shop bouquets</Link>
              <Link href="/occasions" className="btn btn-outline">Shop by occasion</Link>
            </div>
            <div className="hero-badges">
              <span>Same-day delivery</span>
              <span>Pay by eSewa or COD</span>
              <span>Free card message</span>
            </div>
          </div>
          <div className="hero-photos">
            <img src="/images/blush-crimson-boutique.png" alt="Blush and crimson rose boutique bouquet" />
            <img src="/images/peach-garden-boutique.png" alt="Peach garden boutique bouquet with pink roses" />
          </div>
        </div>
      </section>

      <section className="section">
        <div className="container">
          <div className="section-head">
            <p className="eyebrow">Browse</p>
            <h2>Shop by flower</h2>
          </div>
          <div className="cat-grid">
            {FLOWER_CATEGORIES.map((c) => (
              <Link key={c} href={`/shop?category=${encodeURIComponent(c)}`} className="cat-pill">
                <span className="emoji">{CAT_EMOJI[c]}</span>
                {c}
              </Link>
            ))}
          </div>
        </div>
      </section>

      <section className="section alt">
        <div className="container">
          <div className="section-head">
            <p className="eyebrow">Our favourites</p>
            <h2>Bestselling bouquets</h2>
            <p>Hand-arranged by our florists and wrapped fresh every morning.</p>
          </div>
          <div className="grid-products">
            {featured.map((p) => (
              <ProductCard key={p.id} p={p} />
            ))}
          </div>
          <p className="center" style={{ marginTop: '2rem' }}>
            <Link href="/shop" className="btn btn-outline">View all bouquets</Link>
          </p>
        </div>
      </section>

      <section className="section">
        <div className="container">
          <div className="section-head">
            <p className="eyebrow">Make it extra special</p>
            <h2>Add a gift with your flowers</h2>
          </div>
          <div className="cat-grid" style={{ marginBottom: '2rem' }}>
            {ADDON_CATEGORIES.map((c) => (
              <Link key={c} href={`/shop?kind=addon&category=${encodeURIComponent(c)}`} className="cat-pill">
                <span className="emoji">{ADDON_EMOJI[c]}</span>
                {c}
              </Link>
            ))}
          </div>
          <div className="grid-products">
            {addons.map((p) => (
              <ProductCard key={p.id} p={p} />
            ))}
          </div>
        </div>
      </section>

      <section className="section sage">
        <div className="container">
          <div className="section-head">
            <p className="eyebrow">Simple &amp; quick</p>
            <h2>How it works</h2>
          </div>
          <div className="steps">
            <div className="step"><div className="num">1</div><h3>Choose</h3><p className="muted">Pick a bouquet and add a cake, chocolates, teddy or card.</p></div>
            <div className="step"><div className="num">2</div><h3>Tell us where</h3><p className="muted">Enter the recipient&apos;s address in {SHOP.city}, pick a date and time slot.</p></div>
            <div className="step"><div className="num">3</div><h3>Pay your way</h3><p className="muted">Pay securely with eSewa, or choose cash on delivery.</p></div>
            <div className="step"><div className="num">4</div><h3>We deliver</h3><p className="muted">Fresh flowers arrive on time, with your personal message.</p></div>
          </div>
        </div>
      </section>

      <section className="section">
        <div className="container">
          <div className="section-head">
            <p className="eyebrow">Every moment</p>
            <h2>Shop by occasion</h2>
          </div>
          <div className="cat-grid">
            {OCCASIONS.map((o) => (
              <Link key={o.key} href={`/shop?occasion=${o.key}`} className="cat-pill">
                <b>{o.label}</b>
                <small>{o.blurb}</small>
              </Link>
            ))}
          </div>
        </div>
      </section>

      <section className="section alt">
        <div className="container">
          <div className="section-head">
            <p className="eyebrow">Kind words</p>
            <h2>Loved by our customers</h2>
          </div>
          <div className="quotes">
            {REVIEWS.map((r) => (
              <div className="quote" key={r.who}>
                <div className="stars">★★★★★</div>
                <p>“{r.text}”</p>
                <strong>{r.who}</strong>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="section">
        <div className="container" style={{ maxWidth: 820 }}>
          <h2>Online flower delivery in {SHOP.city}</h2>
          <p className="muted">
            {SHOP.name} is your local online flower shop in {SHOP.city}, Banke. Whether it is a birthday, an anniversary, Valentine&apos;s Day or a wedding, flowers say what words cannot. Red roses speak of romance, white lilies of peace and respect, sunflowers of joy and carnations of gratitude.
          </p>
          <p className="muted">
            Bouquet prices start from Rs 1,250. Delivery inside {SHOP.city} costs just Rs {DELIVERY.fee} (free above Rs {DELIVERY.freeAbove.toLocaleString('en-IN')}). Living in Kathmandu, Dubai, Malaysia, the UK or anywhere else in the world? Place your order online, pay with eSewa and we will deliver to your loved ones in {SHOP.city}.
          </p>
        </div>
      </section>
    </>
  );
}
