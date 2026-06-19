export function saveToken(token: string): void {
  localStorage.setItem('token', token);
  document.cookie = `token=${token}; path=/; max-age=${60 * 60 * 24 * 30}`;
}

export function clearToken(): void {
  localStorage.removeItem('token');
  document.cookie = 'token=; path=/; max-age=0';
}

export function getToken(): string | null {
  return localStorage.getItem('token');
}
