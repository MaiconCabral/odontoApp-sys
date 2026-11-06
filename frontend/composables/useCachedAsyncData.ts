import type { NuxtApp } from '#app'

// Data an earlier call already loaded for the key. Nuxt only reuses it while hydrating, and these
// screens never hydrate, so without this every mount and every second caller asks the API again.
// refresh() and refreshNuxtData() skip it and still reload.
function reuseLoaded(key: string, nuxtApp: NuxtApp) {
  return nuxtApp.payload.data[key] ?? undefined
}

// useAsyncData that keeps what it loaded between screens and joins a request already on its way.
// Data changed from another screen must be dropped with clearNuxtData(key), because a key nobody
// shows anymore does not listen to refreshNuxtData.
export function useCachedAsyncData<T>(key: string, handler: () => Promise<T>) {
  return useAsyncData(key, handler, { dedupe: 'defer', getCachedData: reuseLoaded })
}
