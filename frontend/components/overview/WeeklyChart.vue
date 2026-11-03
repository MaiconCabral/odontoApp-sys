<script setup lang="ts">
import { ArrowDownRight, ArrowRight, ArrowUpRight } from 'lucide-vue-next'
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
    subtitle="Consultas agendadas, confirmadas e concluídas"
  >
    <template #action>
      <span class="inline-flex items-center rounded-[7px] border border-line bg-surface px-[9px] py-[7px] text-[10px] text-body">
        {{ weekly.range }}
      </span>
    </template>

    <div class="mt-5 flex items-baseline gap-2 sm:mt-[25px]">
      <strong class="font-display text-[26px] font-extrabold leading-[normal] tracking-[-.8px]">{{ weekly.total }}</strong>
      <span class="flex items-center text-[10px] font-bold" :class="weekly.trend === 'down' ? 'text-[#c2553f]' : weekly.trend === 'up' ? 'text-[#21a477]' : 'text-muted'">
        <component :is="weekly.trend === 'down' ? ArrowDownRight : weekly.trend === 'up' ? ArrowUpRight : ArrowRight" :size="14" /> {{ weekly.change }}
      </span>
      <small class="text-[10px] text-faint">{{ weekly.comparison }}</small>
    </div>

    <div
      class="relative mt-4 flex h-[190px] pb-[26px]"
      role="img"
      :aria-label="`Agendamentos por dia: ${chartDescription}`"
    >
      <div class="z-[2] flex h-[164px] flex-col justify-between pr-3 text-[9px] text-faint">
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
            :class="bar.highlighted ? 'bg-brand shadow-[0_5px_12px_rgba(2,132,199,.2)]' : 'bg-brand-soft'"
            :style="{ height: `${barHeightPercent(bar.value, weekly.scaleMax)}%` }"
          >
            <span
              v-if="bar.highlighted"
              class="absolute left-1/2 top-[-22px] -translate-x-1/2 rounded bg-brand-soft px-[5px] py-[3px] text-[9px] text-white"
            >
              {{ bar.value }}
            </span>
          </div>
          <small class="text-[9px] text-muted">{{ bar.day }}</small>
        </div>
      </div>
      <div class="absolute inset-[0_0_25px_30px] flex flex-col justify-between">
        <i
          v-for="tick in weekly.axis"
          :key="tick"
          class="border-t border-dashed border-line"
        />
      </div>
    </div>
  </OverviewPanel>
</template>
