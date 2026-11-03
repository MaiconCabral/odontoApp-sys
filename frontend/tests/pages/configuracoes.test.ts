import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import SettingsPage from '~/pages/app/configuracoes.vue'
import { adminSession } from '../fixtures/session'
import { clinicFixture, preferencesFixture } from '../fixtures/settings'
import { seedSession } from '../utils/session'

function openTab(page: VueWrapper, label: string) {
  const tab = page.findAll('aside button').find(item => item.text().startsWith(label))

  if (!tab) {
    throw new Error(`Tab not found: ${label}`)
  }

  return tab.trigger('click')
}

describe('Configurações', () => {
  beforeEach(() => {
    clearNuxtData('team')
  })

  it('shows the team tab', async () => {
    const page = await mountSuspended(SettingsPage)

    await openTab(page, 'Equipe')
    await flushPromises()

    expect(page.find('aside [aria-current="true"]').text()).toBe('Equipe')
    expect(page.find('[data-testid="clinic-tab"]').attributes('style')).toContain('display: none')
    expect(page.text()).toContain('Dr. Felipe Rocha')
    expect(page.text()).toContain('Odontologia estética')
  })

  it('counts the active professionals against the plan limit', async () => {
    const page = await mountSuspended(SettingsPage)

    await openTab(page, 'Equipe')
    await flushPromises()

    // Carlos (admin who attends) and Ana count; Felipe is inactive.
    expect(page.find('#team-plan-limit').text()).toBe('2 de 5 profissionais')
  })

  it('warns when the plan is full but keeps adding reception open', async () => {
    seedSession({ ...adminSession, plan: { ...adminSession.plan, maxProfessionals: 2 } })
    const page = await mountSuspended(SettingsPage)

    await openTab(page, 'Equipe')
    await flushPromises()

    const add = page.findAll('button').find(item => item.text() === 'Novo usuário')

    expect(page.find('#team-plan-limit').text()).toContain('limite atingido, só é possível adicionar recepção')
    expect(add?.attributes('disabled')).toBeUndefined()
    expect(add?.attributes('aria-describedby')).toBe('team-plan-limit')
  })

  it('shows the opening hours tab', async () => {
    const page = await mountSuspended(SettingsPage)

    await openTab(page, 'Horários')

    expect(page.text()).toContain('Horários de funcionamento')
    await flushPromises()
    expect(page.text()).toContain('Sábado')
    expect(page.text()).toContain('Fechado')
  })

  it('shows the subscription tab with the plan of the clinic', async () => {
    const page = await mountSuspended(SettingsPage)

    await openTab(page, 'Assinatura')

    for (const text of ['Plano Pro', 'Seu trial termina em 14 dias.', 'Trial ativo', 'R$ 249', '/mês', 'Até 5 profissionais', 'Lembretes automáticos', 'Gerenciar assinatura']) {
      expect(page.text()).toContain(text)
    }
  })

  it('opens the plans from the subscription card', async () => {
    const page = await mountSuspended(SettingsPage)

    await openTab(page, 'Assinatura')
    await page.findAll('button').find(item => item.text() === 'Gerenciar assinatura')?.trigger('click')

    const plans = page.find('[aria-label="Planos disponíveis"]')

    expect(plans.text()).toContain('Free')
    expect(plans.text()).toContain('R$ 599')
    expect(plans.text()).toContain('Profissionais ilimitados')
    expect(plans.find('[aria-current="true"]').text()).toContain('Pro')
  })

  it('keeps unsaved clinic edits when switching tabs', async () => {
    const page = await mountSuspended(SettingsPage)

    await page.find('#clinic-name').setValue('Outra clínica')
    await openTab(page, 'Horários')
    await openTab(page, 'Clínica')

    expect(page.find('[data-testid="clinic-tab"]').attributes('style') ?? '').not.toContain('display: none')
    expect((page.find('#clinic-name').element as HTMLInputElement).value).toBe('Outra clínica')
  })

  it('renders the header without an action button', async () => {
    const page = await mountSuspended(SettingsPage)

    expect(page.find('h1').text()).toBe('Configurações')
    expect(page.text()).not.toContain('Novo')
    expect(page.text()).toContain('Salvar alterações')
  })

  it('marks the current section and shows the plan badge', async () => {
    const page = await mountSuspended(SettingsPage)

    expect(page.find('aside [aria-current="true"]').text()).toBe('Clínica')
    expect(page.findAll('aside button').at(-1)?.text()).toContain('Assinatura')
    expect(page.findAll('aside button').at(-1)?.find('span').text()).toBe('Pro')
  })

  it('links every field to its label with the clinic data', async () => {
    const page = await mountSuspended(SettingsPage)
    const expected = {
      'Nome da clínica': clinicFixture.name,
      'Unidade': clinicFixture.unit,
      'CNPJ': clinicFixture.cnpj,
      'Telefone principal': clinicFixture.phone,
      'E-mail de contato': clinicFixture.email,
      'Endereço': clinicFixture.address,
    }

    for (const [label, value] of Object.entries(expected)) {
      const element = page.findAll('label').find(item => item.text() === label)
      const input = page.find(`#${element?.attributes('for')}`)

      expect((input.element as HTMLInputElement).value).toBe(value)
    }
  })

  it('edits the form without changing the source data', async () => {
    const page = await mountSuspended(SettingsPage)
    const input = page.find('#clinic-name')

    await input.setValue('Outra clínica')

    expect((input.element as HTMLInputElement).value).toBe('Outra clínica')
    expect(clinicFixture.name).toBe('Sorriso Perfeito Odontologia')
  })

  it('saves the clinic and updates the session', async () => {
    const update = vi.spyOn(clinicService, 'update').mockResolvedValue({ ...clinicFixture, name: 'Clínica Nova' })
    const page = await mountSuspended(SettingsPage)

    await page.find('#clinic-name').setValue('Clínica Nova')
    await page.find('[data-testid="clinic-tab"] form').trigger('submit')
    await flushPromises()

    expect(update).toHaveBeenCalledWith(expect.objectContaining({ name: 'Clínica Nova' }))
    expect(useTenantStore().tenant?.name).toBe('Clínica Nova')
    expect(useToast().message.value).toBe('Dados da clínica atualizados.')
  })

  it('shows the errors of the API on the clinic fields', async () => {
    vi.spyOn(clinicService, 'update').mockRejectedValue({ data: { message: 'Inválido', errors: { cnpj: ['Informe um CNPJ válido.'] } } })

    const page = await mountSuspended(SettingsPage)

    await page.find('[data-testid="clinic-tab"] form').trigger('submit')
    await flushPromises()

    expect(page.find('#clinic-cnpj').attributes('aria-invalid')).toBe('true')
    expect(page.text()).toContain('Informe um CNPJ válido.')
  })

  it('shows the preferences saved by the clinic', async () => {
    const page = await mountSuspended(SettingsPage)
    const switches = page.findAll('[role="switch"]')

    expect(page.text()).toContain('Lembrete 24h antes')
    expect(switches.map(item => item.attributes('aria-checked'))).toEqual(['true', 'false', 'true'])
  })

  it('saves a preference as soon as it is toggled', async () => {
    const update = vi.spyOn(settingsService, 'updatePreferences').mockResolvedValue({ ...preferencesFixture, reminder24h: false })
    const page = await mountSuspended(SettingsPage)
    const toggle = page.find('[role="switch"]')

    await toggle.trigger('click')
    await flushPromises()

    expect(toggle.attributes('aria-checked')).toBe('false')
    expect(update).toHaveBeenCalledWith({ autoConfirmation: false, waitingList: true, reminder24h: false })
    expect(useToast().message.value).toBe('Preferências salvas.')
  })

  it('puts the preference back when saving fails', async () => {
    vi.spyOn(settingsService, 'updatePreferences').mockRejectedValue(new Error('offline'))
    const page = await mountSuspended(SettingsPage)
    const toggle = page.find('[role="switch"]')

    await toggle.trigger('click')
    await flushPromises()

    expect(toggle.attributes('aria-checked')).toBe('true')
    expect(useToast().message.value).toBe('Não foi possível salvar a preferência. Tente novamente.')
  })
})
