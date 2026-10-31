<script setup lang="ts">
useHead({ title: 'Pacientes' })

const { openModal } = useActiveModal()

const { data: list, error, pending, refresh } = await usePatients()
</script>

<template>
  <div>
    <p v-if="pending && !list" role="status" class="text-[13px] text-muted">
      Carregando os pacientes...
    </p>

    <div v-else-if="error || !list" role="alert" class="text-[13px] text-muted">
      <p>Não foi possível carregar os pacientes.</p>
      <button type="button" class="mt-2 font-bold text-brand-text" @click="refresh()">
        Tentar novamente
      </button>
    </div>

    <template v-else>
      <LayoutPageHeader
        eyebrow="Relacionamento"
        title="Pacientes"
        description="Tenha uma visão clara das pessoas que confiam na sua clínica."
        action-label="Novo paciente"
        @action="openModal('patient')"
      />
      <PatientsTable :list="list" />
    </template>
  </div>
</template>
