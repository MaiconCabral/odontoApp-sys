import { describe, expect, it } from 'vitest'
import { agendaMock } from '~/mocks/agenda'

describe('useAgendaCalendar', () => {
  it('starts on the week of today with the selected filters', () => {
    const calendar = useAgendaCalendar(agendaMock)

    expect(calendar.rangeLabel.value).toBe('21 — 27 Outubro, 2024')
    expect(calendar.days.value).toHaveLength(7)
    expect(calendar.days.value.find(day => day.isToday)?.date).toBe('2024-10-24')
    expect(calendar.selectedProfessionals.value).toEqual(['carlos', 'ana'])
    expect(calendar.visibleEvents.value).toHaveLength(3)
  })

  it('shows only the current date in the day view', () => {
    const calendar = useAgendaCalendar(agendaMock)

    calendar.view.value = 'day'

    expect(calendar.days.value.map(day => day.date)).toEqual(['2024-10-24'])
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

  it('filters the events by professional and by room', () => {
    const calendar = useAgendaCalendar(agendaMock)

    calendar.selectedProfessionals.value = ['ana']
    expect(calendar.visibleEvents.value.map(event => event.patientName)).toEqual(['Rafael Oliveira'])

    calendar.selectedProfessionals.value = ['carlos', 'ana']
    calendar.selectedRooms.value = ['room-2']
    expect(calendar.visibleEvents.value.map(event => event.patientName)).toEqual(['Juliana Almeida'])
  })
})
