// Clinic data and preferences shown in the settings.
export function useSettings() {
  return useCachedAsyncData('settings', () => settingsService.get())
}
