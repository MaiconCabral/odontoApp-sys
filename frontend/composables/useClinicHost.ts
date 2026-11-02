// Clinic of the current host and links between hosts.
// The clinic comes from the subdomain. On the central domain there is no clinic at all.
export function useClinicHost() {
  const { centralDomain } = useRuntimeConfig().public
  const url = useRequestURL()
  const slug = tenantFromHost(url.hostname, centralDomain)

  // Origin with the current protocol and port.
  function originOf(host: string) {
    return `${url.protocol}//${host}${url.port ? `:${url.port}` : ''}`
  }

  return {
    slug,
    isClinicHost: slug !== null,
    centralUrl: (path: string) => `${originOf(centralDomain)}${path}`,
    clinicUrl: (clinicSlug: string, path: string) => `${originOf(`${clinicSlug}.${centralDomain}`)}${path}`,
  }
}
