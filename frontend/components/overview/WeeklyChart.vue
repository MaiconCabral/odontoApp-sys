<script setup lang="ts">
import { ArrowUpRight, ChevronDown } from 'lucide-vue-next'
import type { WeeklyAppointments } from '~/types/overview'

const props = defineProps<{
  weekly: WeeklyAppointments
}>()

const chartDescription = computed(() =>
  props.weekly.bars.map(bar => `${bar.day}: ${bar.value}`).join(', '),
)
</script>

<template>
  <OverviewPanel
    class="p-4 sm:p-[21px]"
    title="Agendamentos da semana"
    subtitle="Volume de consultas confirmadas"
  >
    <template #action>
      <button type="button" class="inline-flex items-center gap-[7px] rounded-[7px] border border-[#e6ebf1] bg-surface px-[9px] py-[7px] text-[10px] text-[#6e7b8c] dark:border-line dark:text-[#acb8c8]">
        {{ weekly.range }} <ChevronDown :size="14" />
      </button>
    </template>

    <div class="mt-5 flex items-baseline gap-2 sm:mt-[25px]">
      <strong class="font-display text-[26px] font-extrabold leading-[normal] tracking-[-.8px]">{{ weekly.total }}</strong>
      <span class="flex items-center text-[10px] font-bold text-[#21a477]">
        <ArrowUpRight :size="14" /> {{ weekly.change }}
      </span>
      <small class="text-[10px] text-faint">{{ weekly.comparison }}</small>
    </div>

    <div
      class="relative mt-4 flex h-[190px] pb-[26px]"
      role="img"
      :aria-label="`Agendamentos por dia: ${chartDescription}`"
    >
      <div class="z-[2] flex h-[164px] flex-col justify-between pr-3 text-[9px] text-[#abb5c1]">
        <span v-for="tick in weekly.axis" :key="tick">{{ tick }}</span>
      </div>
      <div class="relative z-[3] flex flex-1 items-end justify-around px-2">
        <div
          v-for="bar in weekly.bars"
          :key="bar.day"
          class="flex h-full flex-col items-center justify-end gap-2"
        >
          <div
            class="relative max-h-[140px] min-h-[15px] w-[18px] rounded-[6px_6px_2px_2px] transition-[height] duration-500 ease-in-out sm:w-[22px]"
            :class="bar.highlighted ? 'bg-[#0c94b5] shadow-[0_5px_12px_rgba(12,148,181,.2)]' : 'bg-[#dceff3] dark:bg-[#214251]'"
            :style="{ height: `${barHeightPercent(bar.value, weekly.scaleMax)}%` }"
          >
            <span
              v-if="bar.highlighted"
              class="absolute left-1/2 top-[-22px] -translate-x-1/2 rounded bg-[#163d4b] px-[5px] py-[3px] text-[9px] text-white"
            >
              {{ bar.value }}
            </span>
          </div>
          <small class="text-[9px] text-[#9aa6b4]">{{ bar.day }}</small>
        </div>
      </div>
      <div class="absolute inset-[0_0_25px_30px] flex flex-col justify-between">
        <i
          v-for="tick in weekly.axis"
          :key="tick"
          class="border-t border-dashed border-[#e9eff3] dark:border-[#202e3d]"
        />
      </div>
    </div>
  </OverviewPanel>
</template>
