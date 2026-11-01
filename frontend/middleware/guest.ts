export default defineNuxtRouteMiddleware(async () => {
  const auth = useAuthStore()
  const tenant = useTenantStore()

  await useAuth().loadSession()

  if (auth.isAuthenticated) {
    return navigateTo(tenantPath(tenant.slug))
  }
})
