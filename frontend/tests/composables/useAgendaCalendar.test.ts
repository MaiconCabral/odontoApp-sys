import { describe, expect, it } from 'vitest'
import { agendaMock } from '../fixtures/agenda'

describe('useAgendaCalendar', () => {
  it('starts on the week of today with the selected filters', () => {
    const calendar = useAgendaCalendar(agendaMock)

    expect(calendar.rangeLabel.value).toBe('21 — 27 Outubro, 2024')
    expect(calendar.currentDate.value).toBe('2024-10-24')
    expect(calendar.selectedProfessionals.value).toEqual(['carlos', 'ana'])
    expect(calendar.visibleEvents.value).toHaveLength(4)
    expect(calendar.visibleBlocks.value).toHaveLength(1)
  })

  it('labels the day view with the current date', () => {
    const calendar = useAgendaCalendar(agendaMock)

    calendar.view.value = 'day'

    expect(calendar.rangeLabel.value).toBe('24 Outubro, 2024')
  })

  it('navigates by period and returns to today', () => {
    const calendar = useAgendaCalendar(agendaMock)

    calendar.goToNext()
    expect(calendar.rangeLabel.value).toBe('28 Outubro — 3 Novembro, 2024')

    calendar.goToPrevious()
    calendar.goToPrevious()
    expect(calendar.rangeLabel.value).toBe('14 — 20 Outubro, 2024')

    calendar.goToToday()
    expect(calendar.currentDate.value).toBe('2024-10-24')
  })

  it('asks the API for a new period only outside the loaded one', async () => {
    const calendar = useAgendaCalendar(agendaMock)

    calendar.goToNext()
    await nextTick()
    expect(useAgendaRange().value).toBeNull()

    calendar.goToPrevious()
    calendar.goToPrevious()
    await nextTick()
    expect(useAgendaRange().value).toEqual({ from: '2024-10-14', to: '2024-11-24' })
  })

  it('filters the events by professional and by room, keeping requests without a room', () => {
    const calendar = useAgendaCalendar(agendaMock)

    calendar.selectedProfessionals.value = ['ana']
    expect(calendar.visibleEvents.value.map(event => event.patientName)).toEqual(['Rafael Oliveira'])

    calendar.selectedProfessionals.value = ['carlos', 'ana']
    calendar.selectedRooms.value = ['room-2']
    expect(calendar.visibleEvents.value.map(event => event.patientName)).toEqual(['Juliana Almeida', 'Pedido Online'])
  })
})
