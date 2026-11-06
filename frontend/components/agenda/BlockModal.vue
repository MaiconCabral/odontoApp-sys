<script setup lang="ts">
import { Ban } from 'lucide-vue-next'
import type { AgendaBlock } from '~/types/agenda'

type BlockFormFields = {
  professionalId: string
  startDate: string
  endDate: string
  allDay: boolean
  startTime: string
  endTime: string
  reason: string
}

const { closeModal, modalPayload } = useActiveModal()
const { showToast } = useToast()
const { data: agenda } = await useAgendaOptions()

const existing = (modalPayload.value?.block ?? null) as AgendaBlock | null
const today = agenda.value?.today ?? ''

const form = reactive<BlockFormFields>({
  professionalId: '',
  startDate: today,
  endDate: today,
  allDay: true,
  startTime: '08:00',
  endTime: '12:00',
  reason: '',
})

// Required fields and the order of the dates.
function validate(fields: BlockFormFields) {
  const errors: Partial<Record<keyof BlockFormFields, string>> = {}

  if (!fields.startDate) {
    errors.startDate = 'Informe o início.'
  }

  if (!fields.endDate || fields.endDate < fields.startDate) {
    errors.endDate = 'O fim precisa ser depois do início.'
  }

  if (!fields.allDay && fields.startDate === fields.endDate && fields.endTime <= fields.startTime) {
    errors.endTime = 'O fim precisa ser depois do início.'
  }

  if (fields.reason.trim().length < 3) {
    errors.reason = 'Informe o motivo, como "Feriado" ou "Folga".'
  }

  return errors
}

const { errors, formError, isSubmitting, submit } = useAuthForm(form, validate, async () => {
  await scheduleService.createBlock({
    professionalId: form.professionalId,
    startsAt: `${form.startDate} ${form.allDay ? '00:00' : form.startTime}`,
    endsAt: `${form.endDate} ${form.allDay ? '23:59' : form.endTime}`,
    reason: form.reason.trim(),
  })

  showToast('Bloqueio registrado. Consultas já marcadas no período continuam na agenda.')
  await refreshAgendaData()
  closeModal()
})

const removeError = ref('')

// Deletes the block shown in the modal.
async function remove() {
  if (!existing) {
    return
  }

  try {
    await scheduleService.removeBlock(existing.id)
    showToast('Bloqueio removido.')
    await refreshAgendaData()
    closeModal()
  }
  catch (failure) {
    removeError.value = parseApiError(failure).message
  }
}

const professionalName = computed(() =>
  agenda.value?.professionals.find(professional => professional.id === existing?.professionalId)?.name ?? 'Clínica inteira',
)
</script>

<template>
  <AppModal
    v-if="existing"
    eyebrow="Agenda clínica"
    :title="existing.reason"
    :description="`${professionalName} · de ${existing.startsAt} até ${existing.endsAt}`"
    @close="closeModal"
  >
    <p class="text-[11px] text-muted sm:col-span-2">
      Enquanto o bloqueio existir, a agenda não aceita novas consultas neste período.
    </p>
    <button type="button" class="self-start rounded-lg border border-line px-3 py-2 text-[11px] font-bold text-tone-red-text" @click="remove">
      Excluir bloqueio
    </button>
    <p v-if="removeError" role="alert" class="text-[11px] text-tone-red-text sm:col-span-2">
      {{ removeError }}
    </p>
  </AppModal>

  <AppModal
    v-else
    eyebrow="Agenda clínica"
    title="Bloquear horário"
    description="Feriado, folga ou qualquer período sem atendimento."
    submit-label="Salvar bloqueio"
    :submitting="isSubmitting"
    @close="closeModal"
    @submit="submit()"
  >
    <template #submit-icon>
      <Ban :size="17" />
    </template>

    <p v-if="formError" role="alert" class="text-[11px] text-tone-red-text sm:col-span-2">
      {{ formError }}
    </p>

    <AppFormField v-slot="field" label="Vale para" full>
      <select :id="field.id" v-model="form.professionalId" :class="field.controlClass">
        <option value="">
          Clínica inteira (feriado, recesso)
        </option>
        <option v-for="professional in agenda?.professionals ?? []" :key="professional.id" :value="professional.id">
          Folga de {{ professional.name }}
        </option>
      </select>
    </AppFormField>

    <AppFormField v-slot="field" label="De" :error="errors.startDate">
      <input :id="field.id" v-model="form.startDate" type="date" :class="field.controlClass" :aria-invalid="field.invalid">
    </AppFormField>

    <AppFormField v-slot="field" label="Até" :error="errors.endDate">
      <input :id="field.id" v-model="form.endDate" type="date" :min="form.startDate" :class="field.controlClass" :aria-invalid="field.invalid">
    </AppFormField>

    <label class="flex items-center gap-2 text-[11px] text-body sm:col-span-2">
      <input v-model="form.allDay" type="checkbox" class="h-[13px] w-[13px] accent-brand">
      Dia inteiro
    </label>

    <template v-if="!form.allDay">
      <AppFormField v-slot="field" label="Das">
        <input :id="field.id" v-model="form.startTime" type="time" step="1800" :class="field.controlClass">
      </AppFormField>
      <AppFormField v-slot="field" label="Às" :error="errors.endTime">
        <input :id="field.id" v-model="form.endTime" type="time" step="1800" :class="field.controlClass" :aria-invalid="field.invalid">
      </AppFormField>
    </template>

    <AppFormField v-slot="field" label="Motivo" :error="errors.reason" full>
      <input :id="field.id" v-model="form.reason" maxlength="120" placeholder="Ex.: Feriado municipal" :class="[field.controlClass, 'placeholder:text-faint']" :aria-invalid="field.invalid">
    </AppFormField>
  </AppModal>
</template>
