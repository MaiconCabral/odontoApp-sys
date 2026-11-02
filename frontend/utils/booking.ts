import type { BookingForm } from '~/types/schedule'
import type { FormErrors } from '~/utils/auth'

// Contact data the patient types in the last step of the public booking.
export function validateBookingContact(form: BookingForm): FormErrors<BookingForm> {
  const errors: FormErrors<BookingForm> = {}
  const phone = form.phone.replace(/\D/g, '')

  if (form.name.trim().length < 3) {
    errors.name = 'Informe seu nome completo.'
  }

  if (phone.length < 10 || phone.length > 13) {
    errors.phone = 'Informe um telefone com DDD.'
  }

  if (form.email.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) {
    errors.email = 'Informe um e-mail válido.'
  }

  if (!form.consent) {
    errors.consent = 'Autorize o contato da clínica para continuar.'
  }

  return errors
}

// Today in the browser time zone, as YYYY-MM-DD.
export function localToday(): string {
  const now = new Date()

  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
}
