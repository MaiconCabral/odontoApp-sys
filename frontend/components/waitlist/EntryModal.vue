<script setup lang="ts">
import { Search } from 'lucide-vue-next'
import type { Patient } from '~/types/patients'
import type { WaitlistEntry, WaitlistForm } from '~/types/waitlist'

const props = defineProps<{
  entry: WaitlistEntry | null
  // Patient already chosen, when the modal opens from the patient page.
  patient?: { id: string, name: string }
}>()

const emit = defineEmits<{
  close: []
  saved: [entry: WaitlistEntry]
}>()

const { showToast } = useToast()
const { data: agenda } = await useAgenda()

const today = localToday()
const preset = props.entry?.patient ?? props.patient ?? null

const form = reactive<WaitlistForm>({
  patientId: preset?.id ?? '',
  procedureId: props.entry?.procedure ?? agenda.value?.procedures[0]?.id ?? '',
  professionalId: props.entry?.professional?.id ?? '',
  fromDate: props.entry?.fromDate ?? today,
  toDate: props.entry?.toDate ?? addDays(today, 30),
  period: props.entry?.period ?? 'any',
  notes: props.entry?.notes ?? '',
})

const patientQuery = ref(preset?.name ?? '')
const patientResults = ref<Patient[]>([])
const patientsListId = useId()
let selectedName = preset?.name ?? ''
let searchTimer: ReturnType<typeof setTimeout> | undefined

// Searches patients while the user types; typing again drops the chosen patient.
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
    patientResults.value = await patientsService.list({ search: query.trim(), page: 1 })
      .then(list => list.patients.slice(0, 6))
      .catch(() => [])
  }, 250)
})

// Uses a patient from the search results.
function choosePatient(patient: Patient) {
  selectedName = patient.name
  patientResults.value = []
  form.patientId = patient.id
  patientQuery.value = patient.name
}

// Required fields and the order of the dates.
function validateEntry(fields: WaitlistForm) {
  const errors: Partial<Record<keyof WaitlistForm, string>> = {}

  if (!fields.patientId) {
    errors.patientId = 'Escolha o paciente.'
  }

  if (!fields.procedureId) {
    errors.procedureId = 'Escolha o procedimento.'
  }

  if (fields.toDate < fields.fromDate) {
    errors.toDate = 'A data final precisa ser igual ou depois da inicial.'
  }

  return errors
}

const { errors, formError, isSubmitting, submit } = useAuthForm(form, validateEntry, async () => {
  const saved = props.entry ? await waitlistService.update(props.entry.id, { ...form }) : await waitlistService.create({ ...form })

  showToast(props.entry ? 'Lista de espera atualizada.' : `${saved.patient.name} entrou na lista de espera.`)
  await refreshNuxtData('waitlist')
  emit('saved', saved)
})
</script>

<template>
  <AppModal
    eyebrow="Lista de espera"
    :title="entry ? 'Editar espera' : 'Adicionar à lista de espera'"
    description="Quando um horário compatível vagar, o paciente recebe um e-mail para pedir o horário."
    :submit-label="entry ? 'Salvar alterações' : 'Adicionar'"
    :submitting="isSubmitting"
    @close="emit('close')"
    @submit="submit()"
  >
    <p v-if="formError" role="alert" class="text-[11px] text-tone-red-text sm:col-span-2">
      {{ formError }}
    </p>

    <AppFormField v-slot="field" label="Paciente" :error="errors.patientId" full>
      <div class="relative">
        <div class="flex h-[37px] items-center gap-2 rounded-[7px] border border-line px-[11px] text-faint focus-within:border-brand-accent" :class="{ '!border-tone-red-line': field.invalid }">
          <Search :size="15" class="flex-none" />
          <input
            :id="field.id"
            v-model="patientQuery"
            class="w-full border-0 bg-transparent text-[11px] text-body outline-none placeholder:text-faint disabled:text-muted"
            placeholder="Buscar por nome, CPF ou telefone"
            autocomplete="off"
            role="combobox"
            :disabled="Boolean(preset)"
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
    </AppFormField>

    <AppFormField v-slot="field" label="Procedimento" :error="errors.procedureId">
      <select :id="field.id" v-model="form.procedureId" :class="field.controlClass" :aria-invalid="field.invalid">
        <option v-for="procedure in agenda?.procedures ?? []" :key="procedure.id" :value="procedure.id">
          {{ procedure.label }}
        </option>
      </select>
    </AppFormField>

    <AppFormField v-slot="field" label="Profissional" :error="errors.professionalId">
      <select :id="field.id" v-model="form.professionalId" :class="field.controlClass">
        <option value="">
          Qualquer profissional
        </option>
        <option v-for="professional in agenda?.professionals ?? []" :key="professional.id" :value="professional.id">
          {{ professional.name }}
        </option>
      </select>
    </AppFormField>

    <AppFormField v-slot="field" label="A partir de" :error="errors.fromDate">
      <input :id="field.id" v-model="form.fromDate" type="date" :min="today" :class="field.controlClass" :aria-invalid="field.invalid" :aria-describedby="field.describedBy">
    </AppFormField>

    <AppFormField v-slot="field" label="Até" :error="errors.toDate">
      <input :id="field.id" v-model="form.toDate" type="date" :min="form.fromDate" :class="field.controlClass" :aria-invalid="field.invalid" :aria-describedby="field.describedBy">
    </AppFormField>

    <AppFormField v-slot="field" label="Período">
      <select :id="field.id" v-model="form.period" :class="field.controlClass">
        <option value="any">
          Qualquer horário
        </option>
        <option value="morning">
          Manhã
        </option>
        <option value="afternoon">
          Tarde
        </option>
      </select>
    </AppFormField>

    <AppFormField v-slot="field" label="Observações (opcional)">
      <input :id="field.id" v-model="form.notes" maxlength="500" :class="field.controlClass">
    </AppFormField>
  </AppModal>
</template>
