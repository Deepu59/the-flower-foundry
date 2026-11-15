import { requireAdmin } from '@/lib/session';
import AdminNav from '@/components/AdminNav';

export const dynamic = 'force-dynamic';
export const metadata = { title: 'Admin', robots: { index: false, follow: false } };

export default async function AdminLayout({ children }) {
  await requireAdmin();
  return (
    <div className="container admin-wrap">
      <AdminNav />
      <div>{children}</div>
    </div>
  );
}
