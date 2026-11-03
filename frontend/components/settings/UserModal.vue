<script setup lang="ts">
import { UserPlus } from 'lucide-vue-next'
import type { UserRole } from '~/types/auth'
import type { TeamUser, TeamUserForm } from '~/types/users'

const props = defineProps<{
  user: TeamUser | null
}>()

const emit = defineEmits<{
  close: []
  saved: [message: string]
}>()

const roles: { value: UserRole, label: string }[] = [
  { value: 'admin', label: 'Administrador' },
  { value: 'professional', label: 'Profissional' },
  { value: 'reception', label: 'Recepção' },
]

const auth = useAuthStore()
const isSelf = computed(() => props.user?.id === auth.user?.id)

const form = reactive<TeamUserForm>({
  name: props.user?.name ?? '',
  email: props.user?.email ?? '',
  role: props.user?.role ?? 'professional',
  specialty: props.user?.specialty ?? '',
  attendsPatients: props.user?.attendsPatients ?? false,
  active: props.user?.active ?? true,
})

const { errors, formError, submit } = useAuthForm(form, validateTeamUser, async () => {
  if (props.user) {
    await usersService.update(props.user.id, form)
    emit('saved', `${form.name} foi atualizado.`)
  }
  else {
    await usersService.create(form)
    emit('saved', `${form.name} foi adicionado. Enviamos o link para definir a senha.`)
  }
})

// Rules that protect the clinic's administrators come from the API under this field.
const guardError = computed(() => (errors.value as Record<string, string | undefined>).user)
const checkboxRowClass = 'flex items-center gap-2 text-[11px] text-body sm:col-span-2'
</script>

<template>
  <AppModal
    eyebrow="Equipe"
    :title="user ? 'Editar usuário' : 'Novo usuário'"
    :description="user ? 'Atualize os dados e o nível de acesso.' : 'A pessoa recebe um e-mail para definir a própria senha.'"
    :submit-label="user ? 'Salvar alterações' : 'Adicionar usuário'"
    @close="emit('close')"
    @submit="submit()"
  >
    <template #submit-icon>
      <UserPlus :size="17" />
    </template>

    <p v-if="formError || guardError" role="alert" class="text-[11px] text-tone-red-text sm:col-span-2">
      {{ formError || guardError }}
    </p>

    <AppFormField v-slot="field" label="Nome completo" :error="errors.name" full>
      <input
        :id="field.id"
        v-model="form.name"
        autocomplete="off"
        :class="field.controlClass"
        :aria-invalid="field.invalid"
        :aria-describedby="field.describedBy"
      >
    </AppFormField>

    <AppFormField v-slot="field" label="E-mail" :error="errors.email" full>
      <input
        :id="field.id"
        v-model="form.email"
        type="email"
        autocomplete="off"
        :class="field.controlClass"
        :aria-invalid="field.invalid"
        :aria-describedby="field.describedBy"
      >
    </AppFormField>

    <AppFormField v-slot="field" label="Perfil de acesso" :error="errors.role">
      <select
        :id="field.id"
        v-model="form.role"
        :disabled="isSelf"
        :class="field.controlClass"
        :aria-invalid="field.invalid"
        :aria-describedby="field.describedBy"
      >
        <option v-for="role in roles" :key="role.value" :value="role.value">
          {{ role.label }}
        </option>
      </select>
    </AppFormField>

    <AppFormField v-slot="field" label="Especialidade (opcional)" :error="errors.specialty">
      <input
        :id="field.id"
        v-model="form.specialty"
        autocomplete="off"
        :class="field.controlClass"
        :aria-invalid="field.invalid"
        :aria-describedby="field.describedBy"
      >
    </AppFormField>

    <label v-if="form.role === 'admin'" :class="checkboxRowClass">
      <input v-model="form.attendsPatients" type="checkbox" class="h-[13px] w-[13px] accent-brand">
      Também atende pacientes
    </label>

    <label v-if="user && !isSelf" :class="checkboxRowClass">
      <input v-model="form.active" type="checkbox" class="h-[13px] w-[13px] accent-brand">
      Conta ativa
    </label>
  </AppModal>
</template>
