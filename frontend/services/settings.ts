import { settingsMock } from '~/mocks/settings'

export const settingsService = {
  get: () => Promise.resolve(settingsMock),
}
