<script setup lang="ts">
import type { AppointmentDetails } from '~/types/agenda'

const props = defineProps<{
  patientId: string
}>()

const { openModal } = useActiveModal()

const appointments = ref<AppointmentDetails[]>([])
const page = ref(0)
const lastPage = ref(1)
const isLoading = ref(false)
const loadFailed = ref(false)

// Loads the next page and appends it to the list.
async function loadMore() {
  isLoading.value = true
  loadFailed.value = false

  try {
    const result = await patientsService.appointments(props.patientId, page.value + 1)

    appointments.value.push(...result.appointments)
    page.value = result.page
    lastPage.value = result.lastPage
  }
  catch {
    loadFailed.value = true
  }
  finally {
    isLoading.value = false
  }
}

await loadMore()
</script>

<template>
  <SettingsCard title="Consultas" subtitle="Da mais recente para a mais antiga. Clique para ver os detalhes.">
    <p v-if="loadFailed && !appointments.length" role="alert" class="pt-4 text-[11px] text-muted">
      Não foi possível carregar as consultas.
      <button type="button" class="font-bold text-brand-text" @click="loadMore">
        Tentar novamente
      </button>
    </p>

    <p v-else-if="!appointments.length" class="pt-4 text-[11px] text-muted">
      Este paciente ainda não tem consultas.
    </p>

    <ul v-else class="mt-[5px]">
      <li v-for="appointment in appointments" :key="appointment.id" class="border-b border-line-soft last:border-b-0">
        <button
          type="button"
          class="flex w-full flex-wrap items-center justify-between gap-2 px-[3px] py-3 text-left text-[11px] text-body transition duration-200 hover:bg-line-soft"
          @click="openModal('appointment-details', { id: appointment.id })"
        >
          <span class="min-w-0">
            <strong class="block">{{ formatAppointmentDate(appointment.date, appointment.hour) }}</strong>
            <span class="block text-[10px] text-muted">
              {{ appointment.procedureLabel }} · {{ appointment.professional?.name ?? 'A definir' }}
            </span>
          </span>
          <span class="rounded-md border-l-[3px] px-2 py-1 text-[10px] font-bold" :class="STATUS_CLASSES[appointment.status]">
            {{ appointment.statusLabel }}
          </span>
        </button>
      </li>
    </ul>

    <button
      v-if="page < lastPage"
      type="button"
      class="mt-3 text-[11px] font-bold text-brand-text disabled:opacity-60"
      :disabled="isLoading"
      @click="loadMore"
    >
      {{ isLoading ? 'Carregando...' : 'Carregar mais' }}
    </button>
  </SettingsCard>
</template>
