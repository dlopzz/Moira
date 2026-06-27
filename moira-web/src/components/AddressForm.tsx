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
    <form onSubmit={onSubmit} className="address-form">
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

      <div className="co-field form-row">
        <label className="co-label">
          Código postal *{zipLoading && <span className="co-field-hint"> (buscando...)</span>}
        </label>
        <input
          className="input-text"
          value={form.zip_code}
          onChange={(e) => set('zip_code', e.target.value)}
          onBlur={(e) => lookupZip(e.target.value)}
          required
        />
        {errors.zip_code && <span className="co-field-error">{errors.zip_code}</span>}
      </div>

      <FormField
        label="Ciudad *"
        value={form.city}
        onChange={(e) => set('city', e.target.value)}
        error={errors.city}
        required
      />

      <div className="co-field form-row">
        <label className="co-label">Provincia *</label>
        <select
          value={form.state}
          onChange={(e) => set('state', e.target.value)}
          className="input-text addr-select"
          required
        >
          <option value="">Seleccioná una provincia</option>
          {PROVINCES.map((p) => (
            <option key={p} value={p}>{p}</option>
          ))}
        </select>
        {errors.state && <span className="co-field-error">{errors.state}</span>}
      </div>

      <div className="co-field form-row">
        <label className="co-label">País</label>
        <input className="input-text" value="Argentina" disabled />
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

      <div className="address-form-checkboxes">
        <label className="co-checkbox-label">
          <input
            type="checkbox"
            checked={!!form.is_default_billing}
            onChange={(e) => set('is_default_billing', e.target.checked)}
          />
          Usar como dirección de facturación predeterminada
        </label>

        <label className="co-checkbox-label">
          <input
            type="checkbox"
            checked={!!form.is_default_shipping}
            onChange={(e) => set('is_default_shipping', e.target.checked)}
          />
          Usar como dirección de envío predeterminada
        </label>
      </div>

      <div className="address-form-submit">
        <button type="submit" disabled={loading} className="button alt">
          {loading ? 'Guardando...' : 'Guardar dirección'}
        </button>
      </div>
    </form>
  );
}
