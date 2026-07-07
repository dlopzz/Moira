import { NextRequest, NextResponse } from 'next/server';
import { QA_COOKIE_NAME, QA_LOGIN_PATH, verifyQaCookie } from '@/lib/qa-auth';

export async function proxy(req: NextRequest) {
  const { pathname } = req.nextUrl;

  // Gate de QA: mientras QA_AUTH_SECRET esté seteado, todo el sitio requiere
  // haber pasado por /qa-login antes de llegar a cualquier otra ruta.
  const qaSecret = process.env.QA_AUTH_SECRET;
  if (qaSecret) {
    const isQaRoute = pathname.startsWith(QA_LOGIN_PATH) || pathname.startsWith('/api/qa-login');

    if (!isQaRoute) {
      const cookie = req.cookies.get(QA_COOKIE_NAME)?.value;
      const isValid = await verifyQaCookie(cookie, qaSecret);

      if (!isValid) {
        const loginUrl = new URL(QA_LOGIN_PATH, req.url);
        loginUrl.searchParams.set('next', pathname + req.nextUrl.search);
        return NextResponse.redirect(loginUrl);
      }
    }
  }

  const token = req.cookies.get('token')?.value;
  const isProfile = pathname.startsWith('/profile');
  const isAuth = pathname.startsWith('/auth');

  if (isProfile && !token) {
    return NextResponse.redirect(new URL('/auth/login', req.url));
  }

  if (isAuth && token) {
    return NextResponse.redirect(new URL('/profile', req.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    '/((?!_next/static|_next/image|favicon.ico|.*\\.(?:png|jpg|jpeg|svg|gif|webp|ico|css|js)$).*)',
  ],
};
