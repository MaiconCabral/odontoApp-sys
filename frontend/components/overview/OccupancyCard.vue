<script setup lang="ts">
import type { ApexOptions } from 'apexcharts'
import { Ellipsis } from 'lucide-vue-next'

const props = defineProps<{
  percentage: number
}>()

const { isDark } = useTheme()

// Booked part in the brand color over a track in the line color of the theme.
const options = computed<ApexOptions>(() => ({
  colors: ['#0369a1'],
  plotOptions: {
    radialBar: {
      hollow: { size: '68%' },
      track: { background: isDark.value ? '#27272a' : '#e2e8f0' },
      dataLabels: { show: false },
    },
  },
  stroke: { lineCap: 'round' },
}))
</script>

<template>
  <OverviewPanel
    class="p-4 sm:p-[21px]"
    title="Ocupação da agenda"
    subtitle="Capacidade média dos profissionais"
  >
    <template #action>
      <Ellipsis :size="18" class="flex-none text-faint" />
    </template>

    <div class="grid place-items-center pb-5 pt-[27px]">
      <div class="relative grid h-[160px] w-[160px] place-items-center">
        <AppChart class="absolute inset-0" type="radialBar" :series="[percentage]" :options="options" :height="180" :label="`${percentage}% da agenda ocupada`" />
        <div class="pointer-events-none relative flex flex-col items-center justify-center" aria-hidden="true">
          <strong class="font-display text-[25px] font-extrabold leading-[normal] tracking-[-1px]">{{ percentage }}%</strong>
          <span class="text-[10px] text-muted">ocupada</span>
        </div>
      </div>
    </div>

    <div class="grid gap-2.5">
      <span class="flex items-center gap-[7px] text-[10px] text-muted">
        <i class="h-[7px] w-[7px] rounded-sm bg-brand" />
        Consultas agendadas
        <b class="ml-auto text-body">{{ percentage }}%</b>
      </span>
      <span class="flex items-center gap-[7px] text-[10px] text-muted">
        <i class="h-[7px] w-[7px] rounded-sm bg-line" />
        Horários livres
        <b class="ml-auto text-body">{{ 100 - percentage }}%</b>
      </span>
    </div>

    <OverviewTextButton class="mt-5" :to="panelPath('agenda')">
      Ver agenda completa
    </OverviewTextButton>
  </OverviewPanel>
</template>
