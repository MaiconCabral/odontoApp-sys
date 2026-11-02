// Moves central pages to the central domain.
export default defineNuxtRouteMiddleware((to) => {
  const { isClinicHost, centralUrl } = useClinicHost()

  if (isClinicHost) {
    return navigateTo(centralUrl(to.fullPath), { external: true })
  }
})
