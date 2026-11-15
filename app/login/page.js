import { redirect } from 'next/navigation';
import { getUser } from '@/lib/session';
import AuthForm from '@/components/AuthForm';

export const dynamic = 'force-dynamic';
export const metadata = { title: 'Log in' };

export default async function LoginPage({ searchParams }) {
  const sp = await searchParams;
  const next = typeof sp.next === 'string' && sp.next.startsWith('/') && !sp.next.startsWith('//') ? sp.next : '';
  if (await getUser()) redirect(next || '/account');
  return (
    <div className="container">
      <AuthForm mode="login" next={next} />
    </div>
  );
}
