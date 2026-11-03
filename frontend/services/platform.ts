import type {
  PlatformAccountForm,
  PlatformAdmin,
  PlatformClinic,
  PlatformClinicPage,
  PlatformClinicQuery,
  PlatformPlan,
  PlatformPlanForm,
} from '~/types/platform'

type ClinicsResponse = { data: PlatformClinic[], meta: { total: number, current_page: number, last_page: number } }

// Plan form in the API field names.
function toPlanBody(form: PlatformPlanForm) {
  return {
    code: form.code.trim(),
    name: form.name.trim(),
    price_cents: toCents(form.price),
    max_professionals: form.maxProfessionals.trim() ? Number(form.maxProfessionals) : null,
  }
}

export const platformService = {
  // Signed-in platform admin, or null without a session.
  session: async (): Promise<PlatformAdmin | null> => {
    try {
      return (await useApi().api<{ admin: PlatformAdmin }>('/api/platform/session')).admin
    }
    catch (error) {
      if (apiStatus(error) === 401) {
        return null
      }

      throw error
    }
  },

  // Signs in on the central domain.
  login: async (email: string, password: string) =>
    (await useApi().api<{ admin: PlatformAdmin }>('/api/platform/login', { method: 'POST', body: { email, password } })).admin,

  // Ends the platform session.
  logout: () => useApi().api<void>('/api/platform/logout', { method: 'POST' }),

  // One page of clinics with search and filters.
  clinics: async (query: PlatformClinicQuery): Promise<PlatformClinicPage> => {
    const response = await useApi().api<ClinicsResponse>('/api/platform/clinics', {
      query: { search: query.search || undefined, plan_id: query.planId || undefined, status: query.status || undefined, page: query.page },
    })

    return { clinics: response.data, total: response.meta.total, page: response.meta.current_page, lastPage: response.meta.last_page }
  },

  // One clinic with its usage.
  clinic: (id: string) => useApi().api<PlatformClinic>(`/api/platform/clinics/${id}`),

  // Changes plan, trial and suspension of a clinic.
  updateClinic: (id: string, form: PlatformAccountForm) =>
    useApi().api<PlatformClinic>(`/api/platform/clinics/${id}`, {
      method: 'PUT',
      body: { plan_id: form.planId, trial_ends_at: form.trialEndsAt || null, suspended: form.suspended },
    }),

  // Plans with how many clinics use each one.
  plans: async () => (await useApi().api<{ data: PlatformPlan[] }>('/api/platform/plans')).data,

  // Adds a plan.
  createPlan: (form: PlatformPlanForm) => useApi().api<PlatformPlan>('/api/platform/plans', { method: 'POST', body: toPlanBody(form) }),

  // Edits a plan.
  updatePlan: (id: string, form: PlatformPlanForm) =>
    useApi().api<PlatformPlan>(`/api/platform/plans/${id}`, { method: 'PUT', body: toPlanBody(form) }),

  // Deletes a plan nobody uses.
  removePlan: (id: string) => useApi().api<void>(`/api/platform/plans/${id}`, { method: 'DELETE' }),
}
