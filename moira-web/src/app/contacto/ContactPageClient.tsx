'use client';

import { useRef, useState } from 'react';
import Header from '@/components/Header';
import { FormField } from '@/components/FormField';
import { RecaptchaField } from '@/components/RecaptchaField';
import { api, ApiError } from '@/lib/api';
import { getRecaptchaToken, resetRecaptcha, useRecaptchaEnabled, type ReCAPTCHAType } from '@/lib/use-recaptcha';

type Fields = { name: string; last_name: string; email: string; message: string };
type Errors = Partial<Record<keyof Fields | 'recaptcha_token' | '_global', string>>;

export default function ContactPageClient() {
  const recaptchaRef = useRef<ReCAPTCHAType>(null);
  const recaptchaEnabled = useRecaptchaEnabled();
  const [form, setForm] = useState<Fields>({ name: '', last_name: '', email: '', message: '' });
  const [errors, setErrors] = useState<Errors>({});
  const [loading, setLoading] = useState(false);
  const [sent, setSent] = useState(false);

  function field(key: keyof Fields) {
    return (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) =>
      setForm((f) => ({ ...f, [key]: e.target.value }));
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setErrors({});

    const { token: recaptchaToken, error: recaptchaError } = getRecaptchaToken(recaptchaRef, recaptchaEnabled);
    if (recaptchaError) {
      setErrors({ recaptcha_token: recaptchaError });
      return;
    }

    setLoading(true);
    try {
      await api.submitContact({ ...form, recaptcha_token: recaptchaToken });
      setSent(true);
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        const mapped: Errors = {};
        for (const [k, v] of Object.entries(err.errors)) {
          mapped[k as keyof Errors] = v[0];
        }
        setErrors(mapped);
      } else if (err instanceof ApiError) {
        setErrors({ _global: err.message });
      }
      resetRecaptcha(recaptchaRef);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      <main className="max-w-2xl mx-auto px-4 py-10">
        <div className="mb-3 text-sm text-gray-400">
          <a href="/" className="hover:text-gray-600">Inicio</a>
          <span className="mx-1 text-gray-300">/</span>
          <span className="text-gray-600">Contactanos</span>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-gray-100 px-8 py-10">
          <h1 className="text-2xl font-bold text-gray-900 mb-2">Contactanos</h1>
          <p className="text-gray-500 text-sm mb-8">
            Completá el formulario y nos ponemos en contacto a la brevedad.
          </p>

          {sent ? (
            <div className="rounded-lg bg-green-50 border border-green-200 px-6 py-8 text-center">
              <p className="text-green-700 font-semibold text-lg mb-1">¡Consulta enviada!</p>
              <p className="text-green-600 text-sm">
                Recibimos tu mensaje. Nos pondremos en contacto a la brevedad.
              </p>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="space-y-5" noValidate>
              {errors._global && (
                <div className="text-sm text-red-600 bg-red-50 border border-red-200 rounded px-4 py-2">
                  {errors._global}
                </div>
              )}

              <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <FormField
                  label="Nombre"
                  type="text"
                  value={form.name}
                  onChange={field('name')}
                  error={errors.name}
                  required
                  autoComplete="given-name"
                />
                <FormField
                  label="Apellido"
                  type="text"
                  value={form.last_name}
                  onChange={field('last_name')}
                  error={errors.last_name}
                  required
                  autoComplete="family-name"
                />
              </div>

              <FormField
                label="Email"
                type="email"
                value={form.email}
                onChange={field('email')}
                error={errors.email}
                required
                autoComplete="email"
              />

              <div className="co-field form-row">
                <label className="co-label">Motivo del contacto</label>
                <textarea
                  className="input-text"
                  rows={5}
                  value={form.message}
                  onChange={field('message')}
                  required
                  style={{ resize: 'vertical' }}
                />
                {errors.message && <span className="co-field-error">{errors.message}</span>}
              </div>

              <RecaptchaField
                recaptchaRef={recaptchaRef}
                visible={recaptchaEnabled}
                error={errors.recaptcha_token}
                errorClassName="co-field-error block mt-1"
              />

              <button
                type="submit"
                disabled={loading}
                className="button alt wp-element-button w-full disabled:opacity-60"
              >
                {loading ? 'Enviando...' : 'Enviar consulta'}
              </button>
            </form>
          )}
        </div>
      </main>
    </div>
  );
}
