import type { Agenda } from '~/types/agenda'

export const agendaService = {
  // Agenda the user may see.
  get: () => useApi().api<Agenda>('/api/agenda'),
}
