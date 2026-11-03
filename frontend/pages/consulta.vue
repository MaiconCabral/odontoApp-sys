<script setup lang="ts">
import type { PatientAppointment } from '~/types/catalog'

definePageMeta({ layout: 'auth', middleware: 'clinic-host' })
useHead({ title: 'Sua consulta' })

const route = useRoute()
const token = typeof route.query.token === 'string' ? route.query.token : ''

const appointment = ref<PatientAppointment | null>(null)
const isInvalid = ref(!token)
const isBusy = ref(false)
const actionError = ref('')
const confirmingCancel = ref(false)

// Loads the appointment of the link.
async function load() {
  if (!token) {
    return
  }

  try {
    appointment.value = await patientLinksService.get(token)
  }
  catch {
    isInvalid.value = true
  }
}

// Confirms or cancels, and shows the new status.
async function answer(action: 'confirm' | 'cancel') {
  isBusy.value = true
  actionError.value = ''

  try {
    appointment.value = action === 'confirm'
      ? await patientLinksService.confirm(token)
      : await patientLinksService.cancel(token)
    confirmingCancel.value = false
  }
  catch (failure) {
    actionError.value = apiStatus(failure) === 404
      ? 'Este link expirou. Fale com a clínica.'
      : parseApiError(failure).message
  }
  finally {
    isBusy.value = false
  }
}

await load()

const primaryClass = 'inline-flex h-11 w-full items-center justify-center rounded-lg bg-brand text-[14px] font-bold text-white disabled:opacity-60'
const secondaryClass = 'inline-flex h-11 w-full items-center justify-center rounded-lg border border-line text-[14px] font-semibold text-[#b4543f] disabled:opacity-60'
</script>

<template>
  <div>
    <template v-if="isInvalid">
      <AuthHeading title="Link inválido ou expirado" description="O link vale até o horário da consulta e muda se ela for remarcada." />
      <p class="text-[13px] text-muted">
        Para confirmar ou desmarcar, fale direto com a clínica.
      </p>
    </template>

    <template v-else-if="appointment">
      <AuthHeading :title="appointment.clinic.name" description="Sua consulta" />

      <dl class="mb-5 grid gap-3 rounded-xl border border-line p-4 text-[13px]">
        <div>
          <dt class="text-[11px] text-muted">
            Quando
          </dt>
          <dd class="font-semibold">
            {{ appointment.when }}
          </dd>
        </div>
        <div>
          <dt class="text-[11px] text-muted">
            Atendimento
          </dt>
          <dd>{{ appointment.procedure }}<template v-if="appointment.professional"> · {{ appointment.professional }}</template></dd>
        </div>
        <div>
          <dt class="text-[11px] text-muted">
            Situação
          </dt>
          <dd role="status">
            {{ appointment.statusLabel }}
          </dd>
        </div>
        <div v-if="appointment.clinic.address">
          <dt class="text-[11px] text-muted">
            Endereço
          </dt>
          <dd>{{ appointment.clinic.address }}</dd>
        </div>
      </dl>

      <AuthAlert v-if="actionError">
        {{ actionError }}
      </AuthAlert>

      <AuthAlert v-if="appointment.status === 'confirmed'" tone="success">
        Presença confirmada. Até lá!
      </AuthAlert>
      <AuthAlert v-else-if="appointment.status === 'cancelled'" tone="success">
        Consulta cancelada. O horário foi liberado.
      </AuthAlert>

      <div class="grid gap-2">
        <button v-if="appointment.canConfirm" type="button" :class="primaryClass" :disabled="isBusy" @click="answer('confirm')">
          Confirmo presença
        </button>

        <template v-if="appointment.canCancel">
          <button v-if="!confirmingCancel" type="button" :class="secondaryClass" :disabled="isBusy" @click="confirmingCancel = true">
            Preciso cancelar
          </button>
          <button v-else type="button" :class="secondaryClass" :disabled="isBusy" @click="answer('cancel')">
            Sim, cancelar a consulta
          </button>
        </template>
      </div>

      <p v-if="appointment.clinic.phone" class="mt-6 text-center text-[12px] text-muted">
        Dúvidas? Ligue para {{ appointment.clinic.phone }}.
      </p>
    </template>
  </div>
</template>
