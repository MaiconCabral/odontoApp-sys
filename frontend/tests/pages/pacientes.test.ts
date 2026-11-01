import { mountSuspended } from '@nuxt/test-utils/runtime'
import { describe, expect, it } from 'vitest'
import PatientsPage from '~/pages/app/[tenant]/pacientes.vue'

describe('Pacientes', () => {
  it('renders the header and every patient', async () => {
    const page = await mountSuspended(PatientsPage)

    expect(page.find('h1').text()).toBe('Pacientes')
    expect(page.text()).toContain('Novo paciente')
    expect(page.findAll('[role="row"]')).toHaveLength(6)
    expect(page.text()).toContain('Mostrando 5 de 248 pacientes')

    for (const text of ['Mariana Costa', '(11) 98901-6288', '06 out, 2024', 'Bradesco Saúde']) {
      expect(page.text()).toContain(text)
    }
  })

  it('distinguishes private patients from insurance plans', async () => {
    const page = await mountSuspended(PatientsPage)
    const tag = (text: string) => page.findAll('[role="cell"] span').find(item => item.text() === text)

    expect(tag('Particular')?.classes()).toContain('text-[#aa7830]')
    expect(tag('Amil')?.classes()).toContain('text-[#378b70]')
  })

  it('filters the rows while searching and updates the count', async () => {
    const page = await mountSuspended(PatientsPage)

    await page.find('input').setValue('99127')

    expect(page.text()).toContain('Rafael Oliveira')
    expect(page.text()).not.toContain('Mariana Costa')
    expect(page.text()).toContain('Mostrando 1 de 248 pacientes')
  })

  it('shows the empty state when nothing matches', async () => {
    const page = await mountSuspended(PatientsPage)

    await page.find('input').setValue('sem resultado')

    expect(page.text()).toContain('Nenhum paciente encontrado.')
    expect(page.text()).toContain('Mostrando 0 de 248 pacientes')
  })
})
