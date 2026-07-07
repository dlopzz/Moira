'use client';

import dynamic from 'next/dynamic';
import type { RefObject } from 'react';
import { useSiteInfo } from './site-info-context';
import type ReCAPTCHAType from 'react-google-recaptcha';

// next/dynamic erases the class-component type, so ref stops typechecking;
// cast back to the real class type (ReCAPTCHA still renders correctly at runtime).
export const ReCAPTCHA = dynamic(() => import('react-google-recaptcha'), { ssr: false }) as unknown as typeof ReCAPTCHAType;

export type { ReCAPTCHAType };

export function useRecaptchaEnabled(): boolean {
  return useSiteInfo()?.recaptcha_enabled ?? false;
}

/**
 * Toma el ref como parámetro en vez de crearlo/devolverlo: un hook que
 * retorna un useRef() dispara "Cannot access ref value during render"
 * (eslint react-hooks/refs) al pasarlo después a <ReCAPTCHA ref={...}>.
 */
export function getRecaptchaToken(
  ref: RefObject<ReCAPTCHAType | null>,
  enabled: boolean
): { token?: string; error?: string } {
  if (!enabled) return {};

  const token = ref.current?.getValue() ?? '';

  return token ? { token } : { error: 'Por favor, completá la verificación.' };
}

export function resetRecaptcha(ref: RefObject<ReCAPTCHAType | null>): void {
  ref.current?.reset();
}
