import type { TeamUser, TeamUserForm } from '~/types/users'

// Form in the API field names.
function toBody(form: TeamUserForm) {
  return {
    name: form.name,
    email: form.email,
    role: form.role,
    specialty: form.specialty,
    attends_patients: form.attendsPatients,
    active: form.active,
  }
}

export const usersService = {
  // Team of the clinic.
  list: async () => (await useApi().api<{ data: TeamUser[] }>('/api/users')).data,

  // Adds a team member.
  create: (form: TeamUserForm) =>
    useApi().api<TeamUser>('/api/users', { method: 'POST', body: toBody(form) }),

  // Updates a team member.
  update: (id: string, form: TeamUserForm) =>
    useApi().api<TeamUser>(`/api/users/${id}`, { method: 'PUT', body: toBody(form) }),

  // Deletes a team member.
  remove: (id: string) => useApi().api<void>(`/api/users/${id}`, { method: 'DELETE' }),
}
