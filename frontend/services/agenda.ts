import type { Agenda, AgendaRange } from '~/types/agenda'

export const agendaService = {
  // Agenda of a period, or of the default period when none is given.
  get: (range?: AgendaRange | null) =>
    useApi().api<Agenda>('/api/agenda', { query: range ?? undefined }),
}
