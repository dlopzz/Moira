import { redirect } from 'next/navigation';

export default function PasswordPage() {
  redirect('/profile/edit-account');
}
