export function useSettings() {
  return useAsyncData('settings', () => settingsService.get())
}
