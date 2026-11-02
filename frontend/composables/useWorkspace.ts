import type { Workspace } from '~/types/workspace'

// The panel can still render for a moment after logout, while the route changes.
const signedOut: Pick<Workspace, 'clinic' | 'user' | 'plan'> = {
  clinic: { slug: '', name: '', unit: '', initials: '' },
  user: { id: '', name: '', email: '', initials: '', role: 'professional', roleLabel: '' },
  plan: { name: '', trialDaysLeft: 0 },
}

// Clinic, user, plan and menu of the panel.
export function useWorkspace() {
  const auth = useAuthStore()
  const tenant = useTenantStore()

  return computed<Workspace>(() => {
    const user = auth.user ?? signedOut.user

    return {
      clinic: tenant.tenant ?? signedOut.clinic,
      user,
      plan: tenant.plan ?? signedOut.plan,
      nav: workspaceService.navEntries()
        .filter(entry => entry.roles.includes(user.role))
        .map(({ segment, roles: _roles, ...entry }) => ({ ...entry, path: panelPath(segment) })),
    }
  })
}
