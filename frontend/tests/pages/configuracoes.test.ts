import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { settingsMock } from '~/mocks/settings'
import SettingsPage from '~/pages/app/configuracoes.vue'
import { clinicFixture } from '../fixtures/settings'

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

  it('shows the opening hours tab', async () => {
    const page = await mountSuspended(SettingsPage)

    await openTab(page, 'Horários')

    expect(page.text()).toContain('Horários de funcionamento')
    expect(page.text()).toContain('08:00 às 12:00')
    expect(page.text()).toContain('Fechado')
  })

  it('shows the subscription tab with the trial from the workspace', async () => {
    const page = await mountSuspended(SettingsPage)

    await openTab(page, 'Assinatura')

    for (const text of ['Plano Pro', 'Seu trial termina em 14 dias.', 'Trial ativo', 'R$ 249', '/mês', 'Lembretes automáticos', 'Gerenciar assinatura']) {
      expect(page.text()).toContain(text)
    }
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

  it('toggles a preference without changing the source data', async () => {
    const page = await mountSuspended(SettingsPage)
    const toggle = page.find('[role="switch"]')

    expect(toggle.attributes('aria-checked')).toBe('true')

    await toggle.trigger('click')

    expect(toggle.attributes('aria-checked')).toBe('false')
    expect(settingsMock.preferences[0]?.enabled).toBe(true)
  })
})
