// Sends visitors to the login.
// Organizes navigation only: the API is what must protect the data.
export default defineNuxtRouteMiddleware(async (to) => {
  const auth = useAuthStore()

  await useAuth().loadSession()

  if (!auth.isAuthenticated) {
    return navigateTo({ path: '/login', query: { redirect: to.fullPath } })
  }
})
