<script setup lang="ts">
import { Ellipsis } from 'lucide-vue-next'
import type { AppointmentStatus, UpcomingAppointment } from '~/types/overview'

defineProps<{
  appointment: UpcomingAppointment
}>()

const { openModal } = useActiveModal()

const statuses: Record<AppointmentStatus, { label: string, pill: string, dot: string }> = {
  completed: { label: 'Concluído', pill: 'bg-[#e8f8f1] text-[#26966f]', dot: 'bg-[#2cae80]' },
  in_progress: { label: 'Em atendimento', pill: 'bg-brand-soft text-brand-text', dot: 'bg-brand' },
  waiting: { label: 'Aguardando', pill: 'bg-[#fff5df] text-[#ad7c2d]', dot: 'bg-[#dfaa4c]' },
}
</script>

<template>
  <div class="flex min-h-[65px] items-center gap-2 border-t border-line-soft sm:gap-[13px]">
    <div class="flex w-11 flex-none flex-col gap-[3px]">
      <strong class="text-[11px]">{{ appointment.time }}</strong>
      <span class="text-[9px] text-faint">{{ appointment.duration }}</span>
    </div>
    <PatientsAvatar :initials="appointment.patientInitials" :tone="appointment.patientTone" />
    <div class="flex min-w-0 flex-1 flex-col gap-1">
      <strong class="truncate text-[11px]">{{ appointment.patientName }}</strong>
      <span class="text-[10px] text-muted">{{ appointment.procedure }}</span>
    </div>
    <div class="hidden w-[108px] min-w-0 flex-col gap-1 lg:flex">
      <span class="text-[9px] text-faint">Profissional</span>
      <strong class="truncate text-[11px]">{{ appointment.professional }}</strong>
    </div>
    <span
      class="inline-flex items-center gap-[5px] whitespace-nowrap rounded-[5px] p-[5px] text-[0px] sm:px-2 sm:text-[9px]"
      :class="statuses[appointment.status].pill"
    >
      <i class="h-1.5 w-1.5 rounded-full sm:h-[5px] sm:w-[5px]" :class="statuses[appointment.status].dot" />
      {{ appointment.statusLabel || statuses[appointment.status].label }}
    </span>
    <button
      type="button"
      class="grid h-[30px] w-[30px] flex-none place-items-center rounded-[7px] text-faint transition duration-200 hover:bg-line-soft hover:text-brand-text dark:hover:bg-brand-soft"
      :aria-label="`Abrir ${appointment.patientName}`"
      @click="openModal('appointment-details', { id: appointment.id })"
    >
      <Ellipsis :size="18" />
    </button>
  </div>
</template>
