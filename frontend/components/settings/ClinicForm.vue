<script setup lang="ts">
import type { ClinicData } from '~/types/settings'

const props = defineProps<{
  clinic: ClinicData
}>()

const fields: { key: keyof ClinicData, label: string, type: string, full?: boolean }[] = [
  { key: 'name', label: 'Nome da clínica', type: 'text' },
  { key: 'unit', label: 'Unidade', type: 'text' },
  { key: 'cnpj', label: 'CNPJ', type: 'text' },
  { key: 'phone', label: 'Telefone principal', type: 'tel' },
  { key: 'email', label: 'E-mail de contato', type: 'email' },
  { key: 'address', label: 'Endereço', type: 'text', full: true },
]

const formId = useId()
const tenant = useTenantStore()
const { showToast } = useToast()

const form = reactive<ClinicData>({ ...props.clinic })

const { errors, formError, isSubmitting, submit } = useAuthForm(form, validateClinic, async () => {
  const saved = await clinicService.update(form)

  // The sidebar shows the clinic from the session store.
  if (tenant.tenant) {
    tenant.setTenant(
      { ...tenant.tenant, name: saved.name, unit: saved.unit, initials: patientInitials(saved.name) },
      tenant.plan,
    )
  }

  showToast('Dados da clínica atualizados.')
})
</script>

<template>
  <SettingsCard
    title="Dados da clínica"
    subtitle="Essas informações aparecem nas confirmações dos pacientes."
  >
    <template #action>
      <button
        type="submit"
        :form="formId"
        :disabled="isSubmitting"
        class="inline-flex flex-none items-center justify-center gap-2 rounded-lg border border-line bg-surface px-[15px] py-2.5 text-[12px] font-bold text-body transition duration-200 disabled:opacity-60"
      >
        {{ isSubmitting ? 'Salvando...' : 'Salvar alterações' }}
      </button>
    </template>

    <p v-if="formError" role="alert" class="pt-4 text-[11px] text-tone-red-text">
      {{ formError }}
    </p>

    <form :id="formId" novalidate class="grid grid-cols-1 gap-x-3.5 gap-y-[17px] pt-5 sm:grid-cols-2" @submit.prevent="submit">
      <div
        v-for="field in fields"
        :key="field.key"
        class="flex flex-col gap-[7px]"
        :class="{ 'sm:col-span-2': field.full }"
      >
        <label :for="`clinic-${field.key}`" class="text-[10px] font-semibold text-muted dark:text-muted">
          {{ field.label }}
        </label>
        <input
          :id="`clinic-${field.key}`"
          v-model="form[field.key]"
          :type="field.type"
          :aria-invalid="Boolean(errors[field.key])"
          class="h-[37px] w-full rounded-[7px] border border-line bg-surface px-[11px] text-[11px] text-body outline-none transition duration-200 focus:border-brand-accent aria-[invalid=true]:!border-tone-red-line"
        >
        <p v-if="errors[field.key]" class="text-[10px] text-tone-red-text">
          {{ errors[field.key] }}
        </p>
      </div>
    </form>
  </SettingsCard>
</template>
