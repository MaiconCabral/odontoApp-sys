<script setup lang="ts">
import type { ApexOptions } from 'apexcharts'
import { ArrowDownRight, ArrowRight, ArrowUpRight } from 'lucide-vue-next'
import type { WeeklyAppointments } from '~/types/overview'

const props = defineProps<{
  weekly: WeeklyAppointments
}>()

const { isDark } = useTheme()

const chartDescription = computed(() =>
  props.weekly.bars.map(bar => `${bar.day}: ${bar.value}`).join(', '),
)

const series = computed(() => [{ name: 'Consultas', data: props.weekly.bars.map(bar => bar.value) }])

// Today in the brand color, the other days in the soft tone of the theme.
const options = computed<ApexOptions>(() => {
  const soft = isDark.value ? '#0c3347' : '#bae6fd'

  return {
    colors: props.weekly.bars.map(bar => (bar.highlighted ? '#0369a1' : soft)),
    plotOptions: { bar: { distributed: true, borderRadius: 5, borderRadiusApplication: 'end', columnWidth: '40%' } },
    dataLabels: { enabled: false },
    legend: { show: false },
    states: { active: { filter: { type: 'none' } } },
    xaxis: { categories: props.weekly.bars.map(bar => bar.day), axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { min: 0, max: props.weekly.scaleMax, tickAmount: Math.max(1, props.weekly.axis.length - 1) },
    tooltip: { y: { formatter: (value: number) => `${value} consulta(s)` } },
  }
})
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
      <span class="flex items-center text-[10px] font-bold" :class="weekly.trend === 'down' ? 'text-tone-red-text' : weekly.trend === 'up' ? 'text-tone-green-text' : 'text-muted'">
        <component :is="weekly.trend === 'down' ? ArrowDownRight : weekly.trend === 'up' ? ArrowUpRight : ArrowRight" :size="14" /> {{ weekly.change }}
      </span>
      <small class="text-[10px] text-faint">{{ weekly.comparison }}</small>
    </div>

    <AppChart
      class="mt-2"
      type="bar"
      :series="series"
      :options="options"
      :height="200"
      :label="`Agendamentos por dia: ${chartDescription}`"
    />
  </OverviewPanel>
</template>
