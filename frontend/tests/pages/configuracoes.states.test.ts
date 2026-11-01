import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import SettingsPage from '~/pages/app/[tenant]/configuracoes.vue'

const { useSettingsMock } = vi.hoisted(() => ({ useSettingsMock: vi.fn() }))

mockNuxtImport('useSettings', () => useSettingsMock)

describe('Configurações states', () => {
  const refresh = vi.fn()

  beforeEach(() => {
    refresh.mockClear()
  })

  it('shows the loading message while there is no data', async () => {
    useSettingsMock.mockResolvedValue({ data: ref(null), error: ref(null), pending: ref(true), refresh })

    const page = await mountSuspended(SettingsPage)

    expect(page.find('[role="status"]').text()).toContain('Carregando')
  })

  it('shows the error message and retries on request', async () => {
    useSettingsMock.mockResolvedValue({ data: ref(null), error: ref(new Error('failed')), pending: ref(false), refresh })

    const page = await mountSuspended(SettingsPage)

    expect(page.find('[role="alert"]').text()).toContain('Não foi possível')

    await page.find('[role="alert"] button').trigger('click')

    expect(refresh).toHaveBeenCalledOnce()
  })
})
