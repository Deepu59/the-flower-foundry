'use client';

import { useEffect, useRef } from 'react';

export default function AutoSubmit({ action, fields }) {
  const ref = useRef(null);
  useEffect(() => {
    const t = setTimeout(() => ref.current?.submit(), 600);
    return () => clearTimeout(t);
  }, []);
  return (
    <form ref={ref} action={action} method="POST">
      {Object.entries(fields).map(([k, v]) => (
        <input key={k} type="hidden" name={k} value={v} />
      ))}
      <noscript>
        <button className="btn btn-primary" type="submit">Continue to eSewa</button>
      </noscript>
      <button className="btn btn-outline" type="submit" style={{ marginTop: '1rem' }}>Continue to eSewa now</button>
    </form>
  );
}
