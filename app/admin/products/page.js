import Link from 'next/link';
import { getDb } from '@/lib/db';
import { npr } from '@/lib/format';
import { toggleProductAction } from '@/lib/admin-actions';

export default async function AdminProducts({ searchParams }) {
  const sp = await searchParams;
  const products = getDb().prepare('SELECT * FROM products ORDER BY kind, category, id').all();
  return (
    <>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '1rem' }}>
        <h1 style={{ fontSize: '2rem', margin: 0 }}>Products</h1>
        <Link href="/admin/products/new" className="btn btn-primary">+ Add product</Link>
      </div>
      {sp.saved && <div className="alert alert-ok" style={{ marginTop: '1rem' }}>Product saved.</div>}
      <div className="table-wrap" style={{ marginTop: '1rem' }}>
        <table className="table">
          <thead><tr><th></th><th>Name</th><th>Type</th><th>Category</th><th>Price</th><th>Status</th><th></th></tr></thead>
          <tbody>
            {products.map((p) => (
              <tr key={p.id} style={p.active ? undefined : { opacity: 0.55 }}>
                <td>{p.image ? <img className="thumb" src={p.image} alt="" /> : <div className="thumb" />}</td>
                <td><b>{p.name}</b>{p.featured ? ' ★' : ''}</td>
                <td>{p.kind === 'addon' ? 'Add-on' : 'Flower'}</td>
                <td>{p.category}</td>
                <td>{npr(p.price)}</td>
                <td><span className={`badge ${p.active ? 'paid' : 'cancelled'}`}>{p.active ? 'Visible' : 'Hidden'}</span></td>
                <td>
                  <div className="row-actions">
                    <Link href={`/admin/products/${p.id}`} className="btn btn-outline btn-sm">Edit</Link>
                    <form action={toggleProductAction}>
                      <input type="hidden" name="id" value={p.id} />
                      <button className="btn btn-outline btn-sm" type="submit">{p.active ? 'Hide' : 'Show'}</button>
                    </form>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}
