import { Suspense } from 'react';
import type { Metadata } from 'next';
import CategoryPageClient from './CategoryPageClient';

type Props = { params: Promise<{ slug: string }> };

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const { slug } = await params;
  try {
    const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/categories/${slug}`, {
      next: { revalidate: 60 },
    });
    if (!res.ok) return {};
    const { data } = await res.json();
    return {
      title: data.meta_title || data.name,
      description: data.meta_description || data.description || undefined,
      openGraph: {
        title: data.meta_title || data.name,
        description: data.meta_description || data.description || undefined,
        images: data.image_url ? [data.image_url] : [],
      },
    };
  } catch {
    return {};
  }
}

export default async function CategoryPage({ params }: Props) {
  const { slug } = await params;
  return (
    <Suspense>
      <CategoryPageClient slug={slug} />
    </Suspense>
  );
}
