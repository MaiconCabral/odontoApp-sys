// The global panel lives on the central domain and needs a platform session.
// Organizes navigation only: the API is what protects the data.
export default defineNuxtRouteMiddleware(async (to) => {
  const { isClinicHost, centralUrl } = useClinicHost()

  if (isClinicHost) {
    return navigateTo(centralUrl(to.fullPath), { external: true })
  }

  const admin = await usePlatformSession().load()

  if (!admin) {
    return navigateTo({ path: '/admin/login', query: { redirect: to.fullPath } })
  }
})
