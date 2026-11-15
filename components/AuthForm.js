'use client';

import Link from 'next/link';
import { useActionState } from 'react';
import { loginAction, registerAction } from '@/lib/auth-actions';

export default function AuthForm({ mode, next }) {
  const isLogin = mode === 'login';
  const [state, formAction, pending] = useActionState(isLogin ? loginAction : registerAction, null);

  return (
    <form action={formAction} className="panel auth">
      <h2 className="center">{isLogin ? 'Welcome back' : 'Create your account'}</h2>
      <p className="muted center">{isLogin ? 'Log in to track your orders.' : 'Save your details and track every order.'}</p>
      {state?.error && <div className="alert alert-error" role="alert">{state.error}</div>}
      <input type="hidden" name="next" value={next || ''} />
      {!isLogin && (
        <div className="field"><label htmlFor="name">Full name</label><input id="name" name="name" autoComplete="name" required /></div>
      )}
      <div className="field"><label htmlFor="email">Email</label><input id="email" type="email" name="email" autoComplete="email" required /></div>
      {!isLogin && (
        <div className="field"><label htmlFor="phone">Phone (optional)</label><input id="phone" name="phone" autoComplete="tel" placeholder="98XXXXXXXX" /></div>
      )}
      <div className="field">
        <label htmlFor="password">Password</label>
        <input id="password" type="password" name="password" autoComplete={isLogin ? 'current-password' : 'new-password'} minLength={isLogin ? undefined : 8} required />
        {!isLogin && <small>At least 8 characters.</small>}
      </div>
      <button className="btn btn-primary btn-block" disabled={pending} type="submit">{pending ? 'Please wait…' : isLogin ? 'Log in' : 'Create account'}</button>
      <p className="center muted" style={{ marginTop: '1rem', fontSize: '.92rem' }}>
        {isLogin ? (<>New here? <Link className="link-btn" href={`/register${next ? `?next=${encodeURIComponent(next)}` : ''}`}>Create an account</Link></>) : (<>Already have an account? <Link className="link-btn" href={`/login${next ? `?next=${encodeURIComponent(next)}` : ''}`}>Log in</Link></>)}
      </p>
    </form>
  );
}
