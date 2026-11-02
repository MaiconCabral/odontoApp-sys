// Opening hours of the clinic.
export function useBusinessHours() {
  return useAsyncData('business-hours', () => scheduleService.hours())
}
