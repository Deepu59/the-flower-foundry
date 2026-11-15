import './globals.css';
import { CartProvider } from '@/components/CartProvider';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { getUser } from '@/lib/session';
import { logoutAction } from '@/lib/auth-actions';
import { SHOP, DELIVERY } from '@/lib/config';

export const metadata = {
  title: { default: `${SHOP.name} | Flower delivery in ${SHOP.city}`, template: `%s | ${SHOP.name}` },
  description: `Order fresh flower bouquets, cakes, chocolates and gifts online for delivery in ${DELIVERY.area}. Pay with eSewa or cash on delivery.`,
};

export default async function RootLayout({ children }) {
  const user = await getUser();
  return (
    <html lang="en">
      <head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="" />
        <link
          rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@400;500;600&display=swap"
        />
      </head>
      <body>
        <CartProvider>
          <div className="topbar">
            🌸 Same-day flower delivery in {SHOP.city} — order before {DELIVERY.sameDayCutoffHour > 12 ? DELIVERY.sameDayCutoffHour - 12 : DELIVERY.sameDayCutoffHour} PM · Delivery from Rs {DELIVERY.fee}
          </div>
          <Header user={user} logoutAction={logoutAction} />
          <main>{children}</main>
          <Footer />
        </CartProvider>
      </body>
    </html>
  );
}
