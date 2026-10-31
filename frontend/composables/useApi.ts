type ApiOptions = NonNullable<Parameters<typeof $fetch>[1]>

export function useApi() {
  const config = useRuntimeConfig()

  function api<T>(path: string, options: ApiOptions = {}) {
    return $fetch<T>(path, {
      ...options,
      baseURL: config.public.apiBase,
      credentials: 'include',
    })
  }

  return { api }
}
