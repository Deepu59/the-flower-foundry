'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';

const LINKS = [
  ['/admin', 'Dashboard'],
  ['/admin/orders', 'Orders'],
  ['/admin/products', 'Products'],
  ['/admin/messages', 'Messages'],
];

export default function AdminNav() {
  const pathname = usePathname();
  return (
    <nav className="admin-nav" aria-label="Admin">
      {LINKS.map(([href, label]) => (
        <Link key={href} href={href} className={pathname === href || (href !== '/admin' && pathname.startsWith(href)) ? 'active' : ''}>
          {label}
        </Link>
      ))}
    </nav>
  );
}
