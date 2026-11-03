<script setup lang="ts">
import type { ApexOptions } from 'apexcharts'

const props = defineProps<{
  type: 'bar' | 'line' | 'area' | 'radialBar' | 'donut'
  series: ApexOptions['series']
  options: ApexOptions
  height?: number | string
  // Text read by screen readers instead of the drawing.
  label: string
}>()

// ApexCharts touches window, so it only loads in the browser.
const ApexChart = defineAsyncComponent(() => import('vue3-apexcharts').then(module => module.default))

const { isDark } = useTheme()

// Reads a theme token, so the chart follows the light and the dark theme.
function token(name: string, fallback: string) {
  if (!import.meta.client) {
    return fallback
  }

  return getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback
}

// Theme colors merged into the options of each chart.
const themed = computed<ApexOptions>(() => {
  // Depends on the theme, so the colors are read again when it changes.
  void isDark.value
  const text = token('--color-muted', '#64748b')
  const line = token('--color-line', '#e2e8f0')

  return {
    ...props.options,
    chart: {
      fontFamily: '"DM Sans", sans-serif',
      toolbar: { show: false },
      background: 'transparent',
      animations: { enabled: false },
      ...props.options.chart,
    },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    grid: { borderColor: line, strokeDashArray: 3, ...props.options.grid },
    xaxis: { ...props.options.xaxis, labels: { style: { colors: text }, ...props.options.xaxis?.labels } },
    yaxis: Array.isArray(props.options.yaxis)
      ? props.options.yaxis
      : { ...props.options.yaxis, labels: { style: { colors: text }, ...props.options.yaxis?.labels } },
    legend: { labels: { colors: text }, ...props.options.legend },
    tooltip: { theme: isDark.value ? 'dark' : 'light', ...props.options.tooltip },
  }
})
</script>

<template>
  <figure :aria-label="label" role="img" class="m-0">
    <ClientOnly>
      <ApexChart :key="isDark ? 'dark' : 'light'" :type="type" :series="series" :options="themed" :height="height ?? 260" />
    </ClientOnly>
  </figure>
</template>
