<script setup lang="ts">
import type { SettingsTabId } from '~/types/settings'

definePageMeta({ middleware: 'role', roles: ['admin'] })
useHead({ title: 'Configurações' })

const { data: settings, error, pending, refresh } = await useSettings()

const activeTab = ref<SettingsTabId>('clinic')
</script>

<template>
  <div>
    <p v-if="pending && !settings" role="status" class="text-[13px] text-muted">
      Carregando as configurações...
    </p>

    <div v-else-if="error || !settings" role="alert" class="text-[13px] text-muted">
      <p>Não foi possível carregar as configurações.</p>
      <button type="button" class="mt-2 font-bold text-brand-text" @click="refresh()">
        Tentar novamente
      </button>
    </div>

    <template v-else>
      <LayoutPageHeader
        eyebrow="Administração"
        title="Configurações"
        description="Personalize sua clínica e mantenha tudo sob controle."
      />

      <div class="grid grid-cols-1 items-start gap-[18px] md:grid-cols-[185px_1fr]">
        <SettingsNav v-model:active="activeTab" :tabs="settings.tabs" />
        <section class="min-w-0">
          <!-- v-show keeps unsaved edits when switching tabs. -->
          <div v-show="activeTab === 'clinic'" class="grid gap-[17px]" data-testid="clinic-tab">
            <SettingsClinicForm :clinic="settings.clinic" />
            <SettingsPreferencesCard :preferences="settings.preferences" />
          </div>
          <SettingsTeamCard v-if="activeTab === 'team'" />
          <SettingsHoursCard v-else-if="activeTab === 'hours'" :opening-hours="settings.openingHours" />
          <SettingsSubscriptionCard v-else-if="activeTab === 'subscription'" :subscription="settings.subscription" />
        </section>
      </div>
    </template>
  </div>
</template>
