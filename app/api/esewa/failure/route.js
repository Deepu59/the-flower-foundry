import { NextResponse } from 'next/server';
import { siteUrl } from '@/lib/esewa';

export const dynamic = 'force-dynamic';

export async function GET(request) {
  const code = new URL(request.url).searchParams.get('c') || '';
  const safe = /^FS-[A-Z0-9]{8}$/.test(code) ? code : '';
  return NextResponse.redirect(`${siteUrl()}${safe ? `/order/${safe}?payment=failed` : '/cart'}`, 303);
}
