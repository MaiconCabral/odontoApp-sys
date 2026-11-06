// Plans of the platform, shared by the clinics list, the clinic page and the plans page.
export function usePlatformPlans() {
  return useCachedAsyncData('platform-plans', () => platformService.plans())
}
