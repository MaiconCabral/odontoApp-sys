import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import BlockModal from '~/components/agenda/BlockModal.vue'
import DetailsModal from '~/components/appointments/DetailsModal.vue'
import AppointmentModal from '~/components/appointments/NewModal.vue'
import PatientModal from '~/components/patients/NewModal.vue'
import AgendaPage from '~/pages/app/agenda.vue'
import PatientsPage from '~/pages/app/pacientes/index.vue'
import { agendaMock, appointmentDetails, patientFixture, requestDetails } from '../fixtures/agenda'

// Control linked to a label.
function fieldByLabel(root: VueWrapper, label: string) {
  const element = root.findAll('label').find(item => item.text() === label)

  return root.find(`[id="${element?.attributes('for')}"]`)
}

// Button by its text.
function button(root: VueWrapper, text: string) {
  return root.findAll('button').find(item => item.text() === text)
}

// Types part of a name and picks the first patient found. The search waits for a pause in typing.
async function choosePatient(modal: VueWrapper) {
  await fieldByLabel(modal, 'Paciente').setValue('Cam')
  await new Promise(resolve => setTimeout(resolve, 300))
  await flushPromises()
  await modal.find('[role="option"] button').trigger('click')
}

// Validation error as the services return it, with field names in camelCase.
function validationError(errors: Record<string, string[]>) {
  return Object.assign(new Error('invalid'), { data: { message: 'Inválido', errors } })
}

describe('modals', () => {
  beforeEach(() => {
    useActiveModal().openModal('appointment')
    useToast().hideToast()
  })

  it('opens the modals from the page header buttons', async () => {
    const { activeModal, closeModal } = useActiveModal()

    closeModal()

    const patients = await mountSuspended(PatientsPage)
    await button(patients, 'Novo paciente')?.trigger('click')
    expect(activeModal.value).toBe('patient')

    closeModal()

    const agenda = await mountSuspended(AgendaPage)
    await button(agenda, 'Novo agendamento')?.trigger('click')
    expect(activeModal.value).toBe('appointment')
  })
})

describe('AppointmentsNewModal', () => {
  beforeEach(() => {
    useActiveModal().openModal('appointment')
  })

  it('starts with the agenda defaults and the free times of the day', async () => {
    const modal = await mountSuspended(AppointmentModal)
    await flushPromises()

    expect((fieldByLabel(modal, 'Procedimento').element as HTMLSelectElement).value).toBe('1')
    expect((fieldByLabel(modal, 'Profissional').element as HTMLSelectElement).value).toBe('carlos')
    expect((fieldByLabel(modal, 'Data').element as HTMLInputElement).value).toBe(agendaMock.today)
    expect(fieldByLabel(modal, 'Horário').findAll('option').map(option => option.text())).toEqual(['Escolha um horário livre', '15:00', '15:30'])
  })

  it('blocks the appointment without a patient or a time', async () => {
    const create = vi.spyOn(appointmentsService, 'create')
    const modal = await mountSuspended(AppointmentModal)

    await modal.find('form').trigger('submit')
    await flushPromises()

    expect(modal.text()).toContain('Selecione um paciente cadastrado.')
    expect(modal.text()).toContain('Escolha um horário livre.')
    expect(create).not.toHaveBeenCalled()
  })

  it('finds a patient, books the time and refreshes the agenda', async () => {
    const search = vi.spyOn(patientsService, 'list').mockResolvedValue({ patients: [patientFixture], total: 1, page: 1, lastPage: 1 })
    const create = vi.spyOn(appointmentsService, 'create').mockResolvedValue(appointmentDetails)
    const modal = await mountSuspended(AppointmentModal)

    await choosePatient(modal)

    expect(search).toHaveBeenCalledWith({ search: 'Cam', page: 1 })

    await fieldByLabel(modal, 'Horário').setValue('15:00')
    await modal.find('form').trigger('submit')
    await flushPromises()

    expect(create).toHaveBeenCalledWith(expect.objectContaining({ patientId: 'patient-1', professionalId: 'carlos', time: '15:00' }))
    expect(useToast().message.value).toBe('Consulta de Camila Ferreira marcada para Qui, 24/10 às 10:00.')
    expect(useActiveModal().activeModal.value).toBeNull()
  })

  it('shows on the time field why the API refused it', async () => {
    vi.spyOn(patientsService, 'list').mockResolvedValue({ patients: [patientFixture], total: 1, page: 1, lastPage: 1 })
    vi.spyOn(appointmentsService, 'create').mockRejectedValue(validationError({ startsAt: ['Este horário cai no intervalo de almoço da clínica.'] }))
    const modal = await mountSuspended(AppointmentModal)

    await choosePatient(modal)
    await fieldByLabel(modal, 'Horário').setValue('15:00')
    await modal.find('form').trigger('submit')
    await flushPromises()

    expect(modal.text()).toContain('Este horário cai no intervalo de almoço da clínica.')
    expect(useActiveModal().activeModal.value).toBe('appointment')
  })

  it('reschedules with the appointment itself left out of the conflicts', async () => {
    const availability = vi.spyOn(appointmentsService, 'availability')
    useActiveModal().openModal('appointment', { appointment: appointmentDetails })

    const modal = await mountSuspended(AppointmentModal)
    await flushPromises()

    expect(modal.text()).toContain('Remarcar consulta')
    expect(availability).toHaveBeenCalledWith(expect.objectContaining({ ignore: 'event-2', date: '2024-10-24' }))
    expect((fieldByLabel(modal, 'Paciente').element as HTMLInputElement).value).toBe('Camila Ferreira')
  })
})

describe('AppointmentsDetailsModal', () => {
  it('shows the appointment, its history and the actions of the role', async () => {
    vi.spyOn(appointmentsService, 'get').mockResolvedValue(appointmentDetails)
    useActiveModal().openModal('appointment-details', { id: 'event-2' })

    const modal = await mountSuspended(DetailsModal)

    expect(modal.text()).toContain('Camila Ferreira')
    expect(modal.text()).toContain('Agendado')
    expect(modal.text()).toContain('Paula Nunes')
    expect(modal.findAll('button').map(item => item.text())).toEqual(expect.arrayContaining(['Confirmar presença', 'Cancelar consulta', 'Remarcar']))
  })

  it('shows the messages sent to the patient', async () => {
    vi.spyOn(appointmentsService, 'get').mockResolvedValue(appointmentDetails)
    useActiveModal().openModal('appointment-details', { id: 'event-2' })

    const modal = await mountSuspended(DetailsModal)
    const notices = modal.find('[aria-label="Avisos ao paciente"]')

    expect(notices.text()).toContain('Consulta agendada')
    expect(notices.text()).toContain('Enviado')
    expect(notices.text()).not.toContain('avise por telefone')
  })

  it('warns when the patient has no e-mail', async () => {
    vi.spyOn(appointmentsService, 'get').mockResolvedValue(requestDetails)
    useActiveModal().openModal('appointment-details', { id: 'event-4' })

    const modal = await mountSuspended(DetailsModal)

    expect(modal.text()).toContain('Paciente sem e-mail: avise por telefone.')
    expect(modal.text()).toContain('Não enviado: paciente sem e-mail')
  })

  it('offers only rooms in use when accepting', async () => {
    vi.spyOn(appointmentsService, 'get').mockResolvedValue(requestDetails)
    useActiveModal().openModal('appointment-details', { id: 'event-4' })

    const modal = await mountSuspended(DetailsModal)
    await button(modal, 'Aceitar pedido')?.trigger('click')

    expect(fieldByLabel(modal, 'Sala da consulta').findAll('option').map(option => option.text())).not.toContain('Sala Antiga')
  })

  it('confirms an appointment in one click', async () => {
    vi.spyOn(appointmentsService, 'get').mockResolvedValue(appointmentDetails)
    const change = vi.spyOn(appointmentsService, 'changeStatus').mockResolvedValue({ ...appointmentDetails, status: 'confirmed', statusLabel: 'Confirmado', actions: ['cancel'] })
    useActiveModal().openModal('appointment-details', { id: 'event-2' })

    const modal = await mountSuspended(DetailsModal)
    await button(modal, 'Confirmar presença')?.trigger('click')
    await flushPromises()

    expect(change).toHaveBeenCalledWith('event-2', 'confirmed', '')
    expect(modal.text()).toContain('Confirmado')
    expect(useToast().message.value).toBe('Consulta de Camila Ferreira: Confirmado.')
  })

  it('asks the reason before cancelling and shows the API error', async () => {
    vi.spyOn(appointmentsService, 'get').mockResolvedValue(appointmentDetails)
    const change = vi.spyOn(appointmentsService, 'changeStatus').mockRejectedValue(validationError({ reason: ['Informe o motivo.'] }))
    useActiveModal().openModal('appointment-details', { id: 'event-2' })

    const modal = await mountSuspended(DetailsModal)
    await button(modal, 'Cancelar consulta')?.trigger('click')

    expect(change).not.toHaveBeenCalled()
    expect(fieldByLabel(modal, 'Motivo').exists()).toBe(true)

    await button(modal, 'Confirmar: cancelar consulta')?.trigger('click')
    await flushPromises()

    expect(change).toHaveBeenCalledWith('event-2', 'cancelled', '')
    expect(modal.find('[role="alert"]').text()).toBe('Informe o motivo.')
  })

  it('accepts a request in the chosen room', async () => {
    vi.spyOn(appointmentsService, 'get').mockResolvedValue(requestDetails)
    const accept = vi.spyOn(appointmentsService, 'accept').mockResolvedValue({ ...requestDetails, status: 'scheduled', statusLabel: 'Agendado', actions: [] })
    useActiveModal().openModal('appointment-details', { id: 'event-4' })

    const modal = await mountSuspended(DetailsModal)

    expect(modal.text()).toContain('Pedido online do paciente')

    await button(modal, 'Aceitar pedido')?.trigger('click')
    await fieldByLabel(modal, 'Sala da consulta').setValue('room-2')
    await button(modal, 'Aceitar e agendar')?.trigger('click')
    await flushPromises()

    expect(accept).toHaveBeenCalledWith('event-4', 'room-2')
  })

  it('opens the reschedule form with the appointment', async () => {
    vi.spyOn(appointmentsService, 'get').mockResolvedValue(appointmentDetails)
    useActiveModal().openModal('appointment-details', { id: 'event-2' })

    const modal = await mountSuspended(DetailsModal)
    await button(modal, 'Remarcar')?.trigger('click')

    expect(useActiveModal().activeModal.value).toBe('appointment')
    expect(useActiveModal().modalPayload.value).toEqual({ appointment: appointmentDetails })
  })
})

describe('AgendaBlockModal', () => {
  it('registers a clinic closure for the whole day', async () => {
    const create = vi.spyOn(scheduleService, 'createBlock').mockResolvedValue(agendaMock.blocks[0]!)
    useActiveModal().openModal('block')

    const modal = await mountSuspended(BlockModal)

    await modal.find('form').trigger('submit')
    expect(modal.text()).toContain('Informe o motivo')

    await fieldByLabel(modal, 'Motivo').setValue('Feriado municipal')
    await modal.find('form').trigger('submit')
    await flushPromises()

    expect(create).toHaveBeenCalledWith({ professionalId: '', startsAt: '2024-10-24 00:00', endsAt: '2024-10-24 23:59', reason: 'Feriado municipal' })
    expect(useActiveModal().activeModal.value).toBeNull()
  })

  it('deletes an existing block', async () => {
    const remove = vi.spyOn(scheduleService, 'removeBlock').mockResolvedValue()
    useActiveModal().openModal('block', { block: agendaMock.blocks[0] })

    const modal = await mountSuspended(BlockModal)

    expect(modal.text()).toContain('Clínica inteira')
    await button(modal, 'Excluir bloqueio')?.trigger('click')
    await flushPromises()

    expect(remove).toHaveBeenCalledWith('block-1')
  })
})

describe('PatientsNewModal', () => {
  it('validates and registers a new patient', async () => {
    const create = vi.spyOn(patientsService, 'create').mockResolvedValue({ ...patientFixture, name: 'Ana Souza' })
    useActiveModal().openModal('patient')

    const modal = await mountSuspended(PatientModal)

    await modal.find('form').trigger('submit')
    expect(modal.text()).toContain('Informe o nome completo.')
    expect(create).not.toHaveBeenCalled()

    await fieldByLabel(modal, 'Nome completo').setValue('Ana Souza')
    await fieldByLabel(modal, 'CPF (opcional)').setValue('12345678901')
    await fieldByLabel(modal, 'Telefone').setValue('11912345678')
    await modal.find('form').trigger('submit')
    await flushPromises()

    expect(create).toHaveBeenCalledWith(expect.objectContaining({ name: 'Ana Souza', cpf: '123.456.789-01', phone: '(11) 91234-5678' }))
    expect(useToast().message.value).toBe('Cadastro de Ana Souza concluído.')
    expect(useActiveModal().activeModal.value).toBeNull()
  })

  it('shows a repeated CPF on its field', async () => {
    vi.spyOn(patientsService, 'update').mockRejectedValue(validationError({ cpf: ['Já existe um paciente com este CPF.'] }))
    useActiveModal().openModal('patient', { patient: patientFixture })

    const modal = await mountSuspended(PatientModal)

    expect(modal.text()).toContain('Editar paciente')
    await modal.find('form').trigger('submit')
    await flushPromises()

    expect(fieldByLabel(modal, 'CPF (opcional)').attributes('aria-invalid')).toBe('true')
    expect(modal.text()).toContain('Já existe um paciente com este CPF.')
  })
})
