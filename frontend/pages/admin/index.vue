<script setup lang="ts">
import type { PlatformClinicQuery } from '~/types/platform'

definePageMeta({ layout: 'platform', middleware: 'platform-auth' })
useHead({ title: 'Clínicas' })

const query = reactive<PlatformClinicQuery>({ search: '', planId: '', status: '', page: 1 })
const search = ref('')
let searchTimer: ReturnType<typeof setTimeout> | undefined

// Waits for the typing to stop before searching, and goes back to the first page.
watch(search, (value) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    query.search = value.trim()
    query.page = 1
  }, 300)
})

const { data: plans } = await useAsyncData('platform-plans', () => platformService.plans())
const { data: page, error, pending, refresh } = await useAsyncData(
  'platform-clinics',
  () => platformService.clinics(query),
  { watch: [() => query.search, () => query.planId, () => query.status, () => query.page] },
)


const controlClass = 'h-[34px] rounded-[7px] border border-line bg-surface px-2 text-[11px] text-body'
</script>

<template>
  <div>
    <LayoutPageHeader eyebrow="Plataforma" title="Clínicas" description="Todas as clínicas, com plano, situação e uso." />

    <div class="mb-[18px] flex flex-wrap items-end gap-3 text-[11px]">
      <label class="flex min-w-[200px] flex-1 flex-col gap-1">
        <span class="text-muted">Buscar</span>
        <input v-model="search" type="search" placeholder="Nome, e-mail ou endereço" :class="controlClass">
      </label>
      <label class="flex flex-col gap-1">
        <span class="text-muted">Plano</span>
        <select v-model="query.planId" :class="controlClass" @change="query.page = 1">
          <option value="">Todos</option>
          <option v-for="plan in plans ?? []" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
        </select>
      </label>
      <label class="flex flex-col gap-1">
        <span class="text-muted">Situação</span>
        <select v-model="query.status" :class="controlClass" @change="query.page = 1">
          <option value="">Todas</option>
          <option v-for="(label, status) in CLINIC_STATUS_LABELS" :key="status" :value="status">{{ label }}</option>
        </select>
      </label>
    </div>

    <p v-if="pending && !page" role="status" class="text-[13px] text-muted">
      Carregando as clínicas...
    </p>

    <div v-else-if="error || !page" role="alert" class="text-[13px] text-muted">
      <p>Não foi possível carregar as clínicas.</p>
      <button type="button" class="mt-2 font-bold text-brand-text" @click="refresh()">
        Tentar novamente
      </button>
    </div>

    <SettingsCard v-else title="Clínicas" :subtitle="`${page.total} no total`">
      <p v-if="!page.clinics.length" class="pt-4 text-[11px] text-muted">
        Nenhuma clínica encontrada.
      </p>

      <div v-else class="mt-3 overflow-x-auto">
        <table class="w-full min-w-[640px] text-left text-[11px] text-body">
          <thead class="text-[10px] text-muted">
            <tr class="border-b border-line">
              <th scope="col" class="py-2 font-semibold">
                Clínica
              </th>
              <th scope="col" class="py-2 font-semibold">
                Plano
              </th>
              <th scope="col" class="py-2 font-semibold">
                Situação
              </th>
              <th scope="col" class="py-2 text-right font-semibold">
                Profissionais
              </th>
              <th scope="col" class="py-2 text-right font-semibold">
                Consultas (30 dias)
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="clinic in page.clinics" :key="clinic.id" class="border-b border-line-soft">
              <th scope="row" class="py-2.5 font-semibold">
                <NuxtLink :to="`/admin/clinicas/${clinic.id}`" class="hover:text-brand-text hover:underline">
                  {{ clinic.name }}
                </NuxtLink>
                <span class="block text-[10px] font-normal text-muted">{{ clinic.slug }}</span>
              </th>
              <td class="py-2.5">
                {{ clinic.plan.name ?? '—' }}
              </td>
              <td class="py-2.5">
                <span class="rounded-md px-2 py-1 text-[10px] font-semibold" :class="CLINIC_STATUS_CLASSES[clinic.status]">
                  {{ CLINIC_STATUS_LABELS[clinic.status] }}<template v-if="clinic.status === 'trial'"> · {{ clinic.trialDaysLeft }} dias</template>
                </span>
              </td>
              <td class="py-2.5 text-right">
                {{ clinic.usage?.professionals ?? '—' }}
              </td>
              <td class="py-2.5 text-right">
                {{ clinic.usage?.appointmentsLast30Days ?? '—' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="page.lastPage > 1" class="mt-3 flex items-center justify-end gap-2 text-[11px]">
        <button type="button" class="rounded-lg border border-line px-3 py-1.5 font-bold disabled:opacity-50" :disabled="page.page <= 1" @click="query.page--">
          Anterior
        </button>
        <span class="text-muted">Página {{ page.page }} de {{ page.lastPage }}</span>
        <button type="button" class="rounded-lg border border-line px-3 py-1.5 font-bold disabled:opacity-50" :disabled="page.page >= page.lastPage" @click="query.page++">
          Próxima
        </button>
      </div>
    </SettingsCard>
  </div>
</template>
