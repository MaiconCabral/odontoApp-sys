import type { AgendaEvent } from '~/types/agenda'

export function useAgenda() {
  return useAsyncData('agenda', () => agendaService.get())
}

// Appointments confirmed in this session, until the API exists.
export function useSessionAgendaEvents() {
  return useState<AgendaEvent[]>('agenda-session-events', () => [])
}
