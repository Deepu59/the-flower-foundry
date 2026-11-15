import crypto from 'crypto';

// Defaults are eSewa's public TEST (sandbox) credentials. Override with env vars for production.
export const ESEWA = {
  productCode: process.env.ESEWA_PRODUCT_CODE || 'EPAYTEST',
  secret: process.env.ESEWA_SECRET_KEY || '8gBm/:&EnhH.1/q',
  formUrl: process.env.ESEWA_FORM_URL || 'https://rc-epay.esewa.com.np/api/epay/main/v2/form',
  statusUrl: process.env.ESEWA_STATUS_URL || 'https://rc.esewa.com.np/api/epay/transaction/status/',
};

export function siteUrl() {
  return (process.env.SITE_URL || 'http://localhost:3000').replace(/\/$/, '');
}

export function sign(message) {
  return crypto.createHmac('sha256', ESEWA.secret).update(message).digest('base64');
}

// Build the fields for the eSewa ePay v2 form POST.
export function buildPaymentFields({ total, transactionUuid, orderCode }) {
  const totalAmount = String(total);
  const signedFieldNames = 'total_amount,transaction_uuid,product_code';
  const message = `total_amount=${totalAmount},transaction_uuid=${transactionUuid},product_code=${ESEWA.productCode}`;
  return {
    amount: totalAmount,
    tax_amount: '0',
    total_amount: totalAmount,
    transaction_uuid: transactionUuid,
    product_code: ESEWA.productCode,
    product_service_charge: '0',
    product_delivery_charge: '0',
    success_url: `${siteUrl()}/api/esewa/success`,
    failure_url: `${siteUrl()}/api/esewa/failure?c=${orderCode}`,
    signed_field_names: signedFieldNames,
    signature: sign(message),
  };
}

// Verify the base64 `data` eSewa sends back to success_url.
export function verifyResponse(base64Data) {
  let data;
  try {
    data = JSON.parse(Buffer.from(base64Data, 'base64').toString('utf8'));
  } catch {
    return { ok: false, reason: 'Unreadable response' };
  }
  const names = String(data.signed_field_names || '').split(',').filter(Boolean);
  if (!names.length || !data.signature) return { ok: false, reason: 'Missing signature' };
  const message = names.map((n) => `${n}=${data[n]}`).join(',');
  const expected = sign(message);
  const a = Buffer.from(expected);
  const b = Buffer.from(String(data.signature));
  if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) return { ok: false, reason: 'Bad signature' };
  return { ok: true, data };
}

// Ask eSewa directly for the transaction status (second, server-to-server check).
export async function checkStatus({ total, transactionUuid }) {
  const url = `${ESEWA.statusUrl}?product_code=${encodeURIComponent(ESEWA.productCode)}&total_amount=${encodeURIComponent(
    total
  )}&transaction_uuid=${encodeURIComponent(transactionUuid)}`;
  const res = await fetch(url, { cache: 'no-store', signal: AbortSignal.timeout(8000) });
  if (!res.ok) throw new Error(`eSewa status HTTP ${res.status}`);
  return res.json();
}
