// Keeps clinic pages off the central domain.
export default defineNuxtRouteMiddleware(() => {
  if (!useClinicHost().isClinicHost) {
    return navigateTo('/login')
  }
})
