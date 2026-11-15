import Link from 'next/link';

export default function NotFound() {
  return (
    <div className="container empty" style={{ padding: '6rem 1rem' }}>
      <div className="big">🥀</div>
      <h1 style={{ fontSize: '2.2rem' }}>Page not found</h1>
      <p className="muted">The page you are looking for has wilted away.</p>
      <Link href="/" className="btn btn-primary">Back to home</Link>
    </div>
  );
}
