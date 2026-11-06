<script setup lang="ts">
import { ArrowLeft } from 'lucide-vue-next'
import type { PlatformAccountForm } from '~/types/platform'

definePageMeta({ layout: 'platform', middleware: 'platform-auth' })

const id = String(useRoute().params.id)
const { showToast } = useToast()

// The clinic and the plans load at the same time.
const [{ data: clinic, error, pending, refresh }, { data: plans }] = await Promise.all([
  useAsyncData(`platform-clinic-${id}`, () => platformService.clinic(id)),
  usePlatformPlans(),
])

useHead({ title: () => clinic.value?.name ?? 'Clínica' })

const form = reactive<PlatformAccountForm>({ planId: '', trialEndsAt: '', suspended: false })

// Starts the form from the clinic as it is now.
watch(clinic, (value) => {
  if (value) {
    Object.assign(form, { planId: value.plan.id, trialEndsAt: value.trialEndsAt ?? '', suspended: value.status === 'suspended' })
  }
}, { immediate: true })

// A plan must be chosen.
function validateAccount(fields: PlatformAccountForm) {
  return fields.planId ? {} : { planId: 'Escolha o plano.' }
}

const { errors, formError, isSubmitting, submit } = useAuthForm(form, validateAccount, async () => {
  clinic.value = await platformService.updateClinic(id, { ...form })
  showToast('Conta da clínica atualizada.')
})
</script>

<template>
  <div>
    <NuxtLink to="/admin" class="mb-4 inline-flex items-center gap-1.5 text-[11px] font-bold text-brand-text">
      <ArrowLeft :size="14" /> Clínicas
    </NuxtLink>

    <p v-if="pending && !clinic" role="status" class="text-[13px] text-muted">
      Carregando a clínica...
    </p>

    <div v-else-if="error || !clinic" role="alert" class="text-[13px] text-muted">
      <p>Não foi possível carregar a clínica.</p>
      <button type="button" class="mt-2 font-bold text-brand-text" @click="refresh()">
        Tentar novamente
      </button>
    </div>

    <template v-else>
      <LayoutPageHeader eyebrow="Plataforma" :title="clinic.name" :description="`${clinic.slug} · ${clinic.email ?? 'sem e-mail'}`" />

      <div class="mb-[18px] grid grid-cols-2 gap-[15px] md:grid-cols-4">
        <div v-for="card in [
          { label: 'Situação', value: CLINIC_STATUS_LABELS[clinic.status] },
          { label: 'Usuários ativos', value: String(clinic.usage?.activeUsers ?? '—') },
          { label: 'Profissionais', value: String(clinic.usage?.professionals ?? '—') },
          { label: 'Consultas em 30 dias', value: String(clinic.usage?.appointmentsLast30Days ?? '—') },
        ]" :key="card.label" class="rounded-xl border border-line bg-surface px-4 py-4">
          <div class="text-[11px] text-muted">
            {{ card.label }}
          </div>
          <div class="mt-2 font-display text-[20px] font-extrabold text-heading">
            {{ card.value }}
          </div>
        </div>
      </div>

      <SettingsCard title="Conta" subtitle="Plano, fim do trial e suspensão. Sem data de fim, a clínica é considerada pagante.">
        <form class="grid gap-3 pt-4 sm:grid-cols-2" novalidate @submit.prevent="submit()">
          <p v-if="formError" role="alert" class="text-[11px] text-tone-red-text sm:col-span-2">
            {{ formError }}
          </p>
          <AppFormField v-slot="field" label="Plano" :error="errors.planId">
            <select :id="field.id" v-model="form.planId" :class="field.controlClass" :aria-invalid="field.invalid">
              <option v-for="plan in plans ?? []" :key="plan.id" :value="plan.id">
                {{ plan.name }} · {{ formatPlanPrice(plan.priceCents) }}/mês
              </option>
            </select>
          </AppFormField>
          <AppFormField v-slot="field" label="Fim do trial (opcional)">
            <input :id="field.id" v-model="form.trialEndsAt" type="date" :class="field.controlClass">
          </AppFormField>
          <label class="flex items-start gap-2 rounded-lg border border-line-soft p-3 text-[11px] text-body sm:col-span-2">
            <input v-model="form.suspended" type="checkbox" class="mt-0.5 accent-brand">
            <span>
              <strong class="block">Suspender a clínica</strong>
              <span class="text-muted">Bloqueia o login, o painel e o agendamento online. Os pacientes ainda podem confirmar ou cancelar pelo link do e-mail.</span>
            </span>
          </label>
          <button type="submit" :disabled="isSubmitting" class="justify-self-start rounded-lg bg-brand px-[15px] py-2.5 text-[12px] font-bold text-white hover:bg-brand-hover disabled:opacity-60">
            Salvar
          </button>
        </form>
      </SettingsCard>
    </template>
  </div>
</template>
