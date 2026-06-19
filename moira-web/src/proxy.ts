import { NextRequest, NextResponse } from 'next/server';

export function proxy(req: NextRequest) {
  const token = req.cookies.get('token')?.value;
  const isProfile = req.nextUrl.pathname.startsWith('/profile');
  const isAuth = req.nextUrl.pathname.startsWith('/auth');

  if (isProfile && !token) {
    return NextResponse.redirect(new URL('/auth/login', req.url));
  }

  if (isAuth && token) {
    return NextResponse.redirect(new URL('/profile', req.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: ['/profile/:path*', '/auth/:path*'],
};
