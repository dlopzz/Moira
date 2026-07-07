export function wasRecentlyDismissed(key: string, cooldownMs: number): boolean {
  if (typeof window === 'undefined') return false;
  const last = localStorage.getItem(key);
  return !!last && Date.now() - Number(last) < cooldownMs;
}

/** A permanent version of wasRecentlyDismissed: once marked, stays dismissed forever. */
export function isPermanentlyDismissed(key: string): boolean {
  return wasRecentlyDismissed(key, Infinity);
}

export function markDismissed(key: string): void {
  if (typeof window === 'undefined') return;
  localStorage.setItem(key, String(Date.now()));
}
