<script setup lang="ts">
defineProps<{
  label: string
  error?: string
  full?: boolean
}>()

const id = useId()
const errorId = `${id}-error`

const controlClass = 'h-[37px] w-full rounded-[7px] border border-[#e1e8ee] bg-surface px-[11px] text-[11px] text-[#506075] outline-none transition duration-200 focus:border-[#73cddd] aria-[invalid=true]:!border-[#d9705c] dark:border-line dark:text-ink dark:[color-scheme:dark]'
</script>

<template>
  <div class="flex min-w-0 flex-col gap-[7px]" :class="{ 'sm:col-span-2': full }">
    <label :for="id" class="text-[10px] font-semibold text-[#748196] dark:text-muted">{{ label }}</label>
    <slot
      :id="id"
      :control-class="controlClass"
      :invalid="Boolean(error)"
      :described-by="error ? errorId : undefined"
    />
    <p v-if="error" :id="errorId" class="text-[10px] text-[#c2553f]">
      {{ error }}
    </p>
  </div>
</template>
