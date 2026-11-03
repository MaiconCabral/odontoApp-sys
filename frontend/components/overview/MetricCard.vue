<script setup lang="ts">
import { Activity, ArrowDownRight, ArrowRight, ArrowUpRight, CalendarDays, CreditCard, Users } from 'lucide-vue-next'
import type { Metric, MetricIcon, MetricTone, Trend } from '~/types/overview'

defineProps<{
  metric: Metric
}>()

const icons = {
  calendar: CalendarDays,
  billing: CreditCard,
  attendance: Activity,
  patients: Users,
} satisfies Record<MetricIcon, unknown>

const toneClasses: Record<MetricTone, string> = {
  blue: 'bg-brand-soft text-brand-text',
  green: 'bg-tone-green-bg text-tone-green-text',
  amber: 'bg-tone-amber-bg text-tone-amber-text',
  violet: 'bg-tone-violet-bg text-tone-violet-text',
}
const trends = {
  up: { icon: ArrowUpRight, class: 'text-tone-green-text', label: 'subiu' },
  down: { icon: ArrowDownRight, class: 'text-tone-red-text', label: 'caiu' },
  flat: { icon: ArrowRight, class: 'text-muted', label: 'estável' },
} satisfies Record<Trend, unknown>
</script>

<template>
  <div class="relative min-h-[130px] rounded-xl border border-line bg-surface px-3.5 py-4 shadow-[0_4px_14px_rgba(42,64,91,.025)] sm:min-h-[143px] sm:px-[18px] sm:pb-[17px] sm:pt-5">
    <div
      class="absolute right-[13px] top-3.5 grid h-[33px] w-[33px] place-items-center rounded-[9px] sm:right-[17px] sm:top-[18px]"
      :class="toneClasses[metric.tone]"
    >
      <component :is="icons[metric.icon]" :size="18" />
    </div>
    <div class="pr-10 text-[11px] text-muted">
      {{ metric.label }}
    </div>
    <div class="mt-[17px] font-display text-[22px] font-extrabold leading-[normal] tracking-[-.8px] text-heading sm:mt-[15px] sm:text-[25px]">
      {{ metric.value }}
    </div>
    <div class="mt-[11px] flex items-center gap-[3px] text-[11px] font-bold" :class="trends[metric.trend].class">
      <component :is="trends[metric.trend].icon" :size="13" :aria-label="trends[metric.trend].label" />
      {{ metric.change }}
      <span class="ml-[3px] hidden font-normal text-faint sm:inline">{{ metric.period }}</span>
    </div>
  </div>
</template>
