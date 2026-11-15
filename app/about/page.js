import Link from 'next/link';
import { SHOP, DELIVERY } from '@/lib/config';

export const metadata = { title: 'About us' };

export default function About() {
  return (
    <>
      <section className="page-title"><div className="container"><h1>About {SHOP.name}</h1><p>{SHOP.tagline}</p></div></section>
      <div className="container" style={{ maxWidth: 820, padding: '2rem 0 4rem' }}>
        <h2>Our story</h2>
        <p className="muted">
          {SHOP.name} started with a simple idea: flowers should be easy to send and beautiful to receive. From our shop in {SHOP.city}, our florists hand-tie every bouquet using fresh blooms and premium wrapping, so each gift looks as thoughtful as the feeling behind it.
        </p>
        <h2>What we offer</h2>
        <ul className="info-list" style={{ color: 'var(--ink)' }}>
          <li>Boutique bouquets, roses, carnations, lilies, gerberas, sunflowers and mixed arrangements</li>
          <li>Cakes, chocolates, teddy bears and greeting cards to pair with your flowers</li>
          <li>Delivery across {DELIVERY.area}, with convenient time slots</li>
          <li>Secure payment with eSewa or cash on delivery</li>
          <li>Orders from abroad for family and friends in {SHOP.city}</li>
        </ul>
        <h2 style={{ marginTop: '2rem' }}>Delivery</h2>
        <p className="muted">
          Delivery inside {SHOP.city} costs Rs {DELIVERY.fee} and is free for orders of Rs {DELIVERY.freeAbove.toLocaleString('en-IN')} or more. Orders placed before {DELIVERY.sameDayCutoffHour - 12} PM can be delivered the same day. Choose a morning, midday or afternoon slot at checkout.
        </p>
        <p><Link href="/shop" className="btn btn-primary">Shop now</Link></p>
      </div>
    </>
  );
}
