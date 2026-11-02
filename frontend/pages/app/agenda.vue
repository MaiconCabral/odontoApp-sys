<script setup lang="ts">
useHead({ title: 'Agenda' })

const { openModal } = useActiveModal()
const auth = useAuthStore()

// Professionals only read their agenda. The API is what enforces it.
const canSchedule = computed(() => auth.user?.role !== 'professional')

const { data: agenda, error, pending, refresh } = await useAgenda()
</script>

<template>
  <div>
    <p v-if="pending && !agenda" role="status" class="text-[13px] text-muted">
      Carregando a agenda...
    </p>

    <div v-else-if="error || !agenda" role="alert" class="text-[13px] text-muted">
      <p>Não foi possível carregar a agenda.</p>
      <button type="button" class="mt-2 font-bold text-brand-text" @click="refresh()">
        Tentar novamente
      </button>
    </div>

    <template v-else>
      <LayoutPageHeader
        eyebrow="Agenda clínica"
        title="Agenda de consultas"
        description="Organize o dia da sua equipe com clareza e tranquilidade."
        :action-label="canSchedule ? 'Novo agendamento' : undefined"
        @action="openModal('appointment')"
      />
      <AgendaBoard :agenda="agenda" />
    </template>
  </div>
</template>
