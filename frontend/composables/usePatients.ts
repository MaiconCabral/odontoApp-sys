import type { Patient } from '~/types/patients'
import { patientsMock } from '~/mocks/patients'

export function usePatients() {
  return useAsyncData('patients', () => Promise.resolve(patientsMock))
}

// Patients registered in this session, until the API exists.
export function useSessionPatients() {
  return useState<Patient[]>('session-patients', () => [])
}
