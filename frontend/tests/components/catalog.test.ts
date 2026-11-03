import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import ProcedureModal from '~/components/settings/ProcedureModal.vue'
import ProceduresCard from '~/components/settings/ProceduresCard.vue'
import RoomModal from '~/components/settings/RoomModal.vue'
import RoomsCard from '~/components/settings/RoomsCard.vue'
import { proceduresFixture, roomsFixture } from '../fixtures/agenda'

// Control linked to a label.
function fieldByLabel(root: VueWrapper, label: string) {
  const element = root.findAll('label').find(item => item.text() === label)

  return root.find(`[id="${element?.attributes('for')}"]`)
}

// Validation error as the services return it.
function validationError(errors: Record<string, string[]>) {
  return Object.assign(new Error('invalid'), { data: { message: 'Inválido', errors } })
}

describe('SettingsProceduresCard', () => {
  beforeEach(() => {
    clearNuxtData('procedures')
    useToast().hideToast()
  })

  it('lists the procedures with length, price and status', async () => {
    const card = await mountSuspended(ProceduresCard)
    await flushPromises()

    expect(card.text()).toContain('Clareamento')
    expect(card.text()).toMatch(/90 min · R\$\s450,00/)
    expect(card.text()).toContain('Inativo')
  })

  it('explains why a used procedure is kept', async () => {
    vi.spyOn(proceduresService, 'remove').mockRejectedValue(validationError({ procedure: ['Este procedimento já foi usado em consultas.'] }))

    const card = await mountSuspended(ProceduresCard)
    await flushPromises()

    await card.find('[aria-label="Excluir Avaliação"]').trigger('click')
    await card.find('[aria-label="Confirmar exclusão de Avaliação"]').trigger('click')
    await flushPromises()

    expect(useToast().message.value).toBe('Este procedimento já foi usado em consultas.')
  })
})

describe('SettingsProcedureModal', () => {
  it('validates the price before sending', async () => {
    const create = vi.spyOn(proceduresService, 'create')
    const modal = await mountSuspended(ProcedureModal, { props: { procedure: null } })

    await fieldByLabel(modal, 'Nome').setValue('Clareamento')
    await fieldByLabel(modal, 'Preço (R$)').setValue('abc')
    await modal.find('form').trigger('submit')
    await flushPromises()

    expect(modal.text()).toContain('Informe um preço válido')
    expect(create).not.toHaveBeenCalled()
  })

  it('creates a procedure with duration and price', async () => {
    const create = vi.spyOn(proceduresService, 'create').mockResolvedValue(proceduresFixture[1]!)
    const modal = await mountSuspended(ProcedureModal, { props: { procedure: null } })

    await fieldByLabel(modal, 'Nome').setValue('Clareamento')
    await fieldByLabel(modal, 'Duração').setValue('90')
    await fieldByLabel(modal, 'Preço (R$)').setValue('450,00')
    await modal.find('form').trigger('submit')
    await flushPromises()

    expect(create).toHaveBeenCalledWith({ name: 'Clareamento', durationMinutes: 90, price: '450,00', active: true })
    expect(modal.emitted('saved')?.[0]?.[0]).toBe('Clareamento foi adicionado.')
  })

  it('shows the API errors on the fields', async () => {
    vi.spyOn(proceduresService, 'update').mockRejectedValue(validationError({ name: ['Já existe um procedimento ativo com este nome.'] }))
    const modal = await mountSuspended(ProcedureModal, { props: { procedure: proceduresFixture[1]! } })

    expect((fieldByLabel(modal, 'Preço (R$)').element as HTMLInputElement).value).toBe('450,00')

    await modal.find('form').trigger('submit')
    await flushPromises()

    expect(modal.text()).toContain('Já existe um procedimento ativo com este nome.')
  })
})

describe('SettingsRoomsCard and RoomModal', () => {
  beforeEach(() => {
    clearNuxtData('rooms')
  })

  it('lists the rooms and marks the inactive ones', async () => {
    const card = await mountSuspended(RoomsCard)
    await flushPromises()

    expect(card.text()).toContain('Sala 01 · Consultório')
    expect(card.text()).toContain('Inativa')
  })

  it('deactivates a room', async () => {
    const update = vi.spyOn(roomsService, 'update').mockResolvedValue({ ...roomsFixture[0]!, active: false })
    const modal = await mountSuspended(RoomModal, { props: { room: roomsFixture[0]! } })

    await modal.find('input[type="checkbox"]').setValue(false)
    await modal.find('form').trigger('submit')
    await flushPromises()

    expect(update).toHaveBeenCalledWith('room-1', { name: 'Sala 01 · Consultório', active: false })
  })
})
