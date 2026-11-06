import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import AttachmentsTab from '~/components/patients/AttachmentsTab.vue'
import RecordTab from '~/components/patients/RecordTab.vue'
import type { ClinicalNote, PatientAttachment } from '~/types/clinical'

const note = (id: string): ClinicalNote => ({ id, body: `Anotação ${id}`, author: 'Ana', appointment: null, amendsId: null, createdAt: '2024-10-06T09:40:00-03:00' })
const file = (id: string): PatientAttachment => ({ id, name: `exame-${id}.pdf`, mime: 'application/pdf', sizeBytes: 100, uploader: 'Ana', createdAt: '2024-10-06T09:45:00-03:00', canDelete: false })

// "Carregar mais" button of the list, if shown.
const moreButton = (root: VueWrapper) => root.findAll('button').find(item => item.text() === 'Carregar mais')

describe('medical record pages', () => {
  it('shows the first page and appends the next one on "Carregar mais"', async () => {
    const notes = vi.spyOn(clinicalService, 'notes')
      .mockResolvedValueOnce({ items: [note('2')], page: 1, lastPage: 2 })
      .mockResolvedValueOnce({ items: [note('1')], page: 2, lastPage: 2 })

    const tab = await mountSuspended(RecordTab, { props: { patientId: 'patient-1' } })
    await flushPromises()

    expect(tab.text()).toContain('Anotação 2')
    expect(tab.text()).not.toContain('Anotação 1')

    await moreButton(tab)!.trigger('click')
    await flushPromises()

    expect(notes).toHaveBeenLastCalledWith('patient-1', 2)
    expect(tab.text()).toContain('Anotação 2')
    expect(tab.text()).toContain('Anotação 1')
    expect(moreButton(tab)).toBeUndefined()
  })

  it('hides "Carregar mais" on a single page', async () => {
    vi.spyOn(clinicalService, 'notes').mockResolvedValue({ items: [note('1')], page: 1, lastPage: 1 })

    const tab = await mountSuspended(RecordTab, { props: { patientId: 'patient-1' } })
    await flushPromises()

    expect(moreButton(tab)).toBeUndefined()
  })

  it('offers a retry when the notes fail to load', async () => {
    vi.spyOn(clinicalService, 'notes').mockRejectedValueOnce(new Error('offline')).mockResolvedValue({ items: [note('1')], page: 1, lastPage: 1 })

    const tab = await mountSuspended(RecordTab, { props: { patientId: 'patient-1' } })
    await flushPromises()

    expect(tab.find('[role="alert"]').text()).toContain('Não foi possível carregar o prontuário.')

    await tab.findAll('button').find(item => item.text() === 'Tentar novamente')!.trigger('click')
    await flushPromises()

    expect(tab.text()).toContain('Anotação 1')
  })
})

describe('attachment pages', () => {
  it('appends the next page of files', async () => {
    vi.spyOn(clinicalService, 'attachments')
      .mockResolvedValueOnce({ items: [file('2')], page: 1, lastPage: 2 })
      .mockResolvedValueOnce({ items: [file('1')], page: 2, lastPage: 2 })

    const tab = await mountSuspended(AttachmentsTab, { props: { patientId: 'patient-1' } })
    await flushPromises()

    expect(tab.text()).toContain('exame-2.pdf')

    await moreButton(tab)!.trigger('click')
    await flushPromises()

    expect(tab.text()).toContain('exame-1.pdf')
    expect(moreButton(tab)).toBeUndefined()
  })

  it('asks the API for the page in the query, and the first page without one', async () => {
    const api = vi.mocked($fetch)

    await clinicalService.attachments('patient-1')
    await clinicalService.attachments('patient-1', 3)

    expect(api.mock.calls.at(-2)?.[1]).toMatchObject({ query: undefined })
    expect(api.mock.calls.at(-1)?.[1]).toMatchObject({ query: { page: 3 } })
  })
})
