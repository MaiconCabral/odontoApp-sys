export type PatientTone = 'blue' | 'amber' | 'green' | 'violet' | 'pink'

export type Patient = {
  id: string
  initials: string
  name: string
  tone: PatientTone
  cpf: string
  phone: string
  lastAppointment: string
  insurance: string
  isPrivate: boolean
}

export type PatientList = {
  total: number
  activeFilters: number
  insurances: string[]
  patients: Patient[]
}
