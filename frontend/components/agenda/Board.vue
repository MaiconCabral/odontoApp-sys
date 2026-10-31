<script setup lang="ts">
import type { Agenda } from '~/types/agenda'

const props = defineProps<{
  agenda: Agenda
}>()

const {
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
} = useAgendaCalendar(props.agenda)
</script>

<template>
  <AgendaToolbar
    v-model:view="view"
    :range-label="rangeLabel"
    @previous="goToPrevious"
    @next="goToNext"
    @today="goToToday"
  />

  <div class="grid grid-cols-1 gap-4 md:grid-cols-[220px_1fr]">
    <AgendaFiltersPanel
      v-model:selected-professionals="selectedProfessionals"
      v-model:selected-rooms="selectedRooms"
      :professionals="agenda.professionals"
      :rooms="agenda.rooms"
      :free-slots-today="agenda.freeSlotsToday"
    />
    <section
      class="min-w-0 overflow-auto rounded-xl border border-[#e7edf3] bg-surface shadow-[0_4px_14px_rgba(42,64,91,.025)] dark:border-line"
      aria-label="Calendário de consultas"
    >
      <AgendaMonthGrid v-if="view === 'month'" :date="currentDate" :events="visibleEvents" />
      <AgendaTimeGrid v-else :days="days" :hours="agenda.hours" :events="visibleEvents" />
    </section>
  </div>
</template>
