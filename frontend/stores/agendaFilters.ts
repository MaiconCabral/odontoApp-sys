import type { Agenda } from '~/types/agenda'

export const useAgendaFiltersStore = defineStore('agendaFilters', () => {
  const selectedProfessionals = ref<string[]>([])
  const selectedRooms = ref<string[]>([])
  const isInitialized = ref(false)

  // Keeps the current choice when the user comes back to the agenda.
  function initialize(agenda: Agenda) {
    if (isInitialized.value) {
      return
    }

    selectedProfessionals.value = agenda.professionals.filter(item => item.selected).map(item => item.id)
    selectedRooms.value = agenda.rooms.filter(item => item.selected).map(item => item.id)
    isInitialized.value = true
  }

  function reset() {
    selectedProfessionals.value = []
    selectedRooms.value = []
    isInitialized.value = false
  }

  return { selectedProfessionals, selectedRooms, isInitialized, initialize, reset }
})
