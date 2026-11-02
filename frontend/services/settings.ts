import { settingsMock } from '~/mocks/settings'
import type { Settings } from '~/types/settings'

export const settingsService = {
  // Real clinic data with the settings still mocked.
  get: async (): Promise<Settings> => ({ ...settingsMock, clinic: await clinicService.get() }),
}
