<script setup lang="ts">
import { Pencil, Trash2 } from 'lucide-vue-next'
import type { WaitlistEntry, WaitlistStatus } from '~/types/waitlist'

definePageMeta({ middleware: 'role', roles: ['admin', 'reception'] })
useHead({ title: 'Lista de espera' })

const { showToast } = useToast()

const status = ref<WaitlistStatus | ''>('')
const { data: entries, error, pending, refresh } = await useAsyncData(
  'waitlist',
  () => waitlistService.list({ status: status.value || undefined }),
  { watch: [status] },
)

const editing = ref<WaitlistEntry | null>(null)
const isModalOpen = ref(false)
const confirmingDelete = ref<string | null>(null)

// Opens the modal to add or to edit an entry.
function open(entry: WaitlistEntry | null) {
  editing.value = entry
  isModalOpen.value = true
}

// Marks that the patient got a time, which takes the entry out of the open list.
async function markBooked(entry: WaitlistEntry) {
  try {
    await waitlistService.update(entry.id, {
      patientId: entry.patient.id,
      procedureId: entry.procedure,
      professionalId: entry.professional?.id ?? '',
      fromDate: entry.fromDate,
      toDate: entry.toDate,
      period: entry.period,
      notes: entry.notes ?? '',
      status: 'booked',
    })
    showToast(`${entry.patient.name} foi marcado como agendado.`)
    await refresh()
  }
  catch (failure) {
    showToast(parseApiError(failure).message)
  }
}

// Removes an entry after a second click.
async function remove(entry: WaitlistEntry) {
  if (confirmingDelete.value !== entry.id) {
    confirmingDelete.value = entry.id

    return
  }

  confirmingDelete.value = null
  await waitlistService.remove(entry.id)
  showToast(`${entry.patient.name} saiu da lista de espera.`)
  await refresh()
}

// Range of days as "10/11 a 30/11".
function range(entry: WaitlistEntry) {
  return `${formatShortDate(entry.fromDate)} a ${formatShortDate(entry.toDate)}`
}

const iconButtonClass = 'grid h-[30px] min-w-[30px] place-items-center rounded-lg text-muted transition duration-200 hover:bg-line-soft hover:text-brand-text'
</script>

<template>
  <div>
    <LayoutPageHeader
      eyebrow="Agenda clínica"
      title="Lista de espera"
      description="Quando um horário compatível vaga, até 3 pacientes recebem um e-mail, por ordem de chegada."
      action-label="Adicionar paciente"
      @action="open(null)"
    />

    <div class="mb-3 flex items-center gap-2 text-[11px] text-body">
      <label for="waitlist-status" class="text-muted">Mostrar</label>
      <select id="waitlist-status" v-model="status" class="h-[32px] rounded-[7px] border border-line bg-surface px-2 text-[11px]">
        <option value="">
          Em aberto
        </option>
        <option value="booked">
          Agendados
        </option>
        <option value="removed">
          Removidos
        </option>
      </select>
    </div>

    <p v-if="pending && !entries" role="status" class="text-[13px] text-muted">
      Carregando a lista de espera...
    </p>

    <div v-else-if="error || !entries" role="alert" class="text-[13px] text-muted">
      <p>Não foi possível carregar a lista de espera.</p>
      <button type="button" class="mt-2 font-bold text-brand-text" @click="refresh()">
        Tentar novamente
      </button>
    </div>

    <SettingsCard v-else title="Pacientes" :subtitle="`${entries.length} na lista`">
      <p v-if="!entries.length" class="pt-4 text-[11px] text-muted">
        Ninguém na lista.
      </p>

      <ul v-else class="mt-[5px]">
        <li
          v-for="entry in entries"
          :key="entry.id"
          class="flex flex-wrap items-center justify-between gap-3 border-b border-line-soft px-[3px] py-3 text-[11px] text-body last:border-b-0"
        >
          <div class="min-w-0">
            <NuxtLink :to="panelPath(`pacientes/${entry.patient.id}`)" class="font-bold hover:text-brand-text hover:underline">
              {{ entry.patient.name }}
            </NuxtLink>
            <span class="block text-[10px] text-muted">
              {{ entry.procedureLabel }} · {{ entry.professional?.name ?? 'Qualquer profissional' }} · {{ range(entry) }} · {{ entry.periodLabel }}
            </span>
            <span v-if="!entry.patient.hasEmail" class="block text-[10px] text-tone-amber-text">Sem e-mail: não recebe o aviso automático.</span>
          </div>

          <div class="flex flex-none items-center gap-1">
            <span
              class="rounded-md px-2 py-1 text-[10px] font-semibold"
              :class="entry.status === 'notified' ? 'bg-tone-green-bg text-tone-green-text' : 'bg-line-soft text-muted'"
            >
              {{ entry.statusLabel }}
            </span>
            <button v-if="entry.status === 'waiting' || entry.status === 'notified'" type="button" class="rounded-lg px-2 py-1 text-[10px] font-bold text-brand-text hover:bg-line-soft" @click="markBooked(entry)">
              Marcar agendado
            </button>
            <button type="button" :class="iconButtonClass" :aria-label="`Editar espera de ${entry.patient.name}`" @click="open(entry)">
              <Pencil :size="14" />
            </button>
            <button
              type="button"
              :class="iconButtonClass"
              :aria-label="confirmingDelete === entry.id ? `Confirmar remoção de ${entry.patient.name}` : `Remover ${entry.patient.name} da lista`"
              @click="remove(entry)"
            >
              <span v-if="confirmingDelete === entry.id" class="text-[10px] font-bold text-tone-red-text">Sim?</span>
              <Trash2 v-else :size="14" />
            </button>
          </div>
        </li>
      </ul>
    </SettingsCard>

    <WaitlistEntryModal v-if="isModalOpen" :entry="editing" @close="isModalOpen = false" @saved="isModalOpen = false" />
  </div>
</template>
