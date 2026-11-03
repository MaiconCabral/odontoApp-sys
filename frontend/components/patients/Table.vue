<script setup lang="ts">
import { ChevronLeft, ChevronRight, Pencil, Search, Trash2 } from 'lucide-vue-next'
import type { Patient, PatientList } from '~/types/patients'

const props = defineProps<{
  list: PatientList
}>()

const query = usePatientsQuery()
const { openModal } = useActiveModal()
const { showToast } = useToast()

const search = ref(query.value.search)
const confirmingDelete = ref<string | null>(null)
let searchTimer: ReturnType<typeof setTimeout> | undefined

// Sends the search to the API after a short pause in typing.
watch(search, (value) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    query.value = { search: value.trim(), page: 1 }
  }, 300)
})

// Moves to another page of results.
function goToPage(page: number) {
  if (page >= 1 && page <= props.list.lastPage) {
    query.value = { ...query.value, page }
  }
}

// Deletes a patient after a second click.
async function remove(patient: Patient) {
  if (confirmingDelete.value !== patient.id) {
    confirmingDelete.value = patient.id

    return
  }

  confirmingDelete.value = null

  try {
    await patientsService.remove(patient.id)
    showToast(`Cadastro de ${patient.name} excluído.`)
    await refreshNuxtData('patients')
  }
  catch (failure) {
    const { message, fields } = parseApiError(failure)

    showToast(fields.patient ?? message)
  }
}

const rowClass = 'grid grid-cols-[1fr_70px] items-center gap-2 border-b border-line-soft px-[13px] sm:grid-cols-[1.5fr_1fr_70px] sm:gap-3.5 sm:px-5 md:grid-cols-[1.5fr_1.05fr_1.05fr_1fr_70px]'
const pageButtonClass = 'grid h-[34px] w-[34px] place-items-center rounded-lg text-muted transition duration-200 hover:bg-line-soft hover:text-brand-text disabled:opacity-40 dark:hover:bg-brand-soft'
const iconButtonClass = 'grid h-[30px] w-[30px] place-items-center rounded-[7px] text-faint transition duration-200 hover:bg-line-soft hover:text-brand-text dark:hover:bg-brand-soft'
const mutedCellClass = 'text-[10px] text-muted'
</script>

<template>
  <section class="overflow-hidden rounded-xl border border-line bg-surface shadow-[0_4px_14px_rgba(42,64,91,.025)]">
    <div class="flex flex-col items-stretch gap-2.5 border-b border-line-soft px-5 py-[17px] sm:flex-row sm:items-center sm:justify-between">
      <label class="flex w-full items-center gap-2 rounded-[7px] border border-line px-2.5 py-2 text-faint transition duration-200 focus-within:border-brand-accent dark:bg-surface-soft sm:w-[290px]">
        <Search :size="17" class="flex-none" />
        <input
          v-model="search"
          class="w-full border-0 bg-transparent text-[11px] text-body outline-none placeholder:text-faint"
          placeholder="Buscar por nome, CPF ou telefone"
          aria-label="Buscar por nome, CPF ou telefone"
        >
      </label>
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
        v-for="patient in list.patients"
        :key="patient.id"
        role="row"
        :class="rowClass"
        class="min-h-[65px]"
      >
        <div role="cell" class="flex min-w-0 items-center gap-2.5 text-[11px]">
          <PatientsAvatar :initials="patient.initials" :tone="patient.tone" />
          <div class="min-w-0">
            <strong class="block truncate">{{ patient.name }}</strong>
            <span v-if="patient.cpf" class="block text-[9px] text-muted">{{ patient.cpf }}</span>
          </div>
        </div>
        <span role="cell" class="hidden sm:block" :class="mutedCellClass">{{ patient.phone }}</span>
        <span role="cell" class="hidden md:block" :class="mutedCellClass">{{ patient.lastAppointment }}</span>
        <span role="cell" class="hidden md:block">
          <span
            class="inline-block rounded-[5px] px-[7px] py-[5px] text-[9px]"
            :class="patient.isPrivate ? 'bg-tone-amber-bg text-tone-amber-text' : 'bg-tone-green-bg text-tone-green-text'"
          >
            {{ patient.insurance ?? 'Particular' }}
          </span>
        </span>
        <div role="cell" class="flex items-center justify-end gap-1">
          <button type="button" :class="iconButtonClass" :aria-label="`Editar ${patient.name}`" @click="openModal('patient', { patient })">
            <Pencil :size="14" />
          </button>
          <button
            type="button"
            :class="iconButtonClass"
            :aria-label="confirmingDelete === patient.id ? `Confirmar exclusão de ${patient.name}` : `Excluir ${patient.name}`"
            @click="remove(patient)"
          >
            <span v-if="confirmingDelete === patient.id" class="text-[10px] font-bold text-tone-red-text">Sim?</span>
            <Trash2 v-else :size="14" />
          </button>
        </div>
      </div>

      <p v-if="list.patients.length === 0" class="p-[35px] text-center text-[12px] text-muted">
        Nenhum paciente encontrado.
      </p>
    </div>

    <div class="flex items-center justify-between px-5 py-3.5 text-[10px] text-faint">
      <span aria-live="polite">
        Mostrando <strong class="text-body">{{ list.patients.length }}</strong> de {{ list.total }} pacientes
      </span>
      <div class="flex items-center gap-[5px]">
        <button type="button" :class="pageButtonClass" aria-label="Página anterior" :disabled="list.page <= 1" @click="goToPage(list.page - 1)">
          <ChevronLeft :size="15" />
        </button>
        <span class="grid h-[25px] min-w-[25px] place-items-center rounded-md bg-brand-soft px-1 font-bold text-brand-text" aria-current="page">{{ list.page }}</span>
        <button type="button" :class="pageButtonClass" aria-label="Próxima página" :disabled="list.page >= list.lastPage" @click="goToPage(list.page + 1)">
          <ChevronRight :size="15" />
        </button>
      </div>
    </div>
  </section>
</template>
