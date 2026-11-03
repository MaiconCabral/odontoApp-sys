<script setup lang="ts">
const props = withDefaults(defineProps<{
  label: string
  error?: string
  full?: boolean
  size?: 'default' | 'large'
}>(), {
  size: 'default',
})

const id = useId()
const errorId = `${id}-error`

const sizes = {
  default: { control: 'h-[37px] rounded-[7px] px-[11px] text-[11px]', label: 'text-[10px]', error: 'text-[10px]' },
  large: { control: 'h-11 rounded-lg px-3 text-[14px]', label: 'text-[13px]', error: 'text-[12px]' },
}

const controlClass = computed(() => `${sizes[props.size].control} w-full border border-line bg-surface text-body outline-none transition duration-200 focus:border-brand-accent aria-[invalid=true]:!border-tone-red-line dark:[color-scheme:dark]`)
</script>

<template>
  <div class="flex min-w-0 flex-col gap-[7px]" :class="{ 'sm:col-span-2': full }">
    <label :for="id" class="font-semibold text-muted" :class="sizes[size].label">{{ label }}</label>
    <slot
      :id="id"
      :control-class="controlClass"
      :invalid="Boolean(error)"
      :described-by="error ? errorId : undefined"
    />
    <p v-if="error" :id="errorId" class="text-tone-red-text" :class="sizes[size].error">
      {{ error }}
    </p>
  </div>
</template>
