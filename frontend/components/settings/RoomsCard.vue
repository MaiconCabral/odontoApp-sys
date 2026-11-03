<script setup lang="ts">
import { Pencil, Plus, Trash2 } from 'lucide-vue-next'
import type { RoomItem } from '~/types/catalog'

const { showToast } = useToast()
const { data: rooms, error, pending, refresh } = useRooms()

const editing = ref<RoomItem | null>(null)
const isModalOpen = ref(false)
const confirmingDelete = ref<string | null>(null)

// Opens the modal to add a room.
function openNew() {
  editing.value = null
  isModalOpen.value = true
}

// Opens the modal to edit a room.
function openEdit(room: RoomItem) {
  editing.value = room
  isModalOpen.value = true
}

// Closes the modal and reloads the lists that offer rooms.
async function onSaved(message: string) {
  isModalOpen.value = false
  showToast(message)
  await refreshNuxtData(['rooms', 'agenda'])
}

// Deletes a room after a second click.
async function remove(room: RoomItem) {
  if (confirmingDelete.value !== room.id) {
    confirmingDelete.value = room.id

    return
  }

  confirmingDelete.value = null

  try {
    await roomsService.remove(room.id)
    showToast(`${room.name} foi excluída.`)
    await refreshNuxtData(['rooms', 'agenda'])
  }
  catch (failure) {
    const { message, fields } = parseApiError(failure)

    showToast(fields.room ?? message)
  }
}

const iconButtonClass = 'grid h-[30px] w-[30px] place-items-center rounded-lg text-muted transition duration-200 hover:bg-line-soft hover:text-brand-text dark:hover:bg-brand-soft'
</script>

<template>
  <SettingsCard
    title="Salas e cadeiras"
    subtitle="Onde as consultas acontecem. Uma sala desativada some dos novos agendamentos."
  >
    <template #action>
      <button
        type="button"
        class="inline-flex flex-none items-center justify-center gap-2 rounded-lg border border-line bg-surface px-[15px] py-2.5 text-[12px] font-bold text-body transition duration-200"
        @click="openNew"
      >
        <Plus :size="15" />
        Nova sala
      </button>
    </template>

    <p v-if="pending && !rooms" role="status" class="pt-4 text-[11px] text-muted">
      Carregando as salas...
    </p>

    <div v-else-if="error || !rooms" role="alert" class="pt-4 text-[11px] text-muted">
      <p>Não foi possível carregar as salas.</p>
      <button type="button" class="mt-2 font-bold text-brand-text" @click="refresh()">
        Tentar novamente
      </button>
    </div>

    <ul v-else class="mt-[5px]">
      <li
        v-for="room in rooms"
        :key="room.id"
        class="flex min-h-[55px] items-center justify-between gap-4 border-b border-line-soft px-[3px] py-2 text-[11px] text-body"
      >
        <strong class="min-w-0 truncate">{{ room.name }}</strong>

        <div class="flex flex-none items-center gap-1 sm:gap-3">
          <span v-if="!room.active" class="rounded-md bg-line-soft px-2 py-1 text-[10px] font-semibold text-muted">
            Inativa
          </span>
          <button type="button" :class="iconButtonClass" :aria-label="`Editar ${room.name}`" @click="openEdit(room)">
            <Pencil :size="14" />
          </button>
          <button
            type="button"
            :class="iconButtonClass"
            :aria-label="confirmingDelete === room.id ? `Confirmar exclusão de ${room.name}` : `Excluir ${room.name}`"
            @click="remove(room)"
          >
            <span v-if="confirmingDelete === room.id" class="text-[10px] font-bold text-tone-red-text">Sim?</span>
            <Trash2 v-else :size="14" />
          </button>
        </div>
      </li>
    </ul>

    <SettingsRoomModal
      v-if="isModalOpen"
      :room="editing"
      @close="isModalOpen = false"
      @saved="onSaved"
    />
  </SettingsCard>
</template>
