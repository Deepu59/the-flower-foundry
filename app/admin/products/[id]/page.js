import Link from 'next/link';
import { notFound } from 'next/navigation';
import { getDb } from '@/lib/db';
import AdminProductForm from '@/components/AdminProductForm';

export default async function EditProduct({ params }) {
  const { id } = await params;
  const product = getDb().prepare('SELECT * FROM products WHERE id = ?').get(Number(id));
  if (!product) notFound();
  return (
    <>
      <p><Link href="/admin/products" className="link-btn">← All products</Link></p>
      <h1 style={{ fontSize: '2rem' }}>Edit product</h1>
      <AdminProductForm product={product} />
    </>
  );
}
