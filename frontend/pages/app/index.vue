<script setup lang="ts">
definePageMeta({ middleware: 'role', roles: ['admin', 'reception'] })
useHead({ title: 'Visão geral' })

const { openModal } = useActiveModal()

const { data: overview, error, pending, refresh } = await useOverview()
</script>

<template>
  <div>
    <p v-if="pending && !overview" role="status" class="text-[13px] text-muted">
      Carregando a visão geral...
    </p>

    <div v-else-if="error || !overview" role="alert" class="text-[13px] text-muted">
      <p>Não foi possível carregar a visão geral.</p>
      <button type="button" class="mt-2 font-bold text-brand-text" @click="refresh()">
        Tentar novamente
      </button>
    </div>

    <template v-else>
      <LayoutPageHeader
        :eyebrow="overview.greeting"
        title="Visão geral"
        description="Acompanhe o que está acontecendo na sua clínica hoje."
        action-label="Novo agendamento"
        @action="openModal('appointment')"
      />

      <div class="mb-[18px] grid grid-cols-2 gap-[15px] md:grid-cols-4">
        <OverviewMetricCard v-for="metric in overview.metrics" :key="metric.id" :metric="metric" />
      </div>

      <div class="mb-[18px] grid grid-cols-1 gap-[18px] md:grid-cols-[1.35fr_1fr] lg:grid-cols-[1.5fr_1fr]">
        <OverviewWeeklyChart :weekly="overview.weeklyAppointments" />
        <OverviewOccupancyCard :percentage="overview.occupancyPercentage" />
      </div>

      <OverviewPanel
        class="px-3 pb-1.5 pt-4 sm:p-[21px] sm:pb-2"
        title="Próximos pacientes"
        :subtitle="`Agenda de hoje, ${overview.agendaDate}`"
      >
        <template #action>
          <OverviewTextButton :to="panelPath('agenda')">
            Ver agenda
          </OverviewTextButton>
        </template>

        <div class="mt-[18px]">
          <OverviewAppointmentRow
            v-for="appointment in overview.upcomingAppointments"
            :key="appointment.id"
            :appointment="appointment"
          />
        </div>
      </OverviewPanel>
    </template>
  </div>
</template>
