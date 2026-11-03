import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import WaitlistPage from '~/pages/app/lista-espera.vue'

describe('waiting list', () => {
  it('lists the open entries and warns about patients without e-mail', async () => {
    const page = await mountSuspended(WaitlistPage)

    expect(page.text()).toContain('Camila Ferreira')
    expect(page.text()).toContain('Avaliação · Qualquer profissional')
    expect(page.text()).toContain('Sem e-mail: não recebe o aviso automático.')
  })

  it('marks an entry as booked with its current data', async () => {
    const update = vi.spyOn(waitlistService, 'update').mockResolvedValue(undefined as never)
    const page = await mountSuspended(WaitlistPage)

    await page.findAll('button').find(item => item.text() === 'Marcar agendado')?.trigger('click')
    await flushPromises()

    expect(update).toHaveBeenCalledWith('w1', expect.objectContaining({ patientId: 'patient-1', period: 'morning', status: 'booked' }))
  })

  it('asks a second click before removing', async () => {
    const remove = vi.spyOn(waitlistService, 'remove').mockResolvedValue()
    const page = await mountSuspended(WaitlistPage)
    const button = () => page.find('[aria-label*="Camila Ferreira da lista"], [aria-label^="Confirmar remoção"]')

    await button().trigger('click')
    expect(remove).not.toHaveBeenCalled()

    await button().trigger('click')
    expect(remove).toHaveBeenCalledWith('w1')
  })

  it('adds a patient found by the search', async () => {
    const create = vi.spyOn(waitlistService, 'create').mockResolvedValue(undefined as never)
    vi.useFakeTimers({ toFake: ['setTimeout', 'clearTimeout'] })
    const page = await mountSuspended(WaitlistPage)

    await page.findAll('button').find(item => item.text().includes('Adicionar paciente'))?.trigger('click')
    await flushPromises()
    await page.find('[role="combobox"]').setValue('Cam')
    vi.runAllTimers()
    await flushPromises()
    await page.find('[role="option"] button').trigger('click')
    await page.find('dialog form').trigger('submit')
    await flushPromises()
    vi.useRealTimers()

    expect(create).toHaveBeenCalledWith(expect.objectContaining({ patientId: 'patient-1', procedureId: '1', period: 'any' }))
  })
})
