import type { PatientForm } from '~/types/patients'
import type { FormErrors } from '~/utils/auth'

export const INSURANCES = ['Particular', 'SulAmérica', 'Bradesco Saúde', 'Amil', 'Unimed', 'Outro']

// Only the digits of a value.
function digitsOnly(value: string): string {
  return value.replace(/\D/g, '')
}

// Validates the patient form. CPF and e-mail are optional, but must be valid when filled.
export function validatePatient(form: PatientForm): FormErrors<PatientForm> {
  const errors: FormErrors<PatientForm> = {}
  const cpf = digitsOnly(form.cpf)
  const phone = digitsOnly(form.phone)

  if (form.name.trim().length < 3) {
    errors.name = 'Informe o nome completo.'
  }

  if (cpf && cpf.length !== 11) {
    errors.cpf = 'Informe um CPF com 11 dígitos.'
  }

  if (phone.length < 10 || phone.length > 13) {
    errors.phone = 'Informe um telefone com DDD.'
  }

  if (form.email.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) {
    errors.email = 'Informe um e-mail válido.'
  }

  return errors
}

// CPF as 000.000.000-00.
export function formatCpf(value: string): string {
  return digitsOnly(value).replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4')
}

// Phone as (11) 90000-0000.
export function formatPhone(value: string): string {
  return digitsOnly(value).replace(/^(\d{2})(\d{4,5})(\d{4})$/, '($1) $2-$3')
}

// Initials of the first and last names.
export function patientInitials(name: string): string {
  const words = name.trim().split(/\s+/)
  const first = words[0]?.[0] ?? ''
  const last = words.length > 1 ? words[words.length - 1]?.[0] ?? '' : ''

  return `${first}${last}`.toUpperCase()
}
