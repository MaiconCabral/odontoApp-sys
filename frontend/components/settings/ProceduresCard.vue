<script setup lang="ts">
import { Pencil, Plus, Trash2 } from 'lucide-vue-next'
import type { ProcedureItem } from '~/types/catalog'

const { showToast } = useToast()
const { data: procedures, error, pending, refresh } = useProcedures()

const editing = ref<ProcedureItem | null>(null)
const isModalOpen = ref(false)
const confirmingDelete = ref<string | null>(null)

// Opens the modal to add a procedure.
function openNew() {
  editing.value = null
  isModalOpen.value = true
}

// Opens the modal to edit a procedure.
function openEdit(procedure: ProcedureItem) {
  editing.value = procedure
  isModalOpen.value = true
}

// Closes the modal and reloads the lists that offer procedures.
async function onSaved(message: string) {
  isModalOpen.value = false
  showToast(message)
  await refreshNuxtData('procedures')
  forgetAgendaOptions()
}

// Deletes a procedure after a second click.
async function remove(procedure: ProcedureItem) {
  if (confirmingDelete.value !== procedure.id) {
    confirmingDelete.value = procedure.id

    return
  }

  confirmingDelete.value = null

  try {
    await proceduresService.remove(procedure.id)
    showToast(`${procedure.name} foi excluído.`)
    await refreshNuxtData('procedures')
    forgetAgendaOptions()
  }
  catch (failure) {
    const { message, fields } = parseApiError(failure)

    showToast(fields.procedure ?? message)
  }
}

const iconButtonClass = 'grid h-[30px] w-[30px] place-items-center rounded-lg text-muted transition duration-200 hover:bg-line-soft hover:text-brand-text dark:hover:bg-brand-soft'
</script>

<template>
  <SettingsCard
    title="Procedimentos"
    subtitle="Duração e preço de cada atendimento. Consultas já marcadas mantêm o preço da época."
  >
    <template #action>
      <button
        type="button"
        class="inline-flex flex-none items-center justify-center gap-2 rounded-lg border border-line bg-surface px-[15px] py-2.5 text-[12px] font-bold text-body transition duration-200"
        @click="openNew"
      >
        <Plus :size="15" />
        Novo procedimento
      </button>
    </template>

    <p v-if="pending && !procedures" role="status" class="pt-4 text-[11px] text-muted">
      Carregando os procedimentos...
    </p>

    <div v-else-if="error || !procedures" role="alert" class="pt-4 text-[11px] text-muted">
      <p>Não foi possível carregar os procedimentos.</p>
      <button type="button" class="mt-2 font-bold text-brand-text" @click="refresh()">
        Tentar novamente
      </button>
    </div>

    <ul v-else class="mt-[5px]">
      <li
        v-for="procedure in procedures"
        :key="procedure.id"
        class="flex min-h-[55px] items-center justify-between gap-4 border-b border-line-soft px-[3px] py-2 text-[11px] text-body"
      >
        <div class="min-w-0">
          <strong class="block truncate">{{ procedure.name }}</strong>
          <span class="block text-[10px] text-muted">{{ procedure.durationMinutes }} min · {{ formatMoney(procedure.priceCents) }}</span>
        </div>

        <div class="flex flex-none items-center gap-1 sm:gap-3">
          <span v-if="!procedure.active" class="rounded-md bg-line-soft px-2 py-1 text-[10px] font-semibold text-muted">
            Inativo
          </span>
          <button type="button" :class="iconButtonClass" :aria-label="`Editar ${procedure.name}`" @click="openEdit(procedure)">
            <Pencil :size="14" />
          </button>
          <button
            type="button"
            :class="iconButtonClass"
            :aria-label="confirmingDelete === procedure.id ? `Confirmar exclusão de ${procedure.name}` : `Excluir ${procedure.name}`"
            @click="remove(procedure)"
          >
            <span v-if="confirmingDelete === procedure.id" class="text-[10px] font-bold text-tone-red-text">Sim?</span>
            <Trash2 v-else :size="14" />
          </button>
        </div>
      </li>
    </ul>

    <SettingsProcedureModal
      v-if="isModalOpen"
      :procedure="editing"
      @close="isModalOpen = false"
      @saved="onSaved"
    />
  </SettingsCard>
</template>
