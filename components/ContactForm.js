'use client';

import { useActionState } from 'react';
import { contactAction } from '@/lib/shop-actions';

export default function ContactForm() {
  const [state, formAction, pending] = useActionState(contactAction, null);
  if (state?.ok) return <div className="alert alert-ok">Thank you! We have received your message and will reply soon.</div>;
  return (
    <form action={formAction} className="panel">
      <h3>Send us a message</h3>
      {state?.error && <div className="alert alert-error" role="alert">{state.error}</div>}
      <div className="grid-2">
        <div className="field"><label htmlFor="c-name">Name</label><input id="c-name" name="name" required /></div>
        <div className="field"><label htmlFor="c-email">Email</label><input id="c-email" type="email" name="email" required /></div>
      </div>
      <div className="field"><label htmlFor="c-phone">Phone (optional)</label><input id="c-phone" name="phone" /></div>
      <div className="field"><label htmlFor="c-body">Message</label><textarea id="c-body" name="body" rows={5} required /></div>
      <button className="btn btn-primary" disabled={pending} type="submit">{pending ? 'Sending…' : 'Send message'}</button>
    </form>
  );
}
