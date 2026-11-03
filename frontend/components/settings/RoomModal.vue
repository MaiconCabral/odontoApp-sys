<script setup lang="ts">
import { DoorOpen } from 'lucide-vue-next'
import type { RoomForm, RoomItem } from '~/types/catalog'

const props = defineProps<{
  room: RoomItem | null
}>()

const emit = defineEmits<{
  close: []
  saved: [message: string]
}>()

const form = reactive<RoomForm>({
  name: props.room?.name ?? '',
  active: props.room?.active ?? true,
})

// Name of the room.
function validate(fields: RoomForm) {
  return fields.name.trim().length < 2 ? { name: 'Informe o nome da sala.' } : {}
}

const { errors, formError, isSubmitting, submit } = useAuthForm(form, validate, async () => {
  const saved = props.room ? await roomsService.update(props.room.id, form) : await roomsService.create(form)

  emit('saved', props.room ? `${saved.name} foi atualizada.` : `${saved.name} foi adicionada.`)
})
</script>

<template>
  <AppModal
    eyebrow="Salas"
    :title="room ? 'Editar sala' : 'Nova sala'"
    description="Sala, consultório ou cadeira onde a consulta acontece."
    :submit-label="room ? 'Salvar alterações' : 'Adicionar sala'"
    :submitting="isSubmitting"
    @close="emit('close')"
    @submit="submit()"
  >
    <template #submit-icon>
      <DoorOpen :size="17" />
    </template>

    <p v-if="formError" role="alert" class="text-[11px] text-tone-red-text sm:col-span-2">
      {{ formError }}
    </p>

    <AppFormField v-slot="field" label="Nome" :error="errors.name" full>
      <input :id="field.id" v-model="form.name" autocomplete="off" placeholder="Ex.: Sala 03 · Raio-X" :class="[field.controlClass, 'placeholder:text-faint']" :aria-invalid="field.invalid" :aria-describedby="field.describedBy">
    </AppFormField>

    <label v-if="room" class="flex items-center gap-2 text-[11px] text-body sm:col-span-2">
      <input v-model="form.active" type="checkbox" class="h-[13px] w-[13px] accent-brand">
      Em uso para novos agendamentos
    </label>
  </AppModal>
</template>
