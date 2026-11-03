<script setup lang="ts">
import type { Agenda, AgendaBlock, AgendaEvent } from '~/types/agenda'

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
  visibleEvents,
  visibleBlocks,
  goToPrevious,
  goToNext,
  goToToday,
} = useAgendaCalendar(() => props.agenda)

const { showToast } = useToast()

// Opens a day of the month in the day view.
function openDay(date: string) {
  currentDate.value = date
  view.value = 'day'
}

type PendingMove = { event: AgendaEvent, date: string, time: string, revert: () => void }

const pendingMove = ref<PendingMove | null>(null)
const isMoving = ref(false)

// Asks before saving a drag, since moving warns the patient by e-mail.
function askMove(move: PendingMove) {
  pendingMove.value = move
}

// Puts the event back where it was.
function cancelMove() {
  pendingMove.value?.revert()
  pendingMove.value = null
}

// Saves the move through the same reschedule as the form; the API checks conflicts.
async function confirmMove() {
  const move = pendingMove.value

  if (!move) {
    return
  }

  isMoving.value = true

  try {
    const details = await appointmentsService.get(move.event.id)

    await appointmentsService.update(move.event.id, {
      patientId: details.patient.id,
      procedure: details.procedure,
      professionalId: details.professional?.id ?? '',
      roomId: details.room?.id ?? '',
      date: move.date,
      time: move.time,
      notes: details.notes ?? '',
    })
    showToast(`Consulta de ${move.event.patientName} remarcada.`)
    pendingMove.value = null
    await refreshAgendaData()
  }
  catch (failure) {
    const { message, fields } = parseApiError(failure)

    move.revert()
    pendingMove.value = null
    showToast(Object.values(fields)[0] ?? message)
  }
  finally {
    isMoving.value = false
  }
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
      <ClientOnly>
        <AgendaCalendar
          :view="view"
          :date="currentDate"
          :today="agenda.today"
          :hours="agenda.hours"
          :events="visibleEvents"
          :blocks="visibleBlocks"
          :professionals="agenda.professionals"
          :can-manage="canManage"
          @select="emit('select', $event)"
          @select-block="emit('selectBlock', $event)"
          @open-day="openDay"
          @move="askMove"
        />
      </ClientOnly>
    </section>
  </div>

  <AppModal
    v-if="pendingMove"
    eyebrow="Agenda clínica"
    title="Remarcar consulta?"
    :description="`${pendingMove.event.patientName} passa para ${formatAppointmentDate(pendingMove.date, pendingMove.time)}. O paciente recebe um e-mail com o novo horário.`"
    submit-label="Remarcar"
    :submitting="isMoving"
    @close="cancelMove"
    @submit="confirmMove"
  />
</template>
