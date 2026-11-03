<script setup lang="ts">
import { ArrowLeft, Pencil } from 'lucide-vue-next'

type PatientTab = 'appointments' | 'record' | 'odontogram' | 'attachments'

const route = useRoute()
const auth = useAuthStore()
const { openModal } = useActiveModal()

const id = String(route.params.id)
const { data: patient, error, pending, refresh } = await useAsyncData(`patient-${id}`, () => patientsService.get(id))

useHead({ title: () => patient.value?.name ?? 'Paciente' })

const role = computed(() => auth.user?.role)

// Reception keeps the registration; clinical data is for admins and professionals (the API enforces it).
const canEdit = computed(() => role.value === 'admin' || role.value === 'reception')
const seesRecord = computed(() => role.value === 'admin' || role.value === 'professional')

const tabs = computed(() => [
  { id: 'appointments' as const, label: 'Consultas' },
  ...(seesRecord.value
    ? [
        { id: 'record' as const, label: 'Prontuário' },
        { id: 'odontogram' as const, label: 'Odontograma' },
        { id: 'attachments' as const, label: 'Anexos' },
      ]
    : []),
])

const activeTab = ref<PatientTab>('appointments')
const isWaitlistOpen = ref(false)

// Message for a patient the user may not open.
const errorMessage = computed(() => apiStatus(error.value) === 403
  ? 'Você não tem acesso a este paciente.'
  : 'Não foi possível carregar o paciente.')

// Birth date as "12/03/1990".
function formatBirth(date: string) {
  const [year, month, day] = date.split('-')

  return `${day}/${month}/${year}`
}
</script>

<template>
  <div>
    <NuxtLink v-if="canEdit" :to="panelPath('pacientes')" class="mb-4 inline-flex items-center gap-1.5 text-[11px] font-bold text-brand-text">
      <ArrowLeft :size="14" /> Pacientes
    </NuxtLink>

    <p v-if="pending && !patient" role="status" class="text-[13px] text-muted">
      Carregando o paciente...
    </p>

    <div v-else-if="error || !patient" role="alert" class="text-[13px] text-muted">
      <p>{{ errorMessage }}</p>
      <button v-if="apiStatus(error) !== 403" type="button" class="mt-2 font-bold text-brand-text" @click="refresh()">
        Tentar novamente
      </button>
    </div>

    <template v-else>
      <section class="mb-[18px] flex flex-col gap-4 rounded-xl border border-line bg-surface p-4 sm:flex-row sm:items-start sm:p-[22px]" aria-labelledby="patient-name">
        <PatientsAvatar :initials="patient.initials" :tone="patient.tone" />
        <div class="min-w-0 flex-1">
          <h1 id="patient-name" class="font-display text-[20px] font-extrabold tracking-[-.6px] text-ink">
            {{ patient.name }}
          </h1>
          <dl class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-[11px] text-body">
            <div class="flex gap-1">
              <dt class="text-muted">
                Telefone
              </dt>
              <dd>{{ patient.phone }}</dd>
            </div>
            <div v-if="patient.email" class="flex gap-1">
              <dt class="text-muted">
                E-mail
              </dt>
              <dd>{{ patient.email }}</dd>
            </div>
            <div v-if="patient.cpf" class="flex gap-1">
              <dt class="text-muted">
                CPF
              </dt>
              <dd>{{ patient.cpf }}</dd>
            </div>
            <div v-if="patient.birthDate" class="flex gap-1">
              <dt class="text-muted">
                Nascimento
              </dt>
              <dd>{{ formatBirth(patient.birthDate) }}</dd>
            </div>
            <div class="flex gap-1">
              <dt class="text-muted">
                Convênio
              </dt>
              <dd>{{ patient.insurance ?? 'Particular' }}</dd>
            </div>
          </dl>
          <p v-if="patient.notes" class="mt-2 text-[11px] text-muted">
            {{ patient.notes }}
          </p>
        </div>
        <div v-if="canEdit" class="flex flex-none flex-wrap gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-line px-3 py-2 text-[11px] font-bold text-body"
            @click="isWaitlistOpen = true"
          >
            Adicionar à lista de espera
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-line px-3 py-2 text-[11px] font-bold text-body"
            @click="openModal('patient', { patient })"
          >
            <Pencil :size="13" /> Editar
          </button>
        </div>
      </section>

      <div role="tablist" aria-label="Seções do paciente" class="mb-[17px] flex gap-1 overflow-x-auto border-b border-line">
        <button
          v-for="tab in tabs"
          :id="`patient-tab-${tab.id}`"
          :key="tab.id"
          type="button"
          role="tab"
          :aria-selected="activeTab === tab.id"
          :aria-controls="`patient-panel-${tab.id}`"
          class="-mb-px flex-none border-b-2 px-3 py-2 text-[12px] font-bold transition duration-200"
          :class="activeTab === tab.id ? 'border-brand text-brand-text' : 'border-transparent text-muted hover:text-body'"
          @click="activeTab = tab.id"
        >
          {{ tab.label }}
        </button>
      </div>

      <section :id="`patient-panel-${activeTab}`" role="tabpanel" :aria-labelledby="`patient-tab-${activeTab}`">
        <PatientsAppointmentsTab v-if="activeTab === 'appointments'" :patient-id="patient.id" />
        <PatientsRecordTab v-else-if="activeTab === 'record'" :patient-id="patient.id" />
        <PatientsOdontogramTab v-else-if="activeTab === 'odontogram'" :patient-id="patient.id" />
        <PatientsAttachmentsTab v-else-if="activeTab === 'attachments'" :patient-id="patient.id" />
      </section>

      <WaitlistEntryModal
        v-if="isWaitlistOpen"
        :entry="null"
        :patient="{ id: patient.id, name: patient.name }"
        @close="isWaitlistOpen = false"
        @saved="isWaitlistOpen = false"
      />
    </template>
  </div>
</template>
