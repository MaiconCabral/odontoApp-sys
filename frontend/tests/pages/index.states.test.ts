import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import OverviewPage from '~/pages/index.vue'

const { useOverviewMock } = vi.hoisted(() => ({ useOverviewMock: vi.fn() }))

mockNuxtImport('useOverview', () => useOverviewMock)

describe('Visão geral states', () => {
  const refresh = vi.fn()

  beforeEach(() => {
    refresh.mockClear()
  })

  it('shows the loading message while there is no data', async () => {
    useOverviewMock.mockResolvedValue({ data: ref(null), error: ref(null), pending: ref(true), refresh })

    const page = await mountSuspended(OverviewPage)

    expect(page.find('[role="status"]').text()).toContain('Carregando')
  })

  it('shows the error message and retries on request', async () => {
    useOverviewMock.mockResolvedValue({ data: ref(null), error: ref(new Error('failed')), pending: ref(false), refresh })

    const page = await mountSuspended(OverviewPage)

    expect(page.find('[role="alert"]').text()).toContain('Não foi possível')

    await page.find('[role="alert"] button').trigger('click')

    expect(refresh).toHaveBeenCalledOnce()
  })
})
