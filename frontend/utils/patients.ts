import type { Patient } from '~/types/patients'

function normalizeText(value: string): string {
  return value.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase().trim()
}

function digitsOnly(value: string): string {
  return value.replace(/\D/g, '')
}

export function matchesPatientSearch(patient: Patient, query: string): boolean {
  const text = normalizeText(query)

  if (!text) {
    return true
  }

  if (normalizeText(patient.name).includes(text)) {
    return true
  }

  const digits = digitsOnly(query)

  return digits.length > 0
    && (digitsOnly(patient.phone).includes(digits) || digitsOnly(patient.cpf).includes(digits))
}

export type NewPatientForm = {
  name: string
  cpf: string
  phone: string
  insurance: string
}

export type NewPatientErrors = Partial<Record<keyof NewPatientForm, string>>

export function validateNewPatient(form: NewPatientForm, patients: Patient[]): NewPatientErrors {
  const errors: NewPatientErrors = {}
  const cpf = digitsOnly(form.cpf)
  const phone = digitsOnly(form.phone)

  if (form.name.trim().length < 2) {
    errors.name = 'Informe o nome do paciente.'
  }

  if (cpf.length !== 11) {
    errors.cpf = 'Informe um CPF com 11 dígitos.'
  }
  else if (patients.some(patient => digitsOnly(patient.cpf) === cpf)) {
    errors.cpf = 'Já existe um paciente com este CPF.'
  }

  if (phone.length < 10 || phone.length > 11) {
    errors.phone = 'Informe um telefone com DDD.'
  }

  return errors
}

export function formatCpf(value: string): string {
  return digitsOnly(value).replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4')
}

export function formatPhone(value: string): string {
  return digitsOnly(value).replace(/^(\d{2})(\d{4,5})(\d{4})$/, '($1) $2-$3')
}

export function patientInitials(name: string): string {
  const words = name.trim().split(/\s+/)
  const first = words[0]?.[0] ?? ''
  const last = words.length > 1 ? words[words.length - 1]?.[0] ?? '' : ''

  return `${first}${last}`.toUpperCase()
}
