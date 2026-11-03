<script setup lang="ts">
import { Download } from 'lucide-vue-next'
import type { ReportGroup } from '~/types/finance'

definePageMeta({ middleware: 'role', roles: ['admin'] })
useHead({ title: 'Relatórios' })

type Preset = 'this-month' | 'last-month' | '30' | '90' | 'custom'

const today = localToday()

// First and last day of the month of a date, as ISO.
function monthRange(iso: string) {
  const [year = 0, month = 1] = iso.split('-').map(Number)
  const last = new Date(Date.UTC(year, month, 0)).getUTCDate()
  const prefix = `${year}-${String(month).padStart(2, '0')}`

  return { from: `${prefix}-01`, to: `${prefix}-${String(last).padStart(2, '0')}` }
}

const preset = ref<Preset>('this-month')
const range = reactive(monthRange(today))
const group = ref<ReportGroup>('professional')

// Applies a preset period; "custom" keeps the typed dates.
watch(preset, (value) => {
  const ranges: Record<Exclude<Preset, 'custom'>, { from: string, to: string }> = {
    'this-month': monthRange(today),
    'last-month': monthRange(addMonths(today, -1)),
    '30': { from: addDays(today, -29), to: today },
    '90': { from: addDays(today, -89), to: today },
  }

  if (value !== 'custom') {
    Object.assign(range, ranges[value])
  }
})

const { data: report, error, pending, refresh } = await useAsyncData(
  'report',
  () => financeService.report(range.from, range.to, group.value),
  { watch: [() => range.from, () => range.to, group] },
)

const errorMessage = computed(() => (error.value ? parseApiError(error.value).fields.to ?? 'Não foi possível carregar o relatório.' : ''))

const groupLabels: Record<ReportGroup, string> = { day: 'Dia', professional: 'Profissional', procedure: 'Procedimento' }

// Expected and received money of each row, in reais.
const chartSeries = computed(() => [
  { name: 'Previsto', data: report.value?.rows.map(row => row.expectedCents / 100) ?? [] },
  { name: 'Recebido', data: report.value?.rows.map(row => row.receivedCents / 100) ?? [] },
])

const chartOptions = computed(() => ({
  colors: ['#7dd3fc', '#0369a1'],
  plotOptions: { bar: { borderRadius: 3, columnWidth: '55%' } },
  dataLabels: { enabled: false },
  xaxis: { categories: report.value?.rows.map(row => row.label) ?? [] },
  yaxis: { labels: { formatter: (value: number) => formatMoney(Math.round(value * 100)) } },
  tooltip: { y: { formatter: (value: number) => formatMoney(Math.round(value * 100)) } },
}))

// Text alternative of the chart.
const chartLabel = computed(() => report.value
  ? `Gráfico de previsto e recebido por ${groupLabels[group.value].toLowerCase()}: ${report.value.rows.map(row => `${row.label}, previsto ${formatMoney(row.expectedCents)}, recebido ${formatMoney(row.receivedCents)}`).join('; ')}`
  : '')

// Absence rate as "12,5%" or a dash without finished appointments.
function rate(value: number | null) {
  return value === null ? '—' : `${value.toLocaleString('pt-BR', { maximumFractionDigits: 1 })}%`
}

// Saves the table as CSV.
function exportCsv() {
  if (!report.value) {
    return
  }

  const header = [groupLabels[group.value], 'Consultas', 'Concluídas', 'Faltas', 'Taxa de faltas (%)', 'Previsto (R$)', 'Recebido (R$)']
  const line = (label: string, row: typeof report.value.totals) => [
    label,
    row.appointments,
    row.completed,
    row.noShow,
    row.absenceRate === null ? null : csvNumber(row.absenceRate),
    csvNumber(row.expectedCents / 100),
    csvNumber(row.receivedCents / 100),
  ]

  downloadFile(
    `relatorio-${group.value}-${range.from}-a-${range.to}.csv`,
    toCsv([header, ...report.value.rows.map(row => line(row.label, row)), line('Total', report.value.totals)]),
  )
}

const controlClass = 'h-[34px] rounded-[7px] border border-line bg-surface px-2 text-[11px] text-body'
</script>

<template>
  <div>
    <LayoutPageHeader
      eyebrow="Administração"
      title="Relatórios"
      description="Consultas, faltas e dinheiro previsto e recebido no período."
    />

    <div class="mb-[18px] flex flex-wrap items-end gap-3 text-[11px] text-body">
      <label class="flex flex-col gap-1">
        <span class="text-muted">Período</span>
        <select v-model="preset" :class="controlClass">
          <option value="this-month">Este mês</option>
          <option value="last-month">Mês passado</option>
          <option value="30">Últimos 30 dias</option>
          <option value="90">Últimos 90 dias</option>
          <option value="custom">Personalizado</option>
        </select>
      </label>
      <label class="flex flex-col gap-1">
        <span class="text-muted">De</span>
        <input v-model="range.from" type="date" :class="controlClass" @input="preset = 'custom'">
      </label>
      <label class="flex flex-col gap-1">
        <span class="text-muted">Até</span>
        <input v-model="range.to" type="date" :min="range.from" :class="controlClass" @input="preset = 'custom'">
      </label>
      <label class="flex flex-col gap-1">
        <span class="text-muted">Agrupar por</span>
        <select v-model="group" :class="controlClass">
          <option value="professional">Profissional</option>
          <option value="procedure">Procedimento</option>
          <option value="day">Dia</option>
        </select>
      </label>
      <button
        type="button"
        class="ml-auto inline-flex h-[34px] items-center gap-1.5 rounded-lg border border-line px-3 font-bold disabled:opacity-60"
        :disabled="!report?.rows.length"
        @click="exportCsv"
      >
        <Download :size="14" /> Exportar CSV
      </button>
    </div>

    <p v-if="pending && !report" role="status" class="text-[13px] text-muted">
      Carregando o relatório...
    </p>

    <div v-else-if="error || !report" role="alert" class="text-[13px] text-muted">
      <p>{{ errorMessage }}</p>
      <button type="button" class="mt-2 font-bold text-brand-text" @click="refresh()">
        Tentar novamente
      </button>
    </div>

    <template v-else>
      <div class="mb-[18px] grid grid-cols-2 gap-[15px] md:grid-cols-4">
        <div v-for="card in [
          { label: 'Consultas', value: String(report.totals.appointments) },
          { label: 'Taxa de faltas', value: rate(report.totals.absenceRate) },
          { label: 'Previsto', value: formatMoney(report.totals.expectedCents) },
          { label: 'Recebido', value: formatMoney(report.totals.receivedCents) },
        ]" :key="card.label" class="rounded-xl border border-line bg-surface px-4 py-4">
          <div class="text-[11px] text-muted">
            {{ card.label }}
          </div>
          <div class="mt-2 font-display text-[22px] font-extrabold tracking-[-.8px] text-heading">
            {{ card.value }}
          </div>
        </div>
      </div>

      <SettingsCard v-if="report.rows.length" title="Previsto e recebido" :subtitle="`Por ${groupLabels[group].toLowerCase()}`">
        <AppChart type="bar" :series="chartSeries" :options="chartOptions" :label="chartLabel" />
      </SettingsCard>

      <SettingsCard class="mt-[17px]" title="Detalhes" subtitle="Previsto pela data da consulta; recebido pela data do pagamento.">
        <p v-if="!report.rows.length" class="pt-4 text-[11px] text-muted">
          Nenhuma consulta nem pagamento no período.
        </p>
        <div v-else class="mt-3 overflow-x-auto">
          <table class="w-full min-w-[560px] text-left text-[11px] text-body">
            <thead class="text-[10px] text-muted">
              <tr class="border-b border-line">
                <th scope="col" class="py-2 font-semibold">
                  {{ groupLabels[group] }}
                </th>
                <th scope="col" class="py-2 text-right font-semibold">
                  Consultas
                </th>
                <th scope="col" class="py-2 text-right font-semibold">
                  Concluídas
                </th>
                <th scope="col" class="py-2 text-right font-semibold">
                  Faltas
                </th>
                <th scope="col" class="py-2 text-right font-semibold">
                  Previsto
                </th>
                <th scope="col" class="py-2 text-right font-semibold">
                  Recebido
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in report.rows" :key="row.key" class="border-b border-line-soft">
                <th scope="row" class="py-2 font-semibold">
                  {{ row.label }}
                </th>
                <td class="py-2 text-right">
                  {{ row.appointments }}
                </td>
                <td class="py-2 text-right">
                  {{ row.completed }}
                </td>
                <td class="py-2 text-right">
                  {{ row.noShow }} <span class="text-muted">({{ rate(row.absenceRate) }})</span>
                </td>
                <td class="py-2 text-right">
                  {{ formatMoney(row.expectedCents) }}
                </td>
                <td class="py-2 text-right">
                  {{ formatMoney(row.receivedCents) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="report.byMethod.length" class="mt-4 border-t border-line-soft pt-3">
          <h3 class="mb-2 text-[10px] font-bold uppercase tracking-[.06em] text-muted">
            Recebido por forma de pagamento
          </h3>
          <ul class="flex flex-wrap gap-x-5 gap-y-1 text-[11px] text-body">
            <li v-for="method in report.byMethod" :key="method.method">
              {{ method.methodLabel }}: <strong>{{ formatMoney(method.receivedCents) }}</strong>
            </li>
          </ul>
        </div>
      </SettingsCard>
    </template>
  </div>
</template>
