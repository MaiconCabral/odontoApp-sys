<script setup lang="ts">
import type { SettingsTab, SettingsTabId } from '~/types/settings'

defineProps<{
  tabs: SettingsTab[]
}>()

const active = defineModel<SettingsTabId>('active', { required: true })
</script>

<template>
  <aside
    class="flex overflow-auto rounded-xl border border-line bg-surface p-4 shadow-[0_4px_14px_rgba(42,64,91,.025)] sm:p-2 md:grid"
    aria-label="Seções das configurações"
  >
    <button
      v-for="tab in tabs"
      :key="tab.id"
      type="button"
      class="flex justify-between gap-2 whitespace-nowrap rounded-[7px] px-3 py-[11px] text-left text-[11px] hover:bg-brand-soft hover:font-bold hover:text-brand-text md:whitespace-normal"
      :class="active === tab.id ? 'bg-brand-soft font-bold text-brand-text' : 'text-muted'"
      :aria-current="active === tab.id ? 'true' : undefined"
      @click="active = tab.id"
    >
      {{ tab.label }}
      <span
        v-if="tab.badge"
        class="rounded bg-tone-amber-bg px-[5px] py-[3px] text-[8px] font-normal text-tone-amber-text"
      >
        {{ tab.badge }}
      </span>
    </button>
  </aside>
</template>
