<script setup lang="ts">
import { Pencil, Trash2 } from 'lucide-vue-next'
import type { PlatformPlan, PlatformPlanForm } from '~/types/platform'

definePageMeta({ layout: 'platform', middleware: 'platform-auth' })
useHead({ title: 'Planos' })

const { showToast } = useToast()
const { data: plans, error, pending, refresh } = await usePlatformPlans()

const editing = ref<PlatformPlan | null>(null)
const isFormOpen = ref(false)
const confirmingDelete = ref<string | null>(null)
const form = reactive<PlatformPlanForm>({ code: '', name: '', price: '', maxProfessionals: '' })

// Opens the form empty or with a plan.
function open(plan: PlatformPlan | null) {
  editing.value = plan
  Object.assign(form, {
    code: plan?.code ?? '',
    name: plan?.name ?? '',
    price: plan ? centsToInput(plan.priceCents) : '',
    maxProfessionals: plan?.maxProfessionals ? String(plan.maxProfessionals) : '',
  })
  isFormOpen.value = true
}

// Code, name, a valid price and a limit that is empty or a positive number.
function validatePlan(fields: PlatformPlanForm) {
  const errors: Partial<Record<keyof PlatformPlanForm, string>> = {}

  if (!/^[a-z0-9-]+$/.test(fields.code.trim())) {
    errors.code = 'Use letras minúsculas, números e hífen.'
  }

  if (!fields.name.trim()) {
    errors.name = 'Informe o nome.'
  }

  if (toCents(fields.price) === null) {
    errors.price = 'Informe o preço, como 249,00.'
  }

  if (fields.maxProfessionals.trim() && !(Number(fields.maxProfessionals) >= 1)) {
    errors.maxProfessionals = 'Deixe vazio para não ter limite, ou informe ao menos 1.'
  }

  return errors
}

const { errors, formError, isSubmitting, submit } = useAuthForm(form, validatePlan, async () => {
  if (editing.value) {
    await platformService.updatePlan(editing.value.id, { ...form })
  }
  else {
    await platformService.createPlan({ ...form })
  }

  showToast(editing.value ? 'Plano atualizado.' : 'Plano criado.')
  isFormOpen.value = false
  await refresh()
})

// Deletes a plan after a second click; the API refuses plans in use.
async function remove(plan: PlatformPlan) {
  if (confirmingDelete.value !== plan.id) {
    confirmingDelete.value = plan.id

    return
  }

  confirmingDelete.value = null

  try {
    await platformService.removePlan(plan.id)
    showToast(`Plano ${plan.name} excluído.`)
    await refresh()
  }
  catch (failure) {
    const { message, fields } = parseApiError(failure)

    showToast(fields.plan ?? message)
  }
}

const iconButtonClass = 'grid h-[30px] min-w-[30px] place-items-center rounded-lg text-muted transition duration-200 hover:bg-line-soft hover:text-brand-text'
</script>

<template>
  <div>
    <LayoutPageHeader
      eyebrow="Plataforma"
      title="Planos"
      description="Preço e limite de profissionais de cada plano. Um limite menor vale só para novos profissionais."
      action-label="Novo plano"
      @action="open(null)"
    />

    <SettingsCard v-if="isFormOpen" class="mb-[17px]" :title="editing ? `Editar ${editing.name}` : 'Novo plano'" subtitle="O código identifica o plano e não aparece para as clínicas.">
      <form class="grid gap-3 pt-4 sm:grid-cols-4" novalidate @submit.prevent="submit()">
        <p v-if="formError" role="alert" class="text-[11px] text-tone-red-text sm:col-span-4">
          {{ formError }}
        </p>
        <AppFormField v-slot="field" label="Código" :error="errors.code">
          <input :id="field.id" v-model="form.code" autocomplete="off" :class="field.controlClass" :aria-invalid="field.invalid" :aria-describedby="field.describedBy">
        </AppFormField>
        <AppFormField v-slot="field" label="Nome" :error="errors.name">
          <input :id="field.id" v-model="form.name" autocomplete="off" :class="field.controlClass" :aria-invalid="field.invalid" :aria-describedby="field.describedBy">
        </AppFormField>
        <AppFormField v-slot="field" label="Preço por mês (R$)" :error="errors.price">
          <input :id="field.id" v-model="form.price" inputmode="decimal" autocomplete="off" :class="field.controlClass" :aria-invalid="field.invalid" :aria-describedby="field.describedBy">
        </AppFormField>
        <AppFormField v-slot="field" label="Máximo de profissionais" :error="errors.maxProfessionals">
          <input :id="field.id" v-model="form.maxProfessionals" inputmode="numeric" placeholder="Sem limite" autocomplete="off" :class="[field.controlClass, 'placeholder:text-faint']" :aria-invalid="field.invalid" :aria-describedby="field.describedBy">
        </AppFormField>
        <div class="flex gap-2 sm:col-span-4">
          <button type="submit" :disabled="isSubmitting" class="rounded-lg bg-brand px-[15px] py-2.5 text-[12px] font-bold text-white hover:bg-brand-hover disabled:opacity-60">
            {{ editing ? 'Salvar alterações' : 'Criar plano' }}
          </button>
          <button type="button" class="rounded-lg border border-line px-[15px] py-2.5 text-[12px] font-bold text-body" @click="isFormOpen = false">
            Cancelar
          </button>
        </div>
      </form>
    </SettingsCard>

    <p v-if="pending && !plans" role="status" class="text-[13px] text-muted">
      Carregando os planos...
    </p>

    <div v-else-if="error || !plans" role="alert" class="text-[13px] text-muted">
      <p>Não foi possível carregar os planos.</p>
      <button type="button" class="mt-2 font-bold text-brand-text" @click="refresh()">
        Tentar novamente
      </button>
    </div>

    <SettingsCard v-else title="Planos" :subtitle="`${plans.length} planos`">
      <ul class="mt-[5px]">
        <li v-for="plan in plans" :key="plan.id" class="flex flex-wrap items-center justify-between gap-3 border-b border-line-soft px-[3px] py-3 text-[11px] text-body last:border-b-0">
          <div>
            <strong>{{ plan.name }}</strong> <span class="text-muted">({{ plan.code }})</span>
            <span class="block text-[10px] text-muted">
              {{ formatPlanPrice(plan.priceCents) }}/mês · {{ plan.maxProfessionals === null ? 'profissionais ilimitados' : `até ${plan.maxProfessionals} profissionais` }} · {{ plan.clinics }} clínica(s)
            </span>
          </div>
          <div class="flex items-center gap-1">
            <button type="button" :class="iconButtonClass" :aria-label="`Editar ${plan.name}`" @click="open(plan)">
              <Pencil :size="14" />
            </button>
            <button
              type="button"
              :class="iconButtonClass"
              :aria-label="confirmingDelete === plan.id ? `Confirmar exclusão de ${plan.name}` : `Excluir ${plan.name}`"
              @click="remove(plan)"
            >
              <span v-if="confirmingDelete === plan.id" class="text-[10px] font-bold text-tone-red-text">Sim?</span>
              <Trash2 v-else :size="14" />
            </button>
          </div>
        </li>
      </ul>
    </SettingsCard>
  </div>
</template>
