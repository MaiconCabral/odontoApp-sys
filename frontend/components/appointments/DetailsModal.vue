<script setup lang="ts">
import type { AppointmentAction, AppointmentDetails } from '~/types/agenda'

const { closeModal, modalPayload, openModal } = useActiveModal()
const { showToast } = useToast()

const id = String(modalPayload.value?.id ?? '')
const details = ref<AppointmentDetails | null>(null)
const loadFailed = ref(false)

// Loads the appointment with its history and allowed actions.
async function load() {
  loadFailed.value = false

  try {
    details.value = await appointmentsService.get(id)
  }
  catch {
    loadFailed.value = true
  }
}

// The form options and the appointment load at the same time.
const [{ data: agenda }] = await Promise.all([useAgendaOptions(), load()])

const pendingAction = ref<AppointmentAction | null>(null)
const reason = ref('')
const activeRooms = computed(() => agenda.value?.rooms.filter(room => room.active !== false) ?? [])
const roomId = ref(activeRooms.value[0]?.id ?? '')
const actionError = ref('')
const isBusy = ref(false)

const needsInput: AppointmentAction[] = ['accept', 'reject', 'cancel']

// Runs an action. Accepting, rejecting and cancelling first ask for a room or a reason.
async function run(action: AppointmentAction) {
  if (!details.value) {
    return
  }

  if (action === 'reschedule') {
    openModal('appointment', { appointment: details.value })

    return
  }

  if (needsInput.includes(action) && pendingAction.value !== action) {
    pendingAction.value = action
    actionError.value = ''

    return
  }

  const status = ACTION_STATUS[action]

  isBusy.value = true
  actionError.value = ''

  try {
    details.value = action === 'accept'
      ? await appointmentsService.accept(id, roomId.value)
      : await appointmentsService.changeStatus(id, status!, reason.value)

    pendingAction.value = null
    reason.value = ''
    showToast(`Consulta de ${details.value.patient.name}: ${details.value.statusLabel}.`)
    await refreshAgendaData()
  }
  catch (failure) {
    const { message, fields } = parseApiError(failure)

    actionError.value = Object.values(fields)[0] ?? message
  }
  finally {
    isBusy.value = false
  }
}

const buttonClass = 'inline-flex items-center justify-center rounded-lg border border-line bg-surface px-3 py-2 text-[11px] font-bold text-body transition duration-200 hover:border-brand-accent disabled:opacity-60'
const dangerActions: AppointmentAction[] = ['cancel', 'reject', 'no_show']

// Admins and reception record payments; the API enforces it.
const auth = useAuthStore()
const canManagePayments = computed(() => auth.user?.role === 'admin' || auth.user?.role === 'reception')
</script>

<template>
  <AppModal
    eyebrow="Agenda clínica"
    :title="details ? details.patient.name : 'Consulta'"
    :description="details ? `${details.procedureLabel} · ${formatAppointmentDate(details.date, details.hour)} – ${details.endHour}` : 'Carregando...'"
    @close="closeModal"
  >
    <div v-if="loadFailed" role="alert" class="text-[11px] text-muted sm:col-span-2">
      Não foi possível carregar a consulta.
      <button type="button" class="font-bold text-brand-text" @click="load">
        Tentar novamente
      </button>
    </div>

    <template v-else-if="details">
      <NuxtLink
        :to="panelPath(`pacientes/${details.patient.id}`)"
        class="justify-self-start text-[11px] font-bold text-brand-text hover:underline sm:col-span-2"
        @click="closeModal"
      >
        Ver ficha do paciente
      </NuxtLink>

      <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-[11px] sm:col-span-2">
        <div>
          <dt class="text-[10px] text-muted">
            Status
          </dt>
          <dd>
            <span class="inline-block rounded-md border-l-[3px] px-2 py-1 text-[10px] font-bold" :class="STATUS_CLASSES[details.status]">
              {{ details.statusLabel }}
            </span>
          </dd>
        </div>
        <div>
          <dt class="text-[10px] text-muted">
            Origem
          </dt>
          <dd>{{ details.source === 'public' ? 'Pedido online do paciente' : 'Marcada pela clínica' }}</dd>
        </div>
        <div>
          <dt class="text-[10px] text-muted">
            Profissional
          </dt>
          <dd>{{ details.professional?.name ?? 'A definir' }}</dd>
        </div>
        <div>
          <dt class="text-[10px] text-muted">
            Sala
          </dt>
          <dd>{{ details.room?.name ?? 'A definir' }}</dd>
        </div>
        <div>
          <dt class="text-[10px] text-muted">
            Telefone
          </dt>
          <dd>{{ details.patient.phone }}</dd>
        </div>
        <div v-if="details.patient.email">
          <dt class="text-[10px] text-muted">
            E-mail
          </dt>
          <dd class="truncate">
            {{ details.patient.email }}
          </dd>
        </div>
        <div v-if="details.notes" class="col-span-2">
          <dt class="text-[10px] text-muted">
            Observações
          </dt>
          <dd>{{ details.notes }}</dd>
        </div>
        <div v-if="details.cancelReason" class="col-span-2">
          <dt class="text-[10px] text-muted">
            Motivo
          </dt>
          <dd>{{ details.cancelReason }}</dd>
        </div>
      </dl>

      <div v-if="details.actions.length" class="flex flex-wrap gap-2 sm:col-span-2">
        <button
          v-for="action in details.actions"
          :key="action"
          type="button"
          :class="[buttonClass, dangerActions.includes(action) ? 'text-tone-red-text' : '', pendingAction === action ? '!border-brand-accent' : '']"
          :disabled="isBusy"
          @click="run(action)"
        >
          {{ ACTION_LABELS[action] }}
        </button>
      </div>

      <div v-if="pendingAction" class="grid gap-2 rounded-lg border border-line-soft p-3 sm:col-span-2">
        <template v-if="pendingAction === 'accept'">
          <AppFormField v-slot="field" label="Sala da consulta">
            <select :id="field.id" v-model="roomId" :class="field.controlClass">
              <option v-for="room in activeRooms" :key="room.id" :value="room.id">
                {{ room.name }}
              </option>
            </select>
          </AppFormField>
        </template>
        <AppFormField v-else v-slot="field" label="Motivo">
          <input :id="field.id" v-model="reason" maxlength="255" autocomplete="off" :class="field.controlClass">
        </AppFormField>
        <div class="flex gap-2">
          <button type="button" class="rounded-lg bg-brand px-3 py-2 text-[11px] font-bold text-white disabled:opacity-60" :disabled="isBusy" @click="run(pendingAction)">
            {{ pendingAction === 'accept' ? 'Aceitar e agendar' : `Confirmar: ${ACTION_LABELS[pendingAction].toLowerCase()}` }}
          </button>
          <button type="button" :class="buttonClass" @click="pendingAction = null">
            Voltar
          </button>
        </div>
      </div>

      <p v-if="actionError" role="alert" class="text-[11px] text-tone-red-text sm:col-span-2">
        {{ actionError }}
      </p>

      <Suspense v-if="canManagePayments">
        <AppointmentsPaymentsSection :appointment-id="details.id" :status="details.status" @changed="refreshAgendaData()" />
      </Suspense>

      <section v-if="details.notifications" class="sm:col-span-2" aria-label="Avisos ao paciente">
        <h3 class="mb-2 text-[10px] font-bold uppercase tracking-[.06em] text-muted">
          Avisos ao paciente
        </h3>
        <p v-if="details.patientHasEmail === false" class="mb-2 rounded-md bg-tone-amber-bg px-2.5 py-2 text-[10px] text-tone-amber-text">
          Paciente sem e-mail: avise por telefone.
        </p>
        <p v-if="!details.notifications.length" class="text-[10px] text-muted">
          Nenhum aviso enviado ainda.
        </p>
        <ul v-else class="grid gap-1 text-[10px] text-muted">
          <li v-for="(notice, index) in details.notifications" :key="index">
            <strong class="text-body">{{ notice.typeLabel }}</strong>
            · {{ notice.statusLabel }} · {{ new Date(notice.at).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) }}
          </li>
        </ul>
      </section>

      <section v-if="details.history?.length" class="sm:col-span-2" aria-label="Histórico">
        <h3 class="mb-2 text-[10px] font-bold uppercase tracking-[.06em] text-muted">
          Histórico
        </h3>
        <ol class="grid gap-1.5 border-l border-line-soft pl-3 text-[10px] text-muted">
          <li v-for="(entry, index) in details.history" :key="index">
            <strong class="text-body">{{ entry.toLabel }}</strong>
            · {{ entry.author }} · {{ new Date(entry.at).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) }}
            <span v-if="entry.reason" class="block">{{ entry.reason }}</span>
          </li>
        </ol>
      </section>
    </template>
  </AppModal>
</template>
