<script setup lang="ts">
import { ChevronLeft, ChevronRight, Ellipsis, Filter, Search } from 'lucide-vue-next'
import type { PatientList } from '~/types/patients'

const props = defineProps<{
  list: PatientList
}>()

const query = ref('')
const sessionPatients = useSessionPatients()

const total = computed(() => props.list.total + sessionPatients.value.length)

const visiblePatients = computed(() =>
  [...sessionPatients.value, ...props.list.patients].filter(patient => matchesPatientSearch(patient, query.value)),
)

const rowClass = 'grid grid-cols-[1fr_30px] items-center gap-2 border-b border-line-soft px-[13px] sm:grid-cols-[1.5fr_1fr_40px] sm:gap-3.5 sm:px-5 md:grid-cols-[1.5fr_1.05fr_1.05fr_1fr_40px]'
const pageButtonClass = 'grid h-[34px] w-[34px] place-items-center rounded-lg text-muted transition duration-200 hover:bg-line-soft hover:text-brand-text dark:hover:bg-brand-soft'
const mutedCellClass = 'text-[10px] text-muted'
</script>

<template>
  <section class="overflow-hidden rounded-xl border border-line bg-surface shadow-[0_4px_14px_rgba(42,64,91,.025)]">
    <div class="flex flex-col items-stretch gap-2.5 border-b border-line-soft px-5 py-[17px] sm:flex-row sm:items-center sm:justify-between">
      <label class="flex w-full items-center gap-2 rounded-[7px] border border-line px-2.5 py-2 text-faint transition duration-200 focus-within:border-brand-accent dark:bg-surface-soft sm:w-[290px]">
        <Search :size="17" class="flex-none" />
        <input
          v-model="query"
          class="w-full border-0 bg-transparent text-[11px] text-body outline-none placeholder:text-faint"
          placeholder="Buscar por nome, CPF ou telefone"
          aria-label="Buscar por nome, CPF ou telefone"
        >
      </label>
      <button
        type="button"
        class="inline-flex items-center gap-[7px] rounded-[7px] border border-line bg-surface px-[9px] py-[7px] text-[10px] text-body"
      >
        <Filter :size="15" /> Filtros
        <span class="ml-0.5 grid h-[15px] min-w-[15px] place-items-center rounded bg-brand-soft text-[9px] text-brand-text">
          {{ list.activeFilters }}
        </span>
      </button>
    </div>

    <div role="table" aria-label="Pacientes">
      <div role="row" :class="rowClass" class="min-h-[35px] bg-surface-soft text-[9px] font-bold uppercase tracking-[.04em] text-muted sm:min-h-[42px]">
        <span role="columnheader" class="hidden sm:block">Paciente</span>
        <span role="columnheader" class="hidden sm:block">Telefone</span>
        <span role="columnheader" class="hidden md:block">Última consulta</span>
        <span role="columnheader" class="hidden md:block">Convênio</span>
        <span />
      </div>

      <div
        v-for="patient in visiblePatients"
        :key="patient.id"
        role="row"
        :class="rowClass"
        class="min-h-[65px]"
      >
        <div role="cell" class="flex min-w-0 items-center gap-2.5 text-[11px]">
          <PatientsAvatar :initials="patient.initials" :tone="patient.tone" />
          <strong class="truncate">{{ patient.name }}</strong>
        </div>
        <span role="cell" class="hidden sm:block" :class="mutedCellClass">{{ patient.phone }}</span>
        <span role="cell" class="hidden md:block" :class="mutedCellClass">{{ patient.lastAppointment }}</span>
        <span role="cell" class="hidden md:block">
          <span
            class="inline-block rounded-[5px] px-[7px] py-[5px] text-[9px]"
            :class="patient.isPrivate ? 'bg-[#fff4df] text-[#aa7830]' : 'bg-[#e7f7ef] text-[#378b70]'"
          >
            {{ patient.insurance }}
          </span>
        </span>
        <button
          type="button"
          class="grid h-[30px] w-[30px] place-items-center rounded-[7px] text-faint transition duration-200 hover:bg-line-soft hover:text-brand-text dark:hover:bg-brand-soft"
          :aria-label="`Abrir ${patient.name}`"
        >
          <Ellipsis :size="18" />
        </button>
      </div>

      <p v-if="visiblePatients.length === 0" class="p-[35px] text-center text-[12px] text-muted">
        Nenhum paciente encontrado.
      </p>
    </div>

    <div class="flex items-center justify-between px-5 py-3.5 text-[10px] text-faint">
      <span aria-live="polite">
        Mostrando <strong class="text-body">{{ visiblePatients.length }}</strong> de {{ total }} pacientes
      </span>
      <div class="flex items-center gap-[5px]">
        <button type="button" :class="pageButtonClass" aria-label="Página anterior">
          <ChevronLeft :size="15" />
        </button>
        <span class="grid h-[25px] w-[25px] place-items-center rounded-md bg-brand-soft font-bold text-brand-text" aria-current="page">1</span>
        <button type="button" :class="pageButtonClass" aria-label="Próxima página">
          <ChevronRight :size="15" />
        </button>
      </div>
    </div>
  </section>
</template>
