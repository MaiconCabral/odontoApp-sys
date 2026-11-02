import type { MaybeRefOrGetter } from 'vue'
import type { Agenda, AgendaDay, AgendaView } from '~/types/agenda'

// Agenda days to show, with filters and navigation. Asks a new period when the view leaves the loaded one.
export function useAgendaCalendar(source: MaybeRefOrGetter<Agenda>) {
  const agenda = computed(() => toValue(source))
  const range = useAgendaRange()
  const view = ref<AgendaView>('week')
  const currentDate = ref(agenda.value.today)

  const filters = useAgendaFiltersStore()
  filters.initialize(agenda.value)
  const { selectedProfessionals, selectedRooms } = storeToRefs(filters)

  const rangeLabel = computed(() => formatRange(currentDate.value, view.value))

  const days = computed<AgendaDay[]>(() => {
    const dates = view.value === 'week' ? weekDates(currentDate.value) : [currentDate.value]

    return dates.map(date => ({
      date,
      weekday: weekdayLabel(date),
      dayOfMonth: dayOfMonth(date),
      isToday: date === agenda.value.today,
    }))
  })

  // Requests have no room yet, so they stay visible whatever rooms are selected.
  const visibleEvents = computed(() =>
    agenda.value.events.filter(event =>
      (event.professionalId === null || selectedProfessionals.value.includes(event.professionalId))
      && (event.roomId === null || selectedRooms.value.includes(event.roomId)),
    ),
  )

  const visibleBlocks = computed(() =>
    agenda.value.blocks.filter(block => block.professionalId === null || selectedProfessionals.value.includes(block.professionalId)),
  )

  watch([currentDate, view], () => {
    const visible = visibleRange(currentDate.value, view.value)

    if (visible.from < agenda.value.from || visible.to > agenda.value.to) {
      range.value = { from: visible.from, to: addDays(visible.from, 41) }
    }
  })

  // Goes one period back.
  function goToPrevious() {
    currentDate.value = shiftDate(currentDate.value, view.value, -1)
  }

  // Goes one period forward.
  function goToNext() {
    currentDate.value = shiftDate(currentDate.value, view.value, 1)
  }

  // Returns to the period of today.
  function goToToday() {
    currentDate.value = agenda.value.today
  }

  return {
    view,
    currentDate,
    selectedProfessionals,
    selectedRooms,
    rangeLabel,
    days,
    visibleEvents,
    visibleBlocks,
    goToPrevious,
    goToNext,
    goToToday,
  }
}
