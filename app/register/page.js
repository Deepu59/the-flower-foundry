import { redirect } from 'next/navigation';
import { getUser } from '@/lib/session';
import AuthForm from '@/components/AuthForm';

export const dynamic = 'force-dynamic';
export const metadata = { title: 'Create account' };

export default async function RegisterPage({ searchParams }) {
  const sp = await searchParams;
  const next = typeof sp.next === 'string' && sp.next.startsWith('/') && !sp.next.startsWith('//') ? sp.next : '';
  if (await getUser()) redirect(next || '/account');
  return (
    <div className="container">
      <AuthForm mode="register" next={next} />
    </div>
  );
}
