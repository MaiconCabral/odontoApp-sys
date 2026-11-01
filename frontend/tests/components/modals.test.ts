import { mountSuspended } from '@nuxt/test-utils/runtime'
import type { VueWrapper } from '@vue/test-utils'
import { beforeEach, describe, expect, it } from 'vitest'
import AppointmentModal from '~/components/appointments/NewModal.vue'
import PatientModal from '~/components/patients/NewModal.vue'
import { agendaMock } from '~/mocks/agenda'
import { patientsMock } from '~/mocks/patients'
import AgendaPage from '~/pages/app/[tenant]/agenda.vue'
import PatientsPage from '~/pages/app/[tenant]/pacientes.vue'

function fieldByLabel(root: VueWrapper, label: string) {
  const element = root.findAll('label').find(item => item.text() === label)

  return root.find(`[id="${element?.attributes('for')}"]`)
}

describe('modals', () => {
  beforeEach(() => {
    useSessionAgendaEvents().value = []
    useSessionPatients().value = []
    useActiveModal().openModal('appointment')
    useToast().hideToast()
  })

  it('opens the modals from the page header buttons', async () => {
    const { activeModal, closeModal } = useActiveModal()

    closeModal()

    const patients = await mountSuspended(PatientsPage)
    await patients.findAll('button').find(button => button.text() === 'Novo paciente')?.trigger('click')
    expect(activeModal.value).toBe('patient')

    closeModal()

    const agenda = await mountSuspended(AgendaPage)
    await agenda.findAll('button').find(button => button.text() === 'Novo agendamento')?.trigger('click')
    expect(activeModal.value).toBe('appointment')
  })

  it('starts the appointment form with the layout defaults', async () => {
    const modal = await mountSuspended(AppointmentModal)

    expect((fieldByLabel(modal, 'Procedimento').element as HTMLSelectElement).value).toBe('Avaliação')
    expect((fieldByLabel(modal, 'Profissional').element as HTMLSelectElement).value).toBe('carlos')
    expect((fieldByLabel(modal, 'Data').element as HTMLInputElement).value).toBe(agendaMock.today)
    expect((fieldByLabel(modal, 'Horário').element as HTMLSelectElement).value).toBe('15:00')
    expect(modal.text()).toContain('Disponível')
  })

  it('blocks the appointment without a registered patient', async () => {
    const modal = await mountSuspended(AppointmentModal)

    await modal.find('form').trigger('submit')

    expect(modal.text()).toContain('Selecione um paciente cadastrado.')
    expect(useSessionAgendaEvents().value).toHaveLength(0)
    expect(useActiveModal().activeModal.value).toBe('appointment')
  })

  it('confirms the appointment, shows it on the agenda and closes', async () => {
    const modal = await mountSuspended(AppointmentModal)

    await fieldByLabel(modal, 'Paciente').setValue('Mariana Costa')
    await modal.find('form').trigger('submit')

    expect(useSessionAgendaEvents().value[0]).toMatchObject({ patientName: 'Mariana Costa', date: '2024-10-24', hour: '15:00' })
    expect(useToast().message.value).toContain('24/10/2024 às 15:00')
    expect(useActiveModal().activeModal.value).toBeNull()
    expect(agendaMock.events).toHaveLength(3)

    const agenda = await mountSuspended(AgendaPage)

    expect(agenda.text()).toContain('Mariana Costa')
  })

  it('marks an occupied room for the selected slot', async () => {
    const modal = await mountSuspended(AppointmentModal)

    await fieldByLabel(modal, 'Horário').setValue('10:00')

    expect(modal.text()).toContain('Ocupada')
    expect(modal.findAll<HTMLInputElement>('input[type="radio"]')[0]?.element.disabled).toBe(true)
  })

  it('validates and registers a new patient', async () => {
    useActiveModal().openModal('patient')

    const modal = await mountSuspended(PatientModal)

    await modal.find('form').trigger('submit')
    expect(modal.text()).toContain('Informe o nome do paciente.')
    expect(useSessionPatients().value).toHaveLength(0)

    await fieldByLabel(modal, 'Nome completo').setValue('Ana Souza')
    await fieldByLabel(modal, 'CPF').setValue('12345678901')
    await fieldByLabel(modal, 'Telefone').setValue('11912345678')
    await modal.find('form').trigger('submit')

    expect(useSessionPatients().value[0]).toMatchObject({
      initials: 'AS',
      cpf: '123.456.789-01',
      phone: '(11) 91234-5678',
      isPrivate: true,
    })
    expect(useActiveModal().activeModal.value).toBeNull()
    expect(patientsMock.patients).toHaveLength(5)

    const patients = await mountSuspended(PatientsPage)

    expect(patients.text()).toContain('Ana Souza')
    expect(patients.text()).toContain('Mostrando 6 de 249 pacientes')
  })
})
