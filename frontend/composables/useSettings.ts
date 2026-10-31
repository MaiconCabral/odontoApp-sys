import { settingsMock } from '~/mocks/settings'

export function useSettings() {
  return useAsyncData('settings', () => Promise.resolve(settingsMock))
}
