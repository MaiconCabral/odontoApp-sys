<script setup lang="ts">
import type { AgendaBlock } from '~/types/agenda'

useHead({ title: 'Agenda' })

const { openModal } = useActiveModal()
const auth = useAuthStore()

// Professionals only read their agenda and finish their appointments. The API is what enforces it.
const canManage = computed(() => auth.user?.role !== 'professional')

const { data: agenda, error, pending, refresh } = await useAgenda()

// Opens an appointment or a request with its actions.
function openAppointment(id: string) {
  openModal('appointment-details', { id })
}

// Opens a block to see it or delete it.
function openBlock(block: AgendaBlock) {
  openModal('block', { block })
}
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
        :action-label="canManage ? 'Novo agendamento' : undefined"
        @action="openModal('appointment')"
      />
      <AgendaRequestsPanel v-if="canManage" @open="openAppointment" />
      <AgendaBoard
        :agenda="agenda"
        :can-manage="canManage"
        @select="openAppointment"
        @select-block="openBlock"
        @block="openModal('block')"
      />
    </template>
  </div>
</template>
