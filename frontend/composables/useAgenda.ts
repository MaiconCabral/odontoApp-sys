import type { NuxtApp } from '#app'
import type { Agenda, AgendaOptions, AgendaRange } from '~/types/agenda'

// Period asked to the API. Null keeps the default period of the server.
export function useAgendaRange() {
  return useState<AgendaRange | null>('agenda-range', () => null)
}

// Loads the agenda again whenever the period changes.
export function useAgenda() {
  const range = useAgendaRange()

  return useAsyncData('agenda', () => agendaService.get(range.value), { watch: [range] })
}

// Procedures, professionals and rooms the booking forms list. Reuses the agenda already on screen,
// so opening a modal does not load the agenda again just to fill the selects.
export function useAgendaOptions() {
  return useAsyncData('agenda-options', () => agendaService.options(), {
    dedupe: 'defer',
    getCachedData: agendaOptionsFromPayload,
  })
}

// Options already loaded, alone or inside the agenda.
function agendaOptionsFromPayload(key: string, nuxtApp: NuxtApp): AgendaOptions | undefined {
  const agenda = nuxtApp.payload.data.agenda as Agenda | null | undefined

  return nuxtApp.payload.data[key]
    ?? (agenda ? { today: agenda.today, procedures: agenda.procedures, professionals: agenda.professionals, rooms: agenda.rooms } : undefined)
}

// Drops the options after the catalog or the team change, so the next form loads them again.
export function forgetAgendaOptions() {
  clearNuxtData(['agenda-options', 'agenda'])
}

// Reloads everything that shows appointments after a change.
export async function refreshAgendaData() {
  await refreshNuxtData(['agenda', 'appointment-requests'])
}
