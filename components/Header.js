'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useState } from 'react';
import { useCart } from './CartProvider';
import { SHOP } from '@/lib/config';

const LINKS = [
  ['/', 'Home'],
  ['/shop', 'Shop'],
  ['/occasions', 'Occasions'],
  ['/about', 'About'],
  ['/contact', 'Contact'],
];

export default function Header({ user, logoutAction }) {
  const pathname = usePathname();
  const { count, ready } = useCart();
  const [open, setOpen] = useState(false);

  return (
    <header className="site-header">
      <div className="container header-inner">
        <Link href="/" className="brand" onClick={() => setOpen(false)}>
          <svg width="30" height="30" viewBox="0 0 32 32" aria-hidden="true">
            <g fill="#f1a7bb">
              <circle cx="16" cy="7" r="6" />
              <circle cx="25" cy="14" r="6" />
              <circle cx="21.5" cy="24" r="6" />
              <circle cx="10.5" cy="24" r="6" />
              <circle cx="7" cy="14" r="6" />
            </g>
            <circle cx="16" cy="16" r="5" fill="#f3d27a" />
          </svg>
          {SHOP.name}
        </Link>

        <nav className={`nav ${open ? 'open' : ''}`} aria-label="Main">
          {LINKS.map(([href, label]) => (
            <Link
              key={href}
              href={href}
              className={pathname === href || (href !== '/' && pathname.startsWith(href)) ? 'active' : ''}
              onClick={() => setOpen(false)}
            >
              {label}
            </Link>
          ))}
          {user?.role === 'admin' && (
            <Link href="/admin" onClick={() => setOpen(false)}>
              Admin
            </Link>
          )}
        </nav>

        <div className="header-actions">
          {user ? (
            <>
              <Link href="/account" className="icon-btn hide-sm">
                Hi, {user.name.split(' ')[0]}
              </Link>
              <form action={logoutAction} className="hide-sm">
                <button className="icon-btn" type="submit">
                  Logout
                </button>
              </form>
            </>
          ) : (
            <Link href="/login" className="icon-btn hide-sm">
              Login
            </Link>
          )}
          <Link href="/cart" className="icon-btn" aria-label="Cart">
            🛍 Cart <span className="cart-count">{ready ? count : 0}</span>
          </Link>
          <button className="icon-btn menu-toggle" onClick={() => setOpen((o) => !o)} aria-label="Menu" aria-expanded={open}>
            ☰
          </button>
        </div>
      </div>
    </header>
  );
}
