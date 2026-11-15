import { SHOP } from '@/lib/config';
import ContactForm from '@/components/ContactForm';

export const metadata = { title: 'Contact us' };

export default function Contact() {
  return (
    <>
      <section className="page-title"><div className="container"><h1>Contact us</h1><p>Questions about an order, a custom bouquet or a wedding? We would love to help.</p></div></section>
      <div className="container two-col">
        <ContactForm />
        <div className="panel">
          <h3>Visit or call</h3>
          <dl className="kv" style={{ gridTemplateColumns: '6rem 1fr' }}>
            <dt>Address</dt><dd>{SHOP.address}</dd>
            <dt>Phone</dt><dd>{SHOP.phone}</dd>
            <dt>Email</dt><dd>{SHOP.email}</dd>
            <dt>Hours</dt><dd>{SHOP.hours}</dd>
          </dl>
          <a className="btn btn-sage btn-block" style={{ marginTop: '1rem' }} href={`https://wa.me/${SHOP.whatsapp}`} target="_blank" rel="noopener noreferrer">Chat on WhatsApp</a>
        </div>
      </div>
    </>
  );
}
