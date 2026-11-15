import Link from 'next/link';
import { OCCASIONS } from '@/lib/config';

export const metadata = { title: 'Flowers by occasion' };

const EMOJI = { birthday: '🎂', anniversary: '💞', valentine: '🌹', wedding: '💍', congratulations: '🎉', condolence: '🕊️' };

export default function Occasions() {
  return (
    <>
      <section className="page-title"><div className="container"><h1>Flowers by occasion</h1><p>The right flower for every moment.</p></div></section>
      <div className="container" style={{ padding: '2rem 0 4rem' }}>
        <div className="cat-grid">
          {OCCASIONS.map((o) => (
            <Link key={o.key} href={`/shop?occasion=${o.key}`} className="cat-pill">
              <span className="emoji">{EMOJI[o.key]}</span>
              <b>{o.label}</b>
              <small>{o.blurb}</small>
            </Link>
          ))}
        </div>
      </div>
    </>
  );
}
