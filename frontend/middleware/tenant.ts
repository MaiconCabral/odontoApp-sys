export default defineNuxtRouteMiddleware((to) => {
  const tenant = useTenantStore()

  if (to.params.tenant !== tenant.slug) {
    return navigateTo(tenantPath(tenant.slug))
  }
})
