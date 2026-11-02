import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

function clearCookies() {
  document.cookie = 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/'
}

function headersOf(call: unknown[]) {
  return (call[1] as { headers: Headers }).headers
}

describe('useApi', () => {
  const fetchMock = vi.fn()

  beforeEach(() => {
    fetchMock.mockReset()
    fetchMock.mockResolvedValue({ ok: true })
    clearCookies()
    vi.stubGlobal('$fetch', fetchMock)
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    clearCookies()
  })

  it('reads with cookies, without asking for a CSRF token', async () => {
    await useApi().api('/api/session')

    expect(fetchMock).toHaveBeenCalledOnce()
    expect(fetchMock.mock.calls[0]?.[0]).toBe('/api/session')
    expect(fetchMock.mock.calls[0]?.[1]).toMatchObject({ credentials: 'include' })
    expect(headersOf(fetchMock.mock.calls[0]!).get('Accept')).toBe('application/json')
    expect(headersOf(fetchMock.mock.calls[0]!).has('X-XSRF-TOKEN')).toBe(false)
  })

  it('calls the API on the same host as the browser', async () => {
    await useApi().api('/api/session')

    const { baseURL } = fetchMock.mock.calls[0]?.[1] as { baseURL: string }

    expect(baseURL).toBe(`http://${useRequestURL().hostname}:8000`)
  })

  it('asks for the CSRF cookie before the first change', async () => {
    fetchMock.mockImplementation(async (path: string) => {
      if (path === '/sanctum/csrf-cookie') {
        document.cookie = 'XSRF-TOKEN=abc%3D; path=/'
      }

      return { ok: true }
    })

    await useApi().api('/api/login', { method: 'POST', body: {} })

    expect(fetchMock.mock.calls.map(call => call[0])).toEqual(['/sanctum/csrf-cookie', '/api/login'])
    expect(headersOf(fetchMock.mock.calls[1]!).get('X-XSRF-TOKEN')).toBe('abc=')
  })

  it('reuses a CSRF cookie it already has', async () => {
    document.cookie = 'XSRF-TOKEN=abc; path=/'

    await useApi().api('/api/logout', { method: 'POST' })

    expect(fetchMock.mock.calls.map(call => call[0])).toEqual(['/api/logout'])
  })

  it('tries once more when the CSRF token expired', async () => {
    document.cookie = 'XSRF-TOKEN=old; path=/'
    fetchMock.mockRejectedValueOnce(Object.assign(new Error('expired'), { statusCode: 419 }))

    await useApi().api('/api/logout', { method: 'POST' })

    expect(fetchMock.mock.calls.map(call => call[0])).toEqual(['/api/logout', '/sanctum/csrf-cookie', '/api/logout'])
  })

  it('names field errors the way the forms do', async () => {
    fetchMock.mockRejectedValue(Object.assign(new Error('invalid'), {
      statusCode: 422,
      data: { message: 'Inválido', errors: { clinic_name: ['Informe o nome da clínica.'] } },
    }))

    await expect(useApi().api('/api/session')).rejects.toMatchObject({
      data: { errors: { clinicName: ['Informe o nome da clínica.'] } },
    })
  })
})
