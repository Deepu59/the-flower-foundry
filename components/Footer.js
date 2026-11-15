import Link from 'next/link';
import { SHOP, DELIVERY } from '@/lib/config';

export default function Footer() {
  return (
    <>
      <footer className="site-footer">
        <div className="container">
          <div className="footer-grid">
            <div>
              <h4>{SHOP.name}</h4>
              <p style={{ fontSize: '.92rem' }}>{SHOP.tagline}. Hand-tied bouquets, cakes, chocolates and gifts for every occasion.</p>
            </div>
            <div>
              <h4>Shop</h4>
              <ul>
                <li><Link href="/shop">All bouquets</Link></li>
                <li><Link href="/shop?category=Boutique+Bouquets">Boutique bouquets</Link></li>
                <li><Link href="/shop?category=Roses">Roses</Link></li>
                <li><Link href="/shop?kind=addon">Cakes &amp; gifts</Link></li>
              </ul>
            </div>
            <div>
              <h4>Help</h4>
              <ul>
                <li><Link href="/occasions">Occasions</Link></li>
                <li><Link href="/about">About us</Link></li>
                <li><Link href="/contact">Contact</Link></li>
                <li><Link href="/account">My orders</Link></li>
              </ul>
            </div>
            <div>
              <h4>Visit &amp; call</h4>
              <ul>
                <li>📍 {SHOP.address}</li>
                <li>📞 {SHOP.phone}</li>
                <li>✉️ {SHOP.email}</li>
                <li>🕘 {SHOP.hours}</li>
              </ul>
            </div>
          </div>
          <div className="footer-bottom">
            <span>© {new Date().getFullYear()} {SHOP.name}. All rights reserved.</span>
            <span>Delivering to {DELIVERY.area} · Pay by eSewa or cash on delivery</span>
          </div>
        </div>
      </footer>
      <a className="wa-float" href={`https://wa.me/${SHOP.whatsapp}`} target="_blank" rel="noopener noreferrer">
        💬 Chat on WhatsApp
      </a>
    </>
  );
}
