import type { AuthUser } from '~/types/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)
  const isLoaded = ref(false)
  const isAuthenticated = computed(() => user.value !== null)

  function setUser(next: AuthUser | null) {
    user.value = next
    isLoaded.value = true
  }

  return { user, isLoaded, isAuthenticated, setUser }
})
