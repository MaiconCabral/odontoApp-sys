// Opening hours of the clinic.
export function useBusinessHours() {
  return useCachedAsyncData('business-hours', () => scheduleService.hours())
}
