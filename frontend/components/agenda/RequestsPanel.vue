<script setup lang="ts">
import { Inbox } from 'lucide-vue-next'

const emit = defineEmits<{
  open: [id: string]
}>()

const { data: requests } = useAppointmentRequests()
</script>

<template>
  <section
    v-if="requests?.length"
    class="mb-[18px] rounded-xl border border-tone-amber-line bg-tone-amber-bg p-4 dark:border-line dark:bg-surface"
    aria-labelledby="requests-title"
  >
    <h2 id="requests-title" class="mb-3 flex items-center gap-2 text-[12px] font-bold text-tone-amber-text dark:text-body">
      <Inbox :size="16" />
      {{ requests.length }} {{ requests.length === 1 ? 'pedido de agendamento aguardando' : 'pedidos de agendamento aguardando' }}
    </h2>

    <ul class="grid gap-2">
      <li
        v-for="request in requests"
        :key="request.id"
        class="flex flex-col gap-2 rounded-lg border border-line bg-surface px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between"
      >
        <div class="min-w-0 text-[11px]">
          <strong class="block truncate text-body">{{ request.patient.name }}</strong>
          <span class="block text-[10px] text-muted">
            {{ request.procedureLabel }} · {{ formatAppointmentDate(request.date, request.hour) }}
            <template v-if="request.professional"> · {{ request.professional.name }}</template>
          </span>
          <span class="block text-[10px] text-muted">{{ request.patient.phone }}</span>
        </div>
        <button
          type="button"
          class="inline-flex flex-none items-center justify-center rounded-lg bg-brand px-3 py-2 text-[11px] font-bold text-white"
          @click="emit('open', request.id)"
        >
          Responder
        </button>
      </li>
    </ul>
  </section>
</template>
