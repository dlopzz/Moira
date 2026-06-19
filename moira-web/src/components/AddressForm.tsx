import { useState } from 'react';
import { FormField } from './FormField';
import { AddressPayload } from '@/lib/api';

const PROVINCES = [
  'Buenos Aires',
  'Catamarca',
  'Chaco',
  'Chubut',
  'Córdoba',
  'Corrientes',
  'Entre Ríos',
  'Formosa',
  'Jujuy',
  'La Pampa',
  'La Rioja',
  'Mendoza',
  'Misiones',
  'Neuquén',
  'Río Negro',
  'Salta',
  'San Juan',
  'San Luis',
  'Santa Cruz',
  'Santa Fe',
  'Santiago del Estero',
  'Tierra del Fuego',
  'Tucumán',
  'Ciudad Autónoma de Buenos Aires',
];

type Props = {
  form: AddressPayload;
  errors: Record<string, string>;
  loading: boolean;
  onChange: (form: AddressPayload) => void;
  onSubmit: (e: React.FormEvent) => void;
};

export function AddressForm({ form, errors, loading, onChange, onSubmit }: Props) {
  const [zipLoading, setZipLoading] = useState(false);

  const set = (k: keyof AddressPayload, v: string | boolean) => onChange({ ...form, [k]: v });

  async function lookupZip(zip: string) {
    if (zip.length < 4) return;
    setZipLoading(true);
    try {
      const res = await fetch(
        `https://apis.datos.gob.ar/georef/api/localidades?cp=${zip}&campos=nombre,provincia.nombre&max=1`
      );
      const data = await res.json();
      const localidad = data?.localidades?.[0];
      if (localidad) {
        onChange({
          ...form,
          zip_code: zip,
          city: localidad.nombre,
          state: localidad.provincia?.nombre ?? form.state,
        });
      }
    } catch {
      // silently ignore — user can fill manually
    } finally {
      setZipLoading(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="space-y-4 max-w-md">
      <FormField
        label="Etiqueta *"
        placeholder="Casa, Trabajo, ..."
        value={form.label}
        onChange={(e) => set('label', e.target.value)}
        error={errors.label}
        required
      />

      <FormField
        label="Calle y número *"
        value={form.street}
        onChange={(e) => set('street', e.target.value)}
        error={errors.street}
        required
      />

      <FormField
        label="Piso / Depto / Referencia"
        value={form.address_line_2 ?? ''}
        onChange={(e) => set('address_line_2', e.target.value)}
      />

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Código postal * {zipLoading && <span className="text-gray-400 font-normal">(buscando...)</span>}
        </label>
        <input
          className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          value={form.zip_code}
          onChange={(e) => set('zip_code', e.target.value)}
          onBlur={(e) => lookupZip(e.target.value)}
          required
        />
        {errors.zip_code && <p className="text-red-600 text-xs mt-1">{errors.zip_code}</p>}
      </div>

      <FormField
        label="Ciudad *"
        value={form.city}
        onChange={(e) => set('city', e.target.value)}
        error={errors.city}
        required
      />

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Provincia *</label>
        <select
          value={form.state}
          onChange={(e) => set('state', e.target.value)}
          className="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          required
        >
          <option value="">Seleccioná una provincia</option>
          {PROVINCES.map((p) => (
            <option key={p} value={p}>{p}</option>
          ))}
        </select>
        {errors.state && <p className="text-red-600 text-xs mt-1">{errors.state}</p>}
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">País</label>
        <input
          className="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-gray-50 text-gray-500"
          value="Argentina"
          disabled
        />
        <input type="hidden" value="AR" name="country" />
      </div>

      <FormField
        label="Teléfono *"
        type="tel"
        value={form.telephone}
        onChange={(e) => set('telephone', e.target.value)}
        error={errors.telephone}
        required
      />

      <label className="flex items-center gap-2 text-sm">
        <input
          type="checkbox"
          checked={!!form.is_default}
          onChange={(e) => set('is_default', e.target.checked)}
        />
        Usar como dirección predeterminada
      </label>

      <button
        type="submit"
        disabled={loading}
        className="bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
      >
        {loading ? 'Guardando...' : 'Guardar'}
      </button>
    </form>
  );
}
