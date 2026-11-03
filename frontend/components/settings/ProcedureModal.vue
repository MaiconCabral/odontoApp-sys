<script setup lang="ts">
import { Stethoscope } from 'lucide-vue-next'
import type { ProcedureForm, ProcedureItem } from '~/types/catalog'

const props = defineProps<{
  procedure: ProcedureItem | null
}>()

const emit = defineEmits<{
  close: []
  saved: [message: string]
}>()

const DURATIONS = [30, 60, 90, 120, 150, 180, 210, 240]

const form = reactive<ProcedureForm>({
  name: props.procedure?.name ?? '',
  durationMinutes: props.procedure?.durationMinutes ?? 60,
  price: props.procedure ? centsToInput(props.procedure.priceCents) : '',
  active: props.procedure?.active ?? true,
})

// Name and a valid price.
function validate(fields: ProcedureForm) {
  const errors: Partial<Record<keyof ProcedureForm, string>> = {}

  if (fields.name.trim().length < 3) {
    errors.name = 'Informe o nome do procedimento.'
  }

  if (toCents(fields.price) === null) {
    errors.price = 'Informe um preço válido, como 150 ou 150,00.'
  }

  return errors
}

const { errors, formError, isSubmitting, submit } = useAuthForm(form, validate, async () => {
  const saved = props.procedure
    ? await proceduresService.update(props.procedure.id, form)
    : await proceduresService.create(form)

  emit('saved', props.procedure ? `${saved.name} foi atualizado.` : `${saved.name} foi adicionado.`)
})

// The API names the fields duration_minutes and price_cents.
const durationError = computed(() => (errors.value as Record<string, string | undefined>).durationMinutes)
const priceError = computed(() => errors.value.price ?? (errors.value as Record<string, string | undefined>).priceCents)
</script>

<template>
  <AppModal
    eyebrow="Procedimentos"
    :title="procedure ? 'Editar procedimento' : 'Novo procedimento'"
    description="A duração define o espaço na agenda. O preço entra no faturamento estimado."
    :submit-label="procedure ? 'Salvar alterações' : 'Adicionar procedimento'"
    :submitting="isSubmitting"
    @close="emit('close')"
    @submit="submit()"
  >
    <template #submit-icon>
      <Stethoscope :size="17" />
    </template>

    <p v-if="formError" role="alert" class="text-[11px] text-[#c2553f] sm:col-span-2">
      {{ formError }}
    </p>

    <AppFormField v-slot="field" label="Nome" :error="errors.name" full>
      <input :id="field.id" v-model="form.name" autocomplete="off" :class="field.controlClass" :aria-invalid="field.invalid" :aria-describedby="field.describedBy">
    </AppFormField>

    <AppFormField v-slot="field" label="Duração" :error="durationError">
      <select :id="field.id" v-model.number="form.durationMinutes" :class="field.controlClass">
        <option v-for="minutes in DURATIONS" :key="minutes" :value="minutes">
          {{ minutes }} min
        </option>
      </select>
    </AppFormField>

    <AppFormField v-slot="field" label="Preço (R$)" :error="priceError">
      <input
        :id="field.id"
        v-model="form.price"
        inputmode="decimal"
        autocomplete="off"
        placeholder="0,00"
        :class="[field.controlClass, 'placeholder:text-faint']"
        :aria-invalid="Boolean(priceError)"
        :aria-describedby="field.describedBy"
      >
    </AppFormField>

    <label v-if="procedure" class="flex items-center gap-2 text-[11px] text-body sm:col-span-2">
      <input v-model="form.active" type="checkbox" class="h-[13px] w-[13px] accent-brand">
      Oferecido para novos agendamentos
    </label>
  </AppModal>
</template>
