<script setup lang="ts">
import { CalendarDays, Search } from 'lucide-vue-next'
import type { NewAppointmentErrors, NewAppointmentForm } from '~/utils/appointments'

const { closeModal } = useActiveModal()
const { showToast } = useToast()
const sessionEvents = useSessionAgendaEvents()
const sessionPatients = useSessionPatients()

const [{ data: agenda }, { data: patientList }] = await Promise.all([useAgenda(), usePatients()])

const patientsListId = useId()

const form = reactive<NewAppointmentForm>({
  patientName: '',
  procedure: agenda.value?.procedures[0] ?? '',
  professionalId: agenda.value?.professionals[0]?.id ?? '',
  date: agenda.value?.today ?? '',
  hour: '15:00',
  roomId: agenda.value?.rooms[0]?.id ?? '',
})

const errors = ref<NewAppointmentErrors>({})

const patientNames = computed(() =>
  [...sessionPatients.value, ...(patientList.value?.patients ?? [])].map(patient => patient.name),
)

const allEvents = computed(() => [...(agenda.value?.events ?? []), ...sessionEvents.value])

function roomLabel(name: string) {
  return name.split(' ·')[0]
}

function submit() {
  errors.value = validateNewAppointment(form, patientNames.value, allEvents.value)

  if (Object.keys(errors.value).length > 0) {
    return
  }

  const patientName = form.patientName.trim()

  sessionEvents.value = [...sessionEvents.value, {
    id: `session-event-${sessionEvents.value.length + 1}`,
    patientName,
    date: form.date,
    hour: form.hour,
    procedure: form.procedure,
    tone: 'blue',
    professionalId: form.professionalId,
    roomId: form.roomId,
  }]

  showToast(`Consulta de ${patientName} confirmada para ${formatShortDate(form.date)} às ${form.hour}.`)
  closeModal()
}
</script>

<template>
  <AppModal
    v-if="agenda"
    eyebrow="Agenda clínica"
    title="Novo agendamento"
    description="Preencha os dados para reservar um horário."
    submit-label="Confirmar agendamento"
    @close="closeModal"
    @submit="submit"
  >
    <template #submit-icon>
      <CalendarDays :size="17" />
    </template>

    <AppFormField v-slot="field" label="Paciente" :error="errors.patientName" full>
      <div
        class="flex h-[37px] items-center gap-2 rounded-[7px] border border-line px-[11px] text-faint transition duration-200 focus-within:border-brand-accent"
        :class="{ '!border-[#d9705c]': field.invalid }"
      >
        <Search :size="15" class="flex-none" />
        <input
          :id="field.id"
          v-model="form.patientName"
          class="w-full border-0 bg-transparent text-[11px] text-body outline-none placeholder:text-faint [&::-webkit-calendar-picker-indicator]:!hidden"
          placeholder="Buscar paciente cadastrado"
          autocomplete="off"
          autofocus
          :list="patientsListId"
          :aria-invalid="field.invalid"
          :aria-describedby="field.describedBy"
        >
        <datalist :id="patientsListId">
          <option v-for="name in patientNames" :key="name" :value="name" />
        </datalist>
      </div>
    </AppFormField>

    <AppFormField v-slot="field" label="Procedimento">
      <select :id="field.id" v-model="form.procedure" :class="field.controlClass">
        <option v-for="procedure in agenda.procedures" :key="procedure" :value="procedure">
          {{ procedure }}
        </option>
      </select>
    </AppFormField>

    <AppFormField v-slot="field" label="Profissional" :error="errors.professionalId">
      <select
        :id="field.id"
        v-model="form.professionalId"
        :class="field.controlClass"
        :aria-invalid="field.invalid"
        :aria-describedby="field.describedBy"
      >
        <option v-for="professional in agenda.professionals" :key="professional.id" :value="professional.id">
          {{ professional.name }}
        </option>
      </select>
    </AppFormField>

    <AppFormField v-slot="field" label="Data" :error="errors.date">
      <input
        :id="field.id"
        v-model="form.date"
        type="date"
        :class="field.controlClass"
        :aria-invalid="field.invalid"
        :aria-describedby="field.describedBy"
      >
    </AppFormField>

    <AppFormField v-slot="field" label="Horário">
      <select :id="field.id" v-model="form.hour" :class="field.controlClass">
        <option v-for="hour in agenda.hours" :key="hour" :value="hour">
          {{ hour }}
        </option>
      </select>
    </AppFormField>

    <fieldset class="flex min-w-0 flex-col gap-[7px] sm:col-span-2">
      <legend class="mb-[7px] text-[10px] font-semibold text-muted">
        Sala / cadeira
      </legend>
      <div class="flex flex-col gap-2 sm:flex-row">
        <label
          v-for="room in agenda.rooms"
          :key="room.id"
          class="flex flex-1 flex-wrap items-center gap-[7px] rounded-[7px] border p-[9px] text-left text-[10px] focus-within:border-brand-accent"
          :class="[
            form.roomId === room.id
              ? 'border-brand-accent bg-brand-tint text-brand-text dark:bg-brand-soft'
              : 'border-line bg-surface text-body',
            isRoomTaken(allEvents, form.date, form.hour, room.id) ? 'cursor-not-allowed opacity-60' : 'cursor-pointer',
          ]"
        >
          <input
            v-model="form.roomId"
            type="radio"
            name="appointment-room"
            class="sr-only"
            :value="room.id"
            :disabled="isRoomTaken(allEvents, form.date, form.hour, room.id)"
          >
          <span class="h-[7px] w-[7px] flex-none rounded-full border-2 border-faint" />
          {{ roomLabel(room.name) }}
          <small
            class="ml-[15px] w-full text-[8px]"
            :class="isRoomTaken(allEvents, form.date, form.hour, room.id) ? 'text-[#c2553f]' : 'text-[#43a47d]'"
          >
            {{ isRoomTaken(allEvents, form.date, form.hour, room.id) ? 'Ocupada' : 'Disponível' }}
          </small>
        </label>
      </div>
      <p v-if="errors.roomId" class="text-[10px] text-[#c2553f]">
        {{ errors.roomId }}
      </p>
    </fieldset>
  </AppModal>
</template>
