type Props = {
  label: string;
  error?: string;
} & React.InputHTMLAttributes<HTMLInputElement>;

export function FormField({ label, error, ...props }: Props) {
  return (
    <div className="co-field form-row">
      <label className="co-label">{label}</label>
      <input type="text" className="input-text" {...props} />
      {error && <span className="co-field-error">{error}</span>}
    </div>
  );
}
