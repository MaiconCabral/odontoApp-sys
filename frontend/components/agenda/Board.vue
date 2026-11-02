<script setup lang="ts">
import type { Agenda, AgendaBlock } from '~/types/agenda'

const props = defineProps<{
  agenda: Agenda
  canManage?: boolean
}>()

const emit = defineEmits<{
  select: [id: string]
  selectBlock: [block: AgendaBlock]
  block: []
}>()

const {
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
} = useAgendaCalendar(() => props.agenda)

// Opens a day of the month in the day view.
function openDay(date: string) {
  currentDate.value = date
  view.value = 'day'
}
</script>

<template>
  <AgendaToolbar
    v-model:view="view"
    :range-label="rangeLabel"
    :can-block="canManage"
    @previous="goToPrevious"
    @next="goToNext"
    @today="goToToday"
    @block="emit('block')"
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
      class="min-w-0 overflow-auto rounded-xl border border-line bg-surface shadow-[0_4px_14px_rgba(42,64,91,.025)]"
      aria-label="Calendário de consultas"
    >
      <AgendaMonthGrid v-if="view === 'month'" :date="currentDate" :events="visibleEvents" @open-day="openDay" />
      <AgendaTimeGrid
        v-else
        :days="days"
        :hours="agenda.hours"
        :events="visibleEvents"
        :blocks="visibleBlocks"
        :professionals="agenda.professionals"
        :can-manage-blocks="canManage"
        @select="emit('select', $event)"
        @select-block="emit('selectBlock', $event)"
      />
    </section>
  </div>
</template>
