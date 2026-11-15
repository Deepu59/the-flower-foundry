import Link from 'next/link';
import AdminProductForm from '@/components/AdminProductForm';

export default function NewProduct() {
  return (
    <>
      <p><Link href="/admin/products" className="link-btn">← All products</Link></p>
      <h1 style={{ fontSize: '2rem' }}>Add product</h1>
      <AdminProductForm />
    </>
  );
}
