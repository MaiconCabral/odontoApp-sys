// Clinic subdomain of a host, or null on the central domain.
export function tenantFromHost(hostname: string, centralDomain: string): string | null {
  const suffix = `.${centralDomain}`

  if (!hostname.endsWith(suffix)) {
    return null
  }

  const slug = hostname.slice(0, -suffix.length)

  return slug && !slug.includes('.') ? slug : null
}
