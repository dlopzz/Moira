import type { Metadata } from 'next';
import ProductDetailClient from './ProductDetailClient';

type Props = { params: Promise<{ slug: string }> };

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const { slug } = await params;
  try {
    const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/products/${slug}`, {
      next: { revalidate: 60 },
      headers: process.env.INTERNAL_API_KEY ? { 'X-Internal-Key': process.env.INTERNAL_API_KEY } : undefined,
    });
    if (!res.ok) return {};
    const { data } = await res.json();
    return {
      title: data.meta_title || data.name,
      description: data.meta_description || data.short_description || undefined,
      openGraph: {
        title: data.meta_title || data.name,
        description: data.meta_description || data.short_description || undefined,
        images: data.images?.[0] ? [data.images[0]] : [],
      },
    };
  } catch {
    return {};
  }
}

export default async function ProductPage({ params }: Props) {
  const { slug } = await params;
  return <ProductDetailClient slug={slug} />;
}
