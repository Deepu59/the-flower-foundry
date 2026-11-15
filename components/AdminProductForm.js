'use client';

import { useActionState, useState } from 'react';
import { saveProductAction } from '@/lib/admin-actions';
import { FLOWER_CATEGORIES, ADDON_CATEGORIES, OCCASIONS } from '@/lib/config';

export default function AdminProductForm({ product }) {
  const [state, formAction, pending] = useActionState(saveProductAction, null);
  const [kind, setKind] = useState(product?.kind || 'flower');
  const cats = kind === 'addon' ? ADDON_CATEGORIES : FLOWER_CATEGORIES;
  const selectedOcc = (product?.occasions || '').split(',');

  return (
    <form action={formAction} className="panel" encType="multipart/form-data" style={{ maxWidth: 720 }}>
      {state?.error && <div className="alert alert-error" role="alert">{state.error}</div>}
      <input type="hidden" name="id" value={product?.id || ''} />

      <div className="grid-2">
        <div className="field">
          <label htmlFor="kind">Type</label>
          <select id="kind" name="kind" value={kind} onChange={(e) => setKind(e.target.value)}>
            <option value="flower">Flower / bouquet</option>
            <option value="addon">Add-on (cake, chocolate, teddy, card)</option>
          </select>
        </div>
        <div className="field">
          <label htmlFor="category">Category</label>
          <select id="category" name="category" defaultValue={product?.category} key={kind}>
            {cats.map((c) => (<option key={c} value={c}>{c}</option>))}
          </select>
        </div>
      </div>

      <div className="grid-2">
        <div className="field"><label htmlFor="name">Name</label><input id="name" name="name" defaultValue={product?.name || ''} required /></div>
        <div className="field"><label htmlFor="price">Price (NPR)</label><input id="price" name="price" type="number" min="1" step="1" defaultValue={product?.price || ''} required /></div>
      </div>

      <div className="field"><label htmlFor="description">Description</label><textarea id="description" name="description" rows={4} defaultValue={product?.description || ''} /></div>

      <div className="field">
        <label htmlFor="image">Photo (JPG, PNG or WebP, max 5 MB)</label>
        {product?.image && <img src={product.image} alt="Current" style={{ width: 120, borderRadius: 12, marginBottom: '.4rem' }} />}
        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" />
        {product?.image && <small>Leave empty to keep the current photo.</small>}
      </div>

      {kind === 'flower' && (
        <div className="field">
          <label>Occasions</label>
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: '.4rem 1.2rem' }}>
            {OCCASIONS.map((o) => (
              <label key={o.key} className="check" style={{ marginBottom: 0 }}>
                <input type="checkbox" name="occasions" value={o.key} defaultChecked={selectedOcc.includes(o.key)} /> {o.label}
              </label>
            ))}
          </div>
        </div>
      )}

      <div style={{ display: 'flex', gap: '1.5rem', flexWrap: 'wrap', marginBottom: '1rem' }}>
        {kind === 'flower' && (
          <label className="check" style={{ marginBottom: 0 }}><input type="checkbox" name="featured" defaultChecked={!!product?.featured} /> Show as bestseller on home page</label>
        )}
        <label className="check" style={{ marginBottom: 0 }}><input type="checkbox" name="active" defaultChecked={product ? !!product.active : true} /> Visible in shop</label>
      </div>

      <button className="btn btn-primary" disabled={pending} type="submit">{pending ? 'Saving…' : product ? 'Save changes' : 'Create product'}</button>
    </form>
  );
}
