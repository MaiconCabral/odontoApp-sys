import { mountSuspended } from '@nuxt/test-utils/runtime'
import { describe, expect, it } from 'vitest'
import Sidebar from '~/components/layout/Sidebar.vue'
import Topbar from '~/components/layout/Topbar.vue'
import { demoTenantPath } from '../utils/session'

describe('Sidebar', () => {
  it('marks the current page in the navigation', async () => {
    const sidebar = await mountSuspended(Sidebar, { props: { collapsed: false, mobileOpen: false }, route: demoTenantPath })

    expect(sidebar.find('[aria-current="page"]').text()).toContain('Visão geral')
  })

  it('links every page in the navigation', async () => {
    const sidebar = await mountSuspended(Sidebar, { props: { collapsed: false, mobileOpen: false } })
    const links = sidebar.findAll('nav a').map(link => link.attributes('href'))

    expect(links).toEqual([
      demoTenantPath,
      `${demoTenantPath}/agenda`,
      `${demoTenantPath}/pacientes`,
      `${demoTenantPath}/configuracoes`,
    ])
  })

  it('hides the labels when collapsed but keeps them accessible', async () => {
    const sidebar = await mountSuspended(Sidebar, { props: { collapsed: true, mobileOpen: false } })

    expect(sidebar.text()).not.toContain('Pacientes')
    expect(sidebar.find('[aria-label="Pacientes"]').exists()).toBe(true)
  })

  it('shows the full menu in the mobile drawer even when collapsed', async () => {
    const sidebar = await mountSuspended(Sidebar, { props: { collapsed: true, mobileOpen: true } })

    expect(sidebar.text()).toContain('Pacientes')
  })
})

describe('Topbar', () => {
  it('shows the current page and asks to open the menu', async () => {
    const topbar = await mountSuspended(Topbar, { props: { menuOpen: false }, route: demoTenantPath })

    expect(topbar.text()).toContain('Visão geral')

    await topbar.find('[aria-label="Abrir menu"]').trigger('click')

    expect(topbar.emitted('openMenu')).toHaveLength(1)
  })

  it('toggles the theme', async () => {
    const topbar = await mountSuspended(Topbar, { props: { menuOpen: false } })
    const { isDark } = useTheme()
    const initial = isDark.value

    await topbar.find('[aria-label="Alternar tema"]').trigger('click')

    expect(isDark.value).toBe(!initial)
  })
})
