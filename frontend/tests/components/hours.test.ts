import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import HoursCard from '~/components/settings/HoursCard.vue'
import { businessHoursFixture } from '../fixtures/agenda'
import { receptionSession } from '../fixtures/session'
import { seedSession } from '../utils/session'

describe('SettingsHoursCard', () => {
  beforeEach(() => {
    clearNuxtData('business-hours')
    useToast().hideToast()
  })

  it('shows each weekday with its opening hours and lunch break', async () => {
    const card = await mountSuspended(HoursCard)
    await flushPromises()

    expect(card.text()).toContain('Segunda')
    expect(card.text()).toContain('Fechado')
    expect((card.find('[aria-label="Início do almoço Segunda"]').element as HTMLInputElement).value).toBe('12:00')
  })

  it('saves the changes and shows the error of each day', async () => {
    const update = vi.spyOn(scheduleService, 'updateHours')
      .mockRejectedValueOnce({ data: { message: 'Inválido', errors: { 'days.0.closes_at': ['O fechamento precisa ser depois da abertura.'] } } })
      .mockResolvedValueOnce(businessHoursFixture)
    const card = await mountSuspended(HoursCard)
    await flushPromises()

    await card.find('[aria-label="Abertura Segunda"]').setValue('07:00')
    await card.findAll('button').find(button => button.text() === 'Salvar horários')?.trigger('click')
    await flushPromises()

    expect(update.mock.calls[0]?.[0][0]).toMatchObject({ weekday: 1, opensAt: '07:00' })
    expect(card.find('[role="alert"]').text()).toBe('O fechamento precisa ser depois da abertura.')

    await card.findAll('button').find(button => button.text() === 'Salvar horários')?.trigger('click')
    await flushPromises()

    expect(useToast().message.value).toBe('Horários de funcionamento atualizados.')
  })

  it('is read-only for the reception', async () => {
    seedSession(receptionSession)

    const card = await mountSuspended(HoursCard)
    await flushPromises()

    expect(card.text()).not.toContain('Salvar horários')
    expect(card.find('[aria-label="Abertura Segunda"]').attributes('disabled')).toBeDefined()
  })
})
