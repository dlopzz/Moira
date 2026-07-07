import type { Metadata } from 'next';
import ContactPageClient from './ContactPageClient';

export const metadata: Metadata = {
  title: 'Contactanos | Moira Bikinis',
  description: 'Envianos tu consulta y nos ponemos en contacto a la brevedad.',
};

export default function ContactPage() {
  return <ContactPageClient />;
}
