<script setup lang="ts">
import { CalendarDays, Search, UserPlus } from 'lucide-vue-next'
import type { AppointmentDetails, AppointmentForm, AvailableSlot } from '~/types/agenda'
import type { Patient } from '~/types/patients'

const { closeModal, modalPayload } = useActiveModal()
const { showToast } = useToast()
const { data: agenda } = await useAgenda()

const payload = (modalPayload.value ?? {}) as { appointment?: AppointmentDetails, date?: string, time?: string, professionalId?: string }
const editing = payload.appointment ?? null

const form = reactive<AppointmentForm>({
  patientId: editing?.patient.id ?? '',
  procedure: editing?.procedure ?? agenda.value?.procedures[0]?.id ?? '',
  professionalId: editing?.professional?.id ?? payload.professionalId ?? agenda.value?.professionals[0]?.id ?? '',
  roomId: editing?.room?.id ?? agenda.value?.rooms.find(room => room.active !== false)?.id ?? '',
  date: editing?.date ?? payload.date ?? agenda.value?.today ?? '',
  time: editing?.hour ?? payload.time ?? '',
  notes: editing?.notes ?? '',
})

const patientQuery = ref(editing?.patient.name ?? '')
const patientResults = ref<Patient[]>([])
const patientsListId = useId()
let selectedName = editing?.patient.name ?? ''
let searchTimer: ReturnType<typeof setTimeout> | undefined

// Searches registered patients while the user types.
watch(patientQuery, (query) => {
  if (form.patientId && query === selectedName) {
    return
  }

  form.patientId = ''
  clearTimeout(searchTimer)

  if (query.trim().length < 2) {
    patientResults.value = []

    return
  }

  searchTimer = setTimeout(async () => {
    try {
      patientResults.value = (await patientsService.list({ search: query.trim(), page: 1 })).patients.slice(0, 6)
    }
    catch {
      patientResults.value = []
    }
  }, 250)
})

// Uses a patient from the search results.
function choosePatient(patient: Patient) {
  selectedName = patient.name
  form.patientId = patient.id
  patientQuery.value = patient.name
  patientResults.value = []
}

const isAddingPatient = ref(false)
const newPatient = reactive({ name: '', phone: '' })
const newPatientError = ref('')

// Registers a patient with name and phone, then selects it.
async function addPatient() {
  newPatientError.value = ''

  try {
    const patient = await patientsService.create({ name: newPatient.name, phone: newPatient.phone, cpf: '', email: '', insurance: '', birthDate: '', notes: '' })

    choosePatient(patient)
    isAddingPatient.value = false
  }
  catch (failure) {
    const { message, fields } = parseApiError(failure)

    newPatientError.value = Object.values(fields)[0] ?? message
  }
}

const slots = ref<AvailableSlot[]>([])
const isLoadingSlots = ref(false)

// Loads the free start times for the chosen day, procedure, professional and room.
async function loadSlots() {
  if (!form.date || !form.procedure || !form.professionalId) {
    slots.value = []

    return
  }

  isLoadingSlots.value = true

  try {
    slots.value = await appointmentsService.availability({
      date: form.date,
      procedure: form.procedure,
      professionalId: form.professionalId,
      roomId: form.roomId,
      ignore: editing?.id,
    })
  }
  catch {
    slots.value = []
  }
  finally {
    isLoadingSlots.value = false
  }

  if (form.time && !slots.value.some(slot => slot.time === form.time)) {
    form.time = ''
  }
}

watch(() => [form.date, form.procedure, form.professionalId, form.roomId], loadSlots, { immediate: true })

const { errors, formError, isSubmitting, submit } = useAuthForm(form, validateAppointment, async () => {
  const saved = editing
    ? await appointmentsService.update(editing.id, form)
    : await appointmentsService.create(form)

  showToast(`Consulta de ${saved.patient.name} ${editing ? 'remarcada' : 'marcada'} para ${formatAppointmentDate(saved.date, saved.hour)}.`)
  await refreshAgendaData()
  closeModal()
})

// Only rooms still in use are offered. Inactive ones stay in the agenda for old appointments.
const activeRooms = computed(() => agenda.value?.rooms.filter(room => room.active !== false) ?? [])

// The API reports closed or taken times on the start, which the form shows on the time field.
const timeError = computed(() => errors.value.time ?? (errors.value as Record<string, string | undefined>).startsAt)
</script>

<template>
  <AppModal
    v-if="agenda"
    eyebrow="Agenda clínica"
    :title="editing ? 'Remarcar consulta' : 'Novo agendamento'"
    :description="editing ? 'Escolha o novo horário. A consulta volta para Agendado.' : 'Escolha o paciente e um horário livre.'"
    :submit-label="editing ? 'Salvar alterações' : 'Confirmar agendamento'"
    :submitting="isSubmitting"
    @close="closeModal"
    @submit="submit()"
  >
    <template #submit-icon>
      <CalendarDays :size="17" />
    </template>

    <p v-if="formError" role="alert" class="text-[11px] text-tone-red-text sm:col-span-2">
      {{ formError }}
    </p>

    <AppFormField v-slot="field" label="Paciente" :error="errors.patientId" full>
      <div class="relative">
        <div
          class="flex h-[37px] items-center gap-2 rounded-[7px] border border-line px-[11px] text-faint transition duration-200 focus-within:border-brand-accent"
          :class="{ '!border-tone-red-line': field.invalid }"
        >
          <Search :size="15" class="flex-none" />
          <input
            :id="field.id"
            v-model="patientQuery"
            class="w-full border-0 bg-transparent text-[11px] text-body outline-none placeholder:text-faint"
            placeholder="Buscar por nome, CPF ou telefone"
            autocomplete="off"
            role="combobox"
            :aria-expanded="patientResults.length > 0"
            :aria-controls="patientsListId"
            :aria-invalid="field.invalid"
            :aria-describedby="field.describedBy"
          >
        </div>
        <ul
          v-if="patientResults.length"
          :id="patientsListId"
          role="listbox"
          class="absolute inset-x-0 top-[40px] z-10 overflow-hidden rounded-[7px] border border-line bg-surface shadow-[0_8px_24px_rgba(15,39,57,.12)]"
        >
          <li v-for="patient in patientResults" :key="patient.id" role="option" :aria-selected="form.patientId === patient.id">
            <button type="button" class="flex w-full justify-between gap-3 px-3 py-2 text-left text-[11px] hover:bg-brand-tint" @click="choosePatient(patient)">
              <strong class="truncate text-body">{{ patient.name }}</strong>
              <span class="flex-none text-muted">{{ patient.phone }}</span>
            </button>
          </li>
        </ul>
      </div>
      <button
        v-if="!isAddingPatient && !form.patientId"
        type="button"
        class="mt-1.5 inline-flex items-center gap-1.5 self-start text-[10px] font-bold text-brand-text"
        @click="isAddingPatient = true"
      >
        <UserPlus :size="13" /> Cadastrar paciente novo
      </button>
    </AppFormField>

    <div v-if="isAddingPatient" class="grid gap-2 rounded-lg border border-line-soft p-3 sm:col-span-2 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
      <AppFormField v-slot="field" label="Nome do paciente">
        <input :id="field.id" v-model="newPatient.name" autocomplete="off" :class="field.controlClass">
      </AppFormField>
      <AppFormField v-slot="field" label="Telefone com DDD">
        <input :id="field.id" v-model="newPatient.phone" type="tel" autocomplete="off" :class="field.controlClass">
      </AppFormField>
      <button type="button" class="h-[37px] rounded-[7px] bg-brand px-3 text-[11px] font-bold text-white" @click="addPatient">
        Cadastrar
      </button>
      <p v-if="newPatientError" role="alert" class="text-[10px] text-tone-red-text sm:col-span-3">
        {{ newPatientError }}
      </p>
    </div>

    <AppFormField v-slot="field" label="Procedimento">
      <select :id="field.id" v-model="form.procedure" :class="field.controlClass">
        <option v-for="procedure in agenda.procedures" :key="procedure.id" :value="procedure.id">
          {{ procedure.label }} ({{ procedure.duration }} min)
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

    <AppFormField v-slot="field" label="Sala" :error="errors.roomId">
      <select
        :id="field.id"
        v-model="form.roomId"
        :class="field.controlClass"
        :aria-invalid="field.invalid"
        :aria-describedby="field.describedBy"
      >
        <option v-for="room in activeRooms" :key="room.id" :value="room.id">
          {{ room.name }}
        </option>
      </select>
    </AppFormField>

    <AppFormField v-slot="field" label="Data" :error="errors.date">
      <input
        :id="field.id"
        v-model="form.date"
        type="date"
        :min="agenda.today"
        :class="field.controlClass"
        :aria-invalid="field.invalid"
        :aria-describedby="field.describedBy"
      >
    </AppFormField>

    <AppFormField v-slot="field" label="Horário" :error="timeError" full>
      <select
        :id="field.id"
        v-model="form.time"
        :disabled="isLoadingSlots"
        :class="field.controlClass"
        :aria-invalid="Boolean(timeError)"
        :aria-describedby="field.describedBy"
      >
        <option value="" disabled>
          {{ isLoadingSlots ? 'Buscando horários livres...' : slots.length ? 'Escolha um horário livre' : 'Nenhum horário livre neste dia' }}
        </option>
        <option v-for="slot in slots" :key="slot.time" :value="slot.time">
          {{ slot.time }}
        </option>
      </select>
    </AppFormField>

    <AppFormField v-slot="field" label="Observações (opcional)" full>
      <textarea :id="field.id" v-model="form.notes" rows="2" maxlength="1000" :class="[field.controlClass, '!h-auto py-2']" />
    </AppFormField>
  </AppModal>
</template>
