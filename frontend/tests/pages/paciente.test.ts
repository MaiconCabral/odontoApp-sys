import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import PatientPage from '~/pages/app/pacientes/[id].vue'
import { professionalSession, receptionSession } from '../fixtures/session'
import { seedSession } from '../utils/session'

const mountPage = async () => {
  const page = await mountSuspended(PatientPage, { route: '/app/pacientes/patient-1' })

  await flushPromises()

  return page
}

// Opens a tab of the patient page by its label.
async function openTab(page: VueWrapper, label: string) {
  await page.findAll('[role="tab"]').find(tab => tab.text() === label)?.trigger('click')
  await flushPromises()
}

describe('patient page', () => {
  it('shows the patient and the appointments', async () => {
    const page = await mountPage()

    expect(page.find('h1').text()).toBe('Camila Ferreira')
    expect(page.text()).toContain('Bradesco Saúde')
    expect(page.text()).toContain('Avaliação · Dra. Ana Beatriz')
    expect(page.find('[role="tab"][aria-selected="true"]').text()).toBe('Consultas')
  })

  it('hides the clinical tabs from the reception', async () => {
    seedSession(receptionSession)
    const page = await mountPage()

    expect(page.findAll('[role="tab"]').map(tab => tab.text())).toEqual(['Consultas'])
    expect(page.text()).toContain('Adicionar à lista de espera')
  })

  it('shows the clinical tabs to a professional, without editing the registration', async () => {
    seedSession(professionalSession)
    const page = await mountPage()

    expect(page.findAll('[role="tab"]').map(tab => tab.text())).toEqual(['Consultas', 'Prontuário', 'Odontograma', 'Anexos'])
    expect(page.text()).not.toContain('Editar')
  })

  it('says when the patient is not open to the user', async () => {
    vi.spyOn(patientsService, 'get').mockRejectedValue({ status: 403 })
    const page = await mountPage()

    expect(page.find('[role="alert"]').text()).toContain('Você não tem acesso a este paciente.')
  })

  it('appends a note and offers a correction of an earlier one', async () => {
    const addNote = vi.spyOn(clinicalService, 'addNote').mockResolvedValue({
      id: 'n2', body: 'Correção: 26.', author: 'Dr. Carlos Mendes', appointment: null, amendsId: 'n1', createdAt: '2024-10-07T10:00:00-03:00',
    })
    const page = await mountPage()

    await openTab(page, 'Prontuário')
    expect(page.text()).toContain('Sensibilidade no 16.')

    await page.findAll('button').find(item => item.text() === 'Corrigir')?.trigger('click')
    expect(page.text()).toContain('Corrigindo a anotação de')

    await page.find('textarea').setValue('Correção: 26.')
    await page.find('form').trigger('submit')
    await flushPromises()

    expect(addNote).toHaveBeenCalledWith('patient-1', expect.objectContaining({ body: 'Correção: 26.', amendsId: 'n1', appointmentId: '42' }))
    expect(page.text()).toContain('Correção de uma anotação anterior')
  })

  it('records a tooth from a click on its surface', async () => {
    const record = vi.spyOn(clinicalService, 'recordTooth').mockResolvedValue(undefined as never)
    const page = await mountPage()

    await openTab(page, 'Odontograma')

    const tooth = page.find('[aria-label="Dente 16, cárie oclusal"]')

    expect(tooth.exists()).toBe(true)
    expect(page.find('[aria-label="Dente 36, ausente"]').exists()).toBe(true)

    // The top surface of an upper tooth is the vestibular one.
    await page.find('[aria-label="Dente 11, sem registros"] polygon').trigger('click')
    await page.find('form').trigger('submit')
    await flushPromises()

    expect(record).toHaveBeenCalledWith('patient-1', expect.objectContaining({ tooth: 11, surface: 'V', condition: 'caries' }))
  })

  it('chooses a tooth with the keyboard and asks for nothing else on whole-tooth conditions', async () => {
    const record = vi.spyOn(clinicalService, 'recordTooth').mockResolvedValue(undefined as never)
    const page = await mountPage()

    await openTab(page, 'Odontograma')
    await page.find('[aria-label="Dente 21, sem registros"]').trigger('click')
    await page.findAll('select').at(0)?.setValue('implant')
    await page.find('form').trigger('submit')
    await flushPromises()

    expect(record).toHaveBeenCalledWith('patient-1', expect.objectContaining({ tooth: 21, surface: null, condition: 'implant' }))
  })

  it('lists, uploads and refuses files before sending', async () => {
    const upload = vi.spyOn(clinicalService, 'upload').mockResolvedValue({
      id: 'a2', name: 'foto.png', mime: 'image/png', sizeBytes: 2048, uploader: 'Dr. Carlos Mendes', createdAt: '2024-10-07T10:00:00-03:00', canDelete: true,
    })
    const page = await mountPage()

    await openTab(page, 'Anexos')
    expect(page.text()).toContain('raio-x.pdf')
    expect(page.find('[aria-label="Baixar raio-x.pdf"]').attributes('href')).toContain('/api/attachments/a1/download')

    const input = page.find('input[type="file"]')
    const files = [new File(['x'], 'foto.png', { type: 'image/png' }), new File(['x'], 'virus.exe', { type: 'application/x-msdownload' })]

    Object.defineProperty(input.element, 'files', { value: files })
    await input.trigger('change')
    await flushPromises()

    expect(upload).toHaveBeenCalledTimes(1)
    expect(page.text()).toContain('foto.png')
    expect(page.find('[role="alert"]').text()).toContain('virus.exe: Envie um PDF ou uma imagem')
  })
})
