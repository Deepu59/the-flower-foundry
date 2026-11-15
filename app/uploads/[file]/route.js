import fs from 'fs';
import path from 'path';

export const dynamic = 'force-dynamic';

const TYPES = { jpg: 'image/jpeg', png: 'image/png', webp: 'image/webp' };

// Serves product photos uploaded from the admin panel (stored in /data/uploads).
export async function GET(request, { params }) {
  const { file } = await params;
  const m = /^[a-f0-9]{16}\.(jpg|png|webp)$/.exec(file);
  if (!m) return new Response('Not found', { status: 404 });
  try {
    const buf = fs.readFileSync(path.join(process.cwd(), 'data', 'uploads', file));
    return new Response(buf, { headers: { 'Content-Type': TYPES[m[1]], 'Cache-Control': 'public, max-age=86400' } });
  } catch {
    return new Response('Not found', { status: 404 });
  }
}
