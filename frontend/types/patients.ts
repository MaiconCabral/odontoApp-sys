export type PatientTone = 'blue' | 'amber' | 'green' | 'violet' | 'pink'

export type Patient = {
  id: string
  name: string
  initials: string
  tone: PatientTone
  cpf: string | null
  phone: string
  email: string | null
  birthDate: string | null
  insurance: string | null
  isPrivate: boolean
  notes: string | null
  lastAppointment: string
}

export type PatientList = {
  patients: Patient[]
  total: number
  page: number
  lastPage: number
}

export type PatientQuery = {
  search: string
  page: number
}

export type PatientForm = {
  name: string
  cpf: string
  phone: string
  email: string
  insurance: string
  birthDate: string
  notes: string
}
