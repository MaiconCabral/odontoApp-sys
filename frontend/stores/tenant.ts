import type { Plan, Tenant } from '~/types/auth'

export const useTenantStore = defineStore('tenant', () => {
  const tenant = ref<Tenant | null>(null)
  const plan = ref<Plan | null>(null)
  const slug = computed(() => tenant.value?.slug ?? '')

  function setTenant(nextTenant: Tenant | null, nextPlan: Plan | null) {
    tenant.value = nextTenant
    plan.value = nextPlan
  }

  return { tenant, plan, slug, setTenant }
})
