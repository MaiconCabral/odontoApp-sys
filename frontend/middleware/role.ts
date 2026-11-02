import type { UserRole } from '~/types/auth'

// Keeps users out of pages of other roles.
// Organizes navigation only: the API is what must protect the data.
export default defineNuxtRouteMiddleware((to) => {
  const { user } = useAuthStore()
  const roles = to.meta.roles as UserRole[] | undefined

  if (user && roles && !roles.includes(user.role)) {
    return navigateTo(homePath(user.role))
  }
})
