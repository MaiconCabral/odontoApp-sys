import type { AuthUser } from '~/types/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)
  const isLoaded = ref(false)
  const clinicNotFound = ref(false)
  const isAuthenticated = computed(() => user.value !== null)

  // Sets the signed-in user and marks the session as loaded.
  function setUser(next: AuthUser | null) {
    user.value = next
    isLoaded.value = true
  }

  return { user, isLoaded, clinicNotFound, isAuthenticated, setUser }
})
