<script setup lang="ts">
import type { SettingsTab, SettingsTabId } from '~/types/settings'

defineProps<{
  tabs: SettingsTab[]
}>()

const active = defineModel<SettingsTabId>('active', { required: true })
</script>

<template>
  <aside
    class="flex overflow-auto rounded-xl border border-[#e7edf3] bg-surface p-4 shadow-[0_4px_14px_rgba(42,64,91,.025)] dark:border-line sm:p-2 md:grid"
    aria-label="Seções das configurações"
  >
    <button
      v-for="tab in tabs"
      :key="tab.id"
      type="button"
      class="flex justify-between gap-2 whitespace-nowrap rounded-[7px] px-3 py-[11px] text-left text-[11px] hover:bg-[#eaf7fa] hover:font-bold hover:text-[#087f9f] dark:hover:bg-[#172b37] md:whitespace-normal"
      :class="active === tab.id ? 'bg-[#eaf7fa] font-bold text-[#087f9f] dark:bg-[#123543] dark:text-[#6cd0e5]' : 'text-[#8290a1]'"
      :aria-current="active === tab.id ? 'true' : undefined"
      @click="active = tab.id"
    >
      {{ tab.label }}
      <span
        v-if="tab.badge"
        class="rounded bg-[#fff2d9] px-[5px] py-[3px] text-[8px] font-normal text-[#bc8031]"
      >
        {{ tab.badge }}
      </span>
    </button>
  </aside>
</template>
