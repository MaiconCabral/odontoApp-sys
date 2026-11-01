import type { Patient } from '~/types/patients'

export function usePatients() {
  return useAsyncData('patients', () => patientsService.list())
}

// Patients registered in this session, until the API exists.
export function useSessionPatients() {
  return useState<Patient[]>('session-patients', () => [])
}
