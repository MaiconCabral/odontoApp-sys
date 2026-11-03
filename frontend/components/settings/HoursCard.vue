<script setup lang="ts">
import type { BusinessDay } from '~/types/schedule'

const auth = useAuthStore()
const { showToast } = useToast()
const { data: hours, error, pending, refresh } = useBusinessHours()

const days = ref<BusinessDay[]>([])
const dayErrors = ref<Record<number, string>>({})
const formError = ref('')
const isSaving = ref(false)
const canEdit = computed(() => auth.user?.role === 'admin')

watch(hours, (value) => {
  days.value = (value ?? []).map(day => ({ ...day }))
}, { immediate: true })

// Saves the opening hours and shows the errors of each day.
async function save() {
  isSaving.value = true
  dayErrors.value = {}
  formError.value = ''

  try {
    hours.value = await scheduleService.updateHours(days.value)
    showToast('Horários de funcionamento atualizados.')
  }
  catch (failure) {
    const { message, fields } = parseApiError(failure)

    for (const [field, text] of Object.entries(fields)) {
      const index = Number(field.split('.')[1])

      dayErrors.value[index] ??= text
    }

    if (Object.keys(fields).length === 0) {
      formError.value = message
    }
  }
  finally {
    isSaving.value = false
  }
}

const timeClass = 'h-[34px] w-[84px] rounded-[7px] border border-line bg-surface px-2 text-[11px] text-body outline-none focus:border-brand-accent disabled:opacity-60 dark:[color-scheme:dark]'
</script>

<template>
  <SettingsCard
    title="Horários de funcionamento"
    subtitle="A agenda e a página de agendamento só oferecem horários dentro deste expediente."
  >
    <template v-if="canEdit" #action>
      <button
        type="button"
        :disabled="isSaving || !days.length"
        class="inline-flex flex-none items-center justify-center gap-2 rounded-lg border border-line bg-surface px-[15px] py-2.5 text-[12px] font-bold text-body transition duration-200 disabled:opacity-60"
        @click="save"
      >
        {{ isSaving ? 'Salvando...' : 'Salvar horários' }}
      </button>
    </template>

    <p v-if="pending && !hours" role="status" class="pt-4 text-[11px] text-muted">
      Carregando os horários...
    </p>

    <div v-else-if="error" role="alert" class="pt-4 text-[11px] text-muted">
      <p>Não foi possível carregar os horários.</p>
      <button type="button" class="mt-2 font-bold text-brand-text" @click="refresh()">
        Tentar novamente
      </button>
    </div>

    <template v-else>
      <p v-if="formError" role="alert" class="pt-4 text-[11px] text-tone-red-text">
        {{ formError }}
      </p>

      <div class="mt-[5px]">
        <div
          v-for="(day, index) in days"
          :key="day.weekday"
          class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-line-soft px-[3px] py-3 text-[11px] text-body"
        >
          <strong class="w-[70px]">{{ day.label }}</strong>
          <label class="flex items-center gap-1.5 text-[10px] text-muted">
            <input
              type="checkbox"
              class="h-[13px] w-[13px] accent-brand"
              :checked="!day.closed"
              :disabled="!canEdit"
              @change="day.closed = !day.closed"
            >
            Atende
          </label>

          <template v-if="!day.closed">
            <span class="flex items-center gap-1 text-[10px] text-muted">
              <input v-model="day.opensAt" type="time" step="1800" :class="timeClass" :disabled="!canEdit" :aria-label="`Abertura ${day.label}`">
              às
              <input v-model="day.closesAt" type="time" step="1800" :class="timeClass" :disabled="!canEdit" :aria-label="`Fechamento ${day.label}`">
            </span>
            <span class="flex items-center gap-1 text-[10px] text-muted">
              almoço
              <input v-model="day.breakStartsAt" type="time" step="1800" :class="timeClass" :disabled="!canEdit" :aria-label="`Início do almoço ${day.label}`">
              às
              <input v-model="day.breakEndsAt" type="time" step="1800" :class="timeClass" :disabled="!canEdit" :aria-label="`Fim do almoço ${day.label}`">
            </span>
          </template>
          <span v-else class="text-[10px] text-muted">Fechado</span>

          <p v-if="dayErrors[index]" role="alert" class="w-full text-[10px] text-tone-red-text">
            {{ dayErrors[index] }}
          </p>
        </div>
      </div>

      <p class="mt-3 text-[10px] text-muted">
        Feriados e folgas ficam na Agenda, em "Bloquear horário". Mudar o expediente não desmarca consultas já agendadas.
      </p>
    </template>
  </SettingsCard>
</template>
