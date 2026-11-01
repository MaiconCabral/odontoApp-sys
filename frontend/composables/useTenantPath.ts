export function useTenantPath() {
  const tenant = useTenantStore()

  return (segment = '') => tenantPath(tenant.slug, segment)
}
