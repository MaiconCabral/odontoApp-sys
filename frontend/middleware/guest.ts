// Sends signed-in users to their first page.
export default defineNuxtRouteMiddleware(async () => {
  const auth = useAuthStore()

  await useAuth().loadSession()

  if (auth.user) {
    return navigateTo(homePath(auth.user.role))
  }
})
