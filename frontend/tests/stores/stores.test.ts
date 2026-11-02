import { describe, expect, it } from 'vitest'
import { agendaMock } from '../fixtures/agenda'
import { clearSession } from '../utils/session'

describe('useWorkspace', () => {
  it('builds the navigation from the clinic in the session', () => {
    const workspace = useWorkspace()

    expect(workspace.value.clinic.name).toBe('Sorriso Perfeito')
    expect(workspace.value.nav[1]?.path).toBe('/app/agenda')
  })

  it('stays renderable after the session ends', () => {
    const workspace = useWorkspace()

    clearSession()

    expect(workspace.value.user.name).toBe('')
    expect(workspace.value.plan.trialDaysLeft).toBe(0)
  })
})

describe('useAgendaFiltersStore', () => {
  it('starts from the agenda defaults and keeps the choice afterwards', () => {
    const filters = useAgendaFiltersStore()

    filters.initialize(agendaMock)
    expect(filters.selectedProfessionals).toEqual(['carlos', 'ana'])

    filters.selectedProfessionals = ['ana']
    filters.initialize(agendaMock)
    expect(filters.selectedProfessionals).toEqual(['ana'])
  })

  it('keeps the filters between two mounts of the calendar', () => {
    useAgendaCalendar(agendaMock).selectedProfessionals.value = ['ana']

    expect(useAgendaCalendar(agendaMock).selectedProfessionals.value).toEqual(['ana'])
  })

  it('returns to the defaults after a reset', () => {
    const filters = useAgendaFiltersStore()

    filters.initialize(agendaMock)
    filters.selectedProfessionals = []
    filters.reset()
    filters.initialize(agendaMock)

    expect(filters.selectedProfessionals).toEqual(['carlos', 'ana'])
  })
})
