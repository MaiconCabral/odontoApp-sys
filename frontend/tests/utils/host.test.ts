import { describe, expect, it } from 'vitest'
import { tenantFromHost } from '~/utils/host'

describe('tenantFromHost', () => {
  it('reads the clinic from the subdomain', () => {
    expect(tenantFromHost('sorriso-perfeito.localhost', 'localhost')).toBe('sorriso-perfeito')
    expect(tenantFromHost('clinica.odonto.app', 'odonto.app')).toBe('clinica')
  })

  it.each([
    'localhost',
    '127.0.0.1',
    'odonto.app',
    'a.b.localhost',
    'localhost.evil.com',
    'evillocalhost',
  ])('finds no clinic in %s', (hostname) => {
    expect(tenantFromHost(hostname, 'localhost')).toBeNull()
  })
})
