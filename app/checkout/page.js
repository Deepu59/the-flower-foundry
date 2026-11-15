import { getUser } from '@/lib/session';
import { DELIVERY } from '@/lib/config';
import { addDaysISO, nepalNow, todayNP } from '@/lib/format';
import CheckoutForm from '@/components/CheckoutForm';

export const dynamic = 'force-dynamic';
export const metadata = { title: 'Checkout' };

export default async function CheckoutPage() {
  const user = await getUser();
  const today = todayNP();
  const sameDayOpen = nepalNow().getUTCHours() < DELIVERY.sameDayCutoffHour;
  const minDate = sameDayOpen ? today : addDaysISO(today, 1);
  const maxDate = addDaysISO(today, DELIVERY.maxDaysAhead);

  return (
    <>
      <section className="page-title"><div className="container"><h1>Checkout</h1><p>Delivery available in {DELIVERY.area}.</p></div></section>
      <CheckoutForm user={user} delivery={DELIVERY} minDate={minDate} maxDate={maxDate} sameDayOpen={sameDayOpen} />
    </>
  );
}
