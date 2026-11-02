import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import TeamCard from '~/components/settings/TeamCard.vue'
import UserModal from '~/components/settings/UserModal.vue'
import { teamFixture } from '../fixtures/settings'

function fieldByLabel(root: VueWrapper, label: string) {
  const element = root.findAll('label').find(item => item.text() === label)

  return root.find(`[id="${element?.attributes('for')}"]`)
}

function validationError(errors: Record<string, string[]>) {
  return Object.assign(new Error('invalid'), { data: { message: 'Inválido', errors } })
}

describe('TeamCard', () => {
  beforeEach(() => {
    useToast().hideToast()
    clearNuxtData('team')
  })

  it('lists the team with its roles and marks inactive users', async () => {
    const card = await mountSuspended(TeamCard)
    await flushPromises()

    expect(card.text()).toContain('Dra. Ana Beatriz')
    expect(card.text()).toContain('Profissional · Odontologia estética')
    expect(card.text()).toContain('Inativo')
  })

  it('does not offer to delete the signed-in user', async () => {
    const card = await mountSuspended(TeamCard)
    await flushPromises()

    expect(card.find('[aria-label="Excluir Dr. Carlos Mendes"]').exists()).toBe(false)
    expect(card.find('[aria-label="Excluir Dra. Ana Beatriz"]').exists()).toBe(true)
  })

  it('asks for confirmation before deleting', async () => {
    const remove = vi.spyOn(usersService, 'remove').mockResolvedValue()
    const card = await mountSuspended(TeamCard)
    await flushPromises()

    await card.find('[aria-label="Excluir Dra. Ana Beatriz"]').trigger('click')
    expect(remove).not.toHaveBeenCalled()

    await card.find('[aria-label="Confirmar exclusão de Dra. Ana Beatriz"]').trigger('click')
    await flushPromises()

    expect(remove).toHaveBeenCalledWith('2')
    expect(useToast().message.value).toContain('removido')
  })

  it('explains why a user with appointments was kept', async () => {
    vi.spyOn(usersService, 'remove').mockRejectedValue(validationError({ user: ['Este usuário tem agendamentos.'] }))

    const card = await mountSuspended(TeamCard)
    await flushPromises()

    await card.find('[aria-label="Excluir Dra. Ana Beatriz"]').trigger('click')
    await card.find('[aria-label="Confirmar exclusão de Dra. Ana Beatriz"]').trigger('click')
    await flushPromises()

    expect(useToast().message.value).toBe('Este usuário tem agendamentos.')
  })

  it('shows an error and retries when the team fails to load', async () => {
    const list = vi.spyOn(usersService, 'list').mockRejectedValueOnce(new Error('offline'))
    const card = await mountSuspended(TeamCard)
    await flushPromises()

    expect(card.find('[role="alert"]').text()).toContain('Não foi possível')

    await card.find('[role="alert"] button').trigger('click')
    await flushPromises()

    expect(list).toHaveBeenCalled()
    expect(card.text()).toContain('Dra. Ana Beatriz')
  })
})

describe('UserModal', () => {
  it('validates before sending', async () => {
    const create = vi.spyOn(usersService, 'create')
    const modal = await mountSuspended(UserModal, { props: { user: null } })

    await modal.find('form').trigger('submit')
    await flushPromises()

    expect(modal.text()).toContain('Informe o nome completo.')
    expect(modal.text()).toContain('Informe um e-mail válido.')
    expect(create).not.toHaveBeenCalled()
  })

  it('creates a user', async () => {
    const create = vi.spyOn(usersService, 'create').mockResolvedValue(teamFixture[1]!)
    const modal = await mountSuspended(UserModal, { props: { user: null } })

    await fieldByLabel(modal, 'Nome completo').setValue('Nova Pessoa')
    await fieldByLabel(modal, 'E-mail').setValue('nova@sorriso.app')
    await modal.find('form').trigger('submit')
    await flushPromises()

    expect(create).toHaveBeenCalledWith(expect.objectContaining({ name: 'Nova Pessoa', email: 'nova@sorriso.app', role: 'professional' }))
    expect(modal.emitted('saved')?.[0]?.[0]).toContain('definir a senha')
  })

  it('updates an existing user', async () => {
    const update = vi.spyOn(usersService, 'update').mockResolvedValue(teamFixture[1]!)
    const modal = await mountSuspended(UserModal, { props: { user: teamFixture[1]! } })

    await fieldByLabel(modal, 'Nome completo').setValue('Ana Nova')
    await modal.find('form').trigger('submit')
    await flushPromises()

    expect(update).toHaveBeenCalledWith('2', expect.objectContaining({ name: 'Ana Nova', specialty: 'Odontologia estética' }))
  })

  it('shows a repeated e-mail on its field', async () => {
    vi.spyOn(usersService, 'create').mockRejectedValue(validationError({ email: ['Este e-mail já está em uso.'] }))

    const modal = await mountSuspended(UserModal, { props: { user: null } })

    await fieldByLabel(modal, 'Nome completo').setValue('Nova Pessoa')
    await fieldByLabel(modal, 'E-mail').setValue('ana@sorriso.app')
    await modal.find('form').trigger('submit')
    await flushPromises()

    expect(fieldByLabel(modal, 'E-mail').attributes('aria-invalid')).toBe('true')
    expect(modal.text()).toContain('Este e-mail já está em uso.')
  })

  it('shows the rules that protect administrators', async () => {
    vi.spyOn(usersService, 'update').mockRejectedValue(validationError({ user: ['A clínica precisa ter ao menos um administrador ativo.'] }))

    const modal = await mountSuspended(UserModal, { props: { user: teamFixture[1]! } })

    await modal.find('form').trigger('submit')
    await flushPromises()

    expect(modal.find('[role="alert"]').text()).toContain('administrador ativo')
  })

  it('does not let users change their own role or deactivate themselves', async () => {
    const modal = await mountSuspended(UserModal, { props: { user: teamFixture[0]! } })

    expect(fieldByLabel(modal, 'Perfil de acesso').attributes('disabled')).toBeDefined()
    expect(modal.text()).not.toContain('Conta ativa')
  })

  it('offers attending patients only to administrators', async () => {
    const modal = await mountSuspended(UserModal, { props: { user: null } })

    expect(modal.text()).not.toContain('Também atende pacientes')

    await fieldByLabel(modal, 'Perfil de acesso').setValue('admin')

    expect(modal.text()).toContain('Também atende pacientes')
  })
})
