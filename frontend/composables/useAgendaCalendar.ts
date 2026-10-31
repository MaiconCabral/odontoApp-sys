import type { Agenda, AgendaDay, AgendaView } from '~/types/agenda'

export function useAgendaCalendar(agenda: Agenda) {
  const sessionEvents = useSessionAgendaEvents()
  const view = ref<AgendaView>('week')
  const currentDate = ref(agenda.today)
  const selectedProfessionals = ref(
    agenda.professionals.filter(professional => professional.selected).map(professional => professional.id),
  )
  const selectedRooms = ref(agenda.rooms.filter(room => room.selected).map(room => room.id))

  const rangeLabel = computed(() => formatRange(currentDate.value, view.value))

  const days = computed<AgendaDay[]>(() => {
    const dates = view.value === 'week' ? weekDates(currentDate.value) : [currentDate.value]

    return dates.map(date => ({
      date,
      weekday: weekdayLabel(date),
      dayOfMonth: dayOfMonth(date),
      isToday: date === agenda.today,
    }))
  })

  const visibleEvents = computed(() =>
    [...agenda.events, ...sessionEvents.value].filter(event =>
      selectedProfessionals.value.includes(event.professionalId)
      && selectedRooms.value.includes(event.roomId),
    ),
  )

  function goToPrevious() {
    currentDate.value = shiftDate(currentDate.value, view.value, -1)
  }

  function goToNext() {
    currentDate.value = shiftDate(currentDate.value, view.value, 1)
  }

  function goToToday() {
    currentDate.value = agenda.today
  }

  return {
    view,
    currentDate,
    selectedProfessionals,
    selectedRooms,
    rangeLabel,
    days,
    visibleEvents,
    goToPrevious,
    goToNext,
    goToToday,
  }
}
