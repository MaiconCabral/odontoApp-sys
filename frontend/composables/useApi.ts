type ApiOptions = NonNullable<Parameters<typeof $fetch>[1]>

// Reads the CSRF token Laravel stores in a cookie.
function readXsrfToken() {
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)

  return match?.[1] ? decodeURIComponent(match[1]) : ''
}

// Tells whether the request changes data.
function isMutation(options: ApiOptions) {
  return (options.method ?? 'GET').toUpperCase() !== 'GET'
}

// API client for the current host.
export function useApi() {
  const config = useRuntimeConfig()
  const baseURL = config.public.apiBase.replace('{host}', useRequestURL().hostname)

  // Sends the request with cookies and headers.
  function send<T>(path: string, options: ApiOptions) {
    const headers = new Headers(options.headers as HeadersInit | undefined)

    headers.set('Accept', 'application/json')

    if (isMutation(options)) {
      headers.set('X-XSRF-TOKEN', readXsrfToken())
    }

    return $fetch<T>(path, { ...options, baseURL, credentials: 'include', headers })
  }

  // Asks Laravel for a new CSRF cookie.
  function refreshCsrfCookie() {
    return $fetch('/sanctum/csrf-cookie', { baseURL, credentials: 'include' })
  }

  // Calls the API handling CSRF and error field names.
  async function api<T>(path: string, options: ApiOptions = {}): Promise<T> {
    if (isMutation(options) && !readXsrfToken()) {
      await refreshCsrfCookie()
    }

    try {
      return await send<T>(path, options)
    }
    catch (error) {
      // The token dies with the session, so an old tab gets one new attempt.
      if (isMutation(options) && apiStatus(error) === 419) {
        await refreshCsrfCookie()

        return await send<T>(path, options)
      }

      throw camelizeApiErrors(error)
    }
  }

  return { api }
}
