import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import DetailsModal from '~/components/appointments/DetailsModal.vue'
import MetricCard from '~/components/overview/MetricCard.vue'
import ReportsPage from '~/pages/app/relatorios.vue'
import { appointmentDetails } from '../fixtures/agenda'
import { paymentsFixture } from '../fixtures/clinical'
import { professionalSession } from '../fixtures/session'
import { seedSession } from '../utils/session'

// Text with the non-breaking space of currency formats turned into a plain space.
function text(root: Pick<VueWrapper, 'text'>) {
  return root.text().replace(/ /g, ' ')
}

// Button by its text.
function button(root: VueWrapper, text: string) {
  return root.findAll('button').find(item => item.text() === text)
}

// Opens the appointment modal with the payments section loaded.
async function openDetails() {
  vi.spyOn(appointmentsService, 'get').mockResolvedValue(appointmentDetails)
  useActiveModal().openModal('appointment-details', { id: 'event-2' })

  const modal = await mountSuspended(DetailsModal)

  await flushPromises()

  return modal
}

describe('payments in the appointment', () => {
  it('shows price, paid and what is left', async () => {
    const section = (await openDetails()).find('[aria-label="Pagamentos"]')

    expect(text(section)).toContain('R$ 200,00')
    expect(text(section)).toContain('Pix')
    expect(text(section)).toContain('Paula Nunes')
    expect(text(section.find('dd.font-bold'))).toBe('R$ 100,00')
  })

  it('records a payment starting from the balance', async () => {
    const record = vi.spyOn(financeService, 'recordPayment').mockResolvedValue({ ...paymentsFixture, paidCents: 20000, balanceCents: 0, paymentStatus: 'paid' })
    const modal = await openDetails()

    await button(modal, 'Registrar pagamento')?.trigger('click')

    const amount = modal.find('[aria-label="Pagamentos"] input[inputmode="decimal"]')

    expect((amount.element as HTMLInputElement).value).toBe('100,00')

    await modal.find('[aria-label="Pagamentos"] form').trigger('submit')
    await flushPromises()

    expect(record).toHaveBeenCalledWith('event-2', expect.objectContaining({ amount: '100,00', method: 'pix', allowOver: false }))
    expect(modal.find('[aria-label="Pagamentos"]').text()).toContain('Pago')
  })

  it('asks the reason before voiding', async () => {
    const voidPayment = vi.spyOn(financeService, 'voidPayment').mockResolvedValue({ ...paymentsFixture, paidCents: 0, balanceCents: 20000, paymentStatus: 'pending' })
    const modal = await openDetails()

    await button(modal, 'Estornar')?.trigger('click')
    await modal.find('[aria-label="Motivo do estorno"]').setValue('Lançado em dobro')
    await button(modal, 'Confirmar estorno')?.trigger('click')
    await flushPromises()

    expect(voidPayment).toHaveBeenCalledWith('p1', 'Lançado em dobro')
  })

  it('is not shown to professionals', async () => {
    seedSession(professionalSession)
    const modal = await openDetails()

    expect(modal.find('[aria-label="Pagamentos"]').exists()).toBe(false)
  })
})

describe('overview revenue card', () => {
  it('shows the estimate under what was received', async () => {
    const card = await mountSuspended(MetricCard, {
      props: { metric: { id: 'revenue', label: 'Recebido hoje', value: 'R$ 50', detail: 'Previsto R$ 150', change: '—', trend: 'flat', period: 'vs. ontem', icon: 'billing', tone: 'green' } },
    })

    expect(card.text()).toContain('Recebido hoje')
    expect(card.text()).toContain('Previsto R$ 150')
  })
})

describe('reports', () => {
  beforeEach(() => {
    clearNuxtData('report')
  })

  it('shows the totals, the chart and the table', async () => {
    const page = await mountSuspended(ReportsPage)

    await flushPromises()

    expect(text(page)).toContain('R$ 2.700,00')
    expect(text(page)).toContain('7,1%')
    expect(page.find('table').text()).toContain('Dr. Carlos Mendes')
    expect(text(page)).toContain('Pix: R$ 2.400,00')
    expect(page.find('figure').attributes('aria-label')?.replace(/ /g, ' ')).toContain('Dr. Carlos Mendes, previsto R$ 1.800,00')
    // Pages sharing the "report" key would reuse this handler; the app shows one at a time.
    page.unmount()
  })

  it('asks the report again when the grouping changes', async () => {
    const report = vi.spyOn(financeService, 'report')
    const page = await mountSuspended(ReportsPage)

    await flushPromises()
    await page.findAll('select').at(1)?.setValue('procedure')
    await flushPromises()

    expect(report).toHaveBeenLastCalledWith(expect.any(String), expect.any(String), 'procedure')
  })

  it('exports a CSV that Excel in Portuguese reads', async () => {
    const download = vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob:x')

    vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => {})
    const page = await mountSuspended(ReportsPage)

    await flushPromises()
    await page.findAll('button').find(item => item.text().includes('Exportar CSV'))?.trigger('click')

    const csv = await (download.mock.calls[0]?.[0] as Blob).text()

    expect(csv.startsWith('﻿Profissional;Consultas')).toBe(true)
    expect(csv).toContain('Dr. Carlos Mendes;12;9;1;10,00;1800,00;1500,00')
    expect(csv).toContain('"Dra. Ana Beatriz; ""Estética"""')
    expect(csv).toContain('Total;17;13;1;7,10;2700,00;2400,00')
  })
})
