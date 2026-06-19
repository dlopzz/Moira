'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { api, ApiError } from '@/lib/api';
import { clearToken } from '@/lib/auth';

const NAV = [
  { href: '/profile', label: 'Mi cuenta' },
  { href: '/profile/orders', label: 'Mis pedidos' },
  { href: '/profile/reviews', label: 'Mis reseñas' },
  { href: '/profile/wishlist', label: 'Wishlist' },
  { href: '/profile/addresses', label: 'Direcciones' },
  { href: '/profile/password', label: 'Cambiar contraseña' },
];

export default function ProfileLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();

  async function handleLogout() {
    try {
      await api.logout();
    } catch (e) {
      if (e instanceof ApiError && e.status !== 401) throw e;
    }
    clearToken();
    router.push('/auth/login');
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white border-b px-6 py-3 flex items-center justify-between">
        <span className="font-bold text-lg">Moira — Mi cuenta</span>
        <button onClick={handleLogout} className="text-sm text-red-600 hover:underline">
          Cerrar sesión
        </button>
      </header>
      <div className="max-w-4xl mx-auto py-8 px-4 flex gap-6">
        <nav className="w-44 shrink-0">
          <ul className="space-y-1">
            {NAV.map((item) => (
              <li key={item.href}>
                <Link
                  href={item.href}
                  className={`block px-3 py-2 rounded text-sm ${
                    pathname === item.href
                      ? 'bg-blue-600 text-white font-medium'
                      : 'text-gray-700 hover:bg-gray-100'
                  }`}
                >
                  {item.label}
                </Link>
              </li>
            ))}
          </ul>
        </nav>
        <main className="flex-1">{children}</main>
      </div>
    </div>
  );
}
