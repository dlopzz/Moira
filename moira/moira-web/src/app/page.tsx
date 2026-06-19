import Header from '@/components/Header';
import ProductCard from '@/components/ProductCard';
import Link from 'next/link';
import Image from 'next/image';
import { api } from '@/lib/api';

export const dynamic = 'force-dynamic';

async function getData() {
  const [categoriesRes, featuredRes] = await Promise.all([
    api.getCategories(),
    api.getFeaturedProducts(),
  ]);
  return { categories: categoriesRes.data, featured: featuredRes.data };
}

export default async function HomePage() {
  const { categories, featured } = await getData();
  const featuredCategory = categories[0] ?? null;

  let categoryProducts: Awaited<ReturnType<typeof api.getProducts>>['data'] = [];
  if (featuredCategory) {
    const res = await api.getProducts({ category: featuredCategory.slug, per_page: 4 });
    categoryProducts = res.data;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />

      <main className="max-w-6xl mx-auto px-4 py-8 space-y-12">
        {/* Featured category */}
        {featuredCategory && (
          <section>
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center gap-3">
                {featuredCategory.image_url && (
                  <div className="relative w-10 h-10 rounded-full overflow-hidden bg-gray-200">
                    <Image src={featuredCategory.image_url} alt={featuredCategory.name} fill className="object-cover" sizes="40px" />
                  </div>
                )}
                <h2 className="text-xl font-bold text-gray-900">{featuredCategory.name}</h2>
              </div>
              <Link
                href={`/categories/${featuredCategory.slug}`}
                className="text-sm text-blue-600 hover:underline"
              >
                Ver todos
              </Link>
            </div>
            {categoryProducts.length > 0 ? (
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                {categoryProducts.map((p) => (
                  <ProductCard key={p.id} product={p} />
                ))}
              </div>
            ) : (
              <p className="text-sm text-gray-400">No hay productos en esta categoría.</p>
            )}
          </section>
        )}

        {/* 4 random featured products */}
        {featured.length > 0 && (
          <section>
            <h2 className="text-xl font-bold text-gray-900 mb-4">Destacados</h2>
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
              {featured.map((p) => (
                <ProductCard key={p.id} product={p} />
              ))}
            </div>
          </section>
        )}
      </main>
    </div>
  );
}
