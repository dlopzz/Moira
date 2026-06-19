type Step = { label: string; href: string };

const STEPS: Step[] = [
  { label: 'Envío', href: '/checkout/shipping' },
  { label: 'Pago', href: '/checkout/payment' },
];

export default function CheckoutSteps({ current }: { current: number }) {
  return (
    <div className="flex items-center gap-0 mb-8">
      {STEPS.map((step, i) => (
        <div key={i} className="flex items-center">
          <div className={`flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium ${
            i + 1 === current
              ? 'bg-blue-600 text-white'
              : i + 1 < current
              ? 'bg-green-100 text-green-700'
              : 'bg-gray-100 text-gray-400'
          }`}>
            <span className={`w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold ${
              i + 1 === current ? 'bg-white text-blue-600' : i + 1 < current ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-500'
            }`}>
              {i + 1 < current ? '✓' : i + 1}
            </span>
            {step.label}
          </div>
          {i < STEPS.length - 1 && (
            <div className={`w-8 h-0.5 ${i + 1 < current ? 'bg-green-300' : 'bg-gray-200'}`} />
          )}
        </div>
      ))}
    </div>
  );
}
