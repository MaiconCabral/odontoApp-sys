<script setup lang="ts">
import type { ServicePreference } from '~/types/settings'

const props = defineProps<{
  preferences: ServicePreference[]
}>()

const items = ref(props.preferences.map(preference => ({ ...preference })))
</script>

<template>
  <SettingsCard
    title="Preferências de atendimento"
    subtitle="Defina como sua equipe trabalha no dia a dia."
  >
    <div
      v-for="item in items"
      :key="item.id"
      class="flex items-center justify-between gap-5 border-b border-line-soft py-4 last:border-b-0 last:pb-0"
    >
      <div class="flex flex-col gap-1">
        <strong :id="`preference-${item.id}`" class="text-[11px] text-body dark:text-body">{{ item.title }}</strong>
        <span class="text-[10px] text-muted">{{ item.description }}</span>
      </div>
      <button
        type="button"
        role="switch"
        class="h-5 w-[35px] flex-none rounded-[15px] p-0.5"
        :class="item.enabled ? 'bg-brand' : 'bg-line'"
        :aria-checked="item.enabled"
        :aria-labelledby="`preference-${item.id}`"
        @click="item.enabled = !item.enabled"
      >
        <i
          class="block h-4 w-4 rounded-full bg-white transition duration-200"
          :class="{ 'translate-x-[15px]': item.enabled }"
        />
      </button>
    </div>
  </SettingsCard>
</template>
