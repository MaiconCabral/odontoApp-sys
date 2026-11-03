import type { PlatformAdmin } from '~/types/platform'

// Session of the global panel. Separate from the clinic session: other domain, guard and table.
export function usePlatformSession() {
  const admin = useState<PlatformAdmin | null>('platform-admin', () => null)
  const loaded = useState('platform-session-loaded', () => false)

  // Asks the API once who is signed in.
  async function load() {
    if (!loaded.value) {
      admin.value = await platformService.session()
      loaded.value = true
    }

    return admin.value
  }

  // Signs in and keeps the admin.
  async function login(email: string, password: string) {
    admin.value = await platformService.login(email, password)
    loaded.value = true
  }

  // Signs out and clears every panel data, so nothing remains in the tab.
  async function logout() {
    await platformService.logout()
    admin.value = null
    clearNuxtData()
    await navigateTo('/admin/login')
  }

  return { admin, load, login, logout }
}
