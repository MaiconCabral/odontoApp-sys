import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import PatientsPage from '~/pages/app/pacientes/index.vue'

describe('Pacientes', () => {
  it('renders the patients of the page and the total', async () => {
    const page = await mountSuspended(PatientsPage)

    expect(page.find('h1').text()).toBe('Pacientes')
    expect(page.text()).toContain('Novo paciente')
    expect(page.findAll('[role="row"]')).toHaveLength(3)
    expect(page.text()).toContain('Mostrando 2 de 42 pacientes')

    for (const text of ['Camila Ferreira', '000.000.000-03', '(11) 99127-7844', '06/10/2024', 'Bradesco Saúde', 'Sem consultas']) {
      expect(page.text()).toContain(text)
    }
  })

  it('distinguishes private patients from insurance plans', async () => {
    const page = await mountSuspended(PatientsPage)
    const tag = (text: string) => page.findAll('[role="cell"] span').find(item => item.text() === text)

    expect(tag('Particular')?.classes()).toContain('text-tone-amber-text')
    expect(tag('Bradesco Saúde')?.classes()).toContain('text-tone-green-text')
  })

  it('sends the search to the API after a pause and returns to the first page', async () => {
    vi.useFakeTimers()
    usePatientsQuery().value = { search: '', page: 2 }

    const page = await mountSuspended(PatientsPage)

    await page.find('input').setValue('99127')
    expect(usePatientsQuery().value.search).toBe('')

    await vi.advanceTimersByTimeAsync(300)
    vi.useRealTimers()

    expect(usePatientsQuery().value).toEqual({ search: '99127', page: 1 })
  })

  it('moves between pages', async () => {
    const page = await mountSuspended(PatientsPage)

    expect(page.find('[aria-label="Página anterior"]').attributes('disabled')).toBeDefined()

    await page.find('[aria-label="Próxima página"]').trigger('click')

    expect(usePatientsQuery().value.page).toBe(2)
  })

  it('opens the patient to edit and deletes after a second click', async () => {
    const remove = vi.spyOn(patientsService, 'remove').mockRejectedValue({ data: { message: 'Inválido', errors: { patient: ['Este paciente tem consultas registradas e não pode ser excluído.'] } } })
    const page = await mountSuspended(PatientsPage)

    await page.find('[aria-label="Editar Camila Ferreira"]').trigger('click')
    expect(useActiveModal().activeModal.value).toBe('patient')

    await page.find('[aria-label="Excluir Camila Ferreira"]').trigger('click')
    expect(remove).not.toHaveBeenCalled()

    await page.find('[aria-label="Confirmar exclusão de Camila Ferreira"]').trigger('click')
    await flushPromises()

    expect(remove).toHaveBeenCalledWith('patient-1')
    expect(useToast().message.value).toBe('Este paciente tem consultas registradas e não pode ser excluído.')
  })
})
