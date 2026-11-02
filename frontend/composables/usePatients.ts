import type { PatientQuery } from '~/types/patients'

// Search and page of the patients list.
export function usePatientsQuery() {
  return useState<PatientQuery>('patients-query', () => ({ search: '', page: 1 }))
}

// Loads the patients list again when the search or the page changes.
export function usePatients() {
  const query = usePatientsQuery()

  return useAsyncData('patients', () => patientsService.list(query.value), {
    watch: [() => query.value.search, () => query.value.page],
  })
}
