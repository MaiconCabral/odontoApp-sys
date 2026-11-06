<script setup lang="ts">
import type { AppointmentDetails } from '~/types/agenda'
import type { ClinicalNote, ClinicalNoteForm } from '~/types/clinical'

const props = defineProps<{
  patientId: string
}>()

const { showToast } = useToast()

const { items: notes, hasMore, isLoading, loadFailed: notesFailed, reload, loadMore } = usePagedList(page => clinicalService.notes(props.patientId, page))
const appointments = ref<AppointmentDetails[]>([])
const appointmentsFailed = ref(false)
const loadFailed = computed(() => notesFailed.value || appointmentsFailed.value)

// Appointments a note may belong to.
async function loadAppointments() {
  appointmentsFailed.value = false

  try {
    appointments.value = (await patientsService.appointments(props.patientId)).appointments
  }
  catch {
    appointmentsFailed.value = true
  }
}

// First page of notes and the appointments, at the same time.
async function load() {
  await Promise.all([reload(), loadAppointments()])
}

await load()

const form = reactive<ClinicalNoteForm>({ body: '', appointmentId: '', amendsId: '' })
const amending = computed(() => notes.value.find(note => note.id === form.amendsId) ?? null)

// Validates the note before sending.
function validateNote(fields: ClinicalNoteForm) {
  return fields.body.trim().length < 2 ? { body: 'Escreva a anotação.' } : {}
}

const { errors, formError, isSubmitting, submit } = useAuthForm(form, validateNote, async () => {
  const saved = await clinicalService.addNote(props.patientId, { ...form })

  notes.value.unshift(saved)
  Object.assign(form, { body: '', appointmentId: '', amendsId: '' })
  showToast('Anotação registrada no prontuário.')
})

// Starts a correction: the earlier note stays, and the new one points to it.
function amend(note: ClinicalNote) {
  form.amendsId = note.id
  form.appointmentId = note.appointment?.id ?? ''
}

// Date and time of a note as "09/11/2026 às 10:30".
function formatWhen(iso: string) {
  const date = new Date(iso)

  return `${date.toLocaleDateString('pt-BR')} às ${date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}`
}
</script>

<template>
  <div class="grid gap-[17px]">
    <SettingsCard title="Nova anotação" subtitle="As anotações não podem ser editadas nem excluídas. Para corrigir, registre uma correção.">
      <form class="grid gap-3 pt-4" novalidate @submit.prevent="submit()">
        <p v-if="formError" role="alert" class="text-[11px] text-tone-red-text">
          {{ formError }}
        </p>

        <p v-if="amending" class="flex flex-wrap items-center gap-2 rounded-md bg-tone-amber-bg px-2.5 py-2 text-[10px] text-tone-amber-text">
          Corrigindo a anotação de {{ formatWhen(amending.createdAt) }}.
          <button type="button" class="font-bold underline" @click="form.amendsId = ''">
            Desfazer
          </button>
        </p>

        <AppFormField v-slot="field" label="Consulta (opcional)">
          <select :id="field.id" v-model="form.appointmentId" :class="field.controlClass">
            <option value="">
              Sem consulta
            </option>
            <option v-for="appointment in appointments" :key="appointment.id" :value="appointment.id">
              {{ formatAppointmentDate(appointment.date, appointment.hour) }} · {{ appointment.procedureLabel }}
            </option>
          </select>
        </AppFormField>

        <AppFormField v-slot="field" label="Anotação" :error="errors.body">
          <textarea
            :id="field.id"
            v-model="form.body"
            rows="4"
            maxlength="5000"
            :class="[field.controlClass, 'h-auto py-2']"
            :aria-invalid="field.invalid"
            :aria-describedby="field.describedBy"
          />
        </AppFormField>

        <button
          type="submit"
          :disabled="isSubmitting"
          class="justify-self-start rounded-lg bg-brand px-[15px] py-2.5 text-[12px] font-bold text-white hover:bg-brand-hover disabled:opacity-60"
        >
          {{ amending ? 'Registrar correção' : 'Registrar anotação' }}
        </button>
      </form>
    </SettingsCard>

    <SettingsCard title="Prontuário" subtitle="Histórico do paciente, da anotação mais recente para a mais antiga.">
      <p v-if="loadFailed" role="alert" class="pt-4 text-[11px] text-muted">
        Não foi possível carregar o prontuário.
        <button type="button" class="font-bold text-brand-text" @click="load">
          Tentar novamente
        </button>
      </p>

      <p v-else-if="!notes.length" class="pt-4 text-[11px] text-muted">
        Nenhuma anotação ainda.
      </p>

      <ol v-else class="mt-[5px]">
        <li v-for="note in notes" :key="note.id" class="border-b border-line-soft py-3 last:border-b-0">
          <div class="flex flex-wrap items-center justify-between gap-2 text-[10px] text-muted">
            <span>
              <strong class="text-body">{{ note.author }}</strong> · {{ formatWhen(note.createdAt) }}
              <template v-if="note.appointment">
                · consulta de {{ formatShortDate(note.appointment.date) }} ({{ note.appointment.procedureLabel }})
              </template>
            </span>
            <button type="button" class="font-bold text-brand-text" @click="amend(note)">
              Corrigir
            </button>
          </div>
          <p v-if="note.amendsId" class="mt-1 text-[10px] font-bold text-tone-amber-text">
            Correção de uma anotação anterior
          </p>
          <p class="mt-1 whitespace-pre-line text-[11px] text-body">
            {{ note.body }}
          </p>
        </li>
      </ol>

      <button
        v-if="hasMore && !loadFailed"
        type="button"
        class="mt-3 text-[11px] font-bold text-brand-text disabled:opacity-60"
        :disabled="isLoading"
        @click="loadMore"
      >
        {{ isLoading ? 'Carregando...' : 'Carregar mais' }}
      </button>
    </SettingsCard>
  </div>
</template>
