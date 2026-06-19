import type { Metadata } from 'next';
import CmsPageClient from './CmsPageClient';

type Props = { params: Promise<{ slug: string }> };

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const { slug } = await params;
  try {
    const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/pages/${slug}`, {
      next: { revalidate: 300 },
    });
    if (!res.ok) return {};
    const { data } = await res.json();
    return {
      title: data.title,
      description: data.subtitle || undefined,
      openGraph: {
        title: data.title,
        description: data.subtitle || undefined,
      },
    };
  } catch {
    return {};
  }
}

export default async function CmsPage({ params }: Props) {
  const { slug } = await params;
  return <CmsPageClient slug={slug} />;
}
