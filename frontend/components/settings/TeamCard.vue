<script setup lang="ts">
import { Pencil, Plus, Trash2 } from 'lucide-vue-next'
import type { TeamUser } from '~/types/users'

const auth = useAuthStore()
const { showToast } = useToast()
const { data: users, error, pending, refresh } = useTeam()

const editing = ref<TeamUser | null>(null)
const isModalOpen = ref(false)
const confirmingDelete = ref<string | null>(null)
const workspace = useWorkspace()

// Active people who attend patients, the ones the plan counts.
const professionalsUsed = computed(() => users.value?.filter(user => user.active && user.attendsPatients).length ?? 0)

// The API enforces the limit; this only warns. Reception does not count, so adding stays open.
const isPlanFull = computed(() => {
  const max = workspace.value.plan.maxProfessionals

  return max !== null && professionalsUsed.value >= max
})

// Role and specialty in one line.
function roleLine(user: TeamUser) {
  return user.specialty ? `${user.roleLabel} · ${user.specialty}` : user.roleLabel
}

// Opens the modal to add a user.
function openNew() {
  editing.value = null
  isModalOpen.value = true
}

// Opens the modal to edit a user.
function openEdit(user: TeamUser) {
  editing.value = user
  isModalOpen.value = true
}

// Closes the modal and reloads the team.
async function onSaved(message: string) {
  isModalOpen.value = false
  showToast(message)
  await refresh()
}

// Deletes a user after a second click.
async function remove(user: TeamUser) {
  if (confirmingDelete.value !== user.id) {
    confirmingDelete.value = user.id

    return
  }

  confirmingDelete.value = null

  try {
    await usersService.remove(user.id)
    showToast(`${user.name} foi removido da equipe.`)
    await refresh()
  }
  catch (failure) {
    const { message, fields } = parseApiError(failure)

    showToast(fields.user ?? message)
  }
}

const iconButtonClass = 'grid h-[30px] w-[30px] place-items-center rounded-lg text-muted transition duration-200 hover:bg-line-soft hover:text-brand-text dark:hover:bg-brand-soft'
</script>

<template>
  <SettingsCard
    title="Equipe"
    subtitle="Quem acessa o painel e quem atende na agenda da clínica."
  >
    <template #action>
      <div class="flex flex-none flex-col items-end gap-1">
        <button
          type="button"
          class="inline-flex flex-none items-center justify-center gap-2 rounded-lg border border-line bg-surface px-[15px] py-2.5 text-[12px] font-bold text-body transition duration-200"
          :aria-describedby="isPlanFull ? 'team-plan-limit' : undefined"
          @click="openNew"
        >
          <Plus :size="15" />
          Novo usuário
        </button>
        <span v-if="users && workspace.plan.maxProfessionals !== null" id="team-plan-limit" class="text-[10px] text-muted">
          {{ professionalsUsed }} de {{ workspace.plan.maxProfessionals }} profissionais
          <template v-if="isPlanFull">· limite atingido, só é possível adicionar recepção</template>
        </span>
      </div>
    </template>

    <p v-if="pending && !users" role="status" class="pt-4 text-[11px] text-muted">
      Carregando a equipe...
    </p>

    <div v-else-if="error || !users" role="alert" class="pt-4 text-[11px] text-muted">
      <p>Não foi possível carregar a equipe.</p>
      <button type="button" class="mt-2 font-bold text-brand-text" @click="refresh()">
        Tentar novamente
      </button>
    </div>

    <ul v-else class="mt-[5px]">
      <li
        v-for="user in users"
        :key="user.id"
        class="flex min-h-[55px] items-center justify-between gap-4 border-b border-line-soft px-[3px] py-2 text-[11px] text-body"
      >
        <div class="min-w-0">
          <strong class="block truncate">{{ user.name }}</strong>
          <span class="block truncate text-[10px] text-muted">{{ user.email }}</span>
          <span class="block truncate text-[10px] text-muted sm:hidden">{{ roleLine(user) }}</span>
        </div>

        <div class="flex flex-none items-center gap-1 sm:gap-3">
          <span v-if="!user.active" class="rounded-md bg-line-soft px-2 py-1 text-[10px] font-semibold text-muted">
            Inativo
          </span>
          <span class="hidden text-right text-[10px] text-muted sm:inline">{{ roleLine(user) }}</span>
          <button type="button" :class="iconButtonClass" :aria-label="`Editar ${user.name}`" @click="openEdit(user)">
            <Pencil :size="14" />
          </button>
          <button
            v-if="user.id !== auth.user?.id"
            type="button"
            :class="iconButtonClass"
            :aria-label="confirmingDelete === user.id ? `Confirmar exclusão de ${user.name}` : `Excluir ${user.name}`"
            @click="remove(user)"
          >
            <span v-if="confirmingDelete === user.id" class="text-[10px] font-bold text-tone-red-text">Sim?</span>
            <Trash2 v-else :size="14" />
          </button>
        </div>
      </li>
    </ul>

    <SettingsUserModal
      v-if="isModalOpen"
      :user="editing"
      @close="isModalOpen = false"
      @saved="onSaved"
    />
  </SettingsCard>
</template>
