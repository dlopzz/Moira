'use client';

import type { RefObject } from 'react';
import { ReCAPTCHA, type ReCAPTCHAType } from '@/lib/use-recaptcha';

export function RecaptchaField({
  recaptchaRef,
  visible,
  error,
  errorClassName = 'text-red-500 text-xs mt-1 block',
}: {
  recaptchaRef: RefObject<ReCAPTCHAType | null>;
  visible: boolean;
  error?: string;
  errorClassName?: string;
}) {
  if (!visible) return null;

  return (
    <div>
      <ReCAPTCHA ref={recaptchaRef} sitekey={process.env.NEXT_PUBLIC_RECAPTCHA_SITE_KEY!} />
      {error && <span className={errorClassName}>{error}</span>}
    </div>
  );
}
