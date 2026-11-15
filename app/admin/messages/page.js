import { getDb } from '@/lib/db';
import { niceDateTime } from '@/lib/format';

export default function AdminMessages() {
  const messages = getDb().prepare('SELECT * FROM messages ORDER BY created_at DESC LIMIT 200').all();
  return (
    <>
      <h1 style={{ fontSize: '2rem' }}>Messages</h1>
      {messages.length === 0 ? (
        <p className="muted">No messages yet.</p>
      ) : (
        messages.map((m) => (
          <div className="panel" key={m.id}>
            <b>{m.name}</b> · <a className="link-btn" href={`mailto:${m.email}`}>{m.email}</a>{m.phone ? ` · ${m.phone}` : ''}
            <div className="muted" style={{ fontSize: '.82rem' }}>{niceDateTime(m.created_at)}</div>
            <p style={{ marginTop: '.6rem', whiteSpace: 'pre-wrap' }}>{m.body}</p>
          </div>
        ))
      )}
    </>
  );
}
