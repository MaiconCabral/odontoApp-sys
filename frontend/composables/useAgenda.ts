import type { AgendaEvent } from '~/types/agenda'
import { agendaMock } from '~/mocks/agenda'

export function useAgenda() {
  return useAsyncData('agenda', () => Promise.resolve(agendaMock))
}

// Appointments confirmed in this session, until the API exists.
export function useSessionAgendaEvents() {
  return useState<AgendaEvent[]>('agenda-session-events', () => [])
}
