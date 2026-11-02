import type { AgendaRange } from '~/types/agenda'

// Period asked to the API. Null keeps the default period of the server.
export function useAgendaRange() {
  return useState<AgendaRange | null>('agenda-range', () => null)
}

// Loads the agenda again whenever the period changes.
export function useAgenda() {
  const range = useAgendaRange()

  return useAsyncData('agenda', () => agendaService.get(range.value), { watch: [range] })
}

// Reloads everything that shows appointments after a change.
export async function refreshAgendaData() {
  await refreshNuxtData(['agenda', 'appointment-requests'])
}
