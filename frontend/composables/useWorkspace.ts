import type { Workspace } from '~/types/workspace'

// The panel can still render for a moment after logout, while the route changes.
const signedOut = {
  clinic: { slug: '', name: '', unit: '', initials: '' },
  user: { name: '', email: '', initials: '', role: '' },
  plan: { name: '', trialDaysLeft: 0 },
}

export function useWorkspace() {
  const auth = useAuthStore()
  const tenant = useTenantStore()
  const toPath = useTenantPath()

  return computed<Workspace>(() => ({
    clinic: tenant.tenant ?? signedOut.clinic,
    user: auth.user ?? signedOut.user,
    plan: tenant.plan ?? signedOut.plan,
    nav: workspaceService.navEntries().map(({ segment, ...entry }) => ({ ...entry, path: toPath(segment) })),
  }))
}
