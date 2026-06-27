'use client';

import { useState, useEffect } from 'react';
import { api, ApiError, Customer } from '@/lib/api';

export default function EditAccountPage() {
  const [customer, setCustomer] = useState<Customer | null>(null);

  const [form, setForm] = useState({
    first_name: '',
    last_name: '',
    email: '',
    date_of_birth: '',
    current_password: '',
    password: '',
    password_confirmation: '',
  });

  const [errors, setErrors] = useState<string[]>([]);
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);

  const [showCurrentPwd, setShowCurrentPwd] = useState(false);
  const [showNewPwd, setShowNewPwd] = useState(false);
  const [showConfirmPwd, setShowConfirmPwd] = useState(false);

  useEffect(() => {
    api.getProfile().then((res) => {
      setCustomer(res.data);
      setForm((f) => ({
        ...f,
        first_name: res.data.first_name,
        last_name: res.data.last_name,
        email: res.data.email,
        date_of_birth: res.data.dob ?? '',
      }));
    });
  }, []);

  const changingPassword = Boolean(form.current_password || form.password || form.password_confirmation);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setErrors([]);
    setSuccess('');

    const localErrors: string[] = [];
    if (!form.first_name.trim()) localErrors.push('El nombre es obligatorio.');
    if (!form.last_name.trim()) localErrors.push('El apellido es obligatorio.');
    if (!form.email.trim()) localErrors.push('El correo electrónico es obligatorio.');
    if (!form.date_of_birth) localErrors.push('La fecha de nacimiento es obligatoria.');
    if (changingPassword) {
      if (!form.current_password) localErrors.push('La contraseña actual es obligatoria.');
      if (!form.password) localErrors.push('La nueva contraseña es obligatoria.');
      if (!form.password_confirmation) localErrors.push('La confirmación de contraseña es obligatoria.');
    }
    if (localErrors.length) { setErrors(localErrors); return; }

    setLoading(true);
    try {
      const profileRes = await api.updateProfile({
        first_name: form.first_name,
        last_name: form.last_name,
        email: form.email,
        date_of_birth: form.date_of_birth,
      });
      setCustomer(profileRes.data);

      if (changingPassword) {
        await api.updatePassword({
          current_password: form.current_password,
          password: form.password,
          password_confirmation: form.password_confirmation,
        });
        setForm((f) => ({ ...f, current_password: '', password: '', password_confirmation: '' }));
      }

      setSuccess('Cambios guardados correctamente.');
    } catch (err) {
      if (err instanceof ApiError) {
        if (err.errors) {
          setErrors(Object.values(err.errors).flat());
        } else {
          setErrors([err.message]);
        }
      }
    } finally {
      setLoading(false);
    }
  }

  if (!customer) return <p>Cargando...</p>;

  return (
    <>
      {errors.length > 0 && (
        <ul className="woocommerce-error" role="alert">
          {errors.map((e, i) => <li key={i}>{e}</li>)}
        </ul>
      )}

      {success && (
        <div className="woocommerce-message" role="alert">{success}</div>
      )}

      <form onSubmit={handleSubmit} className="woocommerce-EditAccountForm edit-account">

        <p className="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
          <label htmlFor="first_name">
            Nombre&nbsp;<span className="required" aria-hidden="true">*</span>
          </label>
          <input
            id="first_name"
            type="text"
            className="woocommerce-Input woocommerce-Input--text input-text"
            value={form.first_name}
            onChange={(e) => setForm({ ...form, first_name: e.target.value })}
          />
        </p>

        <p className="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
          <label htmlFor="last_name">
            Apellidos&nbsp;<span className="required" aria-hidden="true">*</span>
          </label>
          <input
            id="last_name"
            type="text"
            className="woocommerce-Input woocommerce-Input--text input-text"
            value={form.last_name}
            onChange={(e) => setForm({ ...form, last_name: e.target.value })}
          />
        </p>

        <div className="clear"></div>

        <p className="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
          <label htmlFor="email">
            Correo electrónico&nbsp;<span className="required" aria-hidden="true">*</span>
          </label>
          <input
            id="email"
            type="email"
            className="woocommerce-Input woocommerce-Input--email input-text"
            value={form.email}
            onChange={(e) => setForm({ ...form, email: e.target.value })}
          />
        </p>

        <p className="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
          <label htmlFor="date_of_birth">
            Fecha de nacimiento&nbsp;<span className="required" aria-hidden="true">*</span>
          </label>
          <input
            id="date_of_birth"
            type="date"
            className="woocommerce-Input woocommerce-Input--text input-text"
            value={form.date_of_birth}
            onChange={(e) => setForm({ ...form, date_of_birth: e.target.value })}
          />
        </p>

        <fieldset>
          <legend>Cambio de contraseña</legend>

          <p className="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label htmlFor="current_password">
              Contraseña actual (déjalo en blanco para no cambiarla)
            </label>
            <span className="password-input">
              <input
                id="current_password"
                type={showCurrentPwd ? 'text' : 'password'}
                className="woocommerce-Input woocommerce-Input--password input-text"
                value={form.current_password}
                onChange={(e) => setForm({ ...form, current_password: e.target.value })}
                autoComplete="current-password"
              />
              <button
                type="button"
                className={`show-password-input${showCurrentPwd ? ' display-password' : ''}`}
                aria-label={showCurrentPwd ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                onClick={() => setShowCurrentPwd((v) => !v)}
              />
            </span>
          </p>

          <p className="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label htmlFor="password">
              Nueva contraseña (déjalo en blanco para no cambiarla)
            </label>
            <span className="password-input">
              <input
                id="password"
                type={showNewPwd ? 'text' : 'password'}
                className="woocommerce-Input woocommerce-Input--password input-text"
                value={form.password}
                onChange={(e) => setForm({ ...form, password: e.target.value })}
                autoComplete="new-password"
              />
              <button
                type="button"
                className={`show-password-input${showNewPwd ? ' display-password' : ''}`}
                aria-label={showNewPwd ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                onClick={() => setShowNewPwd((v) => !v)}
              />
            </span>
          </p>

          <p className="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label htmlFor="password_confirmation">
              Confirmar nueva contraseña (déjalo en blanco para no cambiarla)
            </label>
            <span className="password-input">
              <input
                id="password_confirmation"
                type={showConfirmPwd ? 'text' : 'password'}
                className="woocommerce-Input woocommerce-Input--password input-text"
                value={form.password_confirmation}
                onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })}
                autoComplete="new-password"
              />
              <button
                type="button"
                className={`show-password-input${showConfirmPwd ? ' display-password' : ''}`}
                aria-label={showConfirmPwd ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                onClick={() => setShowConfirmPwd((v) => !v)}
              />
            </span>
          </p>
        </fieldset>

        <p>
          <button
            type="submit"
            className="woocommerce-Button button"
            name="save_account_details"
            disabled={loading}
          >
            {loading ? 'Guardando...' : 'GUARDAR LOS CAMBIOS'}
          </button>
        </p>

      </form>
    </>
  );
}
