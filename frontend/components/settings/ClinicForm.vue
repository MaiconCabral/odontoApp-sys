<script setup lang="ts">
import type { ClinicData } from '~/types/settings'

const props = defineProps<{
  clinic: ClinicData
}>()

const fields: { key: keyof ClinicData, label: string, type: string, full?: boolean }[] = [
  { key: 'name', label: 'Nome da clínica', type: 'text' },
  { key: 'cnpj', label: 'CNPJ', type: 'text' },
  { key: 'phone', label: 'Telefone principal', type: 'tel' },
  { key: 'email', label: 'E-mail de contato', type: 'email' },
  { key: 'address', label: 'Endereço', type: 'text', full: true },
]

const form = reactive<ClinicData>({ ...props.clinic })
</script>

<template>
  <SettingsCard
    title="Dados da clínica"
    subtitle="Essas informações aparecem nas confirmações dos pacientes."
  >
    <template #action>
      <button
        type="button"
        class="inline-flex flex-none items-center justify-center gap-2 rounded-lg border border-[#e3e9f0] bg-surface px-[15px] py-2.5 text-[12px] font-bold text-[#647286] transition duration-200 dark:border-line dark:text-[#acb8c8]"
      >
        Salvar alterações
      </button>
    </template>

    <div class="grid grid-cols-1 gap-x-3.5 gap-y-[17px] pt-5 sm:grid-cols-2">
      <div
        v-for="field in fields"
        :key="field.key"
        class="flex flex-col gap-[7px]"
        :class="{ 'sm:col-span-2': field.full }"
      >
        <label :for="`clinic-${field.key}`" class="text-[10px] font-semibold text-[#748196] dark:text-muted">
          {{ field.label }}
        </label>
        <input
          :id="`clinic-${field.key}`"
          v-model="form[field.key]"
          :type="field.type"
          class="h-[37px] w-full rounded-[7px] border border-[#e1e8ee] bg-surface px-[11px] text-[11px] text-[#506075] outline-none transition duration-200 focus:border-[#73cddd] dark:border-line dark:text-ink"
        >
      </div>
    </div>
  </SettingsCard>
</template>
