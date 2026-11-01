<script setup lang="ts">
import { Eye, EyeOff } from 'lucide-vue-next'

defineProps<{
  label: string
  autocomplete: 'current-password' | 'new-password'
  error?: string
}>()

const model = defineModel<string>({ required: true })

const isVisible = ref(false)
</script>

<template>
  <AppFormField v-slot="field" :label="label" :error="error" size="large">
    <div class="relative">
      <input
        :id="field.id"
        v-model="model"
        :type="isVisible ? 'text' : 'password'"
        :autocomplete="autocomplete"
        :class="[field.controlClass, 'pr-11']"
        :aria-invalid="field.invalid"
        :aria-describedby="field.describedBy"
      >
      <button
        type="button"
        class="absolute inset-y-0 right-0 grid w-11 place-items-center rounded-r-lg text-muted transition duration-200 hover:text-brand-text"
        :aria-label="isVisible ? 'Ocultar senha' : 'Mostrar senha'"
        :aria-pressed="isVisible"
        @click="isVisible = !isVisible"
      >
        <EyeOff v-if="isVisible" :size="18" />
        <Eye v-else :size="18" />
      </button>
    </div>
  </AppFormField>
</template>
